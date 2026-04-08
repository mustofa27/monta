<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaSupervision extends Model
{
    use HasFactory;

    protected $fillable = [
        'ta_project_id',
        'student_user_id',
        'supervisor_user_id',
        'meeting_date',
        'summary',
        'ta_document_id',
        'status',
        'supervisor_note',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaProject::class, 'ta_project_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(TaDocument::class, 'ta_document_id');
    }
}
