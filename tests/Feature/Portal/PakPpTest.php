<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use App\Services\VertexAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * PakPpTest
 *
 * Memvalidasi seluruh alur utama modul PAK PP:
 * halaman index, generate BAS, review, dan proteksi RBAC.
 */
class PakPpTest extends TestCase
{
    use RefreshDatabase;

    private function makeOperator(): User
    {
        return User::factory()->create([
            'role'      => 'operator',
            'is_active' => true,
        ]);
    }

    private function makeViewer(): User
    {
        return User::factory()->create([
            'role'      => 'viewer',
            'is_active' => true,
        ]);
    }

    // ── Akses Halaman ──────────────────────────────────────────────

    public function test_operator_dapat_mengakses_halaman_pak_pp(): void
    {
        $operator = $this->makeOperator();

        $this->actingAs($operator)
            ->get(route('lawangsewu.pakpp.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Lawangsewu/PakPp'));
    }

    public function test_viewer_tidak_dapat_mengakses_halaman_pak_pp(): void
    {
        $viewer = $this->makeViewer();

        $this->actingAs($viewer)
            ->get(route('lawangsewu.pakpp.index'))
            ->assertForbidden();
    }

    public function test_tamu_tidak_dapat_mengakses_halaman_pak_pp(): void
    {
        $this->get(route('lawangsewu.pakpp.index'))
            ->assertRedirect(route('login'));
    }

    // ── Generate Endpoint ──────────────────────────────────────────

    public function test_generate_mengembalikan_narasi_bas_sukses(): void
    {
        $operator = $this->makeOperator();

        // Mock VertexAiService agar tidak memerlukan kredensial GCP nyata
        $this->instance(VertexAiService::class, Mockery::mock(VertexAiService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn(['ok' => true, 'text' => 'Saksi menerangkan bahwa ia mengenal Penggugat dan Tergugat.']);
        }));

        $this->actingAs($operator)
            ->postJson(route('lawangsewu.pakpp.generate'), [
                'catatan_kasar' => 'S1 kenal P dan T sejak 2015, sering lihat bertengkar soal ekonomi',
                'jenis_perkara' => 'Cerai Gugat',
            ])
            ->assertOk()
            ->assertJsonStructure(['ok', 'narasi'])
            ->assertJson(['ok' => true]);
    }

    public function test_generate_mengembalikan_error_jika_input_minim(): void
    {
        $operator = $this->makeOperator();

        $this->instance(VertexAiService::class, Mockery::mock(VertexAiService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn(['ok' => true, 'text' => 'DATA_TIDAK_MEMADAI_MOHON_LENGKAPI']);
        }));

        $this->actingAs($operator)
            ->postJson(route('lawangsewu.pakpp.generate'), [
                'catatan_kasar' => 'S tidak tahu apa-apa',
                'jenis_perkara' => 'Cerai Gugat',
            ])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'errorType' => 'insufficient_data']);
    }

    public function test_generate_validasi_catatan_kasar_terlalu_pendek(): void
    {
        $operator = $this->makeOperator();

        $this->actingAs($operator)
            ->postJson(route('lawangsewu.pakpp.generate'), [
                'catatan_kasar' => 'singkat',
                'jenis_perkara' => 'Cerai Gugat',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['catatan_kasar']);
    }

    public function test_generate_validasi_jenis_perkara_tidak_valid(): void
    {
        $operator = $this->makeOperator();

        $this->actingAs($operator)
            ->postJson(route('lawangsewu.pakpp.generate'), [
                'catatan_kasar' => 'Catatan panjang cukup untuk melewati validasi minimum panjang',
                'jenis_perkara' => 'Perkara Tidak Valid',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jenis_perkara']);
    }

    // ── Review Endpoint ────────────────────────────────────────────

    public function test_review_mengembalikan_struktur_feedback_terstruktur(): void
    {
        $operator = $this->makeOperator();

        $reviewOutput = "---TEMUAN---\nTidak ada temuan kritis.\n---REKOMENDASI---\n-\n---VERSI_PERBAIKAN---\nSaksi menerangkan bahwa ia mengenal Penggugat dan Tergugat.";

        $this->instance(VertexAiService::class, Mockery::mock(VertexAiService::class, function ($mock) use ($reviewOutput) {
            $mock->shouldReceive('generate')
                ->once()
                ->andReturn(['ok' => true, 'text' => $reviewOutput]);
        }));

        $this->actingAs($operator)
            ->postJson(route('lawangsewu.pakpp.review'), [
                'narasi_bas' => 'Saksi menerangkan bahwa ia mengenal penggugat dan tergugat sejak tahun dua ribu lima belas.',
            ])
            ->assertOk()
            ->assertJsonStructure(['ok', 'temuan', 'rekomendasi', 'versiPerbaikan']);
    }

    public function test_viewer_tidak_dapat_generate(): void
    {
        $viewer = $this->makeViewer();

        $this->actingAs($viewer)
            ->postJson(route('lawangsewu.pakpp.generate'), [
                'catatan_kasar' => 'Catatan kasar sidang yang cukup panjang untuk melewati validasi',
                'jenis_perkara' => 'Cerai Gugat',
            ])
            ->assertForbidden();
    }
}

// developed by dbprakom™
