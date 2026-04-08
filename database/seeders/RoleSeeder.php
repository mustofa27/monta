<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'mahasiswa',
                'name' => 'Mahasiswa',
                'description' => 'Akses fitur mahasiswa tugas akhir.',
            ],
            [
                'slug' => 'dosen_pembimbing',
                'name' => 'Dosen Pembimbing',
                'description' => 'Akses bimbingan dan persetujuan progress mahasiswa.',
            ],
            [
                'slug' => 'koordinator_ta',
                'name' => 'Koordinator TA',
                'description' => 'Akses monitoring lintas prodi dan penjadwalan.',
            ],
            [
                'slug' => 'admin_prodi',
                'name' => 'Admin Prodi',
                'description' => 'Akses administratif seluruh modul TA.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
