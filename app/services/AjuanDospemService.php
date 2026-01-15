<?php

namespace App\Services;

class AjuanDospemService
{
    public function statusBolehAjukan(string $status): bool
    {
        return $status === 'pending';
    }
}
