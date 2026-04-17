<?php

namespace Tests\Feature\Portal;

use App\Models\GuestbookEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestbookFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_BASE64_IMAGE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WqXWl0AAAAASUVORK5CYII=';

    public function test_active_viewer_can_open_guestbook_form(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'viewer',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/buku-tamu');

        $response
            ->assertOk()
            ->assertSee('Register Buku Tamu')
            ->assertSee('Formulir Kunjungan');
    }

    public function test_active_operator_can_submit_guestbook_entry_with_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'operator',
            'email_verified_at' => now(),
        ]);

        $payload = [
            'id' => '20260406010101999',
            'nama' => 'Andi Saputra',
            'jabatan' => 'Staff',
            'kategori_instansi' => 'INSTANSI_PERUSAHAAN',
            'instansi' => 'Pemerintah Kota Semarang',
            'keperluan' => 'Koordinasi pelayanan',
            'foto' => self::TEST_BASE64_IMAGE,
        ];

        $response = $this->actingAs($user)->post('/buku-tamu', $payload);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('jumlah', 1);

        $this->assertDatabaseHas('guestbook_entries', [
            'id' => '20260406010101999',
            'name' => 'Andi Saputra',
            'institution' => 'Pemerintah Kota Semarang',
        ]);

        Storage::disk('public')->assertExists('guestbook/photos/20260406010101999.jpg');
    }

    public function test_guestbook_list_detail_and_report_pages_are_accessible(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        GuestbookEntry::query()->create([
            'id' => '20260406020202888',
            'name' => 'Budi Hartono',
            'position' => 'Analis',
            'institution_category' => 'MAHKAMAH_AGUNG',
            'institution' => 'Pengadilan Agama Semarang (PA)',
            'purpose' => 'Koordinasi perkara',
            'checkin' => now(),
        ]);

        $list = $this->actingAs($user)->get('/buku-tamu/daftar/all');
        $list->assertOk()->assertSee('Riwayat Buku Tamu')->assertSee('Budi Hartono');

        $detail = $this->actingAs($user)->get('/buku-tamu/detail/20260406020202888');
        $detail->assertOk()->assertSee('Detail Tamu')->assertSee('Koordinasi perkara');

        $report = $this->actingAs($user)->post('/buku-tamu/laporan', [
            'bulan' => now()->format('m'),
            'tahun' => now()->format('Y'),
        ]);

        $report->assertOk()->assertSee('LAPORAN PENERIMAAN TAMU');
    }
}
