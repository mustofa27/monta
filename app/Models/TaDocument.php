<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TaDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'ta_project_id',
        'uploaded_by_user_id',
        'document_type',
        'original_name',
        'stored_path',
        'size_bytes',
        'status',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaProject::class, 'ta_project_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function supervision(): HasOne
    {
        return $this->hasOne(TaSupervision::class, 'ta_document_id');
    }
}
