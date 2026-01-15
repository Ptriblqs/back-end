<?php

namespace App\Models;

use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jurusan extends Model
{
    use HasFactory;

    protected $fillable = ['nama_jurusan'];

    public function dosen(): HasMany
    {
        return $this->hasMany(Dosen::class);
    }

    public function programStudis()
    {
        return $this->hasMany(ProgramStudi::class);
    }
}
