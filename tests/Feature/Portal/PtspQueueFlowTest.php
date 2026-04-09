<?php

namespace Tests\Feature\Portal;

use App\Models\PtspQueueTicket;
use App\Models\QueueTicket;
use App\Models\QueueService;
use App\Models\ServiceCounter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtspQueueFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_viewer_can_open_ptsp_queue_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/antrian-ptsp');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lawangsewu/PtspQueue')
                ->has('todayTickets')
                ->where('canOperate', false)
            );
    }

    public function test_operator_can_create_ptsp_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-ptsp', [
            'service_desk' => 'PTSP-2',
            'visitor_name' => 'Sari Wulandari',
            'purpose' => 'Legalisir dokumen',
        ]);

        $response->assertRedirect('/antrian-ptsp');

        $this->assertDatabaseHas('ptsp_queue_tickets', [
            'ticket_number' => 'A-001',
            'service_desk' => 'PTSP-2',
            'visitor_name' => 'Sari Wulandari',
            'status' => 'waiting',
        ]);

        $this->assertDatabaseHas('service_groups', [
            'code' => 'ptsp',
            'name' => 'Pelayanan PTSP',
        ]);

        $service = QueueService::query()->where('code', 'ptsp_frontdesk')->firstOrFail();
        $counter = ServiceCounter::query()->where('code', 'PTSP-PTSP-2')->firstOrFail();

        $this->assertDatabaseHas('queue_tickets', [
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'A-001',
            'ticket_code' => 'ptsp:A-001',
            'source_type' => 'ptsp_queue_tickets',
            'source_id' => 1,
            'status' => 'waiting',
            'customer_name' => 'Sari Wulandari',
        ]);
    }

    public function test_operator_can_call_and_serve_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $ticket = PtspQueueTicket::query()->create([
            'ticket_number' => 'A-001',
            'queue_date' => today(),
            'service_desk' => 'PTSP-1',
            'visitor_name' => 'Adi Nugroho',
            'purpose' => 'Konsultasi layanan',
            'status' => 'waiting',
        ]);

        $callResponse = $this->actingAs($user)->post('/antrian-ptsp/'.$ticket->id.'/call');
        $callResponse->assertRedirect('/antrian-ptsp');

        $this->assertDatabaseHas('ptsp_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'called',
        ]);

        $this->assertDatabaseHas('queue_tickets', [
            'source_type' => 'ptsp_queue_tickets',
            'source_id' => $ticket->id,
            'status' => 'called',
        ]);

        $serveResponse = $this->actingAs($user)->post('/antrian-ptsp/'.$ticket->id.'/serve');
        $serveResponse->assertRedirect('/antrian-ptsp');

        $this->assertDatabaseHas('ptsp_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'served',
        ]);

        $this->assertDatabaseHas('queue_tickets', [
            'source_type' => 'ptsp_queue_tickets',
            'source_id' => $ticket->id,
            'status' => 'completed',
        ]);
    }

    public function test_operator_can_skip_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $ticket = PtspQueueTicket::query()->create([
            'ticket_number' => 'A-001',
            'queue_date' => today(),
            'service_desk' => 'PTSP-3',
            'visitor_name' => 'Nina Pratiwi',
            'purpose' => 'Informasi perkara',
            'status' => 'called',
            'called_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-ptsp/'.$ticket->id.'/skip');

        $response->assertRedirect('/antrian-ptsp');

        $this->assertDatabaseHas('ptsp_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'skipped',
            'notes' => 'Tidak hadir saat dipanggil.',
        ]);

        $queueTicket = QueueTicket::query()
            ->where('source_type', 'ptsp_queue_tickets')
            ->where('source_id', $ticket->id)
            ->firstOrFail();

        $this->assertSame('skipped', $queueTicket->status);
        $this->assertSame('Tidak hadir saat dipanggil.', $queueTicket->notes);
    }

    public function test_viewer_cannot_operate_queue_actions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-ptsp', [
            'service_desk' => 'PTSP-1',
        ]);

        $response->assertForbidden();
    }
}
