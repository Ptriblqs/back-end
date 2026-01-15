<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\DokumenTAService;

class DokumenTAServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DokumenTAService();
    }

    public function testFormatValid()
    {
        $this->assertTrue($this->service->formatValid('laporan.pdf'));
        $this->assertFalse($this->service->formatValid('laporan.docx'));
    }

    public function testUkuranValid()
    {
        $this->assertTrue($this->service->ukuranValid(1024));
        $this->assertFalse($this->service->ukuranValid(3000));
    }
}
