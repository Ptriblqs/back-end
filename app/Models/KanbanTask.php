<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon; // ✅ import Carbon

class KanbanTask extends Model
{
    use SoftDeletes;

    protected $table = 'kanban_tasks';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'due_date',
        'is_expired',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'is_expired' => 'boolean',
    ];

    // =========================
    // Relasi task dengan user
    // =========================
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
