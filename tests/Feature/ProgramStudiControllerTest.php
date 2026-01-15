<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgramStudiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // User admin untuk akses CRUD program studi
        $this->user = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function it_can_get_all_program_studi_200()
    {
        ProgramStudi::factory()->count(2)->create();

        $response = $this->actingAs($this->user,'sanctum')->getJson('/api/program-studi');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'nama_prodi',
                         'created_at',
                         'updated_at'
                     ]
                 ]);
    }

    /** @test */
    public function it_can_get_program_studi_by_id_200()
    {
        $prodi = ProgramStudi::factory()->create();

        $response = $this->actingAs($this->user,'sanctum')->getJson("/api/program-studi/{$prodi->id}");
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'nama_prodi',
                     'created_at',
                     'updated_at'
                 ])
                 ->assertJson([
                     'id' => $prodi->id,
                     'nama_prodi' => $prodi->nama_prodi
                 ]);
    }

    /** @test */
    public function it_can_create_program_studi_201()
    {
        $data = ['nama_prodi' => 'Teknik Informatika'];

        $response = $this->actingAs($this->user,'sanctum')->postJson('/api/program-studi', $data);
        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'nama_prodi',
                     'created_at',
                     'updated_at'
                 ])
                 ->assertJson([
                     'nama_prodi' => 'Teknik Informatika'
                 ]);

        $this->assertDatabaseHas('program_studis', $data);
    }

    /** @test */
    public function it_returns_400_validation_error_on_create()
    {
        $data = ['nama_prodi' => '']; // kosong
        $response = $this->actingAs($this->user,'sanctum')->postJson('/api/program-studi', $data);
        $response->assertStatus(400)
                 ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function it_can_update_program_studi_200()
    {
        $prodi = ProgramStudi::factory()->create(['nama_prodi' => 'Sistem Informasi']);

        $data = ['nama_prodi' => 'Teknik Elektro'];

        $response = $this->actingAs($this->user,'sanctum')->putJson("/api/program-studi/{$prodi->id}", $data);
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id',
                     'nama_prodi',
                     'created_at',
                     'updated_at'
                 ])
                 ->assertJson([
                     'id' => $prodi->id,
                     'nama_prodi' => 'Teknik Elektro'
                 ]);

        $this->assertDatabaseHas('program_studis', [
            'id' => $prodi->id,
            'nama_prodi' => 'Teknik Elektro'
        ]);
    }

    /** @test */
    public function it_returns_404_on_update_nonexistent()
    {
        $data = ['nama_prodi' => 'Teknik Mesin'];
        $response = $this->actingAs($this->user,'sanctum')->putJson("/api/program-studi/999", $data);
        $response->assertStatus(404)
                 ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_can_delete_program_studi_200()
    {
        $prodi = ProgramStudi::factory()->create();

        $response = $this->actingAs($this->user,'sanctum')->deleteJson("/api/program-studi/{$prodi->id}");
        $response->assertStatus(200)
                 ->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('program_studis', ['id' => $prodi->id]);
    }

    /** @test */
    public function it_returns_404_on_delete_nonexistent()
    {
        $response = $this->actingAs($this->user,'sanctum')->deleteJson("/api/program-studi/999");
        $response->assertStatus(404)
                 ->assertJsonStructure(['message']);
    }

    /** @test */
    public function it_returns_401_unauthorized()
    {
        $response = $this->getJson('/api/program-studi');
        $response->assertStatus(401)
                 ->assertJsonStructure(['message']);
    }
}
