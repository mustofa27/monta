<?php

return [
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
