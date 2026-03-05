<?php

namespace App\Services;

use App\Models\RekapAbsensiHistori;
use App\Models\RekapAbsensiPusaka;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RekapAbsensiService
{
    // =========================================================================
    // UPLOAD
    // =========================================================================

    /**
     * Upload rekap absensi PUSAKA untuk user dan bulan tertentu.
     *
     * Validasi:
     * - Link harus dari drive.google.com
     * - Deadline tidak boleh terlewat (maks tanggal 5 bulan berikutnya)
     * - Bulan yang sama tidak boleh diupload dua kali
     *
     * @throws ValidationException
     */
    public function upload(int $userId, string $bulan, string $link): RekapAbsensiPusaka
    {
        // 1. Validasi link
        if (! str_contains($link, 'drive.google.com')) {
            throw ValidationException::withMessages([
                'link_drive' => 'Link harus berupa Google Drive (drive.google.com).',
            ]);
        }

        // 2. Validasi deadline upload
        $deadline = $this->hitungDeadline($bulan);
        if (now()->gt($deadline)) {
            throw ValidationException::withMessages([
                'bulan' => 'Deadline upload rekap ' . $this->formatBulan($bulan)
                         . ' sudah lewat (maksimal ' . $deadline->format('d M Y') . ').',
            ]);
        }

        // 3. Cek duplikat
        $existing = RekapAbsensiPusaka::where('user_id', $userId)
            ->where('bulan', $bulan)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'bulan' => 'Rekap absensi untuk bulan ' . $existing->nama_bulan . ' sudah pernah diupload.',
            ]);
        }

        return RekapAbsensiPusaka::create([
            'user_id'    => $userId,
            'bulan'      => $bulan,
            'link_drive' => $link,
            'status'     => RekapAbsensiPusaka::STATUS_PENDING_KABID,
        ]);
    }

    // =========================================================================
    // REVISI
    // =========================================================================

    /**
     * Revisi rekap absensi yang ditolak oleh Kabid atau Kakanwil.
     *
     * Alur:
     * 1. Validasi kepemilikan, status, dan deadline
     * 2. Arsipkan link lama ke tabel histori
     * 3. Update link baru, reset status ke pending_kabid, reset semua field approval
     *
     * @throws ValidationException
     */
    public function revisi(int $userId, int $rekapId, string $linkBaru): RekapAbsensiPusaka
    {
        // 1. Ambil rekap milik user ini
        $rekap = RekapAbsensiPusaka::where('user_id', $userId)->findOrFail($rekapId);

        // 2. Hanya boleh revisi jika status ditolak (kabid atau kakanwil)
        if (! in_array($rekap->status, [RekapAbsensiPusaka::STATUS_REJECTED_KABID, RekapAbsensiPusaka::STATUS_REJECTED_KAKANWIL])) {
            throw ValidationException::withMessages([
                'link_drive' => 'Revisi hanya dapat dilakukan pada rekap yang berstatus Ditolak.',
            ]);
        }

        // 3. Cek deadline
        if ($rekap->is_deadline_past) {
            throw ValidationException::withMessages([
                'bulan' => 'Deadline revisi rekap ' . $rekap->nama_bulan
                         . ' sudah lewat (maksimal ' . $rekap->deadline_upload->format('d M Y') . ').',
            ]);
        }

        // 4. Validasi link baru
        if (! str_contains($linkBaru, 'drive.google.com')) {
            throw ValidationException::withMessages([
                'link_drive' => 'Link harus berupa Google Drive (drive.google.com).',
            ]);
        }

        // 5. Simpan histori sebelum diubah
        RekapAbsensiHistori::create([
            'rekap_absensi_id' => $rekap->id,
            'link_drive_lama'  => $rekap->link_drive,
            'revision_number'  => $rekap->revision_count,
            'tanggal_revisi'   => now(),
        ]);

        // 6. Update rekap — reset ke pending_kabid (mulai dari awal lagi)
        $rekap->update([
            'link_drive'           => $linkBaru,
            'status'               => RekapAbsensiPusaka::STATUS_PENDING_KABID,
            'catatan'              => null,
            'verified_by'          => null,
            'catatan_kakanwil'     => null,
            'verified_by_kakanwil' => null,
            'revision_count'       => $rekap->revision_count + 1,
        ]);

        return $rekap->fresh();
    }

    // =========================================================================
    // APPROVAL — KABID
    // =========================================================================

    /**
     * Kabid menyetujui rekap absensi → status menjadi pending_kakanwil.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approveKabid(int $kabidId, int $rekapId, ?string $catatan): RekapAbsensiPusaka
    {
        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KABID)
            ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId))
            ->findOrFail($rekapId);

        $rekap->update([
            'status'      => RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL,
            'verified_by' => $kabidId,
            'catatan'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    /**
     * Kabid menolak rekap absensi → status menjadi rejected_kabid.
     *
     * @throws ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function rejectKabid(int $kabidId, int $rekapId, string $catatan): RekapAbsensiPusaka
    {
        if (empty(trim($catatan))) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan wajib diisi saat menolak rekap.',
            ]);
        }

        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KABID)
            ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId))
            ->findOrFail($rekapId);

        $rekap->update([
            'status'      => RekapAbsensiPusaka::STATUS_REJECTED_KABID,
            'verified_by' => $kabidId,
            'catatan'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    // =========================================================================
    // APPROVAL — KAKANWIL
    // =========================================================================

    /**
     * Kakanwil menyetujui rekap absensi → status menjadi approved.
     * Hanya melihat rekap dengan status pending_kakanwil yang berasal
     * dari bawahan langsung Kakanwil atau bawahan via Kabid.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approveKakanwil(int $kakanwilId, int $rekapId, ?string $catatan): RekapAbsensiPusaka
    {
        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL)
            ->whereHas('user', function ($q) use ($kakanwilId) {
                $q->where(function ($sub) use ($kakanwilId) {
                    // ASN yang langsung di bawah Kakanwil
                    $sub->where('atasan_id', $kakanwilId)
                        // atau ASN yang atasannya (Kabid) berada di bawah Kakanwil
                        ->orWhereHas('atasan', fn($a) => $a->where('atasan_id', $kakanwilId));
                });
            })
            ->findOrFail($rekapId);

        $rekap->update([
            'status'               => RekapAbsensiPusaka::STATUS_APPROVED,
            'verified_by_kakanwil' => $kakanwilId,
            'catatan_kakanwil'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    /**
     * Kakanwil menolak rekap absensi → status menjadi rejected_kakanwil.
     *
     * @throws ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function rejectKakanwil(int $kakanwilId, int $rekapId, string $catatan): RekapAbsensiPusaka
    {
        if (empty(trim($catatan))) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan wajib diisi saat menolak rekap.',
            ]);
        }

        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL)
            ->whereHas('user', function ($q) use ($kakanwilId) {
                $q->where(function ($sub) use ($kakanwilId) {
                    $sub->where('atasan_id', $kakanwilId)
                        ->orWhereHas('atasan', fn($a) => $a->where('atasan_id', $kakanwilId));
                });
            })
            ->findOrFail($rekapId);

        $rekap->update([
            'status'               => RekapAbsensiPusaka::STATUS_REJECTED_KAKANWIL,
            'verified_by_kakanwil' => $kakanwilId,
            'catatan_kakanwil'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    // =========================================================================
    // QUERY — ASN
    // =========================================================================

    /**
     * Ambil semua rekap absensi milik user, diurutkan terbaru.
     * Eager load verifier kabid, verifier kakanwil, dan histori revisi.
     */
    public function getByUser(int $userId): Collection
    {
        return RekapAbsensiPusaka::where('user_id', $userId)
            ->with([
                'verifier:id,name',
                'verifierKakanwil:id,name',
                'histori',
            ])
            ->orderByDesc('bulan')
            ->get();
    }

    /**
     * Ambil rekap absensi untuk user dan bulan tertentu.
     */
    public function getByUserAndBulan(int $userId, string $bulan): ?RekapAbsensiPusaka
    {
        return RekapAbsensiPusaka::where('user_id', $userId)
            ->where('bulan', $bulan)
            ->with('verifier:id,name')
            ->first();
    }

    // =========================================================================
    // QUERY — ATASAN
    // =========================================================================

    /**
     * Ambil rekap absensi bawahan langsung Kabid.
     * Kabid melihat semua status (histori lengkap) dengan filter opsional.
     */
    public function getForKabid(int $kabidId, ?string $filterStatus = null, ?string $filterBulan = null): Collection
    {
        $query = RekapAbsensiPusaka::with([
                'user:id,name,nip,jabatan,unit_kerja_id',
                'user.unitKerja:id,nama_unit',
                'histori',
            ])
            ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId));

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', $filterStatus);
        }

        if ($filterBulan && $filterBulan !== 'semua') {
            $query->where('bulan', 'like', $filterBulan . '%');
        }

        return $query->orderByDesc('bulan')->get();
    }

    /**
     * Ambil rekap absensi yang perlu diproses Kakanwil.
     * Mencakup ASN langsung di bawah Kakanwil dan ASN via Kabid, dengan filter opsional.
     */
    public function getForKakanwil(int $kakanwilId, ?string $filterStatus = null, ?string $filterBulan = null): Collection
    {
        $query = RekapAbsensiPusaka::with([
                'user:id,name,nip,jabatan,unit_kerja_id',
                'user.unitKerja:id,nama_unit',
                'user.atasan:id,name',
                'verifier:id,name',
                'histori',
            ])
            ->whereHas('user', function ($q) use ($kakanwilId) {
                $q->where(function ($sub) use ($kakanwilId) {
                    $sub->where('atasan_id', $kakanwilId)
                        ->orWhereHas('atasan', fn($a) => $a->where('atasan_id', $kakanwilId));
                });
            });

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', $filterStatus);
        }

        if ($filterBulan && $filterBulan !== 'semua') {
            $query->where('bulan', 'like', $filterBulan . '%');
        }

        return $query->orderByDesc('bulan')->get();
    }

    // =========================================================================
    // QUERY — COMPLIANCE
    // =========================================================================

    /**
     * Hitung compliance upload rekap absensi untuk bulan tertentu.
     *
     * @return array{total_asn: int, sudah_upload: int, belum_upload: int, persen: int}
     */
    public function getComplianceBulan(string $bulan): array
    {
        $totalAsn = User::where('role', 'ASN')
            ->where('status_pegawai', 'AKTIF')
            ->count();

        $sudahUpload = RekapAbsensiPusaka::where('bulan', $bulan)
            ->whereHas('user', fn($q) => $q->where('role', 'ASN')->where('status_pegawai', 'AKTIF'))
            ->count();

        $belumUpload = max(0, $totalAsn - $sudahUpload);
        $persen      = $totalAsn > 0 ? round(($sudahUpload / $totalAsn) * 100) : 0;

        return [
            'total_asn'    => $totalAsn,
            'sudah_upload' => $sudahUpload,
            'belum_upload' => $belumUpload,
            'persen'       => $persen,
        ];
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Generate pilihan 12 bulan terakhir untuk dropdown.
     *
     * @return array<string, string> ['YYYY-MM' => 'Nama Bulan YYYY']
     */
    public function getBulanOptions(): array
    {
        $options = [];
        for ($i = 0; $i < 12; $i++) {
            $date  = now()->subMonths($i);
            $key   = $date->format('Y-m');
            $options[$key] = $this->formatBulan($key);
        }

        return $options;
    }

    /**
     * Hitung deadline upload: tanggal 5 bulan berikutnya pukul 23:59:59
     * Contoh: "2026-01" → Carbon(2026-02-05 23:59:59)
     */
    public function hitungDeadline(string $bulan): Carbon
    {
        [$tahun, $bln] = explode('-', $bulan);
        return Carbon::create((int) $tahun, (int) $bln, 1)
                     ->addMonth()
                     ->day(5)
                     ->endOfDay();
    }

    /**
     * Format string bulan "YYYY-MM" menjadi "Nama Bulan YYYY"
     * Contoh: "2026-02" → "Februari 2026"
     */
    private function formatBulan(string $bulan): string
    {
        $namaBulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
            '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
            '10' => 'Oktober', '11' => 'November',  '12' => 'Desember',
        ];

        [$tahun, $bln] = explode('-', $bulan);
        return ($namaBulan[$bln] ?? $bln) . ' ' . $tahun;
    }
}
