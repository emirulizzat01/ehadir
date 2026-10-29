<?php

namespace App\Console\Commands;

use App\Models\Anggota;
use App\Services\Personnel\GradeHierarchyService;
use Illuminate\Console\Command;

class SyncAnggotaHierarchyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anggota:sync-hierarchy {--force : Recalculate all records even if already set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and update job position hierarchy ranking (tahap_hierarki) for all staff';

    /**
     * Execute the console command.
     */
    public function handle(GradeHierarchyService $hierarchyService): int
    {
        $this->info('Starting Anggota hierarchy synchronization...');

        $query = Anggota::query();
        $total = $query->count();
        $updated = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(100, function ($records) use ($hierarchyService, &$updated, $bar) {
            foreach ($records as $anggota) {
                $score = $hierarchyService->calculateScore($anggota->gred, $anggota->jawatan);

                if ($anggota->tahap_hierarki !== $score || $this->option('force')) {
                    $anggota->tahap_hierarki = $score;
                    $anggota->saveQuietly();
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Hierarchy synchronization completed! Processed: {$total}, Updated: {$updated} records.");

        return Command::SUCCESS;
    }
}

