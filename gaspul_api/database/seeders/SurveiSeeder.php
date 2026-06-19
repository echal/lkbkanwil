<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SurveiSeeder extends Seeder
{
    public function run(): void
    {
        $periode = 'MEI-2026';

        $pertanyaan = [
            ['urutan' => 1,  'tipe' => 'SKALA', 'pertanyaan' => 'Bagaimana pendapat Bapak/Ibu terhadap aplikasi eSARAku dalam pengelolaan kinerja ASN?'],
            ['urutan' => 2,  'tipe' => 'SKALA', 'pertanyaan' => 'Bagaimana kemudahan fitur atau menu-menu dalam aplikasi eSARAku ketika digunakan?'],
            ['urutan' => 3,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah Bapak/Ibu sudah memahami seluruh fitur yang ada dalam aplikasi eSARAku?'],
            ['urutan' => 4,  'tipe' => 'SKALA', 'pertanyaan' => 'Dalam penggunaan aplikasi eSARAku, apakah Bapak/Ibu mengakses sendiri atau dibantu oleh orang lain?'],
            ['urutan' => 5,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah aplikasi eSARAku bermanfaat dalam membantu pengelolaan kinerja ASN?'],
            ['urutan' => 6,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah Bapak/Ibu sudah memahami sasaran atau target kinerja satuan/unit kerja Tahun 2026?'],
            ['urutan' => 7,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah Bapak/Ibu mengetahui strategi yang disusun untuk mencapai target/sasaran kinerja satuan/unit kerja Tahun 2026?'],
            ['urutan' => 8,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah atasan langsung Bapak/Ibu telah melakukan pemantauan terhadap pelaksanaan kinerja ASN?'],
            ['urutan' => 9,  'tipe' => 'SKALA', 'pertanyaan' => 'Apakah beban kerja telah didistribusikan secara merata kepada seluruh ASN sesuai jabatan masing-masing?'],
            ['urutan' => 10, 'tipe' => 'TEKS',  'pertanyaan' => 'Saran dan masukan Bapak/Ibu terhadap aplikasi eSARAku dalam pengelolaan kinerja ASN.'],
        ];

        $now = Carbon::now();

        // Cek apakah survei periode ini sudah ada
        $survei = DB::table('survei')->where('periode', $periode)->first();

        if (!$survei) {
            // INSERT survei baru
            $surveiId = DB::table('survei')->insertGetId([
                'judul'       => 'Survei Penggunaan ESARAKU',
                'deskripsi'   => 'Masukan Bapak/Ibu sangat berarti untuk pengembangan aplikasi eSARAku ke depan.',
                'periode'     => $periode,
                'is_required' => 0,
                'status'      => 'DRAFT',
                'dibuka_at'   => $now,
                'ditutup_at'  => Carbon::create(2026, 5, 31, 23, 59, 59),
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            foreach ($pertanyaan as $p) {
                DB::table('survei_pertanyaan')->insert([
                    'survei_id'  => $surveiId,
                    'urutan'     => $p['urutan'],
                    'tipe'       => $p['tipe'],
                    'pertanyaan' => $p['pertanyaan'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->command->info("Survei '{$periode}' berhasil dibuat dengan 10 pertanyaan.");
            return;
        }

        // UPDATE pertanyaan yang sudah ada (upsert per urutan)
        // TIDAK menyentuh survei_jawaban — hanya tabel survei_pertanyaan
        foreach ($pertanyaan as $p) {
            $existing = DB::table('survei_pertanyaan')
                ->where('survei_id', $survei->id)
                ->where('urutan', $p['urutan'])
                ->first();

            if ($existing) {
                DB::table('survei_pertanyaan')
                    ->where('id', $existing->id)
                    ->update([
                        'tipe'       => $p['tipe'],
                        'pertanyaan' => $p['pertanyaan'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('survei_pertanyaan')->insert([
                    'survei_id'  => $survei->id,
                    'urutan'     => $p['urutan'],
                    'tipe'       => $p['tipe'],
                    'pertanyaan' => $p['pertanyaan'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info("Pertanyaan survei '{$periode}' berhasil diperbarui (10 pertanyaan).");
    }
}
