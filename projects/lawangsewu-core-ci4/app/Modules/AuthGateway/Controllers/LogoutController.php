<?php

declare(strict_types=1);

namespace App\Modules\AuthGateway\Controllers;

use CodeIgniter\Controller;

final class LogoutController extends Controller
{
    public function index()
    {
        return $this->response->setStatusCode(501)->setJSON([
            'module' => 'AuthGateway',
            'action' => 'logout',
            'status' => 'not-implemented',
            'note' => 'Logout extraction has not been switched on yet. Use legacy gateway logout in production.',
        ]);
    }
}