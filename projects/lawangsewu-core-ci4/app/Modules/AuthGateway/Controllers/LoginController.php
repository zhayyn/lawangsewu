<?php

declare(strict_types=1);

namespace App\Modules\AuthGateway\Controllers;

use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use CodeIgniter\Controller;

final class LoginController extends Controller
{
    public function index()
    {
        $auth = new LegacyGatewayAuthBridge();
        $returnPath = $auth->normalizeReturnPath((string) ($this->request->getGet('return') ?? ''), '/portal');

        return $this->response->setJSON([
            'module' => 'AuthGateway',
            'action' => 'login-form',
            'status' => 'staging',
            'returnPath' => $returnPath,
            'note' => 'Staging controller only. Legacy gateway login remains active until extraction is complete.',
        ]);
    }

    public function authenticate()
    {
        return $this->response->setStatusCode(501)->setJSON([
            'module' => 'AuthGateway',
            'action' => 'authenticate',
            'status' => 'not-implemented',
            'note' => 'Login extraction has not been switched on yet. Use legacy gateway login in production.',
        ]);
    }
}