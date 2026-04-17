<?php

namespace App\Services;

use App\Models\PtspQueueTicket;
use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\ServiceCounter;
use App\Models\ServiceGroup;
use App\Models\SidangQueueTicket;
use Illuminate\Support\Str;

class PilarQueueAuthority
{
    public function syncPtspTicket(PtspQueueTicket $ticket, ?int $actorId = null): QueueTicket
    {
        $service = $this->firstOrCreateService(
            groupCode: 'ptsp',
            groupName: 'Pelayanan PTSP',
            serviceCode: 'ptsp_frontdesk',
            serviceName: 'PTSP Frontdesk',
            queuePrefix: 'A',
            numberingScope: 'service_daily',
            displayOrder: 1,
        );

        $counter = $this->firstOrCreateCounter(
            service: $service,
            code: $this->normalizeCounterCode('ptsp', (string) $ticket->service_desk),
            name: (string) $ticket->service_desk,
            callLabel: (string) $ticket->service_desk,
            displayLabel: (string) $ticket->service_desk,
            locationType: 'loket',
            externalRef: (string) $ticket->service_desk,
        );

        return QueueTicket::query()->updateOrCreate(
            [
                'source_type' => 'ptsp_queue_tickets',
                'source_id' => $ticket->id,
            ],
            [
                'ticket_date' => $ticket->queue_date,
                'ticket_number' => $ticket->ticket_number,
                'ticket_code' => 'ptsp:' . $ticket->ticket_number,
                'service_id' => $service->id,
                'counter_id' => $counter->id,
                'channel' => 'operator',
                'status' => $this->mapPtspStatus($ticket->status),
                'customer_name' => $ticket->visitor_name,
                'payload_json' => [
                    'purpose' => $ticket->purpose,
                    'legacy_status' => $ticket->status,
                    'legacy_table' => 'ptsp_queue_tickets',
                    'service_desk' => $ticket->service_desk,
                ],
                'created_by' => $actorId,
                'called_by' => $ticket->status === 'called' ? $actorId : null,
                'served_by' => in_array($ticket->status, ['served', 'skipped'], true) ? $actorId : null,
                'called_at' => $ticket->called_at,
                'service_started_at' => $ticket->status === 'called' ? $ticket->called_at : null,
                'completed_at' => $ticket->status === 'served' ? $ticket->served_at : null,
                'cancelled_at' => null,
                'notes' => $ticket->notes,
            ],
        );
    }

    public function syncSidangTicket(SidangQueueTicket $ticket, ?int $actorId = null): QueueTicket
    {
        $service = $this->firstOrCreateService(
            groupCode: 'sidang',
            groupName: 'Pelayanan Persidangan',
            serviceCode: 'sidang',
            serviceName: 'Sidang',
            queuePrefix: 'S',
            numberingScope: 'service_daily',
            displayOrder: 1,
        );

        $counter = $this->firstOrCreateCounter(
            service: $service,
            code: $this->normalizeCounterCode('sidang', (string) $ticket->courtroom),
            name: (string) $ticket->courtroom,
            callLabel: (string) $ticket->courtroom,
            displayLabel: (string) $ticket->courtroom,
            locationType: 'ruang_sidang',
            externalRef: (string) $ticket->courtroom,
        );

        return QueueTicket::query()->updateOrCreate(
            [
                'source_type' => 'sidang_queue_tickets',
                'source_id' => $ticket->id,
            ],
            [
                'ticket_date' => $ticket->queue_date,
                'ticket_number' => $ticket->ticket_number,
                'ticket_code' => 'sidang:' . $ticket->ticket_number,
                'service_id' => $service->id,
                'counter_id' => $counter->id,
                'channel' => 'operator',
                'status' => $this->mapSidangStatus($ticket->status),
                'customer_name' => $ticket->parties,
                'case_number' => $ticket->hearing_number,
                'payload_json' => [
                    'parties' => $ticket->parties,
                    'hearing_time' => optional($ticket->hearing_time)?->toIso8601String(),
                    'legacy_status' => $ticket->status,
                    'legacy_table' => 'sidang_queue_tickets',
                    'courtroom' => $ticket->courtroom,
                ],
                'created_by' => $actorId,
                'called_by' => $ticket->status === 'called' ? $actorId : null,
                'served_by' => in_array($ticket->status, ['completed', 'postponed'], true) ? $actorId : null,
                'called_at' => $ticket->called_at,
                'service_started_at' => $ticket->status === 'called' ? $ticket->called_at : null,
                'completed_at' => $ticket->status === 'completed' ? $ticket->completed_at : null,
                'cancelled_at' => null,
                'notes' => $ticket->notes,
            ],
        );
    }

    private function firstOrCreateService(
        string $groupCode,
        string $groupName,
        string $serviceCode,
        string $serviceName,
        string $queuePrefix,
        string $numberingScope,
        int $displayOrder,
    ): QueueService {
        $group = ServiceGroup::query()->firstOrCreate(
            ['code' => $groupCode],
            ['name' => $groupName],
        );

        return QueueService::query()->firstOrCreate(
            ['code' => $serviceCode],
            [
                'service_group_id' => $group->id,
                'name' => $serviceName,
                'queue_prefix' => $queuePrefix,
                'numbering_scope' => $numberingScope,
                'is_active' => true,
                'display_order' => $displayOrder,
            ],
        );
    }

    private function firstOrCreateCounter(
        QueueService $service,
        string $code,
        string $name,
        string $callLabel,
        string $displayLabel,
        string $locationType,
        string $externalRef,
    ): ServiceCounter {
        return ServiceCounter::query()->firstOrCreate(
            ['code' => $code],
            [
                'queue_service_id' => $service->id,
                'name' => $name,
                'call_label' => $callLabel,
                'display_label' => $displayLabel,
                'location_type' => $locationType,
                'external_ref' => $externalRef,
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }

    private function normalizeCounterCode(string $prefix, string $label): string
    {
        $normalized = Str::of($label)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '-')
            ->trim('-')
            ->value();

        return Str::upper($prefix) . '-' . $normalized;
    }

    private function mapPtspStatus(string $status): string
    {
        return match ($status) {
            'served' => 'completed',
            'skipped' => 'skipped',
            'called' => 'called',
            default => 'waiting',
        };
    }

    private function mapSidangStatus(string $status): string
    {
        return match ($status) {
            'completed' => 'completed',
            'postponed' => 'pending',
            'called' => 'called',
            default => 'waiting',
        };
    }
}
