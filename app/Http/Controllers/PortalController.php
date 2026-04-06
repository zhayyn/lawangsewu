<?php

namespace App\Http\Controllers;

use App\Support\LawangsewuPortal;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Lawangsewu/Dashboard', LawangsewuPortal::dashboardPayload());
    }

    public function cctv(): Response
    {
        return Inertia::render('Lawangsewu/Cctv', LawangsewuPortal::cctvPayload());
    }

    public function chat(): Response
    {
        return Inertia::render('Lawangsewu/Chat', LawangsewuPortal::chatPayload());
    }
}
