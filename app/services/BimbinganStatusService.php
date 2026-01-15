<?php

namespace App\Services;

class BimbinganStatusService
{
    public function validStatus(string $status): bool
    {
        return in_array($status, ['menunggu', 'disetujui', 'ditolak']);
    }

    public function bolehDirevisi(string $status): bool
    {
        return $status === 'ditolak';
    }
}
