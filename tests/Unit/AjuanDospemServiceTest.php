<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AjuanDospemService;

class AjuanDospemServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AjuanDospemService();
    }

    /**
     * Test statusBolehAjukan
     */
    public function testStatusBolehAjukan()
    {
        // Status 'pending' boleh ajukan
        $this->assertTrue($this->service->statusBolehAjukan('pending'));

        // Status lain tidak boleh ajukan
        $this->assertFalse($this->service->statusBolehAjukan('disetujui'));
        $this->assertFalse($this->service->statusBolehAjukan('ditolak'));
        $this->assertFalse($this->service->statusBolehAjukan('selesai'));
    }
}
