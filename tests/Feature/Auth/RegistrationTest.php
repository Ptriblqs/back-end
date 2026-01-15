<?php

namespace Tests\Feature\Auth;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $prodi = ProgramStudi::factory()->create();

        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'nama_lengkap' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'mahasiswa',
            'program_studis' => $prodi->id, // ⬅️ PENTING (lihat catatan bawah)
        ]);

        // Status & struktur response
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user' => ['id', 'role', 'username', 'email'],
                 ]);

        // User tersimpan (tanpa cek email plaintext)
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'role' => 'mahasiswa',
        ]);

        // Email dicek dari response (sudah decrypted)
        $this->assertEquals(
            'test@example.com',
            $response->json('user.email')
        );

        // Mahasiswa otomatis dibuat oleh sistem
        $this->assertDatabaseHas('mahasiswa', [
            'user_id' => $response->json('user.id'),
            'prodi_id' => $prodi->id,
        ]);
    }
}
