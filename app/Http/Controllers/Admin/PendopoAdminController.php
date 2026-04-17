<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuestbookEntry;
use App\Models\GuestbookSetting;
use App\Services\LegacyPendopoSyncService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class PendopoAdminController extends Controller
{
    public function index(Request $request, LegacyPendopoSyncService $service): Response
    {
        $settings = GuestbookSetting::query()->firstOrCreate(
            ['id' => '1'],
            [
                'per_page' => 10,
                'require_identity_fields' => true,
                'event_name' => 'Pendopo Pengadilan Agama Semarang',
            ]
        );

        $summary = $service->dashboardSummary();
        $legacyStatus = $service->legacyStatus();

        return Inertia::render('Admin/PendopoManager', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'status' => $request->session()->get('status'),
            'settings' => [
                'per_page' => $settings->per_page,
                'require_identity_fields' => $settings->require_identity_fields,
                'event_name' => $settings->event_name,
            ],
            'stats' => $summary['stats'],
            'monthlySummary' => $summary['monthly_summary'],
            'recentEntries' => $summary['recent_entries'],
            'photoCount' => $summary['photo_count'],
            'legacy' => [
                ...$summary['legacy'],
                ...$legacyStatus,
            ],
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'per_page' => ['required', 'integer', 'min:5', 'max:100'],
            'require_identity_fields' => ['required', 'boolean'],
            'event_name' => ['nullable', 'string', 'max:255'],
        ]);

        GuestbookSetting::query()->updateOrCreate(
            ['id' => '1'],
            $payload
        );

        return back()->with('status', 'Pengaturan Pendopo berhasil diperbarui.');
    }

    public function syncLegacy(LegacyPendopoSyncService $service): RedirectResponse
    {
        $result = $service->syncAll();

        return back()->with('status', sprintf(
            'Sinkronisasi legacy Pendopo selesai. %d data tamu dan %d foto diproses.',
            $result['entries'],
            $result['photos']
        ));
    }

    public function destroyEntry(GuestbookEntry $entry): RedirectResponse
    {
        foreach (['jpg', 'jpeg', 'png'] as $extension) {
            $path = public_path('guestbook/photos/' . $entry->id . '.' . $extension);
            if (is_file($path)) {
                File::delete($path);
            }
        }

        $entry->delete();

        return back()->with('status', 'Data tamu Pendopo berhasil dihapus.');
    }
}
