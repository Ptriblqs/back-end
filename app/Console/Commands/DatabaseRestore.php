<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DatabaseRestore extends Command
{
    protected $signature = 'backup:restore';

    protected $description = 'Restore database dari file backup terbaru atau berdasarkan nama file.';

    public function handle()
    {
        $backupPath = storage_path('app/backups');
        $latestFile = collect(glob($backupPath.'/*.sql'))
            ->sortByDesc(fn ($file) => filemtime($file))
            ->first();

        if (! $latestFile) {
            $this->error('Tidak ada file backup ditemukan.');

            return;
        }

        $this->info('File backup terbaru: '.basename($latestFile));

        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $database = env('DB_DATABASE');
        $passwordPart = $password ? sprintf('-p"%s"', $password) : '';

        $restoreCommand = sprintf(
            'mysql -u"%s" %s "%s" < "%s"',
            $username,
            $passwordPart,
            $database,
            $latestFile
        );

        exec('cmd /c '.escapeshellarg($restoreCommand), $output, $result);

        if ($result === 0) {
            $this->info('Restore database berhasil!');
        } else {
            $this->error('Restore database gagal.');
        }
    }
}
