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
        [
            'key' => 'admin.network-monitor',
            'name' => 'Network Monitor',
            'description' => 'View network interfaces, bandwidth, connections, and CCTV stream health',
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

        // ── Navigasi (dikelola superadmin per-role) ──────────────────────────
        [
            'key' => 'nav.dashboard',
            'name' => 'Menu: Dashboard Utama',
            'description' => 'Tampilkan menu Dashboard Utama di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['viewer', 'operator', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.chat',
            'name' => 'Menu: Chat Internal',
            'description' => 'Tampilkan menu Chat Internal di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['viewer', 'operator', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.guestbook',
            'name' => 'Menu: Buku Tamu',
            'description' => 'Tampilkan menu Buku Tamu di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['viewer', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.ptsp',
            'name' => 'Menu: Antrian PTSP',
            'description' => 'Tampilkan menu Antrian PTSP di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['operator', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.sidang',
            'name' => 'Menu: Antrian Sidang',
            'description' => 'Tampilkan menu Antrian Sidang di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['viewer', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.pilar',
            'name' => 'Menu: Pilar PASMG',
            'description' => 'Tampilkan menu Pilar Antrian PASMG di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['useradmin', 'admin'],
        ],
        [
            'key' => 'nav.wacaraka',
            'name' => 'Menu: Omnichannel PTSP',
            'description' => 'Tampilkan menu Omnichannel PTSP di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['operator', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.cctv',
            'name' => 'Menu: Monitoring CCTV',
            'description' => 'Tampilkan menu Monitoring CCTV di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['viewer', 'useradmin', 'admin'],
        ],
        [
            'key' => 'nav.sipp',
            'name' => 'Menu: SIPP Hub',
            'description' => 'Tampilkan menu SIPP Hub di navigasi',
            'category' => 'Navigasi',
            'default_roles' => ['useradmin', 'admin'],
        ],
    ],

    'categories' => [
        'User Management' => 'Manajemen pengguna dan akses',
        'Infrastructure' => 'Infrastruktur dan perangkat',
        'Integration' => 'Integrasi eksternal',
        'Monitoring' => 'Pemantauan sistem',
        'Dashboard' => 'Dashboard dan portal',
        'Reporting' => 'Laporan dan analitik',
        'Navigasi' => 'Visibilitas menu navigasi per role',
    ],
];
