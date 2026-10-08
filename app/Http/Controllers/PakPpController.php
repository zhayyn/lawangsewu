<?php

namespace App\Http\Controllers;

use App\Services\PakPpDocxExportService;
use App\Services\VertexAiService;
use App\Support\LawangsewuPortal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * PakPpController
 *
 * Controller utama untuk Modul PAK PP (Personal Asisten Khusus Panitera Pengganti).
 * Mengelola alur: tampil editor → generate BAS → review AI → export DOCX/PDF.
 *
 * Analogi: Controller ini adalah "meja koordinasi" PAK PP — menerima catatan kasar
 * dari PP, menyerahkannya ke asisten AI, lalu mendistribusikan hasilnya ke berbagai
 * format output yang dibutuhkan.
 */
class PakPpController extends Controller
{
    /**
     * Singkatan wajib yang selalu digunakan dalam system prompt.
     * Dipisahkan sebagai konstanta agar mudah dipelihara.
     */
    private const ABBREVIATION_EXPANSIONS = <<<'ABBR'
Singkatan wajib diekspansi:
- 'P' → 'Penggugat'
- 'T' → 'Tergugat'
- 'S' atau 'Sksi' → 'Saksi'
- 'Komp' → 'Kompilasi Hukum Islam'
- 'Maj' atau 'MH' → 'Majelis Hakim'
- 'PP' → 'Panitera Pengganti'
- 'KHI' → 'Kompilasi Hukum Islam'
- 'UU' → 'Undang-Undang'
- 'PA' → 'Pengadilan Agama'
- 'MA' → 'Mahkamah Agung'
- 'Pkr' → 'Perkara'
- 'Tgl' → 'Tanggal'
- 'Jl.' → 'Jalan'
ABBR;

    public function __construct(
        private readonly VertexAiService       $vertexAi,
        private readonly PakPpDocxExportService $docxExporter,
    ) {}

    /**
     * Tampilkan halaman utama PAK Editor.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('Lawangsewu/PakPp', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
        ]);
    }

    /**
     * Agen 1 — PAK Drafter: Ubah catatan kasar menjadi narasi BAS.
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'catatan_kasar' => 'required|string|min:10|max:8000',
            'jenis_perkara' => 'required|string|in:Cerai Gugat,Cerai Talak,Harta Bersama,Hadhanah,Waris,Lainnya',
        ]);

        $systemPrompt = $this->buildDrafterSystemPrompt($validated['jenis_perkara']);
        $result       = $this->vertexAi->generate($systemPrompt, $validated['catatan_kasar'], temperature: 0.2);

        if (! $result['ok']) {
            return response()->json(['ok' => false, 'error' => $result['error']], 502);
        }

        // Tangani sinyal zero-data dari model
        if (trim($result['text']) === 'DATA_TIDAK_MEMADAI_MOHON_LENGKAPI') {
            return response()->json([
                'ok'            => false,
                'error'         => 'DATA_TIDAK_MEMADAI_MOHON_LENGKAPI',
                'errorType'     => 'insufficient_data',
                'errorMessage'  => 'Catatan kasar terlalu minim atau tidak dapat diinterpretasi. Mohon lengkapi detail sidang.',
            ], 422);
        }

        return response()->json([
            'ok'    => true,
            'narasi'=> $result['text'],
        ]);
    }

    /**
     * Agen 2 — PAK Reviewer: Tinjau draf BAS dan berikan feedback terstruktur.
     */
    public function review(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'narasi_bas' => 'required|string|min:30|max:16000',
        ]);

        $systemPrompt = $this->buildReviewerSystemPrompt();
        $result       = $this->vertexAi->generate($systemPrompt, $validated['narasi_bas'], temperature: 0.2);

        if (! $result['ok']) {
            return response()->json(['ok' => false, 'error' => $result['error']], 502);
        }

        $parsed = $this->parseReviewerOutput($result['text']);

        return response()->json([
            'ok'            => true,
            'temuan'        => $parsed['temuan'],
            'rekomendasi'   => $parsed['rekomendasi'],
            'versiPerbaikan'=> $parsed['versi_perbaikan'],
            'rawOutput'     => $result['text'],
        ]);
    }

    /**
     * Export narasi BAS ke format .docx menggunakan PhpWord.
     */
    public function exportDocx(Request $request): Response|\Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'narasi_bas'    => 'required|string|min:10|max:16000',
            'jenis_perkara' => 'required|string|max:100',
        ]);

        $generatedBy = $request->user()->name ?? 'Panitera Pengganti';
        $tempPath    = $this->docxExporter->buildAndSave(
            $validated['narasi_bas'],
            $validated['jenis_perkara'],
            $generatedBy,
        );

        $fileName = 'BAS_' . now()->format('Ymd_His') . '.docx';

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export narasi BAS ke format .pdf menggunakan DomPDF.
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $validated = $request->validate([
            'narasi_bas'    => 'required|string|min:10|max:16000',
            'jenis_perkara' => 'required|string|max:100',
        ]);

        $authUser       = $request->user();
        $narasiBas      = $validated['narasi_bas'];
        $jenisPerkara   = $validated['jenis_perkara'];
        $generatedAt    = now()->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') . ' WIB';

        $pdf = Pdf::loadView('pdf.pak-pp-bas', compact('narasiBas', 'jenisPerkara', 'authUser', 'generatedAt'));
        $pdf->setPaper('A4', 'portrait');

        $fileName = 'BAS_' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($fileName);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Susun System Prompt untuk PAK Drafter dengan jenis perkara yang dipilih.
     */
    private function buildDrafterSystemPrompt(string $jenisPerkara): string
    {
        $abbreviations = self::ABBREVIATION_EXPANSIONS;

        return <<<PROMPT
PERAN ANDA:
Anda adalah "PAK PP" (Personal Asisten Khusus Panitera Pengganti), agen AI ahli yang bertugas di Pengadilan Agama Semarang. Anda memiliki pemahaman mendalam tentang Hukum Acara Perdata Agama di Indonesia.

TUGAS UTAMA:
Ubah catatan kasar, singkatan, atau poin-poin hasil ketikan cepat Panitera Pengganti menjadi paragraf narasi Berita Acara Sidang (BAS) bagian "Keterangan Saksi" atau "Tanya Jawab". Jenis perkara saat ini: {$jenisPerkara}.

{$abbreviations}

ATURAN KETAT (WAJIB DIPATUHI):
1. Gunakan Bahasa Indonesia formal, baku, dan terminologi hukum yang sah di lingkungan Mahkamah Agung RI.
2. Hasilkan output MURNI berupa paragraf naratif yang rapi dan mengalir. DILARANG KERAS menggunakan bullet point, numbered list, heading, atau format markdown apapun.
3. ZERO HALLUCINATION: Dilarang menambahkan nama, tempat, tanggal, nominal, nomor perkara, atau kejadian apapun yang tidak tercantum secara eksplisit dalam input pengguna.
4. Jika input tidak logis, terlalu ambigu, atau terlalu minim untuk diproses menjadi narasi BAS yang sah, kembalikan HANYA teks berikut tanpa penjelasan tambahan: DATA_TIDAK_MEMADAI_MOHON_LENGKAPI
5. Gunakan kata kerja dalam bentuk lampau (telah, menyatakan, menerangkan, mengajukan, membenarkan, dll.) sesuai konvensi BAS.
PROMPT;
    }

    /**
     * Susun System Prompt untuk PAK Reviewer.
     */
    private function buildReviewerSystemPrompt(): string
    {
        return <<<PROMPT
PERAN ANDA:
Anda adalah "PAK Reviewer", seorang Hakim Senior berpengalaman dengan keahlian dalam penulisan dan penelaahan Berita Acara Sidang (BAS) sesuai standar Mahkamah Agung Republik Indonesia.

TUGAS ANDA:
Tinjau draf BAS berikut secara kritis. Identifikasi kesalahan ejaan, inkonsistensi fakta hukum, penggunaan istilah yang tidak baku, atau kalimat yang kurang formal.

FORMAT OUTPUT WAJIB (ikuti persis, termasuk separator ---):
---TEMUAN---
[Daftar temuan spesifik dengan nomor urut. Jika tidak ada temuan: tulis "Tidak ada temuan kritis."]
---REKOMENDASI---
[Saran perbaikan konkret per temuan yang disebutkan di atas.]
---VERSI_PERBAIKAN---
[Teks narasi BAS lengkap yang sudah diperbaiki dan siap digunakan.]

ATURAN KETAT:
1. ZERO HALLUCINATION: Dilarang menambahkan informasi yang tidak ada dalam draf asli.
2. Fokus pada: ejaan KBBI, konsistensi istilah hukum (MA RI), kepaduan antar kalimat, dan struktur narasi BAS yang baku.
3. Output VERSI_PERBAIKAN harus berupa paragraf naratif murni — TANPA bullet, list, atau markdown.
PROMPT;
    }

    /**
     * Parse output Reviewer yang memiliki format ---SEKSI--- menjadi array terstruktur.
     */
    private function parseReviewerOutput(string $rawOutput): array
    {
        $sections = [
            'temuan'          => '',
            'rekomendasi'     => '',
            'versi_perbaikan' => '',
        ];

        $pattern = '/---TEMUAN---(.*?)---REKOMENDASI---(.*?)---VERSI_PERBAIKAN---(.*)/s';

        if (preg_match($pattern, $rawOutput, $matches)) {
            $sections['temuan']          = trim($matches[1]);
            $sections['rekomendasi']     = trim($matches[2]);
            $sections['versi_perbaikan'] = trim($matches[3]);
        } else {
            // Fallback: jika format tidak sesuai, masukkan seluruh output ke versi_perbaikan
            Log::warning('[PakPP] Output reviewer tidak mengikuti format separator yang diharapkan.');
            $sections['versi_perbaikan'] = trim($rawOutput);
            $sections['temuan']          = 'Format output AI tidak standar. Lihat tab Versi Perbaikan.';
        }

        return $sections;
    }
}

// developed by dbprakom™
