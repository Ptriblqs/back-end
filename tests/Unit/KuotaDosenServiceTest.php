<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\KuotaDosenService;

class KuotaDosenServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KuotaDosenService();
    }

    public function testKuotaTersedia()
    {
        $this->assertTrue($this->service->kuotaTersedia(3, 5));
        $this->assertFalse($this->service->kuotaTersedia(5, 5));
    }

    public function testSisaKuota()
    {
        $this->assertEquals(2, $this->service->sisaKuota(3, 5));
        $this->assertEquals(0, $this->service->sisaKuota(6, 5));
    }
}
