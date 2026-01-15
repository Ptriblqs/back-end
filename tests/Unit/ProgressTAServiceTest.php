<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProgressTAService;

class ProgressTAServiceTest extends TestCase
{
    public function test_hitung_progress_normal()
    {
        $service = new ProgressTAService();
        $this->assertEquals(50, $service->hitungProgress(5, 10));
    }

    public function test_progress_nol_jika_total_nol()
    {
        $service = new ProgressTAService();
        $this->assertEquals(0, $service->hitungProgress(0, 0));
    }
}
