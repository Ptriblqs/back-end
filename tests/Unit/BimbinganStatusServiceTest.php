<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\BimbinganStatusService;

class BimbinganStatusServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BimbinganStatusService();
    }

    public function testValidStatus()
    {
        $this->assertTrue($this->service->validStatus('menunggu'));
        $this->assertFalse($this->service->validStatus('selesai'));
    }

    public function testBolehDirevisi()
    {
        $this->assertTrue($this->service->bolehDirevisi('ditolak'));
        $this->assertFalse($this->service->bolehDirevisi('disetujui'));
    }
}
