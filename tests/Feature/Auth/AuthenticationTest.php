<?php

namespace Tests\Feature\Auth;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_using_login_screen(): void
    {
        $prodi = ProgramStudi::factory()->create();

        $user = User::factory()->create([
            'username' => '12345678',
            'role' => 'mahasiswa',
            'password' => bcrypt('password'),
        ]);

        // WAJIB ada data mahasiswa
        Mahasiswa::factory()->create([
            'user_id' => $user->id,
            'nim' => $user->username,
            'prodi_id' => $prodi->id,
        ]);

        $response = $this->postJson('/api/login', [
            'role' => 'mahasiswa',
            'username' => '12345678',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user' => [
                         'id',
                         'username',
                         'role',
                         'mahasiswa_id',
                         'prodi_id',
                     ],
                 ]);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'username' => '12345678',
            'role' => 'mahasiswa',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'role' => 'mahasiswa',
            'username' => '12345678',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
                         ->postJson('/api/logout');

        $response->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
