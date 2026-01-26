<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\SasaranKegiatan;
use App\Models\IndikatorKinerja;

class MasterKinerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get test unit
        $unit = Unit::where('kode_unit', 'TEST-01')->first();

        if (!$unit) {
            $this->command->error('Unit TEST-01 not found. Please run TestUserSeeder first.');
            return;
        }

        // Create Sasaran Kegiatan
        $sasaran1 = SasaranKegiatan::firstOrCreate(
            ['sasaran_kegiatan' => 'Meningkatkan Kualitas Pelayanan Publik', 'unit_kerja' => $unit->nama_unit],
            [
                'status' => 'AKTIF',
            ]
        );

        $sasaran2 = SasaranKegiatan::firstOrCreate(
            ['sasaran_kegiatan' => 'Meningkatkan Tata Kelola Pemerintahan', 'unit_kerja' => $unit->nama_unit],
            [
                'status' => 'AKTIF',
            ]
        );

        $sasaran3 = SasaranKegiatan::firstOrCreate(
            ['sasaran_kegiatan' => 'Meningkatkan Transparansi dan Akuntabilitas', 'unit_kerja' => $unit->nama_unit],
            [
                'status' => 'AKTIF',
            ]
        );

        // Create Indikator Kinerja for Sasaran 1
        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Persentase kepuasan masyarakat terhadap layanan', 'sasaran_kegiatan_id' => $sasaran1->id],
            [
                'status' => 'AKTIF',
            ]
        );

        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Jumlah pengaduan yang terselesaikan', 'sasaran_kegiatan_id' => $sasaran1->id],
            [
                'status' => 'AKTIF',
            ]
        );

        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Waktu rata-rata penyelesaian layanan', 'sasaran_kegiatan_id' => $sasaran1->id],
            [
                'status' => 'AKTIF',
            ]
        );

        // Create Indikator Kinerja for Sasaran 2
        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Jumlah SOP yang dievaluasi dan diperbaharui', 'sasaran_kegiatan_id' => $sasaran2->id],
            [
                'status' => 'AKTIF',
            ]
        );

        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Persentase kehadiran ASN', 'sasaran_kegiatan_id' => $sasaran2->id],
            [
                'status' => 'AKTIF',
            ]
        );

        // Create Indikator Kinerja for Sasaran 3
        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Jumlah laporan keuangan yang dipublikasikan tepat waktu', 'sasaran_kegiatan_id' => $sasaran3->id],
            [
                'status' => 'AKTIF',
            ]
        );

        IndikatorKinerja::firstOrCreate(
            ['indikator_kinerja' => 'Persentase dokumen yang dapat diakses publik', 'sasaran_kegiatan_id' => $sasaran3->id],
            [
                'status' => 'AKTIF',
            ]
        );

        $this->command->info('✅ Master Kinerja data created successfully!');
        $this->command->info('');
        $this->command->info('Created:');
        $this->command->info('- 3 Sasaran Kegiatan');
        $this->command->info('- 7 Indikator Kinerja');
        $this->command->info('');
        $this->command->info('Now you can test SKP Tahunan with real master data!');
    }
}
