<?php

/**
 * Feature and Module Access Control Configuration
 *
 * Define all manageable features/modules for role-based access control.
 * Superadmin can enable/disable features per role or per user.
 */

return [
    'features' => [
        // User Management
        [
            'key' => 'admin.users',
            'name' => 'Kelola User',
            'description' => 'Manage user accounts, roles, and access',
            'category' => 'User Management',
            'default_roles' => ['admin', 'useradmin'], // roles that have access by default
        ],
        [
            'key' => 'admin.users.allowlist',
            'name' => 'Google Allowlist',
            'description' => 'Manage Google email allowlist',
            'category' => 'User Management',
            'default_roles' => ['admin'], // superadmin only in controller, but defined here
        ],

        // CCTV Management
        [
            'key' => 'admin.cctv',
            'name' => 'CCTV Camera',
            'description' => 'Manage CCTV camera configurations',
            'category' => 'Infrastructure',
            'default_roles' => ['admin'],
        ],

        // Pendopo Management
        [
            'key' => 'admin.pendopo',
            'name' => 'Pendopo Admin',
            'description' => 'Manage pendopo integration and settings',
            'category' => 'Integration',
            'default_roles' => ['admin'],
        ],

        // WA Caraka Management
        [
            'key' => 'admin.wacaraka',
            'name' => 'WA Caraka',
            'description' => 'Manage WhatsApp integration (Caraka)',
            'category' => 'Integration',
            'default_roles' => ['admin'],
        ],

        // System Monitoring
        [
            'key' => 'admin.system-monitor',
            'name' => 'System Monitor',
            'description' => 'View system health and performance metrics',
            'category' => 'Monitoring',
            'default_roles' => ['admin'],
        ],

        // Dashboard Access
        [
            'key' => 'dashboard.main',
            'name' => 'Dashboard',
            'description' => 'Access main portal dashboard',
            'category' => 'Dashboard',
            'default_roles' => ['viewer', 'operator', 'useradmin', 'admin'],
        ],

        // Guestbook/Buku Tamu
        [
            'key' => 'guestbook.report',
            'name' => 'Guestbook Report',
            'description' => 'View guestbook entries and reports',
            'category' => 'Reporting',
            'default_roles' => ['viewer', 'operator', 'useradmin', 'admin'],
        ],
    ],

    'categories' => [
        'User Management' => 'Manajemen pengguna dan akses',
        'Infrastructure' => 'Infrastruktur dan perangkat',
        'Integration' => 'Integrasi eksternal',
        'Monitoring' => 'Pemantauan sistem',
        'Dashboard' => 'Dashboard dan portal',
        'Reporting' => 'Laporan dan analitik',
    ],
];
