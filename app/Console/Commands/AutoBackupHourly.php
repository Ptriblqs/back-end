<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoBackupHourly extends Command
{
    protected $signature = 'db:auto-backup';
    protected $description = 'Backup database tiap 1 jam dan hapus backup lama yang lebih dari 2 hari';

    public function handle()
    {
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');
        $file = storage_path('app/backup_' . now()->format('Ymd_His') . '.sql');

        exec("mysqldump -u{$user} -p{$pass} {$db} > {$file}");
        $this->info('Backup database berhasil dibuat: ' . basename($file));

        

        // Hapus backup lama yang lebih dari 2 ahri
        $deleted = 0;
        foreach (glob(storage_path('app/backup_*.sql')) as $oldFile) {
            if (filemtime($oldFile) < now()->subDays(2)->getTimestamp()) {
                unlink($oldFile);
                $deleted++;
            }
        }
        $this->info("$deleted file backup lama dihapus.");
    }
}
