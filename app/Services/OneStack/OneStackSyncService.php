<?php

namespace App\Services\OneStack;

use App\Models\Anggota;
use App\Models\AnggotaPenempatan;
use App\Models\Bahagian;
use App\Models\Unit;
use App\Models\User;
use App\Services\AuditLoggerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OneStackSyncService
{
    public function __construct(
        protected OneStackApiClient $client
    ) {}

    /**
     * Synchronize all users from OneStack directory into eHadir.
     *
     * @param  array<string, mixed>  $options  ['dry_run' => bool, 'actor_id' => ?int]
     * @return array<string, mixed> Sync summary
     */
    public function syncAll(array $options = []): array
    {
        $rawUsers = $this->client->fetchAllUsers();

        return $this->syncUserCollection($rawUsers, $options);
    }

    /**
     * Synchronize a single user by employee ID (id_pengguna).
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed> Sync summary
     */
    public function syncUserById(string $idPengguna, array $options = []): array
    {
        $rawUser = $this->client->fetchUserById($idPengguna);

        if (! $rawUser) {
            return [
                'status' => false,
                'message' => "Pengguna dengan ID '{$idPengguna}' tidak dijumpai di OneStack.",
                'synced' => 0,
            ];
        }

        return $this->syncUserCollection([$rawUser], $options);
    }

    /**
     * Process a collection of raw user records from OneStack.
     *
     * @param  array<int, array<string, mixed>>  $users
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function syncUserCollection(array $users, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $actorId = $options['actor_id'] ?? null;
        $today = Carbon::today()->toDateString();

        $stats = [
            'total_fetched' => count($users),
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'transferred' => 0,
            'deactivated' => 0,
            'skipped_dummies' => 0,
            'errors' => [],
        ];

        foreach ($users as $raw) {
            if ($this->isDummyRecord($raw)) {
                $stats['skipped_dummies']++;

                continue;
            }

            try {
                if ($dryRun) {
                    $stats['synced']++;

                    continue;
                }

                $result = DB::transaction(function () use ($raw, $today, $actorId) {
                    return $this->processSingleRecord($raw, $today, $actorId);
                });

                $stats['synced']++;
                if ($result['is_new']) {
                    $stats['created']++;
                } else {
                    $stats['updated']++;
                }

                if ($result['transferred']) {
                    $stats['transferred']++;
                }

                if ($result['deactivated']) {
                    $stats['deactivated']++;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed syncing OneStack user record', [
                    'id_pengguna' => $raw['id_pengguna'] ?? null,
                    'error' => $e->getMessage(),
                ]);

                $stats['errors'][] = [
                    'id_pengguna' => $raw['id_pengguna'] ?? 'UNKNOWN',
                    'nama' => $raw['nama_pengguna'] ?? 'UNKNOWN',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (! $dryRun && $stats['synced'] > 0) {
            AuditLoggerService::log(
                tindakan: 'SYNC_ONESTACK',
                namaJadual: 'anggota',
                recordId: null,
                dataLama: null,
                dataBaru: $stats,
                userId: $actorId
            );
        }

        return $stats;
    }

    /**
     * Process an individual record within a database transaction.
     *
     * @param  array<string, mixed>  $raw
     * @return array{is_new: bool, transferred: bool, deactivated: bool}
     */
    protected function processSingleRecord(array $raw, string $today, ?int $actorId): array
    {
        $idPengguna = trim((string) ($raw['id_pengguna'] ?? ''));
        $namaPengguna = trim((string) ($raw['nama_pengguna'] ?? ''));
        $jawatan = trim((string) ($raw['jawatan'] ?? ''));
        $rawBahagian = trim((string) ($raw['bahagian'] ?? ''));
        $rawKodBahagian = trim((string) ($raw['kod_bahagian'] ?? ''));
        $isStillServing = (int) ($raw['status_masih_berkhidmat'] ?? 1) === 1;

        $onestackId = isset($raw['id']) ? (int) $raw['id'] : null;
        $gred = ! empty($raw['gred']) ? trim((string) $raw['gred']) : null;
        $emel = ! empty($raw['emel_pengguna']) ? trim((string) $raw['emel_pengguna']) : null;
        $noTelefon = ! empty($raw['no_telefon']) ? trim((string) $raw['no_telefon']) : null;
        
        $tarikhBersara = null;
        if (! empty($raw['tarikh_tamat_berkhidmat'])) {
            $rawDate = trim((string) $raw['tarikh_tamat_berkhidmat']);
            if ($rawDate !== '0000-00-00' && ! str_starts_with($rawDate, '-0001') && ! str_starts_with($rawDate, '0000')) {
                try {
                    $parsed = Carbon::parse($rawDate);
                    if ($parsed->year > 1900 && $parsed->year < 2150) {
                        $tarikhBersara = $parsed->toDateString();
                    }
                } catch (\Throwable $e) {
                    $tarikhBersara = null;
                }
            }
        }

        // 1. Resolve Organization Unit / Bahagian
        $orgTarget = $this->resolveOrganizationTarget($rawBahagian, $rawKodBahagian);

        // 2. Extract Gelaran & Clean Name
        $gelaran = $this->extractGelaran($namaPengguna);

        // 3. Upsert Anggota
        $anggota = Anggota::where('no_pekerja', $idPengguna)->first();
        $isNew = false;
        $deactivated = false;
        $transferred = false;

        if (! $anggota) {
            $isNew = true;
            $anggota = Anggota::create([
                'onestack_id' => $onestackId,
                'no_pekerja' => $idPengguna,
                'nama_penuh' => $namaPengguna,
                'gelaran' => $gelaran,
                'jawatan' => $jawatan ?: null,
                'gred' => $gred,
                'emel' => $emel,
                'no_telefon' => $noTelefon,
                'aktif' => $isStillServing,
                'tarikh_bersara' => $tarikhBersara,
                'susunan' => 0,
                'last_synced_at' => now(),
            ]);
        } else {
            $wasActive = $anggota->aktif;
            $anggota->update([
                'onestack_id' => $onestackId ?: $anggota->onestack_id,
                'nama_penuh' => $namaPengguna,
                'gelaran' => $anggota->gelaran ?: $gelaran,
                'jawatan' => $jawatan ?: $anggota->jawatan,
                'gred' => $gred ?: $anggota->gred,
                'emel' => $emel ?: $anggota->emel,
                'no_telefon' => $noTelefon ?: $anggota->no_telefon,
                'aktif' => $isStillServing,
                'tarikh_bersara' => $tarikhBersara ?: $anggota->tarikh_bersara,
                'last_synced_at' => now(),
            ]);

            if ($wasActive && ! $isStillServing) {
                $deactivated = true;
            }
        }

        // 4. Manage Placement (AnggotaPenempatan) enforcing chk_penempatan_exactly_one
        $activePlacement = AnggotaPenempatan::where('anggota_id', $anggota->id)
            ->whereDate('tarikh_mula', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('tarikh_tamat')->orWhereDate('tarikh_tamat', '>=', $today);
            })
            ->first();

        $targetBahagianId = $orgTarget['type'] === 'bahagian' ? $orgTarget['model']->id : null;
        $targetUnitId = $orgTarget['type'] === 'unit' ? $orgTarget['model']->id : null;

        if ($activePlacement) {
            $samePlacement = ((int) $activePlacement->bahagian_id === (int) $targetBahagianId)
                && ((int) $activePlacement->unit_id === (int) $targetUnitId);

            if (! $samePlacement && $isStillServing) {
                $mulaDateStr = $activePlacement->tarikh_mula->toDateString();

                if ($mulaDateStr === $today) {
                    // Placement started today: update in-place to avoid uq_anggota_mula collision
                    $activePlacement->update([
                        'bahagian_id' => $targetBahagianId,
                        'unit_id' => $targetUnitId,
                    ]);
                } else {
                    // Close previous placement yesterday
                    $yesterday = Carbon::parse($today)->subDay()->toDateString();
                    $activePlacement->update([
                        'tarikh_tamat' => $yesterday,
                    ]);

                    // Create new open-ended placement starting today
                    AnggotaPenempatan::create([
                        'anggota_id' => $anggota->id,
                        'bahagian_id' => $targetBahagianId,
                        'unit_id' => $targetUnitId,
                        'tarikh_mula' => $today,
                        'tarikh_tamat' => null,
                        'created_at' => now(),
                    ]);
                }

                $transferred = true;
            } elseif (! $isStillServing && is_null($activePlacement->tarikh_tamat)) {
                // If staff has retired / inactive, close the open placement
                $activePlacement->update(['tarikh_tamat' => $today]);
            }
        } elseif ($orgTarget && ($targetBahagianId || $targetUnitId)) {
            // No current active placement:
            // If still serving, create an active open-ended placement
            // If inactive/retired, record their department as a closed/historical placement
            $tarikhTamatHistorical = ! $isStillServing
                ? ($tarikhBersara ?: Carbon::parse($today)->subDay()->toDateString())
                : null;
            $tarikhMulaHistorical = $tarikhTamatHistorical
                ? Carbon::parse($tarikhTamatHistorical)->subYears(3)->toDateString()
                : $today;

            AnggotaPenempatan::create([
                'anggota_id' => $anggota->id,
                'bahagian_id' => $targetBahagianId,
                'unit_id' => $targetUnitId,
                'tarikh_mula' => $tarikhMulaHistorical,
                'tarikh_tamat' => $tarikhTamatHistorical,
                'created_at' => now(),
            ]);
        }

        // 5. Synchronize linked User account if present (name, email, and active status)
        $linkedUser = User::where('anggota_id', $anggota->id)->first();
        if ($linkedUser) {
            $userUpdates = [];
            if ($namaPengguna && $linkedUser->nama !== $namaPengguna) {
                $userUpdates['nama'] = $namaPengguna;
            }
            if ($emel && $linkedUser->email !== $emel) {
                $emailTaken = User::where('email', $emel)
                    ->where('id', '!=', $linkedUser->id)
                    ->exists();
                if (! $emailTaken) {
                    $userUpdates['email'] = $emel;
                }
            }
            if (! $isStillServing && $linkedUser->aktif) {
                $userUpdates['aktif'] = false;
            }
            if (! empty($userUpdates)) {
                $linkedUser->update($userUpdates);
            }
        }

        return [
            'is_new' => $isNew,
            'transferred' => $transferred,
            'deactivated' => $deactivated,
        ];
    }

    /**
     * Resolve whether an organization entity is a Bahagian or Unit, and ensure it exists.
     *
     * @return array{type: 'bahagian'|'unit', model: Bahagian|Unit}
     */
    public function resolveOrganizationTarget(string $rawBahagian, string $rawKod): array
    {
        $name = trim($rawBahagian) ?: 'BAHAGIAN PENTADBIRAN';
        $code = $this->resolveDepartmentCode($name, $rawKod);
        $isUnit = $this->isUnit($name, $code);

        if ($isUnit) {
            $unit = Unit::firstOrCreate(
                ['kod' => $code],
                ['nama' => $name, 'aktif' => true]
            );

            if (! $unit->aktif) {
                $unit->update(['aktif' => true]);
            }

            return ['type' => 'unit', 'model' => $unit];
        }

        $bahagian = Bahagian::firstOrCreate(
            ['kod' => $code],
            ['nama' => $name, 'aktif' => true]
        );

        if (! $bahagian->aktif) {
            $bahagian->update(['aktif' => true]);
        }

        return ['type' => 'bahagian', 'model' => $bahagian];
    }

    /**
     * Determine if department name or code represents an independent Unit.
     */
    public function isUnit(string $nama, string $kod): bool
    {
        $normalizedName = strtoupper(trim($nama));
        $normalizedKod = strtoupper(trim($kod));

        return str_starts_with($normalizedName, 'UNIT ')
            || in_array($normalizedKod, ['UI', 'UKK', 'UUU', 'UPF', 'UAD', 'UIT']);
    }

    /**
     * Resolve unique, canonical department code to avoid collisions across departments.
     */
    public function resolveDepartmentCode(string $nama, string $rawKod): string
    {
        $normalizedName = strtoupper(trim($nama));

        $canonicalCodes = [
            'BAHAGIAN HARTANAH' => 'HT',
            'BAHAGIAN PERANCANGAN STRATEGIK' => 'PS',
            'BAHAGIAN PELANCONGAN' => 'BPL',
            'BAHAGIAN PENTADBIRAN DAN PENGURUSAN SUMBER MANUSIA' => 'PPSM',
            'BAHAGIAN KEMAJUAN WILAYAH DAN PEMBANGUNAN PROJEK (UT)' => 'UT',
            'BAHAGIAN PEMBANGUNAN PROJEK' => 'BPP',
            'BAHAGIAN PEMBANGUNAN EKONOMI' => 'BPE',
            'BAHAGIAN PELABURAN' => 'BPEL',
            'BAHAGIAN KEWANGAN' => 'KEW',
            'BAHAGIAN PERTANIAN DAN INDUSTRI ASAS TANI' => 'PIAT',
            'BAHAGIAN PEMBANGUNAN SOSIAL DAN PEMAJUAN DESA' => 'PSPD',
            'BAHAGIAN TEKNOLOGI MAKLUMAT' => 'TM',
            'PEJABAT PENGURUS BESAR' => 'PPB',
            'PEJABAT TIMBALAN PENGURUS BESAR (PEMBANGUNAN)' => 'PTPBB',
            'PEJABAT TIMBALAN PENGURUS BESAR (PENGURUSAN)' => 'PTPBU',
            'PENGURUSAN PEMEGANG TARUH DAN PROJEK KHAS' => 'PPTPK',
            'UNIT AUDIT DALAM' => 'UAD',
            'UNIT INOVASI DAN TRANSFORMASI' => 'UIT',
            'UNIT INTEGRITI' => 'UI',
            'UNIT KOMUNIKASI KORPORAT' => 'UKK',
            'UNIT PENGURUSAN FASILITI' => 'UPF',
            'UNIT PERUNDANGAN' => 'UUU',
        ];

        return $canonicalCodes[$normalizedName] ?? strtoupper(trim($rawKod) ?: 'AM');
    }

    /**
     * Detect common Malaysian honorary / formal titles from full name.
     */
    public function extractGelaran(string $nama): ?string
    {
        $titles = ['HAJI', 'HAJAH', 'DATO\'', 'DATUK', 'DATIN', 'DR.', 'PROF.', 'TS.', 'IR.'];
        $clean = strtoupper(trim($nama));

        foreach ($titles as $title) {
            if (str_starts_with($clean, $title.' ')) {
                return $title;
            }
        }

        return null;
    }

    /**
     * Check if a record is a demo, dummy, or invalid test record.
     */
    public function isDummyRecord(array $item): bool
    {
        $name = strtolower(trim((string) ($item['nama_pengguna'] ?? '')));
        $id = trim((string) ($item['id_pengguna'] ?? ''));

        if (empty($id) || empty($name)) {
            return true;
        }

        if (str_contains($name, 'dummy') || str_contains($name, 'demo')) {
            return true;
        }

        // Dummy names consisting entirely of digits (e.g. 7777777777777777777777777777777777777777)
        if (preg_match('/^[0-9\s]{6,}$/', $name)) {
            return true;
        }

        // Repeating digit patterns like 777777, 888888, 999999
        if (preg_match('/^(.)\1{4,}$/', $id) || preg_match('/^(.)\1{4,}/', $id)) {
            return true;
        }

        return false;
    }
}
