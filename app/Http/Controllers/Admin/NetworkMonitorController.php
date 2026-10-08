<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NetworkMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkMonitorController extends Controller
{
    public function __construct(
        private readonly NetworkMonitorService $networkMonitor
    ) {}

    public function index()
    {
        $snapshot = $this->networkMonitor->snapshot();

        return inertia('Admin/NetworkMonitor', [
            'appMeta' => [
                'title' => 'Monitor Jaringan',
                'description' => 'Monitoring bandwidth, traffic, dan stream CCTV real-time',
            ],
            'endpoints' => [
                'api' => route('admin.network-monitor.api'),
            ],
            'snapshot' => $snapshot,
            'navGroups' => $this->getNavGroups(),
        ]);
    }

    public function api(): JsonResponse
    {
        return response()->json($this->networkMonitor->snapshot());
    }

    public function interfaceDetails(string $interface): JsonResponse
    {
        $details = $this->networkMonitor->getInterfaceDetails($interface);
        return response()->json($details);
    }

    private function getNavGroups(): array
    {
        return \App\Support\LawangsewuPortal::navGroups();
    }
}
