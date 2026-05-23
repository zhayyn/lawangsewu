<?php

namespace App\Http\Controllers;

use App\Events\QueueTicketUpdated;
use App\Models\PtspAntrian;
use App\Models\PtspLoket;
use App\Models\PtspPenyerahanAc;
use App\Services\SippService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PelayananPtspController extends Controller
{
    public function __construct(
        private readonly SippService $sipp,
    ) {}

    // ── Dashboard Utama ───────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $lokets  = PtspLoket::aktif()->get();
        $hari    = today();

        $antrian = PtspAntrian::with('loket')
            ->hariIni()
            ->orderByRaw("FIELD(status,'called','waiting','served','skipped','cancelled')")
            ->orderBy('id')
            ->get()
            ->map(fn (PtspAntrian $t) => [
                'id'             => $t->id,
                'nomor_antrian'  => $t->nomor_antrian,
                'loket_id'       => $t->loket_id,
                'loket_kode'     => $t->loket?->kode,
                'loket_nama'     => $t->loket?->nama,
                'nama_pemohon'   => $t->nama_pemohon,
                'nomor_perkara'  => $t->nomor_perkara,
                'keperluan'      => $t->keperluan,
                'status'         => $t->status,
                'dipanggil_at'   => $t->dipanggil_at?->timezone('Asia/Jakarta')->format('H:i:s'),
                'dilayani_at'    => $t->dilayani_at?->timezone('Asia/Jakarta')->format('H:i:s'),
            ])
            ->values()->all();

        $dipanggil = collect($antrian)->firstWhere('status', 'called');

        // Summary per loket
        $summaryLoket = $lokets->map(fn ($l) => [
            'id'       => $l->id,
            'kode'     => $l->kode,
            'nama'     => $l->nama,
            'waiting'  => collect($antrian)->where('loket_id', $l->id)->where('status', 'waiting')->count(),
            'called'   => collect($antrian)->where('loket_id', $l->id)->where('status', 'called')->count(),
            'served'   => collect($antrian)->where('loket_id', $l->id)->where('status', 'served')->count(),
        ])->values()->all();

        $user = $request->user();
        $canOperate = $user && in_array($user->role ?? '', ['operator', 'admin']) || ($user?->is_superadmin ?? false);

        return Inertia::render('Lawangsewu/PelayananPtsp/Index', [
            'appMeta'      => LawangsewuPortal::appMeta(),
            'navGroups'    => LawangsewuPortal::navGroups(),
            'lokets'       => $lokets->map(fn ($l) => [
                'id'             => $l->id,
                'kode'           => $l->kode,
                'nama'           => $l->nama,
                'prefix_antrian' => $l->prefix_antrian,
                'is_active'      => $l->is_active,
            ])->values()->all(),
            'antrian'      => $antrian,
            'dipanggil'    => $dipanggil,
            'summaryLoket' => $summaryLoket,
            'summary'      => [
                'waiting'  => collect($antrian)->where('status', 'waiting')->count(),
                'called'   => collect($antrian)->where('status', 'called')->count(),
                'served'   => collect($antrian)->where('status', 'served')->count(),
                'skipped'  => collect($antrian)->where('status', 'skipped')->count(),
            ],
            'canOperate'   => $canOperate,
            'flash'        => ['status' => $request->session()->get('status')],
        ]);
    }

    // ── Antrian CRUD ──────────────────────────────────────────────────────────

    public function storeAntrian(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'loket_id'      => ['required', 'exists:ptsp_lokets,id'],
            'nama_pemohon'  => ['nullable', 'string', 'max:120'],
            'nomor_perkara' => ['nullable', 'string', 'max:80'],
            'keperluan'     => ['nullable', 'string', 'max:255'],
        ]);

        $loket  = PtspLoket::findOrFail($data['loket_id']);
        $nomor  = PtspAntrian::generateNomor($loket, today());

        $ticket = PtspAntrian::create([
            'tanggal_antrian' => today(),
            'nomor_antrian'   => $nomor,
            'loket_id'        => $loket->id,
            'nama_pemohon'    => $data['nama_pemohon'] ?? null,
            'nomor_perkara'   => $data['nomor_perkara'] ?? null,
            'keperluan'       => $data['keperluan'] ?? null,
            'status'          => 'waiting',
        ]);

        $this->broadcastAntrian('created', $ticket);

        return back()->with('status', "Nomor antrian {$nomor} ({$loket->kode}) berhasil dibuat.");
    }

    public function callAntrian(PtspAntrian $antrian): RedirectResponse
    {
        // Reset called sebelumnya di loket yang sama
        PtspAntrian::hariIni()
            ->where('loket_id', $antrian->loket_id)
            ->where('status', 'called')
            ->update(['status' => 'waiting', 'dipanggil_at' => null]);

        $antrian->update([
            'status'       => 'called',
            'dipanggil_at' => now(),
            'dipanggil_oleh' => request()->user()?->id,
        ]);

        $this->broadcastAntrian('called', $antrian->fresh());

        return back()->with('status', "Nomor {$antrian->nomor_antrian} dipanggil ke {$antrian->loket?->kode}.");
    }

    public function serveAntrian(PtspAntrian $antrian): RedirectResponse
    {
        $antrian->update([
            'status'      => 'served',
            'dilayani_at' => now(),
            'selesai_at'  => now(),
            'dilayani_oleh' => request()->user()?->id,
        ]);

        $this->broadcastAntrian('served', $antrian->fresh());

        return back()->with('status', "Nomor {$antrian->nomor_antrian} telah dilayani.");
    }

    public function skipAntrian(PtspAntrian $antrian): RedirectResponse
    {
        $antrian->update(['status' => 'skipped', 'catatan' => 'Tidak hadir saat dipanggil.']);

        $this->broadcastAntrian('skipped', $antrian->fresh());

        return back()->with('status', "Nomor {$antrian->nomor_antrian} dilewati.");
    }

    // ── Penyerahan AC / Salinan Putusan ───────────────────────────────────────

    public function indexPenyerahan(Request $request): Response
    {
        $data = PtspPenyerahanAc::with('petugas')
            ->whereDate('tanggal_penyerahan', $request->get('tanggal', today()))
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id'                  => $p->id,
                'nomor_perkara'       => $p->nomor_perkara,
                'nomor_ac'            => $p->nomor_ac,
                'tanggal_bht'         => $p->tanggal_bht?->format('d/m/Y'),
                'tanggal_penyerahan'  => $p->tanggal_penyerahan?->format('d/m/Y'),
                'jenis'               => $p->jenis,
                'nama_penerima'       => $p->nama_penerima,
                'pihak_penerima'      => $p->pihak_penerima,
                'foto_path'           => $p->foto_path ? Storage::url($p->foto_path) : null,
                'petugas'             => $p->petugas?->name,
                'created_at'          => $p->created_at->format('H:i'),
            ])
            ->values()->all();

        return Inertia::render('Lawangsewu/PelayananPtsp/PenyerahanAc', [
            'appMeta'    => LawangsewuPortal::appMeta(),
            'navGroups'  => LawangsewuPortal::navGroups(),
            'penyerahan' => $data,
            'tanggal'    => $request->get('tanggal', today()->format('Y-m-d')),
            'flash'      => ['status' => $request->session()->get('status')],
            'canOperate' => $this->canOperate($request),
        ]);
    }

    public function storePenyerahan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nomor_perkara'      => ['required', 'string', 'max:80'],
            'nomor_ac'           => ['nullable', 'string', 'max:60'],
            'tanggal_bht'        => ['nullable', 'date'],
            'tanggal_penyerahan' => ['required', 'date'],
            'jenis'              => ['required', 'in:ac,salput,ac_salput'],
            'nama_penerima'      => ['nullable', 'string', 'max:120'],
            'pihak_penerima'     => ['nullable', 'in:pihak1,pihak2,kuasa,lainnya'],
            'nik_penerima'       => ['nullable', 'string', 'max:20'],
            'foto_base64'        => ['nullable', 'string'],  // foto dari webcam
            'antrian_id'         => ['nullable', 'exists:ptsp_antrian,id'],
            'catatan'            => ['nullable', 'string', 'max:500'],
        ]);

        // Simpan foto dari kamera jika ada
        $fotoPath = null;
        if (!empty($data['foto_base64'])) {
            $fotoPath = $this->saveFotoFromBase64($data['foto_base64'], $data['nomor_perkara']);
        }

        $penyerahan = PtspPenyerahanAc::create([
            'nomor_perkara'      => $data['nomor_perkara'],
            'nomor_ac'           => $data['nomor_ac'] ?? null,
            'tanggal_bht'        => $data['tanggal_bht'] ?? null,
            'tanggal_penyerahan' => $data['tanggal_penyerahan'],
            'jenis'              => $data['jenis'],
            'nama_penerima'      => $data['nama_penerima'] ?? null,
            'pihak_penerima'     => $data['pihak_penerima'] ?? null,
            'nik_penerima'       => $data['nik_penerima'] ?? null,
            'foto_path'          => $fotoPath,
            'catatan'            => $data['catatan'] ?? null,
            'petugas_id'         => $request->user()?->id,
            'antrian_id'         => $data['antrian_id'] ?? null,
        ]);

        // Tandai antrian sebagai served jika terhubung
        if ($penyerahan->antrian_id) {
            PtspAntrian::find($penyerahan->antrian_id)?->update([
                'status'     => 'served',
                'selesai_at' => now(),
            ]);
        }

        return response()->json([
            'ok'  => true,
            'id'  => $penyerahan->id,
            'msg' => 'Penyerahan berhasil disimpan.',
        ]);
    }

    // ── SIPP Lookup (read-only dari DB server .10) ────────────────────────────

    public function sippLookup(Request $request): JsonResponse
    {
        $nomor = trim($request->get('nomor', ''));

        if (strlen($nomor) < 3) {
            return response()->json(['ok' => false, 'msg' => 'Nomor terlalu pendek.']);
        }

        if (!$this->sipp->isReachable()) {
            return response()->json(['ok' => false, 'msg' => 'SIPP tidak dapat dijangkau saat ini.']);
        }

        try {
            $row = \DB::connection('sipp')->selectOne(
                "SELECT
                    p.nomor_perkara,
                    p.pihak1_text,
                    p.pihak2_text,
                    p.jenis_perkara_text,
                    p.status_perkara,
                    p.tahapan_terakhir_text,
                    ac.nomor_akta_cerai,
                    DATE_FORMAT(ac.tgl_akta_cerai, '%d/%m/%Y') AS tgl_akta_cerai,
                    DATE_FORMAT(ac.tgl_penyerahan_akta_cerai, '%d/%m/%Y') AS tgl_penyerahan_ac_pihak1,
                    DATE_FORMAT(ac.tgl_penyerahan_akta_cerai_pihak2, '%d/%m/%Y') AS tgl_penyerahan_ac_pihak2,
                    pp.penerbitan_salinan_putusan,
                    DATE_FORMAT(pp.tanggal_bht, '%d/%m/%Y') AS tanggal_bht,
                    DATE_FORMAT(pp.kirim_salinan_putusan_pihak1, '%d/%m/%Y') AS tgl_kirim_salput_pihak1,
                    DATE_FORMAT(pp.kirim_salinan_putusan_pihak2, '%d/%m/%Y') AS tgl_kirim_salput_pihak2,
                    DATE_FORMAT(p.tanggal_pendaftaran, '%d/%m/%Y') AS tgl_daftar
                FROM sipp.perkara p
                    LEFT JOIN sipp.perkara_akta_cerai ac ON ac.perkara_id = p.perkara_id
                    LEFT JOIN sipp.perkara_putusan pp ON pp.perkara_id = p.perkara_id
                WHERE p.nomor_perkara LIKE ?
                ORDER BY p.perkara_id DESC
                LIMIT 1",
                [$nomor . '%']
            );

            if (!$row) {
                return response()->json(['ok' => false, 'msg' => 'Perkara tidak ditemukan.']);
            }

            return response()->json(['ok' => true, 'data' => $row]);

        } catch (\Exception $e) {
            \Log::warning('[PelayananPtsp] SIPP lookup error', ['code' => $e->getCode()]);
            return response()->json(['ok' => false, 'msg' => 'Gagal mengambil data SIPP.']);
        }
    }

    // ── Laporan ───────────────────────────────────────────────────────────────

    public function laporan(Request $request): Response
    {
        $dari   = $request->get('dari', today()->format('Y-m-d'));
        $sampai = $request->get('sampai', today()->format('Y-m-d'));

        $antrian = PtspAntrian::with('loket')
            ->whereBetween('tanggal_antrian', [$dari, $sampai])
            ->selectRaw('tanggal_antrian, loket_id, status, COUNT(*) as jumlah')
            ->groupBy('tanggal_antrian', 'loket_id', 'status')
            ->get();

        $penyerahan = PtspPenyerahanAc::whereBetween('tanggal_penyerahan', [$dari, $sampai])
            ->selectRaw('tanggal_penyerahan, jenis, COUNT(*) as jumlah')
            ->groupBy('tanggal_penyerahan', 'jenis')
            ->get();

        return Inertia::render('Lawangsewu/PelayananPtsp/Laporan', [
            'appMeta'    => LawangsewuPortal::appMeta(),
            'navGroups'  => LawangsewuPortal::navGroups(),
            'antrian'    => $antrian,
            'penyerahan' => $penyerahan,
            'dari'       => $dari,
            'sampai'     => $sampai,
            'lokets'     => PtspLoket::aktif()->get(['id', 'kode', 'nama']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function canOperate(Request $request): bool
    {
        $user = $request->user();
        return $user && in_array($user->role ?? '', ['operator', 'admin']) || ($user?->is_superadmin ?? false);
    }

    private function saveFotoFromBase64(string $base64, string $nomorPerkara): ?string
    {
        try {
            // Strip header data:image/jpeg;base64,
            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            $decoded   = base64_decode($imageData);

            if (!$decoded) return null;

            $filename = 'ptsp/ac/' . date('Ymd') . '/' . str_replace('/', '_', $nomorPerkara) . '_' . time() . '.jpg';
            Storage::disk('public')->put($filename, $decoded);

            return $filename;
        } catch (\Exception) {
            return null;
        }
    }

    private function buildSummary(): array
    {
        $today = today();
        return [
            'waiting'  => PtspAntrian::hariIni()->where('status', 'waiting')->count(),
            'called'   => PtspAntrian::hariIni()->where('status', 'called')->count(),
            'served'   => PtspAntrian::hariIni()->where('status', 'served')->count(),
            'skipped'  => PtspAntrian::hariIni()->where('status', 'skipped')->count(),
        ];
    }

    private function broadcastAntrian(string $action, PtspAntrian $antrian): void
    {
        try {
            QueueTicketUpdated::dispatch('ptsp', $action, [
                'id'            => $antrian->id,
                'nomor_antrian' => $antrian->nomor_antrian,
                'loket_id'      => $antrian->loket_id,
                'loket_kode'    => $antrian->loket?->kode,
                'status'        => $antrian->status,
                'dipanggil_at'  => $antrian->dipanggil_at?->timezone('Asia/Jakarta')->format('H:i:s'),
            ], $this->buildSummary());
        } catch (\Exception) {
            // Jika WebSocket gagal, tidak boleh ganggu flow utama
        }
    }
}
