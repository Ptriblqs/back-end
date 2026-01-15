<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DosenFactory extends Factory
{
    protected $model = Dosen::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'nik' => $this->faker->unique()->numerify('##########'),
            'prodi_id' => ProgramStudi::factory(),
            'bidang_keahlian' => $this->faker->word(),
            'no_telepon' => $this->faker->phoneNumber(),
        ];
    }
}
