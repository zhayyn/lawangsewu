<?php

namespace App\Http\Controllers;

use App\Services\LegacyPendopoSyncService;
use App\Support\LawangsewuPortal;
use Inertia\Inertia;
use Inertia\Response;

class SatelliteController extends Controller
{
    /**
     * Display the Pendopo guestbook in a Lawangsewu frame.
     */
    public function pendopo(LegacyPendopoSyncService $service): Response
    {
        $summary = $service->dashboardSummary();

        return Inertia::render('Lawangsewu/Satellite/Pendopo', [
            'appMeta'    => LawangsewuPortal::appMeta(),
            'navGroups'  => LawangsewuPortal::navGroups(),
            'pendopoUrl' => route('lawangsewu.guestbook.form') . '?embedded=1',
            'stats'      => $summary['stats'],
            'guestbookListUrl' => route('lawangsewu.guestbook.list', ['period' => 'all']),
        ]);
    }
}
