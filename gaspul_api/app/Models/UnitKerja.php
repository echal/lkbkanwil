<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja';

    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'eselon',
        'parent_id',
        'level',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // RELASI
    // =========================================================================

    /** Unit Induk (self-referencing) */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'parent_id');
    }

    /** Sub-unit langsung */
    public function children(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'parent_id')->orderBy('nama_unit');
    }

    /** Semua keturunan (recursive eager load) */
    public function allChildren(): HasMany
    {
        return $this->hasMany(UnitKerja::class, 'parent_id')
                    ->with('allChildren')
                    ->orderBy('nama_unit');
    }

    /** Pegawai di unit ini */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_kerja_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }

    // =========================================================================
    // HELPERS — DROPDOWN BERTINGKAT
    // =========================================================================

    /**
     * Flatten seluruh tree unit kerja menjadi Collection flat dengan indent prefix.
     * Digunakan untuk dropdown select di form unit & pegawai.
     *
     * @param  int|null  $excludeId  ID unit yang dikecualikan (untuk form edit: diri sendiri)
     * @return Collection<int, array{id: int, label: string}>
     */
    public static function toDropdownFlat(?int $excludeId = null): Collection
    {
        $roots = static::with('allChildren')
            ->whereNull('parent_id')
            ->aktif()
            ->orderBy('nama_unit')
            ->get();

        $flat = collect();
        static::flattenForDropdown($roots, $flat, 0, $excludeId);
        return $flat;
    }

    /**
     * Rekursif flatten node tree ke array flat.
     */
    private static function flattenForDropdown($nodes, Collection &$flat, int $depth, ?int $excludeId): void
    {
        foreach ($nodes as $node) {
            if ($node->id === $excludeId) {
                continue;
            }
            $prefix = str_repeat('— ', $depth);
            $flat->push(['id' => $node->id, 'label' => $prefix . $node->nama_unit]);
            if ($node->children->isNotEmpty()) {
                static::flattenForDropdown($node->children, $flat, $depth + 1, $excludeId);
            }
        }
    }
}
