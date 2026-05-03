<?php

namespace App\Http\Controllers;

use App\Models\GuestbookEntry;
use App\Models\GuestbookSetting;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class GuestbookController extends Controller
{
    private const INSTANSI_OPTIONS_CACHE_KEY = 'guestbook:instansi-options:v2';

    private function noStoreView(string $view, array $data = [], int $status = Response::HTTP_OK)
    {
        return response()
            ->view($view, $data, $status)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('X-Guestbook-Version', 'file-only-v3');
    }

    public function form()
    {
        $settings = $this->settings();

        $instansiOptionsByCategory = Cache::remember(self::INSTANSI_OPTIONS_CACHE_KEY, now()->addMinutes(15), function () {
            return $this->buildInstansiOptionsByCategory();
        });

        $jakartaNow = now('Asia/Jakarta');

        return $this->noStoreView('guestbook.form', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'idTamu' => $jakartaNow->format('YmdHis') . random_int(100, 999),
            'instansiOptionsByCategory' => $instansiOptionsByCategory,
            'settings' => $settings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('Guestbook store request received.', [
            'user_id' => optional($request->user())->id,
            'content_type' => (string) $request->header('Content-Type', ''),
            'has_foto' => $request->filled('foto'),
            'has_foto_file' => $request->hasFile('foto_file'),
            'foto_file_mime' => optional($request->file('foto_file'))->getMimeType(),
            'foto_file_size' => optional($request->file('foto_file'))->getSize(),
        ]);

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'string', 'max:32'],
            'nama' => ['required', 'string', 'max:120'],
            'jabatan' => ['required', 'string', 'max:120'],
            'kategori_instansi' => ['required', Rule::in(['MAHKAMAH_AGUNG', 'INSTANSI_PERUSAHAAN', 'UNIVERSITAS_SEKOLAH', 'PERSEORANGAN'])],
            'instansi' => ['required', 'string', 'max:160'],
            'keperluan' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'string', 'required_without:foto_file'],
            'foto_file' => ['nullable', 'file', 'required_without:foto', 'max:5120'],
        ], [
            'id.unique' => 'ID tamu sudah terpakai. Silakan refresh halaman lalu coba lagi.',
            'foto.required_without' => 'Foto wajib diisi.',
            'foto_file.required_without' => 'Foto wajib diisi.',
            'foto_file.max' => 'Ukuran file foto maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Mohon lengkapi data tamu.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        [$imgData, $photoExt, $photoError] = $this->extractImagePayload($request);
        if ($photoError !== null || $imgData === null || $photoExt === null) {
            return response()->json([
                'status' => 'error',
                'message' => $photoError ?? 'Format foto tidak valid.',
                'errors' => ['foto' => $photoError ?? 'Data foto harus Base64 image atau file gambar.'],
            ], 422);
        }

        if (strlen($imgData) > 5 * 1024 * 1024) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ukuran foto terlalu besar.',
                'errors' => ['foto' => 'Ukuran foto maksimal 5 MB.'],
            ], 422);
        }

        $entryId = $this->resolveEntryId((string) $request->string('id'));

        $entry = GuestbookEntry::query()->create([
            'id' => $entryId,
            'name' => $this->normalizeDisplayCase((string) $request->string('nama')),
            'position' => $this->normalizeDisplayCase((string) $request->string('jabatan')),
            'institution_category' => (string) $request->string('kategori_instansi'),
            'institution' => $this->normalizeDisplayCase((string) $request->string('instansi')),
            'purpose' => $this->normalizeDisplayCase((string) $request->string('keperluan')),
            'checkin' => now('Asia/Jakarta')->format('Y-m-d H:i:s'),
        ]);

        $storagePath = 'guestbook/photos/' . $entry->id . '.' . $photoExt;
        $savedToStorage = Storage::disk('public')->put($storagePath, $imgData);

        // Keep a mirror copy in public/guestbook/photos for compatibility on servers
        // where /storage symlink is unavailable or blocked.
        $savedToPublicMirror = false;
        $photoDirectory = public_path('guestbook/photos');
        if (! File::exists($photoDirectory)) {
            File::makeDirectory($photoDirectory, 0775, true);
        }

        $saved = file_put_contents($photoDirectory . DIRECTORY_SEPARATOR . $entry->id . '.' . $photoExt, $imgData);
        $savedToPublicMirror = $saved !== false;

        if (! $savedToStorage && ! $savedToPublicMirror) {
            $entry->delete();

            return response()->json([
                'status' => 'error',
                'message' => 'Penyimpanan foto gagal. Silakan coba lagi.',
            ], 500);
        }

        Cache::forget(self::INSTANSI_OPTIONS_CACHE_KEY);

        return response()->json([
            'status' => 'success',
            'jumlah' => GuestbookEntry::query()->count(),
        ]);
    }

    private function resolveEntryId(string $requestedId): string
    {
        $entryId = trim($requestedId);
        if ($entryId === '') {
            $entryId = now('Asia/Jakarta')->format('YmdHisv') . random_int(1000, 9999);
        }

        if (! GuestbookEntry::query()->whereKey($entryId)->exists()) {
            return $entryId;
        }

        do {
            $entryId = now('Asia/Jakarta')->format('YmdHisv') . random_int(1000, 9999);
        } while (GuestbookEntry::query()->whereKey($entryId)->exists());

        return $entryId;
    }

    private function extractImagePayload(Request $request): array
    {
        $fotoFile = $request->file('foto_file');
        if ($fotoFile instanceof UploadedFile) {
            if (! $fotoFile->isValid()) {
                return [null, null, 'File foto tidak valid.'];
            }

            $mimeType = (string) $fotoFile->getMimeType();
            $ext = $this->normalizePhotoExtension($mimeType);
            if ($ext === null) {
                return [null, null, 'Format file foto harus JPG atau PNG.'];
            }

            $binary = $fotoFile->getContent();
            if (! is_string($binary) || $binary === '') {
                return [null, null, 'Konten file foto kosong.'];
            }

            return [$binary, $ext, null];
        }

        $fotoBase64 = trim((string) $request->string('foto'));
        if ($fotoBase64 === '') {
            return [null, null, 'Foto wajib diisi.'];
        }

        $ext = 'jpg';
        if (preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/', $fotoBase64, $matches) === 1) {
            $ext = strtolower((string) ($matches[1] ?? 'jpg'));
            $fotoBase64 = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/', '', $fotoBase64) ?? '';
        }

        $fotoBase64 = str_replace(' ', '+', $fotoBase64);
        $imgData = base64_decode($fotoBase64, true);
        if (! is_string($imgData)) {
            return [null, null, 'Data Base64 foto tidak dapat diproses.'];
        }

        if ($ext === 'png') {
            return [$imgData, 'png', null];
        }

        if ($ext === 'webp') {
            return [$imgData, 'webp', null];
        }

        return [$imgData, 'jpg', null];
    }

    private function normalizePhotoExtension(string $mimeType): ?string
    {
        $normalized = strtolower(trim($mimeType));

        if ($normalized === 'image/png') {
            return 'png';
        }

        if ($normalized === 'image/webp') {
            return 'webp';
        }

        if (in_array($normalized, ['image/jpeg', 'image/jpg'], true)) {
            return 'jpg';
        }

        return null;
    }

    public function listing(string $period = 'all')
    {
        $settings = $this->settings();
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

        $perPage = max(5, min(100, (int) ($settings->per_page ?? 10)));
        $entries = $query->paginate($perPage)->withQueryString();

        $statsAll = GuestbookEntry::query()->count();
        $statsDay = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count();
        $statsWeek = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)])->count();
        $statsMonth = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count();
        $statsYear = GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count();

        return $this->noStoreView('guestbook.list', [
            'entries' => $entries,
            'period' => $period,
            'periodTitle' => $titleByPeriod[$period],
            'settings' => $settings,
            'stats' => [
                'all' => $statsAll,
                'day' => $statsDay,
                'week' => $statsWeek,
                'month' => $statsMonth,
                'year' => $statsYear,
            ],
        ]);
    }

    public function detail(string $id)
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        return $this->noStoreView('guestbook.detail', [
            'entry' => $entry,
        ]);
    }

    public function printCard(string $id)
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        return $this->noStoreView('guestbook.cetak', [
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

        return $this->noStoreView('guestbook.laporan', $payload, Response::HTTP_OK);
    }

    public function manage()
    {
        $settings = $this->settings();
        $now = now('Asia/Jakarta');
        $entries = GuestbookEntry::query()
            ->orderByDesc('checkin')
            ->paginate($settings->per_page, ['*'], 'page', request()->query('page', 1));

        $stats = [
            'all' => GuestbookEntry::query()->count(),
            'day' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])->count(),
            'week' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfWeek(Carbon::MONDAY), $now->copy()->endOfWeek(Carbon::SUNDAY)])->count(),
            'month' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(),
            'year' => GuestbookEntry::query()->whereBetween('checkin', [$now->copy()->startOfYear(), $now->copy()->endOfYear()])->count(),
        ];

        return $this->noStoreView('guestbook.manage', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'entries' => $entries,
            'settings' => $settings,
            'stats' => $stats,
        ]);
    }

    public function rename(Request $request, string $id): JsonResponse
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama' => ['required', 'string', 'max:120'],
        ], [
            'nama.required' => 'Nama tamu wajib diisi.',
            'nama.max'      => 'Nama tamu maksimal 120 karakter.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        $oldName = $entry->name;
        $entry->update([
            'name' => $this->normalizeDisplayCase((string) $request->string('nama')),
        ]);

        Log::info("Guestbook entry {$id} renamed by operator", [
            'user_id'  => optional(auth()->user())->id,
            'old_name' => $oldName,
            'new_name' => $entry->name,
        ]);

        return response()->json([
            'status'   => 'success',
            'message'  => 'Nama tamu berhasil diubah.',
            'new_name' => $entry->name,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $entry = GuestbookEntry::query()->findOrFail($id);

        // Delete photo files if they exist
        try {
            $photoPath = "guestbook/photos/{$id}.jpg";
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            if (File::exists(public_path("guestbook/photos/{$id}.jpg"))) {
                File::delete(public_path("guestbook/photos/{$id}.jpg"));
            }
        } catch (\Exception $e) {
            Log::warning("Failed to delete photo for guestbook entry {$id}", ['error' => $e->getMessage()]);
        }

        $entry->delete();

        Log::info("Guestbook entry {$id} deleted by operator", ['user_id' => optional(auth()->user())->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Data tamu berhasil dihapus.',
        ]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data ID tamu tidak valid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $ids = $request->input('ids');
        $entries = GuestbookEntry::query()->whereIn('id', $ids)->get();

        foreach ($entries as $entry) {
            try {
                $photoPath = "guestbook/photos/{$entry->id}.jpg";
                if (Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
                if (File::exists(public_path("guestbook/photos/{$entry->id}.jpg"))) {
                    File::delete(public_path("guestbook/photos/{$entry->id}.jpg"));
                }
            } catch (\Exception $e) {
                Log::warning("Failed to delete photo for guestbook entry {$entry->id}", ['error' => $e->getMessage()]);
            }

            $entry->delete();
        }

        Log::info("Bulk deleted {$entries->count()} guestbook entries", ['user_id' => optional(auth()->user())->id]);

        return response()->json([
            'status' => 'success',
            'message' => "Total {$entries->count()} data tamu berhasil dihapus.",
            'count' => $entries->count(),
        ]);
    }

    public function saveSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => ['required', 'integer', 'min:5', 'max:50'],
            'event_name' => ['required', 'string', 'max:120'],
            'require_identity_fields' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi pengaturan gagal.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $settings = $this->settings();
        $settings->update([
            'per_page' => $request->input('per_page'),
            'event_name' => $request->input('event_name'),
            'require_identity_fields' => $request->boolean('require_identity_fields'),
        ]);

        Log::info("Guestbook settings updated", [
            'user_id' => optional(auth()->user())->id,
            'per_page' => $settings->per_page,
            'event_name' => $settings->event_name,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaturan pendopo berhasil disimpan.',
            'settings' => $settings,
        ]);
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

    private function settings(): GuestbookSetting
    {
        return GuestbookSetting::query()->firstOrCreate(
            ['id' => '1'],
            [
                'per_page' => 10,
                'require_identity_fields' => true,
                'event_name' => 'Pendopo Pengadilan Agama Semarang',
            ]
        );
    }

    private function buildInstansiOptionsByCategory(): array
    {
        $instansiRows = GuestbookEntry::query()
            ->select('institution')
            ->whereNotNull('institution')
            ->where('institution', '!=', '')
            ->where('institution', '!=', '-')
            ->whereBetween('checkin', [now('Asia/Jakarta')->subYears(2), now('Asia/Jakarta')])
            ->orderByDesc('checkin')
            ->limit(600)
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

        return $instansiOptionsByCategory;
    }
}
