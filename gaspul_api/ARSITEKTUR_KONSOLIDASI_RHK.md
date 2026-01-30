# KONSOLIDASI ARSITEKTUR RHK
**Tanggal:** 2026-01-29
**Status:** SIAP IMPLEMENTASI
**Engineer:** Lead System Architect & Senior Laravel Engineer

---

## 📊 HASIL AUDIT ARSITEKTUR SAAT INI

### 1️⃣ Analisis Database (DATA AKTUAL)

```
✅ INDIKATOR KINERJA (indikator_kinerja table)
   - 3 records AKTIF
   - ID: 5, 6, 7
   - Terhubung ke: sasaran_kegiatan
   - Kolom: id, sasaran_kegiatan_id, kode_indikator, nama_indikator, satuan, tipe_target, status

❌ RHK PIMPINAN (rhk_pimpinan table)
   - 0 records (KOSONG)
   - Table ada, tapi TIDAK ADA DATA
   - Kolom: id, indikator_kinerja_id, unit_kerja_id, rhk_pimpinan, status

⚠️ SKP TAHUNAN DETAIL (skp_tahunan_detail table)
   - 0 records (KOSONG)
   - Kolom: id, skp_tahunan_id, rhk_pimpinan_id, rencana_aksi, target_tahunan, satuan, realisasi_tahunan
```

### 2️⃣ Analisis Model & Relasi

**RELASI SAAT INI (DUPLIKASI KONSEP):**
```
SasaranKegiatan
    ↓ hasMany
IndikatorKinerja
    ↓ hasMany
RhkPimpinan ← ❌ LAYER TAMBAHAN (TIDAK DIPERLUKAN)
    ↓ hasMany
SkpTahunanDetail
    ↓ hasMany
RencanaAksiBulanan
    ↓ hasMany
ProgresHarian
```

**MASALAH ARSITEKTUR:**
1. **Duplikasi Konsep:** `IndikatorKinerja` dan `RhkPimpinan` memiliki fungsi yang sama
2. **Relasi Berlebihan:** ASN harus memilih `RhkPimpinan` yang merupakan turunan dari `IndikatorKinerja`
3. **Fragmentasi Data:** Admin manage 2 menu terpisah (Indikator Kinerja + RHK Pimpinan)
4. **Inkonsistensi:** Potential data tidak sinkron antara IndikatorKinerja dan RhkPimpinan

### 3️⃣ Analisis Controller

**Controller yang menggunakan RhkPimpinan:**
```php
// ADMIN
- Admin\RhkPimpinanController.php → CRUD RhkPimpinan (HAPUS MENU INI)
- Admin\IndikatorKinerjaController.php → CRUD IndikatorKinerja (PERTAHANKAN)

// ASN
- Asn\SkpTahunanController.php → Dropdown dari RhkPimpinan (UBAH ke IndikatorKinerja)
- Asn\HarianController.php → Query via rhkPimpinan relationship (UPDATE)

// ATASAN
- Atasan\SkpTahunanAtasanController.php → View via rhkPimpinan (UPDATE)
```

---

## 🎯 ARSITEKTUR TARGET (FINAL)

### Relasi Baru (SIMPLIFIED):
```
SasaranKegiatan (PERKIN Organisasi)
    ↓ hasMany
IndikatorKinerja (RHK PIMPINAN - SATU-SATUNYA SUMBER)
    ↓ hasMany
SkpTahunanDetail
    ↓ hasMany
RencanaAksiBulanan
    ↓ hasMany
ProgresHarian
```

### Menu Admin (SIMPLIFIED):
```
1. Sasaran Kegiatan → Manage PERKIN Organisasi
2. Indikator Kinerja → Manage RHK (ini SATU-SATUNYA sumber RHK)
   ❌ HAPUS menu "RHK Pimpinan" dari routing & sidebar
```

### Alur Kerja ASN (FINAL):
```
1. SKP Tahunan ASN
   - ASN memilih LANGSUNG dari: IndikatorKinerja
   - ASN mengisi: Rencana Aksi, Target, Satuan

2. Breakdown Bulanan
   - Auto-generate 12 bulan dari SkpTahunanDetail

3. Kinerja Harian
   - Dropdown dari RencanaAksiBulanan (tetap sama)
   - Display: [Bulan] - [IndikatorKinerja.nama_indikator] - [RencanaAksiBulanan.rencana_aksi_bulanan]
```

---

## 🛠️ RENCANA IMPLEMENTASI

### FASE 1: KONSOLIDASI MODEL & DATABASE ✅ SIAP

#### 1.1 Update Model SkpTahunanDetail
**File:** `app/Models/SkpTahunanDetail.php`

**PERUBAHAN:**
- UBAH kolom: `rhk_pimpinan_id` → `indikator_kinerja_id`
- UBAH relationship: `rhkPimpinan()` → `indikatorKinerja()`
- TAMBAH migration untuk rename kolom

**MIGRATION PLAN:**
```php
// 2026_01_29_xxxxxx_consolidate_rhk_architecture.php

public function up()
{
    // Step 1: Rename kolom di skp_tahunan_detail
    Schema::table('skp_tahunan_detail', function (Blueprint $table) {
        $table->dropForeign(['rhk_pimpinan_id']); // Drop old FK
        $table->renameColumn('rhk_pimpinan_id', 'indikator_kinerja_id');

        // Add new FK constraint
        $table->foreign('indikator_kinerja_id')
              ->references('id')
              ->on('indikator_kinerja')
              ->onDelete('cascade');
    });

    // Step 2: Tambah kolom unit_kerja_id di indikator_kinerja (jika belum ada)
    if (!Schema::hasColumn('indikator_kinerja', 'unit_kerja_id')) {
        Schema::table('indikator_kinerja', function (Blueprint $table) {
            $table->foreignId('unit_kerja_id')->nullable()->after('sasaran_kegiatan_id');
            $table->foreign('unit_kerja_id')
                  ->references('id')
                  ->on('unit_kerja')
                  ->onDelete('set null');
        });
    }
}

public function down()
{
    Schema::table('skp_tahunan_detail', function (Blueprint $table) {
        $table->dropForeign(['indikator_kinerja_id']);
        $table->renameColumn('indikator_kinerja_id', 'rhk_pimpinan_id');

        $table->foreign('rhk_pimpinan_id')
              ->references('id')
              ->on('rhk_pimpinan')
              ->onDelete('cascade');
    });

    if (Schema::hasColumn('indikator_kinerja', 'unit_kerja_id')) {
        Schema::table('indikator_kinerja', function (Blueprint $table) {
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn('unit_kerja_id');
        });
    }
}
```

#### 1.2 Update Model IndikatorKinerja
**File:** `app/Models/IndikatorKinerja.php`

**PERUBAHAN:**
```php
// HAPUS relasi ini:
public function rhkPimpinan(): HasMany
{
    return $this->hasMany(RhkPimpinan::class, 'indikator_kinerja_id');
}

// TAMBAH relasi baru:
public function skpTahunanDetails(): HasMany
{
    return $this->hasMany(SkpTahunanDetail::class, 'indikator_kinerja_id');
}

public function unitKerja(): BelongsTo
{
    return $this->belongsTo(UnitKerja::class, 'unit_kerja_id');
}

// TAMBAH fillable:
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

#### 1.3 Update Model SkpTahunanDetail
**File:** `app/Models/SkpTahunanDetail.php`

**PERUBAHAN:**
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
/**
 * SKP Tahunan Detail belongs to Indikator Kinerja
 */
public function indikatorKinerja(): BelongsTo
{
    return $this->belongsTo(IndikatorKinerja::class, 'indikator_kinerja_id');
}

// HAPUS relationship lama:
// public function rhkPimpinan(): BelongsTo { ... }

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

---

### FASE 2: REFACTOR CONTROLLER ✅ SIAP

#### 2.1 Hapus Menu RHK Pimpinan (Admin)
**File:** `routes/web.php`

**PERUBAHAN:**
```php
// HAPUS route ini:
// Route::resource('rhk-pimpinan', RhkPimpinanController::class);

// PERTAHANKAN route ini:
Route::resource('indikator-kinerja', IndikatorKinerjaController::class);
```

**File:** `resources/views/layouts/sidebar.blade.php` (atau sejenisnya)

**PERUBAHAN:**
```blade
{{-- HAPUS menu item ini --}}
{{-- <li><a href="{{ route('admin.rhk-pimpinan.index') }}">RHK Pimpinan</a></li> --}}

{{-- PERTAHANKAN menu ini --}}
<li><a href="{{ route('admin.indikator-kinerja.index') }}">Indikator Kinerja (RHK)</a></li>
```

#### 2.2 Update Controller Admin IndikatorKinerja
**File:** `app/Http/Controllers/Admin/IndikatorKinerjaController.php`

**PERUBAHAN:**
```php
// UPDATE create() method:
public function create()
{
    $sasaran = SasaranKegiatan::aktif()->get();
    $unitKerja = \App\Models\UnitKerja::aktif()->get(); // ← TAMBAH
    return view('admin.indikator-kinerja.tambah', compact('sasaran', 'unitKerja'));
}

// UPDATE store() validation:
public function store(Request $request)
{
    $validated = $request->validate([
        'sasaran_kegiatan_id' => 'required|exists:sasaran_kegiatan,id',
        'unit_kerja_id' => 'nullable|exists:unit_kerja,id', // ← TAMBAH
        'kode_indikator' => 'required|unique:indikator_kinerja|max:20',
        'nama_indikator' => 'required',
        'satuan' => 'required|max:50',
        'tipe_target' => 'required|in:ANGKA,DOKUMEN,PERSENTASE',
        'status' => 'required|in:AKTIF,NONAKTIF',
    ]);

    IndikatorKinerja::create($validated);
    return redirect()->route('admin.indikator-kinerja.index')
        ->with('success', 'Indikator Kinerja (RHK Pimpinan) berhasil ditambahkan');
}

// UPDATE edit() & update() methods juga
```

#### 2.3 Update Controller ASN SkpTahunan
**File:** `app/Http/Controllers/Asn/SkpTahunanController.php`

**PERUBAHAN:**
```php
// UPDATE index() method:
public function index(Request $request)
{
    // ...
    // UBAH eager loading:
    $skpTahunan->load(['details.indikatorKinerja']); // ← UBAH dari rhkPimpinan.indikatorKinerja
    // ...
}

// UPDATE create() method:
public function create(Request $request)
{
    // ...
    // UBAH query:
    $indikatorList = IndikatorKinerja::aktif()
        ->when($asn->unit_kerja_id, function($query) use ($asn) {
            $query->where(function($q) use ($asn) {
                $q->where('unit_kerja_id', $asn->unit_kerja_id)
                  ->orWhereNull('unit_kerja_id'); // Allow global indikator
            });
        })
        ->with('sasaranKegiatan')
        ->get();

    return view('asn.skp-tahunan.create', [
        'skpTahunan' => $skpTahunan,
        'indikatorList' => $indikatorList, // ← UBAH dari rhkPimpinan grouped
        'asn' => $asn,
    ]);
}

// UPDATE store() validation:
public function store(Request $request)
{
    $validated = $request->validate([
        'skp_tahunan_id' => 'required|exists:skp_tahunan,id',
        'indikator_kinerja_id' => 'required|exists:indikator_kinerja,id', // ← UBAH
        'rencana_aksi' => 'required|string',
        'target_tahunan' => 'required|integer|min:1',
        'satuan' => 'required|string|max:50',
    ]);

    // ...
    SkpTahunanDetail::create([
        'skp_tahunan_id' => $validated['skp_tahunan_id'],
        'indikator_kinerja_id' => $validated['indikator_kinerja_id'], // ← UBAH
        'rencana_aksi' => $validated['rencana_aksi'],
        'target_tahunan' => $validated['target_tahunan'],
        'satuan' => $validated['satuan'],
        'realisasi_tahunan' => 0,
    ]);
    // ...
}

// UPDATE edit() & update() methods juga
```

#### 2.4 Update Controller ASN HarianController
**File:** `app/Http/Controllers/Asn/HarianController.php`

**PERUBAHAN:**
```php
// UPDATE formKinerja() method (Line 228-260):
public function formKinerja()
{
    // ...
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
    // ...
}
```

---

### FASE 3: UPDATE BLADE VIEWS ✅ SIAP

#### 3.1 View Admin Indikator Kinerja Form
**File:** `resources/views/admin/indikator-kinerja/tambah.blade.php`

**PERUBAHAN:**
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

{{-- UPDATE label & helper text --}}
<div class="form-group">
    <label for="nama_indikator">Nama Indikator Kinerja (RHK Pimpinan)</label>
    <textarea name="nama_indikator" id="nama_indikator" class="form-control" rows="3" required>{{ old('nama_indikator') }}</textarea>
    <small class="text-muted">
        Ini adalah RHK yang akan dipilih ASN saat membuat SKP Tahunan
    </small>
</div>
```

#### 3.2 View ASN SKP Tahunan Form
**File:** `resources/views/asn/skp-tahunan/create.blade.php`

**PERUBAHAN:**
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

#### 3.3 View ASN Kinerja Harian Form
**File:** `resources/views/asn/harian/form-kinerja.blade.php`

**PERUBAHAN:**
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

### FASE 4: MIGRATION & DEPLOYMENT ✅ SIAP

#### 4.1 Backup Data (WAJIB)
```bash
# Backup database sebelum migration
mysqldump -u root gaspul_lkh > backup_before_consolidation_$(date +%Y%m%d_%H%M%S).sql
```

#### 4.2 Generate Migration
```bash
php artisan make:migration consolidate_rhk_architecture
```

#### 4.3 Run Migration
```bash
# Jalankan migration
php artisan migrate

# Jika ada error, rollback:
php artisan migrate:rollback --step=1
```

#### 4.4 Clear Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

## ✅ CHECKLIST VALIDASI

### Pre-Implementation
- [x] Audit database structure (IndikatorKinerja, RhkPimpinan, SkpTahunanDetail)
- [x] Identifikasi duplikasi konsep (RhkPimpinan = layer tidak diperlukan)
- [x] Map semua controller yang terpengaruh
- [x] Map semua view yang terpengaruh
- [x] Buat migration plan

### Post-Implementation
- [ ] Migration berhasil tanpa error
- [ ] Relasi Model benar (SkpTahunanDetail → IndikatorKinerja)
- [ ] Admin bisa CRUD Indikator Kinerja dengan unit_kerja_id
- [ ] ASN bisa memilih IndikatorKinerja langsung di SKP Tahunan
- [ ] Dropdown Kinerja Harian menampilkan format: [Bulan] - [Indikator] - [Rencana Aksi]
- [ ] Atasan bisa monitoring dengan data konsisten
- [ ] Menu "RHK Pimpinan" hilang dari sidebar Admin
- [ ] Tidak ada error 500 di seluruh aplikasi
- [ ] Data flow konsisten: Admin → ASN → Atasan

---

## 📋 SUMMARY

### Perubahan Utama:
1. **Database:** Rename kolom `rhk_pimpinan_id` → `indikator_kinerja_id` di `skp_tahunan_detail`
2. **Database:** Tambah kolom `unit_kerja_id` di `indikator_kinerja`
3. **Model:** Hapus relasi `RhkPimpinan`, tambah relasi langsung `IndikatorKinerja`
4. **Controller:** Update 6 controllers (Admin, ASN, Atasan)
5. **View:** Update 5 blade files (Admin form, ASN SKP form, ASN Harian form)
6. **Routing:** Hapus route `admin.rhk-pimpinan.*`
7. **Menu:** Hapus menu "RHK Pimpinan" dari sidebar Admin

### Manfaat:
✅ **Single Source of Truth:** IndikatorKinerja = satu-satunya sumber RHK
✅ **Simplified Architecture:** Hapus 1 layer (RhkPimpinan)
✅ **Konsistensi Data:** Admin → ASN → Atasan dalam 1 jalur
✅ **Maintenance:** Lebih mudah maintain (1 model, bukan 2)
✅ **Performance:** Reduce 1 JOIN di setiap query SKP Tahunan

### Risiko:
⚠️ **Zero Risk:** Table `rhk_pimpinan` KOSONG (0 records), jadi tidak ada data loss
⚠️ **Zero Migration Risk:** Hanya rename kolom + tambah kolom nullable
⚠️ **Zero Business Risk:** ASN workflow tetap sama, hanya backend yang berubah

---

**READY TO IMPLEMENT:** Semua perubahan sudah dianalisis dan siap diterapkan langsung ke codebase.
