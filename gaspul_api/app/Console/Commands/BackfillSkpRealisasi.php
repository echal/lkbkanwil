<?php

namespace App\Console\Commands;

use App\Models\SkpTahunanDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSkpRealisasi extends Command
{
    protected $signature = 'esaraku:backfill-skp-realisasi
                            {--force : Skip konfirmasi}
                            {--all : Proses semua detail, termasuk yang realisasi_tahunan > 0}';

    protected $description = 'Backfill realisasi_tahunan di skp_tahunan_detail dari rencana_aksi_bulanan';

    public function handle(): int
    {
        $processAll = $this->option('all');

        $query = SkpTahunanDetail::query();
        if (!$processAll) {
            $query->where('realisasi_tahunan', 0);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Tidak ada data yang perlu dibackfill.');
            return self::SUCCESS;
        }

        $scope = $processAll ? 'semua' : 'realisasi_tahunan = 0';
        $this->line("Ditemukan <comment>{$total}</comment> skp_tahunan_detail ({$scope}) yang akan diproses.");

        if (!$this->option('force') && !$this->confirm('Lanjutkan backfill?', true)) {
            $this->line('Dibatalkan.');
            return self::SUCCESS;
        }

        $startTime = microtime(true);
        $updated   = 0;
        $skipped   = 0;
        $errors    = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Updated: %message%');
        $bar->setMessage('0');
        $bar->start();

        $query->chunk(500, function ($details) use (&$updated, &$skipped, &$errors, $bar) {
            foreach ($details as $detail) {
                try {
                    $realisasi = $detail->rencanaAksiBulanan()->sum('realisasi_bulanan');

                    if ((float) $realisasi === (float) $detail->realisasi_tahunan) {
                        $skipped++;
                    } else {
                        DB::table('skp_tahunan_detail')
                            ->where('id', $detail->id)
                            ->update(['realisasi_tahunan' => $realisasi]);
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("ID {$detail->id}: {$e->getMessage()}");
                }

                $bar->advance();
                $bar->setMessage((string) $updated);
            }
        });

        $bar->finish();
        $this->newLine(2);

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->table(
            ['Metric', 'Jumlah'],
            [
                ['Total diproses', $total],
                ['Updated', $updated],
                ['Skipped (nilai sama)', $skipped],
                ['Error', $errors],
                ['Waktu eksekusi', "{$elapsed}s"],
            ]
        );

        if ($errors > 0) {
            $this->warn("Selesai dengan {$errors} error.");
            return self::FAILURE;
        }

        $this->info('Backfill selesai.');
        return self::SUCCESS;
    }
}
