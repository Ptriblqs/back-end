<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeder program studi harus dijalankan dulu,
        // supaya UserSeeder bisa pakai prodi_id
        $this->call([
            ProgramStudiSeeder::class,
            UserSeeder::class,
        ]);
    }
}
