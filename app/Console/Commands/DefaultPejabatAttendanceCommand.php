<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DefaultPejabatAttendanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:default-pejabat
                            {--date= : Tarikh spesifik (format YYYY-MM-DD, lalai: hari ini)}
                            {--all-days : Jalankan untuk semua hari termasuk hujung minggu}
                            {--force : Paksa jalankan walaupun pada hari cuti/hujung minggu}
                            {--dry-run : Simulasi penyegerakan tanpa mengubah pangkalan data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menetapkan status kehadiran lalai kepada Pejabat bagi anggota aktif yang belum mempunyai rekod';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService): int
    {
        $dateInput = $this->option('date');
        $dryRun = (bool) $this->option('dry-run');
        $allDays = (bool) $this->option('all-days');
        $force = (bool) $this->option('force');

        try {
            $targetDate = $dateInput ? Carbon::parse($dateInput)->toDateString() : Carbon::today()->toDateString();
        } catch (\Throwable $e) {
            $this->error("Format tarikh tidak sah: {$dateInput}. Sila gunakan format YYYY-MM-DD.");

            return self::FAILURE;
        }

        $carbonDate = Carbon::parse($targetDate);

        $this->info('====================================================');
        $this->info('    Automasi Kehadiran Harian — eHadir KEJORA       ');
        $this->info('    Penetapan Status Lalai: PEJABAT                 ');
        $this->info('====================================================');
        $this->line("Tarikh Sasaran: <comment>{$targetDate}</comment> ({$carbonDate->translatedFormat('l, d F Y')})");

        if ($carbonDate->isWeekend() && ! $force && ! $allDays) {
            $this->warn("Tarikh {$targetDate} adalah hari hujung minggu ({$carbonDate->translatedFormat('l')}).");
            $this->warn('Tugasan ditangguhkan secara automatik. Gunakan --force atau --all-days jika perlu.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Mod Simulasi (DRY-RUN) Aktif: Tiada perubahan disimpan ke pangkalan data.');
        }

        try {
            $startTime = microtime(true);

            $stats = $attendanceService->initializeDailyDefaults(
                tarikh: $targetDate,
                actor: null,
                dryRun: $dryRun
            );

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->table(
                ['Metrik Penjanaan Kehadiran', 'Jumlah'],
                [
                    ['Tarikh Proses', $targetDate],
                    ['Jumlah Anggota Aktif', $stats['total_active']],
                    ['Rekod Sedia Ada (Terpelihara)', $stats['already_recorded']],
                    ['Rekod Pejabat Baharu Dijana', $stats['created']],
                    ['Dikecualikan (Tiada Penempatan Sah)', $stats['skipped_no_placement']],
                    ['Mod Pelaksanaan', $stats['dry_run'] ? 'Simulasi (Dry Run)' : 'Sebenar (Live)'],
                ]
            );

            $this->newLine();
            if ($stats['dry_run']) {
                $this->info("Simulasi selesai dalam masa {$duration}s. Sebanyak {$stats['created']} rekod berpotensi dijana.");
            } else {
                $this->info("Proses selesai dalam masa {$duration}s. Sebanyak {$stats['created']} rekod berjaya dijana.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Ralat berlaku semasa penjanaan kehadiran: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}

