<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(TaMilestone::class, 'ta_project_id')->orderBy('id');
    }

    public function supervisions(): HasMany
    {
        return $this->hasMany(TaSupervision::class, 'ta_project_id')->latest('meeting_date');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(TaReview::class, 'ta_project_id')->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TaDocument::class, 'ta_project_id')->latest();
    }
}
