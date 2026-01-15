<?php

namespace App\Services;

class DokumenTAService
{
    public function formatValid(string $filename): bool
    {
        return str_ends_with($filename, '.pdf');
    }

    public function ukuranValid(int $sizeKB): bool
    {
        return $sizeKB <= 2048;
    }
}
