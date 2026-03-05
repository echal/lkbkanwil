<?php

namespace App\Console\Commands;

use App\Models\UnitKerja;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditUnitKerjaHierarchy extends Command
{
    protected $signature = 'audit:unit-kerja
                            {--fix : Otomatis perbaiki level yang tidak sinkron (dry-run jika tidak disebutkan)}';

    protected $description = 'Audit integritas hierarki unit kerja (self-parent, broken parent, circular, level mismatch, orphan root)';

    private int $errorCount = 0;

    public function handle(): int
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════╗');
        $this->line('║         AUDIT HIERARKI UNIT KERJA — ESARAKU          ║');
        $this->line('╚══════════════════════════════════════════════════════╝');
        $this->newLine();

        $all = UnitKerja::all();
        $this->info("Total unit kerja   : {$all->count()}");
        $this->newLine();

        $this->checkSelfParent($all);
        $this->checkBrokenParent($all);
        $this->checkCircularReference($all);
        $this->checkLevelMismatch($all);
        $this->checkOrphanRoot($all);

        $this->newLine();
        $this->line('──────────────────────────────────────────────────────');
        if ($this->errorCount === 0) {
            $this->info('✔  Tidak ada masalah hierarki ditemukan.');
        } else {
            $this->warn("⚠  Total masalah ditemukan : {$this->errorCount}");
            if (! $this->option('fix')) {
                $this->line('   Jalankan dengan --fix untuk memperbaiki level yang tidak sinkron.');
            }
        }
        $this->newLine();

        return $this->errorCount === 0 ? 0 : 1;
    }

    // =========================================================================
    // CEK 1: Self Parent (parent_id == id)
    // =========================================================================

    private function checkSelfParent(Collection $all): void
    {
        $this->line('Cek 1 — Self Parent (parent_id == id)');

        $errors = $all->filter(fn($u) => $u->parent_id !== null && (int) $u->parent_id === (int) $u->id);

        if ($errors->isEmpty()) {
            $this->line('       <fg=green>OK — tidak ada</>');
        } else {
            foreach ($errors as $u) {
                $this->error("  [ID {$u->id}] {$u->nama_unit} — parent_id menunjuk ke dirinya sendiri");
                $this->errorCount++;
            }
        }
        $this->newLine();
    }

    // =========================================================================
    // CEK 2: Broken Parent (parent_id not null tapi ID tidak ada di tabel)
    // =========================================================================

    private function checkBrokenParent(Collection $all): void
    {
        $this->line('Cek 2 — Broken Parent (parent_id tidak ada di tabel)');

        $ids    = $all->pluck('id')->toArray();
        $errors = $all->filter(fn($u) => $u->parent_id !== null && ! in_array((int) $u->parent_id, $ids));

        if ($errors->isEmpty()) {
            $this->line('       <fg=green>OK — tidak ada</>');
        } else {
            foreach ($errors as $u) {
                $this->error("  [ID {$u->id}] {$u->nama_unit} — parent_id={$u->parent_id} tidak ditemukan");
                $this->errorCount++;
            }
        }
        $this->newLine();
    }

    // =========================================================================
    // CEK 3: Circular Reference
    // =========================================================================

    private function checkCircularReference(Collection $all): void
    {
        $this->line('Cek 3 — Circular Reference');

        $parentMap = $all->pluck('parent_id', 'id'); // id => parent_id
        $circleIds = [];

        foreach ($all as $unit) {
            if ($unit->parent_id === null) {
                continue;
            }

            $visited = [$unit->id];
            $current = (int) $unit->parent_id;

            while ($current !== 0) {
                if (in_array($current, $visited)) {
                    $circleIds[] = $unit->id;
                    break;
                }
                $visited[] = $current;
                $current   = isset($parentMap[$current]) ? (int) $parentMap[$current] : 0;
            }
        }

        $circleIds = array_unique($circleIds);

        if (empty($circleIds)) {
            $this->line('       <fg=green>OK — tidak ada</>');
        } else {
            foreach ($circleIds as $id) {
                $u = $all->firstWhere('id', $id);
                $this->error("  [ID {$id}] {$u->nama_unit} — terdeteksi dalam circular reference");
                $this->errorCount++;
            }
        }
        $this->newLine();
    }

    // =========================================================================
    // CEK 4: Level Tidak Sinkron
    // =========================================================================

    private function checkLevelMismatch(Collection $all): void
    {
        $this->line('Cek 4 — Level Tidak Sinkron');

        $fixMode    = $this->option('fix');
        $levelMap   = $all->pluck('level', 'id'); // id => level
        $parentMap  = $all->pluck('parent_id', 'id'); // id => parent_id
        $errors     = [];

        foreach ($all as $unit) {
            $expected = $this->calculateExpectedLevel($unit->id, $parentMap, $levelMap);
            if ($expected !== null && (int) $unit->level !== $expected) {
                $errors[] = ['unit' => $unit, 'expected' => $expected];
            }
        }

        if (empty($errors)) {
            $this->line('       <fg=green>OK — tidak ada</>');
        } else {
            foreach ($errors as $err) {
                $u = $err['unit'];
                $this->warn("  [ID {$u->id}] {$u->nama_unit} — level={$u->level}, seharusnya={$err['expected']}");
                $this->errorCount++;

                if ($fixMode) {
                    $u->update(['level' => $err['expected']]);
                    $this->line("       <fg=green>→ Diperbaiki: level={$err['expected']}</>");
                }
            }

            if (! $fixMode && count($errors) > 0) {
                $this->line('       Jalankan dengan --fix untuk memperbaiki.');
            }
        }
        $this->newLine();
    }

    /**
     * Hitung level yang seharusnya berdasarkan rantai parent.
     * Return null jika ada circular atau broken parent (sudah ditangani cek sebelumnya).
     */
    private function calculateExpectedLevel(int $id, $parentMap, $levelMap): ?int
    {
        $depth    = 0;
        $current  = $id;
        $visited  = [];

        while (true) {
            if (in_array($current, $visited)) {
                return null; // circular — skip
            }
            $visited[] = $current;

            $parentId = isset($parentMap[$current]) ? (int) $parentMap[$current] : null;
            if ($parentId === 0 || $parentId === null) {
                break;
            }
            if (! isset($levelMap[$parentId])) {
                return null; // broken parent — skip
            }
            $depth++;
            $current = $parentId;
        }

        return $depth + 1;
    }

    // =========================================================================
    // CEK 5: Orphan Root Level (parent_id null tapi level != 1)
    // =========================================================================

    private function checkOrphanRoot(Collection $all): void
    {
        $this->line('Cek 5 — Orphan Root Level (parent_id NULL tapi level != 1)');

        $errors = $all->filter(fn($u) => $u->parent_id === null && (int) $u->level !== 1);

        if ($errors->isEmpty()) {
            $this->line('       <fg=green>OK — tidak ada</>');
        } else {
            foreach ($errors as $u) {
                $this->warn("  [ID {$u->id}] {$u->nama_unit} — parent_id=NULL tapi level={$u->level}");
                $this->errorCount++;

                if ($this->option('fix')) {
                    $u->update(['level' => 1]);
                    $this->line("       <fg=green>→ Diperbaiki: level=1</>");
                }
            }
        }
        $this->newLine();
    }
}
