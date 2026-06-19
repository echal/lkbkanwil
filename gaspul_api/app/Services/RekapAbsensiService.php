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
     * Revisi rekap absensi yang ditolak oleh Kabid, Kankemenag, atau Kakanwil.
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

        // 2. Hanya boleh revisi jika status ditolak (kabid, kankemenag, atau kakanwil)
        if (! in_array($rekap->status, [
            RekapAbsensiPusaka::STATUS_REJECTED_KABID,
            RekapAbsensiPusaka::STATUS_REJECTED_KANKEMENAG,
            RekapAbsensiPusaka::STATUS_REJECTED_KAKANWIL,
        ])) {
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
     * Kabid/Kepala Madrasah/Kankemenag Kab menyetujui rekap absensi.
     *
     * Untuk Kepala Madrasah (atasannya adalah Kankemenag Kab):
     *   pending_kabid → pending_kankemenag
     *
     * Untuk Kankemenag Kab (dipanggil saat pending_kankemenag):
     *   pending_kankemenag → pending_kakanwil
     *
     * Untuk Kabid biasa:
     *   pending_kabid → pending_kakanwil
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approveKabid(int $kabidId, int $rekapId, ?string $catatan): RekapAbsensiPusaka
    {
        $isKankemenagKab = $this->isKankemenagKab($kabidId);

        if ($isKankemenagKab) {
            // Kankemenag Kab menyetujui rekap yang sudah disetujui Kepala Madrasah
            $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KANKEMENAG)
                ->whereHas('user', fn($q) => $q->whereHas('atasan', fn($a) => $a->where('atasan_id', $kabidId)))
                ->findOrFail($rekapId);

            $rekap->update([
                'status'               => RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL,
                'verified_by_kakanwil' => $kabidId,
                'catatan_kakanwil'     => $catatan,
            ]);
        } else {
            // Kepala Madrasah / Kabid biasa menyetujui rekap langsung bawahan
            $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KABID)
                ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId))
                ->findOrFail($rekapId);

            $nextStatus = $this->nextStatusAfterKabid($kabidId);

            $rekap->update([
                'status'      => $nextStatus,
                'verified_by' => $kabidId,
                'catatan'     => $catatan,
            ]);
        }

        return $rekap->fresh();
    }

    /**
     * Kankemenag Kab menyetujui rekap absensi → status menjadi pending_kakanwil.
     * Kankemenag Kab melihat rekap dari bawahan Kepala Madrasah yang sudah disetujui Kabid.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approveKankemenag(int $kankemenagId, int $rekapId, ?string $catatan): RekapAbsensiPusaka
    {
        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KANKEMENAG)
            ->whereHas('user', fn($q) => $q->whereHas('atasan', fn($a) => $a->where('atasan_id', $kankemenagId)))
            ->findOrFail($rekapId);

        $rekap->update([
            'status'               => RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL,
            'verified_by_kakanwil' => $kankemenagId,
            'catatan_kakanwil'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    /**
     * Kabid/Kepala Madrasah/Kankemenag Kab menolak rekap absensi.
     *
     * Untuk Kankemenag Kab: pending_kankemenag → rejected_kankemenag
     * Untuk Kabid biasa/Kepala Madrasah: pending_kabid → rejected_kabid
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

        $isKankemenagKab = $this->isKankemenagKab($kabidId);

        if ($isKankemenagKab) {
            $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KANKEMENAG)
                ->whereHas('user', fn($q) => $q->whereHas('atasan', fn($a) => $a->where('atasan_id', $kabidId)))
                ->findOrFail($rekapId);

            $rekap->update([
                'status'               => RekapAbsensiPusaka::STATUS_REJECTED_KANKEMENAG,
                'verified_by_kakanwil' => $kabidId,
                'catatan_kakanwil'     => $catatan,
            ]);
        } else {
            $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KABID)
                ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId))
                ->findOrFail($rekapId);

            $rekap->update([
                'status'      => RekapAbsensiPusaka::STATUS_REJECTED_KABID,
                'verified_by' => $kabidId,
                'catatan'     => $catatan,
            ]);
        }

        return $rekap->fresh();
    }

    /**
     * Kankemenag Kab menolak rekap absensi → status menjadi rejected_kankemenag.
     *
     * @throws ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function rejectKankemenag(int $kankemenagId, int $rekapId, string $catatan): RekapAbsensiPusaka
    {
        if (empty(trim($catatan))) {
            throw ValidationException::withMessages([
                'catatan' => 'Catatan wajib diisi saat menolak rekap.',
            ]);
        }

        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KANKEMENAG)
            ->whereHas('user', fn($q) => $q->whereHas('atasan', fn($a) => $a->where('atasan_id', $kankemenagId)))
            ->findOrFail($rekapId);

        $rekap->update([
            'status'               => RekapAbsensiPusaka::STATUS_REJECTED_KANKEMENAG,
            'verified_by_kakanwil' => $kankemenagId,
            'catatan_kakanwil'     => $catatan,
        ]);

        return $rekap->fresh();
    }

    // =========================================================================
    // APPROVAL — KAKANWIL
    // =========================================================================

    /**
     * Kakanwil menyetujui rekap absensi → status menjadi approved.
     * Kakanwil hanya melihat pending_kakanwil dari:
     * - L1: ASN langsung bawahan Kakanwil
     * - L2: ASN bawahan Kabid yang atasannya Kakanwil (non-madrasah, non-KUA)
     * Rekap dari Guru/Madrasah sudah melalui Kankemenag Kab terlebih dahulu.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approveKakanwil(int $kakanwilId, int $rekapId, ?string $catatan): RekapAbsensiPusaka
    {
        $rekap = RekapAbsensiPusaka::where('status', RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL)
            ->whereHas('user', function ($q) use ($kakanwilId) {
                $q->where(function ($sub) use ($kakanwilId) {
                    // L1: ASN langsung di bawah Kakanwil
                    $sub->where('atasan_id', $kakanwilId)
                        // L2: ASN → Kabid/Kankemenag Kab → Kakanwil
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
     * Ambil rekap absensi untuk Kabid / Kepala Madrasah / Kankemenag Kab.
     *
     * Untuk Kankemenag Kab (atasan_id = 293): menampilkan
     *   - Rekap bawahan langsung (via atasan_id = kabidId)
     *   - Rekap bawahan Kepala Madrasah (L2) yang sudah disetujui Kepala Madrasah (pending_kankemenag)
     *
     * Untuk Kabid biasa: hanya bawahan langsung.
     *
     * Mendukung filter status, bulan, dan NIP.
     */
    public function getForKabid(
        int $kabidId,
        ?string $filterStatus = null,
        ?string $filterBulan  = null,
        ?string $filterNip    = null,
    ): Collection {
        $isKankemenagKab = $this->isKankemenagKab($kabidId);

        if ($isKankemenagKab) {
            // Kankemenag Kab: L1 langsung + L2 dari Kepala Madrasah
            $query = RekapAbsensiPusaka::with([
                    'user:id,name,nip,jabatan,unit_kerja_id,atasan_id',
                    'user.unitKerja:id,nama_unit',
                    'user.atasan:id,name',
                    'histori',
                ])
                ->whereHas('user', function ($q) use ($kabidId) {
                    $q->where(function ($sub) use ($kabidId) {
                        $sub->where('atasan_id', $kabidId)
                            ->orWhereHas('atasan', fn($a) => $a->where('atasan_id', $kabidId));
                    });
                });
        } else {
            // Kabid biasa: hanya bawahan langsung
            $query = RekapAbsensiPusaka::with([
                    'user:id,name,nip,jabatan,unit_kerja_id',
                    'user.unitKerja:id,nama_unit',
                    'histori',
                ])
                ->whereHas('user', fn($q) => $q->where('atasan_id', $kabidId));
        }

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', $filterStatus);
        }

        if ($filterBulan && $filterBulan !== 'semua') {
            $query->where('bulan', 'like', $filterBulan . '%');
        }

        if ($filterNip) {
            $query->whereHas('user', fn($q) => $q->where('nip', 'like', '%' . $filterNip . '%'));
        }

        return $query->orderByDesc('bulan')->get();
    }

    /**
     * Ambil rekap absensi yang perlu diproses Kakanwil.
     * Mencakup ASN langsung (L1) dan ASN via Kabid/Kankemenag Kab (L2), dengan filter opsional.
     * Rekap dari Guru/Madrasah sudah melewati Kankemenag Kab terlebih dahulu.
     */
    public function getForKakanwil(
        int $kakanwilId,
        ?string $filterStatus = null,
        ?string $filterBulan  = null,
        ?string $filterNip    = null,
    ): Collection {
        $query = RekapAbsensiPusaka::with([
                'user:id,name,nip,jabatan,unit_kerja_id',
                'user.unitKerja:id,nama_unit',
                'user.atasan:id,name',
                'verifier:id,name',
                'histori',
            ])
            ->whereHas('user', function ($q) use ($kakanwilId) {
                $q->where(function ($sub) use ($kakanwilId) {
                    // L1: ASN langsung di bawah Kakanwil
                    $sub->where('atasan_id', $kakanwilId)
                        // L2: ASN → Kabid/Kankemenag Kab → Kakanwil
                        ->orWhereHas('atasan', fn($a) => $a->where('atasan_id', $kakanwilId));
                });
            });

        if ($filterStatus && $filterStatus !== 'semua') {
            $query->where('status', $filterStatus);
        }

        if ($filterBulan && $filterBulan !== 'semua') {
            $query->where('bulan', 'like', $filterBulan . '%');
        }

        if ($filterNip) {
            $query->whereHas('user', fn($q) => $q->where('nip', 'like', '%' . $filterNip . '%'));
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
     * Tentukan status berikutnya setelah Kabid/Kepala Madrasah menyetujui.
     * - Jika Kabid adalah Kankemenag Kab (atasan_id = Kakanwil): rekap naik ke pending_kankemenag
     *   (akan diproses oleh Kankemenag Kab, lalu naik lagi ke Kakanwil)
     * - Jika Kabid bukan Kankemenag Kab: rekap naik langsung ke pending_kakanwil
     */
    private function nextStatusAfterKabid(int $kabidId): string
    {
        // Periksa apakah Kabid ini adalah bawahan langsung dari Kankemenag Kab
        // (artinya: Kabid ini adalah Kepala Madrasah yang atasannya adalah Kankemenag Kab)
        $kabid = \App\Models\User::find($kabidId);
        if (! $kabid) {
            return RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL;
        }

        // Jika atasan dari Kabid ini adalah Kankemenag Kab (atasan_id == 293), maka
        // rekap harus melalui Kankemenag Kab dulu
        if ($kabid->atasan_id && $this->isKankemenagKab($kabid->atasan_id)) {
            return RekapAbsensiPusaka::STATUS_PENDING_KANKEMENAG;
        }

        return RekapAbsensiPusaka::STATUS_PENDING_KAKANWIL;
    }

    /**
     * Apakah user dengan ID ini adalah Kankemenag Kab?
     * Kriteria: ATASAN dengan atasan_id = 293 DAN jabatan mengandung
     * "kepala kantor" (bukan Kabid, Kabag TU, atau Pembimas).
     */
    private function isKankemenagKab(int $userId): bool
    {
        return \App\Models\User::where('id', $userId)
            ->where('atasan_id', SubordinateService::KAKANWIL_PROVINSI_ID)
            ->whereRaw("LOWER(jabatan) LIKE '%kepala kantor%'")
            ->exists();
    }

    /**
     * Hitung deadline upload: tanggal 10 bulan berikutnya pukul 23:59:59
     * Contoh: "2026-01" → Carbon(2026-02-10 23:59:59)
     */
    public function hitungDeadline(string $bulan): Carbon
    {
        [$tahun, $bln] = explode('-', $bulan);
        return Carbon::create((int) $tahun, (int) $bln, 1)
                     ->addMonth()
                     ->day(10)
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
