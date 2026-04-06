<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class SatelliteController extends Controller
{
    /**
     * Display the Pendopo guestbook in a Lawangsewu frame.
     */
    public function pendopo(): RedirectResponse
    {
        return redirect()->route('lawangsewu.guestbook.form');
    }
}
