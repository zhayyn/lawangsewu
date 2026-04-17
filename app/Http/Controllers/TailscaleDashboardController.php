<?php

namespace App\Http\Controllers;

use App\Services\TailscaleService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * TailscaleDashboardController
 *
 * Mengelola halaman Tailscale Network Control:
 *  - index()          : halaman utama (Inertia/Vue)
 *  - networkStatus()  : snapshot semua device + reachable check
 *  - deviceDetail()   : detail + service check satu device
 *  - pingAll()        : ping ICMP semua device
 *  - tailscaleInfo()  : info dari tailscale CLI
 *
 * Hanya dapat diakses superadmin.
 * developed by zhayyn™
 */
class TailscaleDashboardController extends Controller
{
    public function __construct(private readonly TailscaleService $tailscale) {}

    /**
     * Halaman utama — render via Inertia.
     */
    public function index(): Response
    {
        return Inertia::render('Tailscale/Index', [
            'appMeta'       => LawangsewuPortal::appMeta(),
            'navGroups'     => LawangsewuPortal::navGroups(),
            'devices'       => $this->tailscale->allDevices(),
            'subnet'        => $this->tailscale->advertisedSubnet(),
            'tailscaleUp'   => $this->tailscale->tailscaleUpCommand(),
            'tailscaleInfo' => $this->tailscale->tailscaleInfo(),
        ]);
    }

    /**
     * Snapshot status semua device (TCP probe semua service).
     */
    public function networkStatus(): JsonResponse
    {
        $snapshot = $this->tailscale->networkSnapshot();

        $reachable   = count(array_filter($snapshot, fn($d) => $d['reachable']));
        $unreachable = count($snapshot) - $reachable;

        return response()->json([
            'status'  => 'ok',
            'devices' => $snapshot,
            'summary' => [
                'total'       => count($snapshot),
                'reachable'   => $reachable,
                'unreachable' => $unreachable,
            ],
            'checked_at' => now()->setTimezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
        ]);
    }

    /**
     * Detail status satu device dengan semua service-nya.
     */
    public function deviceDetail(string $deviceKey): JsonResponse
    {
        try {
            return response()->json($this->tailscale->deviceStatus($deviceKey));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Ping ICMP semua device — return latency ms.
     */
    public function pingAll(): JsonResponse
    {
        $results = $this->tailscale->pingAll();

        return response()->json([
            'status'     => 'ok',
            'pings'      => $results,
            'checked_at' => now()->setTimezone('Asia/Jakarta')->format('H:i:s') . ' WIB',
        ]);
    }

    /**
     * Info dari tailscale CLI (status --json).
     */
    public function tailscaleCliInfo(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'info'   => $this->tailscale->tailscaleInfo(),
        ]);
    }
}
