<?php

namespace Database\Seeders;

use App\Models\ServiceCounter;
use App\Models\ServiceGroup;
use App\Models\QueueService;
use Illuminate\Database\Seeder;

/**
 * Seed katalog layanan dan loket resmi Pilar Antrian PASMG.
 *
 * Ini adalah sumber kebenaran tunggal untuk semua loket PTSP dan ruang sidang.
 * PilarQueueAuthority akan menggunakan data ini saat sync tiket.
 */
class PilarServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // GROUP 1: PTSP
        // ─────────────────────────────────────────────
        $ptspGroup = ServiceGroup::query()->firstOrCreate(
            ['code' => 'ptsp'],
            ['name' => 'Pelayanan PTSP'],
        );

        $ptspService = QueueService::query()->firstOrCreate(
            ['code' => 'ptsp_frontdesk'],
            [
                'service_group_id' => $ptspGroup->id,
                'name'             => 'PTSP Frontdesk',
                'queue_prefix'     => 'A',
                'numbering_scope'  => 'service_daily',
                'is_active'        => true,
                'display_order'    => 1,
            ],
        );

        $ptspCounters = [
            ['code' => 'PTSP-PTSP-1', 'name' => 'PTSP-1', 'call_label' => 'Loket PTSP 1', 'display_label' => 'PTSP 1', 'sort_order' => 1],
            ['code' => 'PTSP-PTSP-2', 'name' => 'PTSP-2', 'call_label' => 'Loket PTSP 2', 'display_label' => 'PTSP 2', 'sort_order' => 2],
            ['code' => 'PTSP-PTSP-3', 'name' => 'PTSP-3', 'call_label' => 'Loket PTSP 3', 'display_label' => 'PTSP 3', 'sort_order' => 3],
            ['code' => 'PTSP-PTSP-4', 'name' => 'PTSP-4', 'call_label' => 'Loket PTSP 4', 'display_label' => 'PTSP 4', 'sort_order' => 4],
        ];

        foreach ($ptspCounters as $counter) {
            ServiceCounter::query()->firstOrCreate(
                ['code' => $counter['code']],
                [
                    'queue_service_id' => $ptspService->id,
                    'name'             => $counter['name'],
                    'call_label'       => $counter['call_label'],
                    'display_label'    => $counter['display_label'],
                    'location_type'    => 'loket',
                    'external_ref'     => $counter['name'],
                    'is_active'        => true,
                    'sort_order'       => $counter['sort_order'],
                ],
            );
        }

        // ─────────────────────────────────────────────
        // GROUP 2: Persidangan
        // ─────────────────────────────────────────────
        $sidangGroup = ServiceGroup::query()->firstOrCreate(
            ['code' => 'sidang'],
            ['name' => 'Pelayanan Persidangan'],
        );

        $sidangService = QueueService::query()->firstOrCreate(
            ['code' => 'sidang'],
            [
                'service_group_id' => $sidangGroup->id,
                'name'             => 'Sidang',
                'queue_prefix'     => 'S',
                'numbering_scope'  => 'service_daily',
                'is_active'        => true,
                'display_order'    => 1,
            ],
        );

        $sidangCounters = [
            ['code' => 'SIDANG-RUANG-SIDANG-1',  'name' => 'Ruang Sidang 1',  'call_label' => 'Ruang Sidang 1',  'display_label' => 'Sidang 1',  'sort_order' => 1],
            ['code' => 'SIDANG-RUANG-SIDANG-2',  'name' => 'Ruang Sidang 2',  'call_label' => 'Ruang Sidang 2',  'display_label' => 'Sidang 2',  'sort_order' => 2],
            ['code' => 'SIDANG-RUANG-SIDANG-3',  'name' => 'Ruang Sidang 3',  'call_label' => 'Ruang Sidang 3',  'display_label' => 'Sidang 3',  'sort_order' => 3],
            ['code' => 'SIDANG-RUANG-MEDIASI',   'name' => 'Ruang Mediasi',   'call_label' => 'Ruang Mediasi',   'display_label' => 'Mediasi',   'sort_order' => 4],
            ['code' => 'SIDANG-RUANG-SIDANG-4',  'name' => 'Ruang Sidang 4',  'call_label' => 'Ruang Sidang 4',  'display_label' => 'Sidang 4',  'sort_order' => 5],
            ['code' => 'SIDANG-RUANG-HAKIM',     'name' => 'Ruang Hakim',     'call_label' => 'Ruang Hakim',     'display_label' => 'Hakim',     'sort_order' => 6],
        ];

        foreach ($sidangCounters as $counter) {
            ServiceCounter::query()->firstOrCreate(
                ['code' => $counter['code']],
                [
                    'queue_service_id' => $sidangService->id,
                    'name'             => $counter['name'],
                    'call_label'       => $counter['call_label'],
                    'display_label'    => $counter['display_label'],
                    'location_type'    => 'ruang_sidang',
                    'external_ref'     => $counter['name'],
                    'is_active'        => true,
                    'sort_order'       => $counter['sort_order'],
                ],
            );
        }

        $this->command->info('✓ Pilar Service Catalog seeded — ' .
            count($ptspCounters) . ' loket PTSP, ' .
            count($sidangCounters) . ' ruang sidang.');
    }
}
