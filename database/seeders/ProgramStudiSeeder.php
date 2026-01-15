<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProgramStudi;

class ProgramStudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prodis = [
            'Teknik Informatika',
            'Teknologi Geomatika',
            'Teknologi Rekayasa Multimedia',
            'Animasi',
            'Rekayasa Keamanan Siber',
            'Teknologi Rekayasa Perangkat Lunak',
            'Teknologi Permainan',
        ];

        foreach ($prodis as $prodi) {
            ProgramStudi::create([
                'nama_prodi' => $prodi,
            ]);
        }
    }
}
