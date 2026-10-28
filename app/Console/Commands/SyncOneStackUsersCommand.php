<?php

namespace App\Console\Commands;

use App\Services\OneStack\Exceptions\OneStackApiException;
use App\Services\OneStack\OneStackSyncService;
use Illuminate\Console\Command;

class SyncOneStackUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'onestack:sync-users
                            {--id= : Segerakkan pengguna tertentu mengikut id_pengguna / no_pekerja}
                            {--dry-run : Simulasi penyegerakan tanpa mengubah pangkalan data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyegerakkan direktori anggota daripada API OneStack KEJORA ke dalam eHadir';

    /**
     * Execute the console command.
     */
    public function handle(OneStackSyncService $syncService): int
    {
        $id = $this->option('id');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('====================================================');
        $this->info('  Penyegerakan Direktori OneStack KEJORA — eHadir   ');
        $this->info('====================================================');

        if ($dryRun) {
            $this->warn('Mod Simulasi (DRY-RUN) Aktif: Tiada perubahan disimpan.');
        }

        try {
            $startTime = microtime(true);

            if ($id) {
                $this->line("Memulakan penyegerakan untuk ID: <comment>{$id}</comment>...");
                $stats = $syncService->syncUserById($id, ['dry_run' => $dryRun]);
            } else {
                $this->line('Menghubungi API OneStack untuk mendapatkan senarai penuh anggota...');
                $stats = $syncService->syncAll(['dry_run' => $dryRun]);
            }

            $duration = round(microtime(true) - $startTime, 2);

            if (isset($stats['status']) && $stats['status'] === false) {
                $this->error($stats['message'] ?? 'Ralat penyegerakan berlaku.');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info("Penyegerakan selesai dalam masa {$duration}s!");
            $this->newLine();

            $this->table(
                ['Metrik', 'Jumlah'],
                [
                    ['Jumlah Rekod Diterima', $stats['total_fetched'] ?? 0],
                    ['Berjaya Diselaraskan', $stats['synced'] ?? 0],
                    ['Anggota Baharu Ditambah', $stats['created'] ?? 0],
                    ['Anggota Sedia Ada Dikemaskini', $stats['updated'] ?? 0],
                    ['Pertukaran Bahagian / Unit', $stats['transferred'] ?? 0],
                    ['Anggota Dinyahaktifkan (Tamat Perkhidmatan)', $stats['deactivated'] ?? 0],
                    ['Rekod Ujian / Dinyahkan', $stats['skipped_dummies'] ?? 0],
                    ['Ralat Pemprosesan', count($stats['errors'] ?? [])],
                ]
            );

            if (! empty($stats['errors'])) {
                $this->newLine();
                $this->error('Senarai ralat yang berlaku:');
                $errorTable = [];
                foreach (array_slice($stats['errors'], 0, 10) as $err) {
                    $errorTable[] = [$err['id_pengguna'], $err['nama'], $err['error']];
                }
                $this->table(['ID', 'Nama', 'Mesej Ralat'], $errorTable);

                if (count($stats['errors']) > 10) {
                    $this->warn('... dan '.(count($stats['errors']) - 10).' ralat lagi (rujuk log sistem).');
                }
            }

            return self::SUCCESS;
        } catch (OneStackApiException $e) {
            $this->error("Ralat OneStack API: {$e->getMessage()}");

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Ralat Sistem Tidak Dijangka: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

