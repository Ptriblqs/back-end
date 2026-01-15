<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Mahasiswa;
use App\Models\User;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MahasiswaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $prodi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'mahasiswa']);
        $this->prodi = ProgramStudi::factory()->create();
    }

    /** @test */
    public function it_can_get_all_mahasiswa_200()
    {
        Mahasiswa::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'prodi_id' => $this->prodi->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/mahasiswa');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => ['id','user_id','nim','prodi_id','portofolio','created_at','updated_at']
                 ]);
    }

    /** @test */
    public function it_can_get_mahasiswa_by_id_200()
    {
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $this->user->id,
            'prodi_id' => $this->prodi->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson("/api/mahasiswa/{$mahasiswa->id}");
        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $mahasiswa->id,
                     'user_id' => $this->user->id,
                     'prodi_id' => $this->prodi->id,
                     'nim' => $mahasiswa->nim,
                     'portofolio' => $mahasiswa->portofolio
                 ]);
    }

    /** @test */
    public function it_can_create_mahasiswa_201()
    {
        $data = [
            'user_id' => $this->user->id,
            'nim' => '12345678',
            'prodi_id' => $this->prodi->id,
            'portofolio' => 'link_portofolio'
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/mahasiswa', $data);
        $response->assertStatus(201);

        $mahasiswa = Mahasiswa::latest()->first();

        $this->assertEquals($data['nim'], $mahasiswa->nim);
        $this->assertEquals($data['portofolio'], $mahasiswa->portofolio);
        $this->assertEquals($data['prodi_id'], $mahasiswa->prodi_id);
    }


    public function it_can_update_mahasiswa_200()
    {
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $this->user->id,
            'prodi_id' => $this->prodi->id
        ]);

        $data = ['portofolio' => 'portofolio_baru'];

        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/mahasiswa/{$mahasiswa->id}", $data);
        $response->assertStatus(200);

        $mahasiswa->refresh();
        $this->assertEquals('portofolio_baru', $mahasiswa->portofolio);
    }
 
    public function it_can_delete_mahasiswa_200()
    {
        $mahasiswa = Mahasiswa::factory()->create([
            'user_id' => $this->user->id,
            'prodi_id' => $this->prodi->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->deleteJson("/api/mahasiswa/{$mahasiswa->id}");
        $response->assertStatus(200);

        $this->assertDatabaseMissing('mahasiswa', [
            'id' => $mahasiswa->id
        ]);
    }

    public function it_returns_400_validation_error()
    {
        $data = ['nim' => ''];
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/mahasiswa', $data);
        $response->assertStatus(400);
    }
 
    public function it_returns_404_not_found()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/mahasiswa/999');
        $response->assertStatus(404);
    }

    public function it_returns_401_unauthorized()
    {
        $response = $this->getJson('/api/mahasiswa');
        $response->assertStatus(401);
    }
}
