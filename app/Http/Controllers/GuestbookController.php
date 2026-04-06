<?php

namespace App\Http\Controllers;

use App\Models\GuestbookEntry;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GuestbookController extends Controller
{
    public function form()
    {
        $instansiRows = GuestbookEntry::query()
            ->select('institution')
            ->whereNotNull('institution')
            ->whereRaw("TRIM(institution) <> ''")
            ->whereRaw("TRIM(institution) <> '-'")
            ->distinct()
            ->orderBy('institution')
            ->limit(1000)
            ->get();

        $instansiOptionsByCategory = [
            'MAHKAMAH_AGUNG' => [
                'Pengadilan Agama (PA)',
                'Pengadilan Tinggi Agama (PTA)',
                'Pengadilan Negeri (PN)',
            ],
            'INSTANSI_PERUSAHAAN' => [
                'Pemerintah Kota Semarang',
                'Instansi Pemerintah Lainnya',
            ],
            'UNIVERSITAS_SEKOLAH' => [
                'Mahasiswa',
                'Universitas / Sekolah',
            ],
            'PERSEORANGAN' => [
                'Perseorangan',
            ],
        ];

        foreach ($instansiRows as $row) {
            $normalized = $this->normalizeInstansiUnit((string) $row->institution);
            if ($normalized === '') {
                continue;
            }

            $category = $this->categorizeInstansi($normalized);
            if ($category !== 'MAHKAMAH_AGUNG') {
                continue;
            }

            if (! $this->isMahkamahCourtLabel($normalized)) {
                continue;
            }

            $instansiOptionsByCategory[$category][] = $normalized;
        }

        foreach ($instansiOptionsByCategory as $category => $options) {
            $options = array_values(array_unique(array_filter($options, static fn (string $value) => trim($value) !== '')));

            if ($category === 'MAHKAMAH_AGUNG') {
                $options = $this->sortMahkamahOptions($options);
            } else {
                natcasesort($options);
                $options = array_values($options);
            }

            $instansiOptionsByCategory[$category] = $options;
        }

        $jakartaNow = now('Asia/Jakarta');

        return view('guestbook.form', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'idTamu' => $jakartaNow->format('YmdHis') . random_int(100, 999),
            'instansiOptionsByCategory' => $instansiOptionsByCategory,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', 'max:32', Rule::unique('guestbook_entries', 'id')],
            'nama' => ['required', 'string', 'max:120'],
            'jabatan' => ['required', 'string', 'max:120'],
            'kategori_instansi' => ['required', Rule::in(['MAHKAMAH_AGUNG', 'INSTANSI_PERUSAHAAN', 'UNIVERSITAS_SEKOLAH', 'PERSEORANGAN'])],
            'instansi' => ['required', 'string', 'max:160'],
            'keperluan' => ['required', 'string', 'max:255'],
            'foto' => ['required', 'string'],
        ], [
            'id.unique' => 'ID tamu sudah terpakai. Silakan refresh halaman lalu coba lagi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Mohon lengkapi data tamu.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $fotoBase64 = (string) $request->string('foto');
        if (! preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $fotoBase64)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format foto tidak valid.',
                'errors' => ['foto' => 'Data foto harus Base64 image.'],
            ], 422);
        }

        $cleanBase64 = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $fotoBase64) ?? '';
        $cleanBase64 = str_replace(' ', '+', $cleanBase64);
        $imgData = base64_decode($cleanBase64, true);

        if ($imgData === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses foto.',
                'errors' => ['foto' => 'Data Base64 tidak dapat diproses.'],
            ], 422);
        }

        $entry = GuestbookEntry::query()->create([
            'id' => (string) $request->string('id'),
            'name' => $this->normalizeDisplayCase((string) $request->string('nama')),
            'position' => $this->normalizeDisplayCase((string) $request->string('jabatan')),
            'institution_category' => (string) $request->string('kategori_instansi'),
            'institution' => $this->normalizeDisplayCase((string) $request->string('instansi')),
            'purpose' => $this->normalizeDisplayCase((string) $request->string('keperluan')),
            'checkin' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

        $photoDirectory = public_path('guestbook/photos');
        if (! File::exists($photoDirectory)) {
            File::makeDirectory($photoDirectory, 0775, true);
        }

        $saved = file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . $entry->id . '.jpg', $imgData);
        if ($saved === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tamu tersimpan, tetapi foto gagal disimpan.',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'jumlah' => GuestbookEntry::query()->count(),
        ]);
    }

    public function listing(string $period = 'all')
    {
        $allowedPeriods = ['all', 'day', 'month', 'year'];
        if (! in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        $titleByPeriod = [
            'all' => 'Daftar Seluruh Tamu',
            'day' => 'Daftar Tamu Hari Ini',
            'month' => 'Daftar Tamu Bulan Ini',
            'year' => 'Daftar Tamu Tahun Ini',
        ];

        $query = GuestbookEntry::query()->orderByDesc('checkin');
        $now = now('Asia/Jakarta');

        if ($period === 'day') {
            $query->whereBetween('checkin', [$now->copy()->startOfDay(), $now->copy()->endOfDay()]);
        } elseif ($period === 'month') {
            $query->whereBetween('checkin', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
        } elseif ($period === 'year') {
            $query->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()]);
        }

        $perPage = 10;
        $entries = $query->paginate($perPage)->withQueryString();

        $statsAll = GuestbookEntry::query()->count();
        $statsDay = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count();
        $statsMonth = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count();
        $statsYear = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count();

        return view('guestbook.list', [
            'entries' => $entries,
            'period' => $period,
            'periodTitle' => $titleByPeriod[$period],
            'stats' => [
                'all' => $statsAll,
                'day' => $statsDay,
                'month' => $statsMonth,
                'year' => $statsYear,
            ],
        ]);
    }

    public function detail(string $id)
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        return view('guestbook.detail', [
            'entry' => $entry,
        ]);
    }

    public function printCard(string $id)
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        return view('guestbook.cetak', [
            'entry' => $entry,
            'row' => (int) request()->query('row', 1),
        ]);
    }

    public function report(Request $request)
    {
        $bulan = (int) ($request->input('bulan', date('m')));
        $tahun = (int) ($request->input('tahun', date('Y')));

        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) date('m');
        }

        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        $start = Carbon::create($tahun, $bulan, 1, 0, 0, 0, 'Asia/Jakarta');
        $end = $start->copy()->endOfMonth();

        $entries = GuestbookEntry::query()
            ->whereBetween('checkin', [$start, $end])
            ->orderBy('checkin')
            ->get();

        $bulanMap = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $payload = [
            'judul' => 'LAPORAN PENERIMAAN TAMU',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'namaBulan' => $bulanMap[$bulan],
            'entries' => $entries,
        ];

        if ($request->query('export') === 'xls') {
            return response()
                ->view('guestbook.laporan_xls', $payload)
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename=laporan_tamu_' . $tahun . '_' . sprintf('%02d', $bulan) . '.xls');
        }

        return response()->view('guestbook.laporan', $payload, Response::HTTP_OK);
    }

    private function normalizeInstansiUnit(string $instansi): string
    {
        $label = trim((string) preg_replace('/\s+/', ' ', $instansi));
        if ($label === '') {
            return '';
        }

        $upper = strtoupper($label);

        if (preg_match('/\b(MAHASISWA|MHS|UNIVERSITAS|UNIV|KAMPUS)\b/u', $upper)) {
            return 'Mahasiswa';
        }

        if (preg_match('/(?:^|\b)PTA\b\s*(.*)$/u', $upper, $m) || preg_match('/PENGADILAN TINGGI AGAMA\s*(.*)$/u', $upper, $m)) {
            $suffix = trim((string) ($m[1] ?? ''), " -.,");

            return $suffix !== ''
                ? 'Pengadilan Tinggi Agama ' . ucwords(strtolower($suffix)) . ' (PTA)'
                : 'Pengadilan Tinggi Agama (PTA)';
        }

        if (preg_match('/(?:^|\b)PA\b\s*(.*)$/u', $upper, $m) || preg_match('/PENGADILAN AGAMA\s*(.*)$/u', $upper, $m)) {
            $suffix = trim((string) ($m[1] ?? ''), " -.,");

            return $suffix !== ''
                ? 'Pengadilan Agama ' . ucwords(strtolower($suffix)) . ' (PA)'
                : 'Pengadilan Agama (PA)';
        }

        if (preg_match('/(?:^|\b)PN\b\s*(.*)$/u', $upper, $m) || preg_match('/PENGADILAN NEGERI\s*(.*)$/u', $upper, $m)) {
            $suffix = trim((string) ($m[1] ?? ''), " -.,");

            return $suffix !== ''
                ? 'Pengadilan Negeri ' . ucwords(strtolower($suffix)) . ' (PN)'
                : 'Pengadilan Negeri (PN)';
        }

        return ucwords(strtolower($label));
    }

    private function categorizeInstansi(string $instansi): string
    {
        $upper = strtoupper($instansi);

        if (
            preg_match('/\b(PENGADILAN|PA|PTA|PN|MAHKAMAH)\b/u', $upper) ||
            $this->looksLikeCityOrRegion($upper)
        ) {
            return 'MAHKAMAH_AGUNG';
        }

        if (preg_match('/\b(MAHASISWA|MHS|UNIVERSITAS|UNIV|SEKOLAH|SMA|SMK|SMP|SD|MADRASAH|POLITEKNIK|POLTEK|AKADEMI|KAMPUS)\b/u', $upper)) {
            return 'UNIVERSITAS_SEKOLAH';
        }

        if (preg_match('/\b(PT\.?|CV\.?|UD\.?|TBK|PERSERO|PERUSAHAAN|INSTANSI|DINAS|KEMENTERIAN|PEMERINTAH|PEMDA|KANTOR|BANK|YAYASAN|RUMAH\s+SAKIT|RS\.?|BUMN|BUMD)\b/u', $upper)) {
            return 'INSTANSI_PERUSAHAAN';
        }

        return 'PERSEORANGAN';
    }

    private function looksLikeCityOrRegion(string $upper): bool
    {
        $cityKeywords = [
            'SEMARANG', 'JAKARTA', 'BOGOR', 'DEMAK', 'JEPARA', 'SALATIGA', 'SRAGEN',
            'SUKOHARJO', 'TEMANGGUNG', 'PURBALINGGA', 'PURWODADI', 'BANJARNEGARA',
            'KARANGANYAR', 'MAGETAN', 'LUMAJANG', 'AMBARAWA', 'WAMENA', 'SURABAYA',
            'BANDUNG', 'YOGYAKARTA', 'SOLO', 'MAKASSAR', 'MEDAN', 'BATANG', 'KUDUS',
            'PATI', 'TEGAL', 'PEKALONGAN', 'CILACAP', 'KENDAL', 'BLORA', 'REMBANG',
        ];

        foreach ($cityKeywords as $city) {
            if (str_contains($upper, $city)) {
                return true;
            }
        }

        return false;
    }

    private function isMahkamahCourtLabel(string $instansi): bool
    {
        $upper = strtoupper($instansi);

        return (bool) preg_match('/\b(PENGADILAN|PA|PTA|PN|MAHKAMAH)\b/u', $upper);
    }

    private function normalizeDisplayCase(string $value): string
    {
        $clean = trim((string) preg_replace('/\s+/', ' ', $value));
        if ($clean === '') {
            return '';
        }

        if (function_exists('mb_convert_case')) {
            $clean = mb_convert_case(mb_strtolower($clean, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        } else {
            $clean = ucwords(strtolower($clean));
        }

        $clean = preg_replace('/\bPa\b/u', 'PA', $clean) ?? $clean;
        $clean = preg_replace('/\bPn\b/u', 'PN', $clean) ?? $clean;
        $clean = preg_replace('/\bPta\b/u', 'PTA', $clean) ?? $clean;

        return $clean;
    }

    private function sortMahkamahOptions(array $options): array
    {
        usort($options, function (string $a, string $b): int {
            $rankA = $this->mahkamahTypeRank($a);
            $rankB = $this->mahkamahTypeRank($b);

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcasecmp($a, $b);
        });

        return array_values($options);
    }

    private function mahkamahTypeRank(string $label): int
    {
        $upper = strtoupper($label);

        if (str_contains($upper, '(PA)') || str_contains($upper, 'PENGADILAN AGAMA')) {
            return 1;
        }

        if (str_contains($upper, '(PN)') || str_contains($upper, 'PENGADILAN NEGERI')) {
            return 2;
        }

        if (str_contains($upper, '(PTA)') || str_contains($upper, 'PENGADILAN TINGGI AGAMA')) {
            return 3;
        }

        return 9;
    }
}
