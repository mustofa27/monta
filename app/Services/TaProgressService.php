<?php

namespace App\Services;

use App\Models\TaProject;

class TaProgressService
{
    public function calculate(TaProject $project): int
    {
        $milestones = $project->milestones;

        if ($milestones->isEmpty()) {
            return 0;
        }

        $totalWeight = max(1, (int) $milestones->sum('weight'));

        $weightedProgress = $milestones->sum(function ($milestone): float {
            return $milestone->weight * $this->statusFactor((string) $milestone->status);
        });

        return (int) round(($weightedProgress / $totalWeight) * 100);
    }

    private function statusFactor(string $status): float
    {
        return match ($status) {
            'approved' => 1.0,
            'submitted', 'in_progress', 'completed' => 0.5,
            default => 0.0,
        };
    }
}
