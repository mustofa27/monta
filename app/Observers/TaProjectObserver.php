<?php

namespace App\Observers;

use App\Models\TaProject;
use App\Support\AuditLogger;

class TaProjectObserver
{
    public function updated(TaProject $project): void
    {
        if (! $project->wasChanged('status')) {
            return;
        }

        AuditLogger::logModelEvent(
            $project,
            'ta_project.status_changed',
            ['status' => $project->getOriginal('status')],
            ['status' => $project->status]
        );
    }
}
