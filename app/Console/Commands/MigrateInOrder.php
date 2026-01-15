<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateInOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate_in_order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute the migrations in the order specified in the file app/Console/Comands/MigrateInOrder.php \n Drop all the table in db before execute the command.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       /** Specify the names of the migrations files in the order you want to 
        * loaded
        * $migrations =[ 
        *               'xxxx_xx_xx_000000_create_nameTable_table.php',
        *    ];
        */
        $migrations = [ 
                        '0001_01_01_000000_create_users_table.php',
                        '0001_01_01_000001_create_cache_table.php',
                        '0001_01_01_000002_create_jobs_table.php',
                        '2025_10_16_200056_create_personal_access_tokens_table.php',
                        '2025_10_22_084722_create_jurusan.php',
                        '2025_10_22_082920_create_program_studi.php',
                        '2025_10_16_205551_create_mahasiswa_profile.php',
                        '2025_10_16_205600_create_dosen_profile.php',
                        '2025_10_22_082848_create_pengumuman.php',
                        '2025_10_22_082906_create_notifikasi.php',
                        '2025_10_22_083005_create_bimbingan.php',
                        '2025_10_22_083020_create_tugas_akhir.php',
                        '2025_10_22_083100_create_bab_dokumen.php',
                        '2025_10_22_083047_create_dokumen.php'
        ];

        foreach($migrations as $migration)
        {
           $basePath = 'database/migrations/';          
           $migrationName = trim($migration);
           $path = $basePath.$migrationName;
           $this->call('migrate:refresh', [
            '--path' => $path ,            
           ]);
        }
    }
} 