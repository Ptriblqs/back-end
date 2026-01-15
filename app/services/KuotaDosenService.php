<?php

namespace App\Services;

class KuotaDosenService
{
    public function kuotaTersedia(int $terpakai, int $maksimal): bool
    {
        return $terpakai < $maksimal;
    }

    public function sisaKuota(int $terpakai, int $maksimal): int
    {
        return max(0, $maksimal - $terpakai);
    }
}
