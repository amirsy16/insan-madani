<?php

return [
    'models' => [
        'permission' => [
            'name' => 'Izin',
            'names' => 'Izin',
            'fields' => [
                'name' => 'Nama',
                'guard_name' => 'Nama Guard',
                'permissions' => 'Izin',
                'roles' => 'Peran',
            ],
        ],

        'role' => [
            'name' => 'Peran',
            'names' => 'Peran',
            'fields' => [
                'name' => 'Nama',
                'guard_name' => 'Nama Guard',
                'permissions' => 'Izin',
                'roles' => 'Peran',
            ],
        ],
    ],

    'exceptions' => [
        'permission_already_exists' => 'Izin :permission_name sudah ada untuk guard :guard_name.',
        'permission_does_not_exist' => 'Izin :permission_name tidak ada untuk guard :guard_name.',
        'role_already_exists' => 'Peran :role_name sudah ada untuk guard :guard_name.',
        'role_does_not_exist' => 'Peran :role_name tidak ada untuk guard :guard_name.',
        'guard_does_not_exist' => 'Guard :guard tidak ada.',
        'role_or_permission_should_use_default_guard' => 'Peran atau izin harus menggunakan default guard `web` daripada `null`.',
        'permission_already_assigned' => 'Izin :permission_name sudah diberikan kepada peran :role_name.',
        'user_already_has_permission' => 'Pengguna sudah memiliki izin :permission_name.',
        'user_does_not_have_permission' => 'Pengguna tidak memiliki izin :permission_name.',
        'user_already_has_role' => 'Pengguna sudah memiliki peran :role_name.',
        'user_does_not_have_role' => 'Pengguna tidak memiliki peran :role_name.',
        'role_already_has_permission' => 'Peran :role_name sudah memiliki izin :permission_name.',
        'role_does_not_have_permission' => 'Peran :role_name tidak memiliki izin :permission_name.',
    ],
];
