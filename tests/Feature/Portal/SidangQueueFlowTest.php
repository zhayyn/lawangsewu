<?php

namespace Tests\Feature\Portal;

use App\Models\QueueService;
use App\Models\QueueTicket;
use App\Models\ServiceCounter;
use App\Models\SidangQueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidangQueueFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_viewer_can_open_sidang_queue_page(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/antrian-sidang-v2');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lawangsewu/SidangQueue')
                ->has('todayTickets')
                ->where('canOperate', false)
            );
    }

    public function test_operator_can_create_sidang_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-sidang-v2', [
            'hearing_number' => '112/Pdt.G/2026/PA.Smg',
            'courtroom' => 'Ruang Sidang 2',
            'parties' => 'Andi vs Budi',
            'hearing_time' => '09:30',
        ]);

        $response->assertRedirect('/antrian-sidang-v2');

        $this->assertDatabaseHas('sidang_queue_tickets', [
            'ticket_number' => 'S-001',
            'hearing_number' => '112/Pdt.G/2026/PA.Smg',
            'courtroom' => 'Ruang Sidang 2',
            'status' => 'waiting',
        ]);

        $service = QueueService::query()->where('code', 'sidang')->firstOrFail();
        $counter = ServiceCounter::query()->where('code', 'SIDANG-RUANG-SIDANG-2')->firstOrFail();

        $this->assertDatabaseHas('queue_tickets', [
            'service_id' => $service->id,
            'counter_id' => $counter->id,
            'ticket_number' => 'S-001',
            'ticket_code' => 'sidang:S-001',
            'source_type' => 'sidang_queue_tickets',
            'source_id' => 1,
            'status' => 'waiting',
            'case_number' => '112/Pdt.G/2026/PA.Smg',
            'customer_name' => 'Andi vs Budi',
        ]);
    }

    public function test_operator_can_call_and_complete_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $ticket = SidangQueueTicket::query()->create([
            'ticket_number' => 'S-001',
            'queue_date' => today(),
            'hearing_number' => '18/Pid.B/2026/PA.Smg',
            'courtroom' => 'Ruang Sidang 1',
            'parties' => 'Jaksa vs Terdakwa',
            'status' => 'waiting',
        ]);

        $callResponse = $this->actingAs($user)->post('/antrian-sidang-v2/'.$ticket->id.'/call');
        $callResponse->assertRedirect('/antrian-sidang-v2');

        $this->assertDatabaseHas('sidang_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'called',
        ]);

        $this->assertDatabaseHas('queue_tickets', [
            'source_type' => 'sidang_queue_tickets',
            'source_id' => $ticket->id,
            'status' => 'called',
        ]);

        $completeResponse = $this->actingAs($user)->post('/antrian-sidang-v2/'.$ticket->id.'/complete');
        $completeResponse->assertRedirect('/antrian-sidang-v2');

        $this->assertDatabaseHas('sidang_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('queue_tickets', [
            'source_type' => 'sidang_queue_tickets',
            'source_id' => $ticket->id,
            'status' => 'completed',
        ]);
    }

    public function test_operator_can_postpone_ticket(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $ticket = SidangQueueTicket::query()->create([
            'ticket_number' => 'S-001',
            'queue_date' => today(),
            'hearing_number' => '130/Pdt.G/2026/PA.Smg',
            'courtroom' => 'Ruang Sidang 3',
            'parties' => 'Pemohon vs Termohon',
            'status' => 'called',
            'called_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-sidang-v2/'.$ticket->id.'/postpone');

        $response->assertRedirect('/antrian-sidang-v2');

        $this->assertDatabaseHas('sidang_queue_tickets', [
            'id' => $ticket->id,
            'status' => 'postponed',
            'notes' => 'Ditunda untuk pemanggilan berikutnya.',
        ]);

        $queueTicket = QueueTicket::query()
            ->where('source_type', 'sidang_queue_tickets')
            ->where('source_id', $ticket->id)
            ->firstOrFail();

        $this->assertSame('pending', $queueTicket->status);
        $this->assertSame('Ditunda untuk pemanggilan berikutnya.', $queueTicket->notes);
    }

    public function test_viewer_cannot_operate_sidang_queue_actions(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post('/antrian-sidang-v2', [
            'hearing_number' => '200/Pdt.G/2026/PA.Smg',
            'courtroom' => 'Ruang Sidang 1',
        ]);

        $response->assertForbidden();
    }
}
