<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SnapshotDatabase extends Command
{
    protected $signature = 'snapshot:db
                            {--tag= : Tag tambahan untuk nama file (contoh: before-migration)}';

    protected $description = 'Backup database MySQL ke storage/app/snapshots/ menggunakan mysqldump';

    public function handle(): int
    {
        $db   = config('database.connections.mysql');
        $host = $db['host']     ?? '127.0.0.1';
        $port = $db['port']     ?? '3306';
        $user = $db['username'] ?? 'root';
        $pass = $db['password'] ?? '';
        $name = $db['database'] ?? '';

        if (empty($name)) {
            $this->error('Nama database tidak ditemukan di konfigurasi database.mysql.');
            return 1;
        }

        // Tentukan nama file
        $timestamp = now()->format('Ymd_Hi');
        $tag       = $this->option('tag') ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $this->option('tag')) : '';
        $filename  = "backup_esaraku_{$timestamp}{$tag}.sql";

        // Pastikan direktori tujuan ada
        $dir = storage_path('app/snapshots');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $this->info("Database  : {$name}");
        $this->info("Host      : {$host}:{$port}");
        $this->info("Output    : storage/app/snapshots/{$filename}");
        $this->newLine();
        $this->line('Menjalankan mysqldump...');

        // Bangun command — password via argumen agar tidak tampil di process list
        // Gunakan --no-tablespaces untuk hindari ERROR 1044 di shared hosting
        if ($pass !== '') {
            $cmd = sprintf(
                'mysqldump --no-tablespaces -h%s -P%s -u%s -p%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($name),
                escapeshellarg($filepath)
            );
        } else {
            $cmd = sprintf(
                'mysqldump --no-tablespaces -h%s -P%s -u%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg((string) $port),
                escapeshellarg($user),
                escapeshellarg($name),
                escapeshellarg($filepath)
            );
        }

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! File::exists($filepath) || File::size($filepath) === 0) {
            $this->error('mysqldump gagal atau file output kosong.');
            if (! empty($output)) {
                $this->line(implode("\n", $output));
            }
            // Hapus file kosong jika terbuat
            if (File::exists($filepath) && File::size($filepath) === 0) {
                File::delete($filepath);
            }
            return 1;
        }

        $sizeKb = round(File::size($filepath) / 1024, 1);
        $this->newLine();
        $this->info("✔  Snapshot berhasil disimpan!");
        $this->line("   File     : storage/app/snapshots/{$filename}");
        $this->line("   Ukuran   : {$sizeKb} KB");
        $this->newLine();

        return 0;
    }
}
