<?php

namespace App\Services;

class ProgressTAService
{
    public function hitungProgress(int $selesai, int $total): int
    {
        if ($total === 0) {
            return 0;
        }

        return (int) round(($selesai / $total) * 100);
    }
}
