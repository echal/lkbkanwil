# SUMMARY IMPLEMENTASI KONSOLIDASI RHK

**Tanggal:** 2026-01-29
**Status:** ✅ BACKEND COMPLETE - VIEWS REMAINING
**Engineer:** Lead System Architect & Senior Laravel Engineer

---

## ✅ COMPLETED: BACKEND CONSOLIDATION

### 1️⃣ DATABASE SCHEMA (MIGRATION)

**File:** `database/migrations/2026_01_29_203733_consolidate_rhk_architecture_rename_column.php`

**Perubahan:**
```sql
-- Rename index di skp_tahunan_detail
ALTER TABLE skp_tahunan_detail DROP INDEX idx_rhk_pimpinan_id;
ALTER TABLE skp_tahunan_detail ADD INDEX idx_indikator_kinerja_id (indikator_kinerja_id);

-- Tambah kolom unit_kerja_id di indikator_kinerja
ALTER TABLE indikator_kinerja
  ADD COLUMN unit_kerja_id BIGINT UNSIGNED NULL AFTER sasaran_kegiatan_id
  COMMENT 'Unit Kerja yang bisa menggunakan indikator ini (NULL = semua unit)';

ALTER TABLE indikator_kinerja
  ADD CONSTRAINT fk_indikator_kinerja_unit_kerja
  FOREIGN KEY (unit_kerja_id) REFERENCES unit_kerja(id) ON DELETE SET NULL;
```

**Status:** ✅ Migration berhasil dijalankan

---

### 2️⃣ MODEL CONSOLIDATION

#### Model: IndikatorKinerja.php
**File:** `app/Models/IndikatorKinerja.php`

**Perubahan:**
```php
// HAPUS relationship lama:
// public function rhkPimpinan(): HasMany

// TAMBAH relationship baru:
public function skpTahunanDetails(): HasMany
{
    return $this->hasMany(SkpTahunanDetail::class, 'indikator_kinerja_id');
}

public function unitKerja(): BelongsTo
{
    return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
}

// UPDATE fillable:
protected $fillable = [
    'sasaran_kegiatan_id',
    'unit_kerja_id', // ← BARU
    'kode_indikator',
    'nama_indikator',
    'satuan',
    'tipe_target',
    'status',
];
```

#### Model: SkpTahunanDetail.php
**File:** `app/Models/SkpTahunanDetail.php`

**Perubahan:**
```php
// UBAH fillable:
protected $fillable = [
    'skp_tahunan_id',
    'indikator_kinerja_id', // ← UBAH dari rhk_pimpinan_id
    'target_tahunan',
    'satuan',
    'rencana_aksi',
    'realisasi_tahunan',
];

// UBAH relationship:
public function indikatorKinerja(): BelongsTo // ← UBAH dari rhkPimpinan()
{
    return $this->belongsTo(IndikatorKinerja::class, 'indikator_kinerja_id');
}

// UPDATE helper method:
public function getDisplayNameAttribute(): string
{
    return sprintf(
        '%s (%d %s) - %s',
        $this->indikatorKinerja->nama_indikator ?? '-', // ← UBAH
        $this->target_tahunan,
        $this->satuan,
        \Str::limit($this->rencana_aksi, 50)
    );
}
```

**Status:** ✅ Model consolidation complete

---

### 3️⃣ CONTROLLER REFACTORING

#### Controller: Admin\IndikatorKinerjaController.php
**File:** `app/Http/Controllers/Admin/IndikatorKinerjaController.php`

**Perubahan:**
```php
// UPDATE index() - tambah eager load unitKerja
$indikator = IndikatorKinerja::with(['sasaranKegiatan', 'unitKerja'])->latest()->get();

// UPDATE create() - tambah unit kerja list
$unitKerja = UnitKerja::aktif()->get();
return view('admin.indikator-kinerja.tambah', compact('sasaran', 'unitKerja'));

// UPDATE store() validation - tambah unit_kerja_id
$validated = $request->validate([
    'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
    'unit_kerja_id' => 'nullable|exists:unit_kerja,id', // ← BARU
    'kode_indikator' => 'required|unique:indikator_kinerja|max:20',
    // ...
]);

// UPDATE edit() - tambah unit kerja list
$unitKerja = UnitKerja::aktif()->get();
return view('admin.indikator-kinerja.edit', compact('indikator', 'sasaran', 'unitKerja'));

// UPDATE update() validation - tambah unit_kerja_id
'unit_kerja_id' => 'nullable|exists:unit_kerja,id', // ← BARU
```

#### Controller: Asn\SkpTahunanController.php
**File:** `app/Http/Controllers/Asn\SkpTahunanController.php`

**Perubahan:**
```php
// UPDATE index() - eager load langsung ke indikatorKinerja
$skpTahunan->load(['details.indikatorKinerja.sasaranKegiatan']);

// UPDATE create() - query IndikatorKinerja langsung (hapus layer RhkPimpinan)
$indikatorList = IndikatorKinerja::aktif()
    ->with('sasaranKegiatan')
    ->when($asn->unit_kerja_id, function($query) use ($asn) {
        $query->where(function($q) use ($asn) {
            $q->where('unit_kerja_id', $asn->unit_kerja_id)
              ->orWhereNull('unit_kerja_id'); // Allow global indikator
        });
    })
    ->get();

// UPDATE store() validation - ubah rhk_pimpinan_id → indikator_kinerja_id
$validated = $request->validate([
    'skp_tahunan_id' => 'required|exists:skp_tahunan,id',
    'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id', // ← UBAH
    'rencana_aksi' => 'required|string',
    'target_tahunan' => 'required|integer|min:1',
    'satuan' => 'required|string|max:50',
]);

// UPDATE store() create - ubah field rhk_pimpinan_id → indikator_kinerja_id
SkpTahunanDetail::create([
    'skp_tahunan_id' => $validated['skp_tahunan_id'],
    'indikator_kinerja_id' => $validated['indikator_kinerja_id'], // ← UBAH
    'rencana_aksi' => $validated['rencana_aksi'],
    'target_tahunan' => $validated['target_tahunan'],
    'satuan' => $validated['satuan'],
    'realisasi_tahunan' => 0,
]);

// UPDATE edit() - eager load langsung ke indikatorKinerja
$detail = SkpTahunanDetail::with(['skpTahunan', 'indikatorKinerja.sasaranKegiatan'])
    ->findOrFail($id);

// UPDATE update() validation - ubah rhk_pimpinan_id → indikator_kinerja_id
'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id', // ← UBAH
```

#### Controller: Asn\HarianController.php
**File:** `app/Http/Controllers/Asn\HarianController.php`

**Perubahan:**
```php
// UPDATE formKinerja() method - eager load indikatorKinerja
$rencanaKerja = RencanaAksiBulanan::whereHas('skpTahunanDetail.skpTahunan', function($query) use ($asn, $tahun) {
        $query->where('user_id', $asn->id)
              ->where('tahun', $tahun);
    })
    ->with(['skpTahunanDetail.indikatorKinerja']) // ← UBAH dari rhkPimpinan
    ->where('bulan', $bulan)
    ->where('tahun', $tahun)
    ->where('status', '!=', 'BELUM_DIISI')
    ->get()
    ->map(function($rencana) {
        return [
            'id' => $rencana->id,
            'indikator_kinerja' => $rencana->skpTahunanDetail->indikatorKinerja->nama_indikator ?? '-', // ← UBAH
            'rencana_aksi_bulanan' => $rencana->rencana_aksi_bulanan,
            'bulan' => $rencana->bulan_nama,
            'target' => $rencana->target_bulanan . ' ' . ($rencana->satuan_target ?? ''),
        ];
    });
```

**Status:** ✅ Controller refactoring complete

---

## 🔄 REMAINING TASKS: BLADE VIEWS

### 1️⃣ Admin Views (BELUM DIUPDATE)

#### File: `resources/views/admin/indikator-kinerja/tambah.blade.php`
**Perubahan yang diperlukan:**
```blade
{{-- TAMBAH dropdown Unit Kerja --}}
<div class="form-group">
    <label for="unit_kerja_id">Unit Kerja (Opsional)</label>
    <select name="unit_kerja_id" id="unit_kerja_id" class="form-control">
        <option value="">-- Semua Unit (Global) --</option>
        @foreach($unitKerja as $unit)
            <option value="{{ $unit->id }}" {{ old('unit_kerja_id') == $unit->id ? 'selected' : '' }}>
                {{ $unit->nama_unit }}
            </option>
        @endforeach
    </select>
    <small class="text-muted">
        Jika kosong, Indikator Kinerja ini bisa digunakan oleh semua unit kerja
    </small>
</div>

{{-- UPDATE label untuk memperjelas bahwa ini adalah RHK --}}
<div class="form-group">
    <label for="nama_indikator">Nama Indikator Kinerja (RHK Pimpinan)</label>
    <textarea name="nama_indikator" id="nama_indikator" class="form-control" rows="3" required>{{ old('nama_indikator') }}</textarea>
    <small class="text-muted">
        Ini adalah RHK yang akan dipilih ASN saat membuat SKP Tahunan
    </small>
</div>
```

#### File: `resources/views/admin/indikator-kinerja/edit.blade.php`
**Perubahan yang diperlukan:** (sama dengan tambah.blade.php)

#### File: `resources/views/admin/indikator-kinerja/index.blade.php`
**Perubahan yang diperlukan:**
```blade
{{-- TAMBAH kolom Unit Kerja di tabel --}}
<thead>
    <tr>
        <th>Kode</th>
        <th>Nama Indikator (RHK)</th>
        <th>Sasaran Kegiatan</th>
        <th>Unit Kerja</th> {{-- BARU --}}
        <th>Satuan</th>
        <th>Status</th>
        <th>Aksi</th>
    </tr>
</thead>
<tbody>
    @foreach($indikator as $ind)
        <tr>
            <td>{{ $ind->kode_indikator }}</td>
            <td>{{ $ind->nama_indikator }}</td>
            <td>{{ $ind->sasaranKegiatan->nama_sasaran ?? '-' }}</td>
            <td>{{ $ind->unitKerja->nama_unit ?? 'Semua Unit' }}</td> {{-- BARU --}}
            <td>{{ $ind->satuan }}</td>
            <td>{{ $ind->status }}</td>
            <td>...</td>
        </tr>
    @endforeach
</tbody>
```

---

### 2️⃣ ASN Views (BELUM DIUPDATE)

#### File: `resources/views/asn/skp-tahunan/create.blade.php`
**Perubahan yang diperlukan:**
```blade
{{-- UBAH dari grouped RhkPimpinan ke flat IndikatorKinerja --}}
<div class="form-group">
    <label for="indikator_kinerja_id">Pilih Indikator Kinerja (RHK Pimpinan)</label>
    <select name="indikator_kinerja_id" id="indikator_kinerja_id" class="form-control" required>
        <option value="">-- Pilih Indikator Kinerja --</option>
        @foreach($indikatorList as $indikator)
            <option value="{{ $indikator->id }}"
                    data-satuan="{{ $indikator->satuan }}"
                    {{ old('indikator_kinerja_id') == $indikator->id ? 'selected' : '' }}>
                {{ $indikator->kode_indikator }} - {{ $indikator->nama_indikator }}
                @if($indikator->unitKerja)
                    ({{ $indikator->unitKerja->nama_unit }})
                @endif
            </option>
        @endforeach
    </select>
    <small class="text-muted">
        Pilih Indikator Kinerja yang sesuai dengan tugas Anda
    </small>
</div>

{{-- Auto-fill satuan dari IndikatorKinerja --}}
<script>
document.getElementById('indikator_kinerja_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const satuan = selectedOption.getAttribute('data-satuan');
    if(satuan) {
        document.getElementById('satuan').value = satuan;
    }
});
</script>
```

#### File: `resources/views/asn/skp-tahunan/edit.blade.php`
**Perubahan yang diperlukan:** (sama dengan create.blade.php)

#### File: `resources/views/asn/skp-tahunan/index.blade.php`
**Perubahan yang diperlukan:**
```blade
{{-- UPDATE display untuk menampilkan indikatorKinerja langsung --}}
@foreach($skpTahunan->details as $detail)
    <tr>
        <td>{{ $detail->indikatorKinerja->kode_indikator ?? '-' }}</td>
        <td>{{ $detail->indikatorKinerja->nama_indikator ?? '-' }}</td>
        <td>{{ $detail->rencana_aksi }}</td>
        <td>{{ $detail->target_tahunan }} {{ $detail->satuan }}</td>
        <td>{{ $detail->realisasi_tahunan }} {{ $detail->satuan }}</td>
        <td>{{ $detail->capaian_persen }}%</td>
        <td>...</td>
    </tr>
@endforeach
```

#### File: `resources/views/asn/harian/form-kinerja.blade.php`
**Perubahan yang diperlukan:**
```blade
{{-- UPDATE dropdown display format --}}
<select id="rencana_kerja_id" name="rencana_kerja_id" class="...">
    <option value="">-- Pilih Rencana Aksi (Opsional) --</option>
    @foreach($rencanaKerja as $rencana)
        <option value="{{ $rencana['id'] }}" {{ old('rencana_kerja_id') == $rencana['id'] ? 'selected' : '' }}>
            {{ $rencana['bulan'] }} - {{ $rencana['indikator_kinerja'] }} - {{ Str::limit($rencana['rencana_aksi_bulanan'], 60) }}
        </option>
    @endforeach
</select>
```

---

## 📊 PROGRESS SUMMARY

### ✅ COMPLETED (Backend)
1. Database migration berhasil
2. Model IndikatorKinerja diupdate dengan relasi baru
3. Model SkpTahunanDetail diupdate untuk konsolidasi
4. Controller Admin\IndikatorKinerjaController diupdate
5. Controller Asn\SkpTahunanController diupdate
6. Controller Asn\HarianController diupdate

### 🔄 REMAINING (Frontend)
1. Update 6 Blade view files (Admin: 3, ASN: 3)
2. Testing end-to-end flow
3. Verifikasi konsistensi data

---

## 🎯 NEXT STEPS

1. Update Blade views sesuai dokumentasi di atas
2. Testing manual:
   - Admin: Tambah Indikator Kinerja dengan unit_kerja_id
   - ASN: Buat SKP Tahunan dari Indikator Kinerja
   - ASN: Isi Kinerja Harian dan pastikan dropdown benar
3. Verifikasi data flow: Admin → ASN → Atasan
4. Hapus menu "RHK Pimpinan" dari sidebar Admin (jika masih ada)

---

## ✅ VALIDATION CHECKLIST

- [x] Migration berhasil tanpa error
- [x] Relasi Model benar (SkpTahunanDetail → IndikatorKinerja)
- [x] Controller refactored (Admin + ASN)
- [x] Eager loading diupdate di semua query
- [ ] Admin bisa CRUD Indikator Kinerja dengan unit_kerja_id (VIEWS pending)
- [ ] ASN bisa memilih IndikatorKinerja langsung di SKP Tahunan (VIEWS pending)
- [ ] Dropdown Kinerja Harian menampilkan format: [Bulan] - [Indikator] - [Rencana Aksi] (VIEWS pending)
- [ ] Tidak ada error 500 di seluruh aplikasi (Testing pending)
- [ ] Data flow konsisten: Admin → ASN → Atasan (Testing pending)

---

**STATUS:** Backend consolidation 100% complete. Remaining: Frontend views update & testing.
