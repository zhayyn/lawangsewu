<?php

namespace App\Http\Controllers;

use App\Support\LawangsewuPortal;
use Inertia\Inertia;
use Inertia\Response;

class SatelliteController extends Controller
{
    /**
     * Display the Pendopo guestbook in a Lawangsewu frame.
     */
    public function pendopo(): Response
    {
        return Inertia::render('Lawangsewu/Satellite/Pendopo', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            // Assuming pendopo is on same domain, relative path or full URL
            'pendopoUrl' => '/pendopo', 
        ]);
    }
}
