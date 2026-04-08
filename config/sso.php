<?php

return [
    'sso_role_mapping' => [
        'super admin' => ['admin_prodi'],
        'super-admin' => ['admin_prodi'],
        'super_admin' => ['admin_prodi'],
        'administrator' => ['admin_prodi'],
    ],

    'role_mapping' => [
        'employee:lecturer' => ['dosen_pembimbing'],
        'employee:staff' => ['koordinator_ta'],
        'employee:*' => ['dosen_pembimbing'],
        'student:*' => ['mahasiswa'],
    ],

    'fallback_roles' => [
        'mahasiswa',
    ],
];
