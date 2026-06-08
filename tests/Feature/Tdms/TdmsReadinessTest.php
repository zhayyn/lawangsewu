<?php

namespace Tests\Feature\Tdms;

use App\Models\TdmsAsset;
use App\Models\TdmsCategory;
use App\Models\TdmsMaintenanceSchedule;
use App\Models\TdmsServiceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TDMS (Tech Device Management System) Feature Test
 *
 * Menggantikan TdmsReadinessTest yang hanya berisi assertTrue(true).
 * Test ini mencakup:
 * - RBAC: akses page dan API per role
 * - Asset CRUD via /tdms/api/create-asset, update-asset, delete-asset
 * - Service Record lifecycle (open → close)
 * - Maintenance Schedule (create + complete + auto-recurring)
 * - QR Code lookup (public route)
 * - Summary data structure validation
 *
 * // developed by dbprakom™
 */
class TdmsReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;
    private User $operator;
    private User $admin;
    private User $superadmin;
    private TdmsCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->superadmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
            'is_superadmin' => true,
        ]);

        $this->category = TdmsCategory::create([
            'name' => 'Laptop & Komputer',
            'icon' => '💻',
            'color' => 'blue',
            'sort_order' => 1,
        ]);
    }

    // ── RBAC: Page Access Tests ───────────────────────────────────────────────

    public function test_guest_cannot_access_tdms_dashboard(): void
    {
        $this->get('/tdms')->assertRedirect('/login');
    }

    public function test_viewer_cannot_access_tdms_dashboard_due_to_rbac(): void
    {
        // TDMS routes berada di group role:operator,admin
        // Viewer tidak memiliki akses — 403 adalah behavior yang BENAR
        $this->actingAs($this->viewer)->get('/tdms')->assertStatus(403);
    }

    public function test_operator_can_access_tdms_assets_page(): void
    {
        $this->actingAs($this->operator)->get('/tdms/assets')->assertOk();
    }

    public function test_viewer_cannot_access_service_records_due_to_rbac(): void
    {
        // Viewer → 403 (route dalam group role:operator,admin)
        $this->actingAs($this->viewer)->get('/tdms/service-records')->assertStatus(403);
    }

    public function test_viewer_cannot_access_maintenance_due_to_rbac(): void
    {
        $this->actingAs($this->viewer)->get('/tdms/maintenance')->assertStatus(403);
    }

    // ── Summary API Tests ─────────────────────────────────────────────────────

    public function test_summary_api_returns_correct_structure(): void
    {
        $response = $this->actingAs($this->operator)
            ->getJson('/tdms/api/summary');

        $response->assertOk();
        $response->assertJsonStructure([
            'assets' => ['total', 'active', 'broken', 'maintenance'],
            'tickets' => ['open', 'breached'],
            'maintenance' => ['overdue', 'dueToday'],
            'replacements' => ['pending'],
        ]);
    }

    public function test_summary_returns_zero_counts_when_empty(): void
    {
        // Summary hanya bisa diakses operator ke atas
        $response = $this->actingAs($this->operator)
            ->getJson('/tdms/api/summary');

        $response->assertOk();
        $response->assertJsonPath('assets.total', 0);
        $response->assertJsonPath('tickets.open', 0);
    }

    // ── Asset CRUD Tests ──────────────────────────────────────────────────────

    public function test_operator_can_create_asset(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-asset', [
                'category_id' => $this->category->id,
                'name' => 'Laptop Dell Latitude 5420',
                'brand' => 'Dell',
                'model' => 'Latitude 5420',
                'serial_number' => 'SN-DELL-001',
                'location' => 'Ruang IT',
                'status' => 'active',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('tdms_assets', [
            'name' => 'Laptop Dell Latitude 5420',
            'brand' => 'Dell',
            'location' => 'Ruang IT',
        ]);
    }

    public function test_created_asset_has_qr_token_auto_generated(): void
    {
        $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-asset', [
                'category_id' => $this->category->id,
                'name' => 'Monitor Acer 24"',
                'status' => 'active',
            ]);

        $asset = TdmsAsset::where('name', 'Monitor Acer 24"')->first();
        $this->assertNotNull($asset, 'Asset harus tersimpan di database');
        $this->assertNotEmpty($asset->qr_token, 'QR token harus di-generate otomatis');
        $this->assertEquals(48, strlen($asset->qr_token), 'QR token harus 48 karakter');
    }

    public function test_create_asset_validates_required_fields(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-asset', []);

        $response->assertStatus(422);
    }

    public function test_create_asset_validates_invalid_status(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-asset', [
                'category_id' => $this->category->id,
                'name' => 'Test Asset',
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
    }

    public function test_operator_can_update_asset(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Printer HP LaserJet',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/update-asset', [
                'id' => $asset->id,
                'category_id' => $this->category->id,
                'name' => 'Printer HP LaserJet Pro',
                'status' => 'maintenance',
                'location' => 'Ruang TU',
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('tdms_assets', [
            'id' => $asset->id,
            'name' => 'Printer HP LaserJet Pro',
            'status' => 'maintenance',
        ]);
    }

    public function test_only_superadmin_can_delete_asset(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Keyboard Logitech',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        // Operator tidak bisa hapus
        $this->actingAs($this->operator)
            ->postJson('/tdms/api/delete-asset', ['id' => $asset->id])
            ->assertStatus(403);

        // Superadmin bisa hapus
        $this->actingAs($this->superadmin)
            ->postJson('/tdms/api/delete-asset', ['id' => $asset->id])
            ->assertOk();

        $this->assertSoftDeleted('tdms_assets', ['id' => $asset->id]);
    }

    // ── QR Code Tests ─────────────────────────────────────────────────────────

    public function test_qr_detail_page_accessible_by_operator(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Scanner QR Test',
            'status' => 'active',
            'created_by' => $this->viewer->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->get("/tdms/qr/{$asset->qr_token}");

        $response->assertOk();
    }

    public function test_qr_detail_returns_404_for_invalid_token(): void
    {
        $this->actingAs($this->operator)
            ->get('/tdms/qr/token-yang-tidak-ada-9999')
            ->assertStatus(404);
    }

    public function test_api_asset_by_qr_returns_asset_data(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Router Mikrotik',
            'location' => 'Server Room',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->getJson("/tdms/api/asset-by-qr?token={$asset->qr_token}");

        $response->assertOk();
        $response->assertJsonPath('name', 'Router Mikrotik');
        $response->assertJsonPath('location', 'Server Room');
    }

    public function test_api_asset_by_qr_returns_404_for_unknown_token(): void
    {
        $response = $this->actingAs($this->operator)
            ->getJson('/tdms/api/asset-by-qr?token=token-palsu');

        $response->assertStatus(404);
    }

    // ── Service Record Lifecycle Tests ────────────────────────────────────────

    public function test_operator_can_create_service_record(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'PC Rusak',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-service-record', [
                'asset_id' => $asset->id,
                'title' => 'Monitor tidak menyala',
                'issue_description' => 'Monitor hitam saat dinyalakan, sudah dicek kabel.',
                'priority' => 'high',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('record.priority', 'high');
    }

    public function test_creating_service_record_changes_asset_status_to_maintenance(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Printer Rusak',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-service-record', [
                'asset_id' => $asset->id,
                'title' => 'Printer macet',
                'issue_description' => 'Kertas sering macet.',
                'priority' => 'medium',
            ]);

        $this->assertDatabaseHas('tdms_assets', [
            'id' => $asset->id,
            'status' => 'maintenance',
        ]);
    }

    public function test_closing_service_record_restores_asset_to_active(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Laptop Diperbaiki',
            'status' => 'maintenance',
            'created_by' => $this->operator->id,
        ]);

        $record = TdmsServiceRecord::create([
            'asset_id' => $asset->id,
            'reporter_id' => $this->operator->id,
            'title' => 'Layar retak',
            'issue_description' => 'Layar LCD retak kena benturan.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        // Update record ke resolved
        $this->actingAs($this->operator)
            ->postJson('/tdms/api/update-service-record', [
                'id' => $record->id,
                'status' => 'resolved',
                'action_taken' => 'Layar LCD diganti baru.',
            ]);

        // Asset harus kembali ke active
        $this->assertDatabaseHas('tdms_assets', [
            'id' => $asset->id,
            'status' => 'active',
        ]);
    }

    // ── Maintenance Schedule Tests ────────────────────────────────────────────

    public function test_operator_can_create_maintenance_schedule(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'AC Ruang Sidang',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-schedule', [
                'asset_id' => $asset->id,
                'maintenance_type' => 'preventive',
                'title' => 'Service AC 3 Bulanan',
                'due_date' => now()->addMonths(3)->format('Y-m-d'),
                'interval_days' => 90,
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('ok', true);

        $this->assertDatabaseHas('tdms_maintenance_schedules', [
            'asset_id' => $asset->id,
            'maintenance_type' => 'preventive',
            'interval_days' => 90,
        ]);
    }

    public function test_completing_recurring_schedule_auto_creates_next(): void
    {
        $asset = TdmsAsset::create([
            'category_id' => $this->category->id,
            'name' => 'Server Room AC',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);

        $schedule = TdmsMaintenanceSchedule::create([
            'asset_id' => $asset->id,
            'maintenance_type' => 'preventive',
            'title' => 'Cleaning Bulanan',
            'due_date' => today(),
            'status' => 'pending',
            'interval_days' => 30, // recurring
        ]);

        $this->actingAs($this->operator)
            ->postJson('/tdms/api/complete-schedule', [
                'id' => $schedule->id,
                'completion_notes' => 'Selesai dibersihkan.',
            ]);

        // Schedule lama harus completed
        $this->assertDatabaseHas('tdms_maintenance_schedules', [
            'id' => $schedule->id,
            'status' => 'completed',
        ]);

        // Schedule baru harus otomatis dibuat (next occurrence)
        $this->assertDatabaseCount('tdms_maintenance_schedules', 2);
        $nextSchedule = TdmsMaintenanceSchedule::where('id', '!=', $schedule->id)->first();
        $this->assertEquals(
            today()->addDays(30)->format('Y-m-d'),
            $nextSchedule->due_date->format('Y-m-d'),
            'Next schedule due date harus 30 hari ke depan'
        );
    }

    // ── Category Management Tests ─────────────────────────────────────────────

    public function test_only_superadmin_can_create_category(): void
    {
        // Operator tidak bisa buat kategori
        $this->actingAs($this->operator)
            ->postJson('/tdms/api/create-category', [
                'name' => 'Furniture Kantor',
                'icon' => '🪑',
            ])
            ->assertStatus(403);

        // Superadmin bisa
        $this->actingAs($this->superadmin)
            ->postJson('/tdms/api/create-category', [
                'name' => 'Furniture Kantor',
                'icon' => '🪑',
                'color' => 'amber',
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('tdms_categories', ['name' => 'Furniture Kantor']);
    }

    // ── Invalid Action Tests ──────────────────────────────────────────────────

    public function test_unknown_api_action_returns_404(): void
    {
        $response = $this->actingAs($this->operator)
            ->postJson('/tdms/api/action-yang-tidak-ada', []);

        $response->assertStatus(404);
        $response->assertJsonPath('ok', false);
    }
}
