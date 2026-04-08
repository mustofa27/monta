<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'ta_project_id',
        'reviewer_user_id',
        'ta_document_id',
        'decision',
        'note',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaProject::class, 'ta_project_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
