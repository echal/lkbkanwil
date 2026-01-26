<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Unit;

class MigrateUserUnitKerja extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:user-unit-kerja';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate users from unit_kerja (string) to unit_id (FK) by matching or creating units';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of user unit_kerja to unit_id...');

        // Get all users yang punya unit_kerja tapi belum punya unit_id
        $users = User::whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->whereNull('unit_id')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users need migration.');
            return 0;
        }

        $this->info("Found {$users->count()} users to migrate.");

        $migrated = 0;
        $created = 0;

        foreach ($users as $user) {
            $unitKerjaName = $user->unit_kerja;

            // Cari unit yang sudah ada berdasarkan nama
            $unit = Unit::where('nama_unit', $unitKerjaName)->first();

            // Jika tidak ada, buat unit baru
            if (!$unit) {
                // Generate kode unit dari nama (ambil huruf kapital atau 10 karakter pertama)
                $kodeUnit = $this->generateKodeUnit($unitKerjaName);

                $unit = Unit::create([
                    'nama_unit' => $unitKerjaName,
                    'kode_unit' => $kodeUnit,
                    'status' => 'AKTIF',
                ]);

                $this->info("  - Created new unit: {$unitKerjaName} ({$kodeUnit})");
                $created++;
            }

            // Update user dengan unit_id
            $user->update(['unit_id' => $unit->id]);

            $this->info("  - Migrated user: {$user->name} ({$user->email}) -> {$unit->nama_unit}");
            $migrated++;
        }

        $this->newLine();
        $this->info("Migration completed!");
        $this->info("  - {$migrated} users migrated");
        $this->info("  - {$created} new units created");

        return 0;
    }

    /**
     * Generate kode unit dari nama unit
     */
    private function generateKodeUnit(string $namaUnit): string
    {
        // Ambil huruf kapital dari nama atau singkatan
        $words = explode(' ', strtoupper($namaUnit));
        $kode = '';

        foreach ($words as $word) {
            if (strlen($word) > 0 && !in_array($word, ['DAN', 'ATAU', 'UNTUK', 'PADA', 'DI'])) {
                $kode .= substr($word, 0, 1);
            }
        }

        // Jika kode terlalu pendek, tambahkan dari kata pertama
        if (strlen($kode) < 3) {
            $kode = strtoupper(substr($namaUnit, 0, 10));
            $kode = preg_replace('/[^A-Z]/', '', $kode);
        }

        // Maksimal 20 karakter
        $kode = substr($kode, 0, 20);

        // Cek apakah kode sudah ada, jika ya tambahkan angka
        $originalKode = $kode;
        $counter = 1;
        while (Unit::where('kode_unit', $kode)->exists()) {
            $kode = $originalKode . $counter;
            $counter++;
        }

        return $kode;
    }
}
