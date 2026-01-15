<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Backup database Laravel';

    public function handle()
    {

        $mysqldumpPath = env('MYSQLDUMP_PATH');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $database = env('DB_DATABASE');

        Storage::makeDirectory('backups');

        // Hapus backup lama yang lebih dari 2 hari
        $files = Storage::files('backups');
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            if (Carbon::createFromTimestamp($lastModified)->lt(Carbon::now()->subDays(2))) {
                Storage::delete($file);
            }
        }
        

        $filename = 'backup-'.Carbon::now()->format('Y-m-d_H-i-s').'.sql';
        $outPath = str_replace('\\', '/', storage_path('app/backups/'.$filename));
        $mysqldumpPath = str_replace('\\', '/', $mysqldumpPath);
        $passwordPart = $password ? sprintf('-p"%s"', $password) : '';

        $inner = sprintf(
            '"%s" -u"%s" %s "%s" > "%s"',
            $mysqldumpPath,
            $username,
            $passwordPart,
            $database,
            $outPath
        );

        $command = 'cmd /c '.escapeshellarg($inner);
        exec($command.' 2>&1', $output, $resultCode);

        if ($resultCode === 0) {
            $this->info("Backup berhasil dibuat: $filename");
        } else {
            $this->error('Backup gagal:');
            foreach ($output as $line) {
                $this->error("  $line");
            }
        }
    }
}
