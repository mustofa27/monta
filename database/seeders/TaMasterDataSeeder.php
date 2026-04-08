<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaMasterDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $semesterCode = '2026-GENAP';

        $templates = [
            ['code' => 'TOPIC_SUBMISSION', 'name' => 'Pengajuan Topik', 'weight' => 15, 'order_no' => 1],
            ['code' => 'SUPERVISION_LOG', 'name' => 'Bimbingan Rutin', 'weight' => 30, 'order_no' => 2],
            ['code' => 'SEMINAR_PROPOSAL', 'name' => 'Seminar Proposal', 'weight' => 25, 'order_no' => 3],
            ['code' => 'FINAL_DEFENSE', 'name' => 'Sidang Akhir', 'weight' => 30, 'order_no' => 4],
        ];

        foreach ($templates as $template) {
            DB::table('ta_milestone_templates')->updateOrInsert(
                ['semester_code' => $semesterCode, 'code' => $template['code']],
                [
                    'name' => $template['name'],
                    'weight' => $template['weight'],
                    'order_no' => $template['order_no'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $catalogs = [
            'ta_project.status' => [
                ['code' => 'draft', 'label' => 'Draft'],
                ['code' => 'submitted', 'label' => 'Submitted'],
                ['code' => 'under_review', 'label' => 'Under Review'],
                ['code' => 'approved', 'label' => 'Approved'],
                ['code' => 'rejected', 'label' => 'Rejected'],
                ['code' => 'revision_required', 'label' => 'Revision Required'],
                ['code' => 'completed', 'label' => 'Completed'],
            ],
            'ta_milestone.status' => [
                ['code' => 'not_started', 'label' => 'Not Started'],
                ['code' => 'in_progress', 'label' => 'In Progress'],
                ['code' => 'submitted', 'label' => 'Submitted'],
                ['code' => 'approved', 'label' => 'Approved'],
                ['code' => 'rejected', 'label' => 'Rejected'],
                ['code' => 'revision_required', 'label' => 'Revision Required'],
            ],
        ];

        foreach ($catalogs as $domain => $items) {
            foreach ($items as $idx => $item) {
                DB::table('ta_status_catalogs')->updateOrInsert(
                    ['domain' => $domain, 'code' => $item['code']],
                    [
                        'label' => $item['label'],
                        'order_no' => $idx + 1,
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
