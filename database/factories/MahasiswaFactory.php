<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'nim' => $this->faker->unique()->numerify('########'),
            'prodi_id' => ProgramStudi::factory(),
            'portofolio' => $this->faker->url(),
        ];
    }
}
