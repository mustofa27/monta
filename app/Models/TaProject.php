<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_user_id',
        'supervisor_user_id',
        'title',
        'abstract',
        'study_program',
        'semester_code',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }
}
