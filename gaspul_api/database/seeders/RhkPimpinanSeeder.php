<?php

namespace Database\Seeders;

use App\Models\RhkPimpinan;
use App\Models\IndikatorKinerja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RHK Pimpinan Seeder
 *
 * Seeds RHK Pimpinan (Rencana Hasil Kerja Pimpinan yang di Intervensi)
 * from existing Indikator Kinerja data
 *
 * Current table structure (after migration):
 * - id
 * - indikator_kinerja_id (FK to indikator_kinerja)
 * - rhk_pimpinan (text)
 * - status
 */
class RhkPimpinanSeeder extends Seeder
{
    public function run(): void
    {
        echo "Creating RHK Pimpinan from Indikator Kinerja...\n";

        // Get all active Indikator Kinerja
        $indikatorList = IndikatorKinerja::where('status', 'AKTIF')->get();

        if ($indikatorList->isEmpty()) {
            echo "⚠️  No active Indikator Kinerja found.\n";
            return;
        }

        echo "Found {$indikatorList->count()} Indikator Kinerja\n";

        $created = 0;

        foreach ($indikatorList as $indikator) {
            // Check if RHK already exists for this Indikator Kinerja
            $exists = RhkPimpinan::where('indikator_kinerja_id', $indikator->id)->exists();

            if ($exists) {
                echo "  - Skip Indikator ID {$indikator->id}: RHK already exists\n";
                continue;
            }

            // Create RHK Pimpinan
            // Use the actual Indikator Kinerja text as RHK Pimpinan
            RhkPimpinan::create([
                'indikator_kinerja_id' => $indikator->id,
                'rhk_pimpinan' => $indikator->indikator_kinerja,
                'status' => 'AKTIF',
            ]);

            $created++;
            echo "  ✅ Created RHK for Indikator ID {$indikator->id}\n";
        }

        echo "\n✅ Successfully created {$created} RHK Pimpinan records\n";
    }
}
