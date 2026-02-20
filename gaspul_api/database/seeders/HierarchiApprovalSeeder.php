<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hierarki Approval Seeder
 *
 * Seeder untuk mengisi relasi atasan_id berdasarkan hierarki organisasi:
 * - ASN → atasan_id = Kabid/Kabag (Eselon III)
 * - Kabid/Kabag (Eselon III) → atasan_id = Kakanwil (Eselon II)
 * - JF Ahli Madya → atasan_id = Kakanwil (Eselon II)
 * - Kakanwil (Eselon II) → atasan_id = null (puncak hierarki)
 *
 * PENTING:
 * - Seeder ini OPSIONAL dan tidak mengubah data existing secara paksa
 * - Seeder hanya mengisi atasan_id yang masih NULL
 * - Jika sudah ada atasan_id, seeder tidak akan overwrite
 *
 * CARA PAKAI:
 * 1. Sesuaikan mapping di method mapAtasanByJabatan() dengan data real
 * 2. Jalankan: php artisan db:seed --class=HierarchiApprovalSeeder
 *
 * @author Claude Sonnet 4.5
 * @date 2026-02-14
 */
class HierarchiApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Starting Hierarchi Approval Seeder...');

        // ====================================================================
        // STEP 1: Identifikasi Kakanwil (Eselon II) sebagai puncak hierarki
        // ====================================================================

        $kakanwil = User::where('jabatan', 'LIKE', '%Kepala Kantor Wilayah%')
            ->orWhere('jabatan', 'LIKE', '%Kakanwil%')
            ->first();

        if (!$kakanwil) {
            $this->command->warn('⚠️  Kakanwil tidak ditemukan. Membuat dummy Kakanwil...');
            $kakanwil = $this->createDummyKakanwil();
        }

        $this->command->info("✓ Kakanwil: {$kakanwil->name} (ID: {$kakanwil->id})");

        // Set Kakanwil atasan_id = null (puncak hierarki)
        $kakanwil->update(['atasan_id' => null]);

        // ====================================================================
        // STEP 2: Set Kabid/Kabag (Eselon III) → atasan = Kakanwil
        // ====================================================================

        $kabidKabag = User::where(function($query) {
            $query->where('jabatan', 'LIKE', '%Kepala Bidang%')
                  ->orWhere('jabatan', 'LIKE', '%Kepala Bagian%')
                  ->orWhere('jabatan', 'LIKE', '%Kabid%')
                  ->orWhere('jabatan', 'LIKE', '%Kabag%');
        })
        ->whereNull('atasan_id') // Hanya update yang belum punya atasan
        ->get();

        $updatedKabid = 0;
        foreach ($kabidKabag as $user) {
            $user->update(['atasan_id' => $kakanwil->id]);
            $updatedKabid++;
        }

        $this->command->info("✓ Updated $updatedKabid Kabid/Kabag → atasan = Kakanwil");

        // ====================================================================
        // STEP 3: Set JF Ahli Madya → atasan = Kakanwil
        // ====================================================================

        $jfAhliMadya = User::where('jabatan', 'LIKE', '%Ahli Madya%')
            ->whereNull('atasan_id')
            ->get();

        $updatedJf = 0;
        foreach ($jfAhliMadya as $user) {
            $user->update(['atasan_id' => $kakanwil->id]);
            $updatedJf++;
        }

        $this->command->info("✓ Updated $updatedJf JF Ahli Madya → atasan = Kakanwil");

        // ====================================================================
        // STEP 4: Set ASN → atasan = Kabid/Kabag (berdasarkan unit_kerja_id)
        // ====================================================================

        // Ambil semua ASN yang belum punya atasan
        $asnList = User::where('role', 'ASN')
            ->whereNull('atasan_id')
            ->get();

        $updatedAsn = 0;

        foreach ($asnList as $asn) {
            // Cari Kabid/Kabag di unit kerja yang sama
            $atasan = User::where(function($query) {
                $query->where('jabatan', 'LIKE', '%Kepala Bidang%')
                      ->orWhere('jabatan', 'LIKE', '%Kepala Bagian%')
                      ->orWhere('jabatan', 'LIKE', '%Kabid%')
                      ->orWhere('jabatan', 'LIKE', '%Kabag%');
            })
            ->where('unit_kerja_id', $asn->unit_kerja_id)
            ->first();

            // Jika ada Kabid/Kabag di unit yang sama, set sebagai atasan
            if ($atasan) {
                $asn->update(['atasan_id' => $atasan->id]);
                $updatedAsn++;
            } else {
                // Fallback: set Kakanwil sebagai atasan langsung
                $asn->update(['atasan_id' => $kakanwil->id]);
                $updatedAsn++;
            }
        }

        $this->command->info("✓ Updated $updatedAsn ASN → atasan = Kabid/Kabag (atau Kakanwil)");

        // ====================================================================
        // STEP 5: Summary Report
        // ====================================================================

        $this->command->info('');
        $this->command->info('=== HIERARKI APPROVAL SUMMARY ===');

        $totalWithAtasan = User::whereNotNull('atasan_id')->count();
        $totalWithoutAtasan = User::whereNull('atasan_id')->count();

        $this->command->table(
            ['Kategori', 'Jumlah'],
            [
                ['User dengan atasan', $totalWithAtasan],
                ['User tanpa atasan (puncak hierarki)', $totalWithoutAtasan],
                ['Kabid/Kabag updated', $updatedKabid],
                ['JF Ahli Madya updated', $updatedJf],
                ['ASN updated', $updatedAsn],
            ]
        );

        $this->command->info('');
        $this->command->info('✅ Hierarki Approval Seeder completed!');
        $this->command->info('⚠️  Catatan: Data existing TIDAK di-overwrite (hanya yang atasan_id NULL)');
    }

    /**
     * Create dummy Kakanwil if not exists
     *
     * @return User
     */
    private function createDummyKakanwil(): User
    {
        return User::create([
            'name' => 'Kakanwil (Dummy)',
            'email' => 'kakanwil@gaspul.com',
            'password' => bcrypt('password'),
            'role' => 'ATASAN',
            'nip' => '198001012005011001',
            'jabatan' => 'Kepala Kantor Wilayah',
            'unit_kerja_id' => 1, // Adjust sesuai data
            'atasan_id' => null, // Puncak hierarki
            'status_pegawai' => 'AKTIF',
        ]);
    }
}
