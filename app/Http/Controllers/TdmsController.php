<?php

namespace App\Http\Controllers;

use App\Models\TdmsAsset;
use App\Models\TdmsCategory;
use App\Models\TdmsMaintenanceSchedule;
use App\Models\TdmsServiceRecord;
use App\Models\TdmsReplacement;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TdmsController extends Controller
{
    // ──────────────────────────────────────────────
    // Page Renderers
    // ──────────────────────────────────────────────

    public function index()
    {
        return Inertia::render('Lawangsewu/Tdms/Index', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'summary'   => $this->buildSummary(),
            'recentServiceRecords' => $this->recentServiceRecords(),
            'upcomingMaintenance'  => $this->upcomingMaintenance(),
            'assetsByCategory'     => $this->assetsByCategory(),
        ]);
    }

    public function assets(Request $request)
    {
        return Inertia::render('Lawangsewu/Tdms/Assets', [
            'appMeta'    => LawangsewuPortal::appMeta(),
            'navGroups'  => LawangsewuPortal::navGroups(),
            'assets'     => $this->getAssets($request),
            'categories' => TdmsCategory::orderBy('sort_order')->get(['id', 'name', 'icon', 'color'])->toArray(),
            'filters'    => $request->only(['search', 'category', 'status', 'location']),
        ]);
    }

    public function serviceRecords(Request $request)
    {
        return Inertia::render('Lawangsewu/Tdms/ServiceRecords', [
            'appMeta'       => LawangsewuPortal::appMeta(),
            'navGroups'     => LawangsewuPortal::navGroups(),
            'records'       => $this->getServiceRecords($request),
            'assets'        => TdmsAsset::where('status', '!=', 'retired')->get(['id', 'asset_code', 'name'])->toArray(),
            'filters'       => $request->only(['search', 'status', 'priority', 'asset_id']),
            'authUser'      => $request->user(),
        ]);
    }

    public function maintenance(Request $request)
    {
        return Inertia::render('Lawangsewu/Tdms/Maintenance', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'schedules' => $this->getSchedules($request),
            'assets'    => TdmsAsset::where('status', 'active')->get(['id', 'asset_code', 'name'])->toArray(),
            'filters'   => $request->only(['search', 'status', 'type']),
            'authUser'  => $request->user(),
        ]);
    }

    // ──────────────────────────────────────────────
    // API Endpoints
    // ──────────────────────────────────────────────

    public function api(Request $request, string $action)
    {
        $user = $request->user();

        $result = match ($action) {
            // Assets
            'create-asset'   => $this->createAsset($request),
            'update-asset'   => $this->updateAsset($request),
            'delete-asset'   => $this->deleteAsset($request, $user),

            // Service Records
            'create-service-record' => $this->createServiceRecord($request, $user),
            'update-service-record' => $this->updateServiceRecord($request, $user),
            'close-service-record'  => $this->closeServiceRecord($request, $user),

            // Maintenance
            'create-schedule'  => $this->createSchedule($request, $user),
            'complete-schedule' => $this->completeSchedule($request, $user),

            // Replacements
            'submit-replacement' => $this->submitReplacement($request, $user),
            'approve-replacement' => $this->approveReplacement($request, $user),

            // Categories
            'create-category' => $this->createCategory($request, $user),

            // Summary data
            'summary' => ['ok' => true, 'status' => 200, 'data' => $this->buildSummary()],

            // QR asset lookup
            'asset-by-qr' => $this->assetByQr($request),

            default => ['ok' => false, 'status' => 404, 'error' => 'Aksi tidak valid.'],
        };

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok'    => false,
                'error' => $result['error'] ?? 'Terjadi kesalahan.',
            ], $result['status'] ?? 422);
        }

        return response()->json($result['data'] ?? ['ok' => true], $result['status'] ?? 200);
    }

    // ──────────────────────────────────────────────
    // Asset QR Code — public (non-auth) detail scan
    // ──────────────────────────────────────────────

    public function assetQrDetail(string $token)
    {
        $asset = TdmsAsset::where('qr_token', $token)
            ->with(['category', 'serviceRecords' => fn ($q) => $q->latest()->limit(5), 'maintenanceSchedules' => fn ($q) => $q->latest()->limit(3)])
            ->firstOrFail();

        return Inertia::render('Lawangsewu/Tdms/AssetDetail', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'asset'     => $this->formatAsset($asset),
        ]);
    }

    // ──────────────────────────────────────────────
    // Private: Query Builders
    // ──────────────────────────────────────────────

    private function buildSummary(): array
    {
        if (!Schema::hasTable('tdms_assets')) {
            return $this->emptySummary();
        }

        $totalAssets     = TdmsAsset::count();
        $activeAssets    = TdmsAsset::where('status', 'active')->count();
        $brokenAssets    = TdmsAsset::where('status', 'broken')->count();
        $maintenanceAssets = TdmsAsset::where('status', 'maintenance')->count();

        $openTickets      = TdmsServiceRecord::open()->count();
        $breachedTickets  = TdmsServiceRecord::breached()->count();
        $overdueSchedules = TdmsMaintenanceSchedule::overdue()->count();
        $dueTodaySchedules = TdmsMaintenanceSchedule::dueToday()->count();
        $pendingReplacements = TdmsReplacement::pending()->count();

        return [
            'assets' => [
                'total'       => $totalAssets,
                'active'      => $activeAssets,
                'broken'      => $brokenAssets,
                'maintenance' => $maintenanceAssets,
            ],
            'tickets' => [
                'open'     => $openTickets,
                'breached' => $breachedTickets,
            ],
            'maintenance' => [
                'overdue'  => $overdueSchedules,
                'dueToday' => $dueTodaySchedules,
            ],
            'replacements' => [
                'pending' => $pendingReplacements,
            ],
        ];
    }

    private function emptySummary(): array
    {
        return [
            'assets'       => ['total' => 0, 'active' => 0, 'broken' => 0, 'maintenance' => 0],
            'tickets'      => ['open' => 0, 'breached' => 0],
            'maintenance'  => ['overdue' => 0, 'dueToday' => 0],
            'replacements' => ['pending' => 0],
        ];
    }

    private function recentServiceRecords(): array
    {
        if (!Schema::hasTable('tdms_service_records')) return [];
        return TdmsServiceRecord::with(['asset:id,asset_code,name', 'reporter:id,name,alias'])
            ->latest()->limit(8)->get()
            ->map(fn ($r) => $this->formatServiceRecord($r))
            ->toArray();
    }

    private function upcomingMaintenance(): array
    {
        if (!Schema::hasTable('tdms_maintenance_schedules')) return [];
        return TdmsMaintenanceSchedule::with('asset:id,asset_code,name')
            ->dueThisWeek()->orderBy('due_date')->limit(8)->get()
            ->map(fn ($s) => $this->formatSchedule($s))
            ->toArray();
    }

    private function assetsByCategory(): array
    {
        if (!Schema::hasTable('tdms_assets')) return [];
        return TdmsCategory::withCount(['assets', 'assets as active_assets_count' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')->get()
            ->map(fn ($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'icon'         => $c->icon,
                'color'        => $c->color,
                'total'        => $c->assets_count,
                'active'       => $c->active_assets_count,
            ])->toArray();
    }

    private function getAssets(Request $request): array
    {
        if (!Schema::hasTable('tdms_assets')) return [];

        $query = TdmsAsset::with('category:id,name,icon,color')
            ->orderBy('asset_code');

        if ($s = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('asset_code', 'like', "%{$s}%")
                ->orWhere('brand', 'like', "%{$s}%")
                ->orWhere('location', 'like', "%{$s}%")
                ->orWhere('assigned_to', 'like', "%{$s}%"));
        }
        if ($c = $request->query('category')) {
            $query->where('category_id', $c);
        }
        if ($st = $request->query('status')) {
            $query->where('status', $st);
        }
        if ($l = $request->query('location')) {
            $query->where('location', 'like', "%{$l}%");
        }

        return $query->get()->map(fn ($a) => $this->formatAsset($a))->toArray();
    }

    private function getServiceRecords(Request $request): array
    {
        if (!Schema::hasTable('tdms_service_records')) return [];

        $query = TdmsServiceRecord::with(['asset:id,asset_code,name', 'reporter:id,name,alias', 'technician:id,name,alias'])
            ->latest();

        if ($s = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('ticket_number', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%"));
        }
        if ($st = $request->query('status')) {
            $query->where('status', $st);
        }
        if ($p = $request->query('priority')) {
            $query->where('priority', $p);
        }
        if ($a = $request->query('asset_id')) {
            $query->where('asset_id', $a);
        }

        return $query->limit(50)->get()
            ->map(fn ($r) => $this->formatServiceRecord($r))
            ->toArray();
    }

    private function getSchedules(Request $request): array
    {
        if (!Schema::hasTable('tdms_maintenance_schedules')) return [];

        $query = TdmsMaintenanceSchedule::with(['asset:id,asset_code,name', 'assignedUser:id,name,alias'])
            ->orderBy('due_date');

        if ($s = $request->query('search')) {
            $query->where('title', 'like', "%{$s}%");
        }
        if ($st = $request->query('status')) {
            $query->where('status', $st);
        }
        if ($t = $request->query('type')) {
            $query->where('maintenance_type', $t);
        }

        return $query->limit(50)->get()
            ->map(fn ($s) => $this->formatSchedule($s))
            ->toArray();
    }

    // ──────────────────────────────────────────────
    // Private: Formatters
    // ──────────────────────────────────────────────

    private function formatAsset(TdmsAsset $asset): array
    {
        return [
            'id'            => $asset->id,
            'assetCode'     => $asset->asset_code,
            'name'          => $asset->name,
            'brand'         => $asset->brand,
            'model'         => $asset->model,
            'serialNumber'  => $asset->serial_number,
            'specifications' => $asset->specifications ?? [],
            'purchaseDate'  => $asset->purchase_date?->format('Y-m-d'),
            'purchasePrice' => $asset->purchase_price,
            'location'      => $asset->location,
            'assignedTo'    => $asset->assigned_to,
            'notes'         => $asset->notes,
            'status'        => $asset->status,
            'statusLabel'   => $asset->status_label,
            'statusColor'   => $asset->status_color,
            'qrToken'       => $asset->qr_token,
            'category'      => $asset->category ? [
                'id'    => $asset->category->id,
                'name'  => $asset->category->name,
                'icon'  => $asset->category->icon,
                'color' => $asset->category->color,
            ] : null,
            'createdAt' => $asset->created_at?->format('Y-m-d'),
        ];
    }

    private function formatServiceRecord(TdmsServiceRecord $record): array
    {
        return [
            'id'               => $record->id,
            'ticketNumber'     => $record->ticket_number,
            'title'            => $record->title,
            'issueDescription' => $record->issue_description,
            'actionTaken'      => $record->action_taken,
            'partsReplaced'    => $record->parts_replaced ?? [],
            'repairCost'       => $record->repair_cost,
            'priority'         => $record->priority,
            'priorityLabel'    => $record->priority_label,
            'status'           => $record->status,
            'statusLabel'      => $record->status_label,
            'isBreached'       => $record->is_breached,
            'slaDeadline'      => $record->sla_deadline?->toISOString(),
            'dateIn'           => $record->date_in?->toISOString(),
            'dateOut'          => $record->date_out?->toISOString(),
            'asset'            => $record->asset ? ['id' => $record->asset->id, 'assetCode' => $record->asset->asset_code, 'name' => $record->asset->name] : null,
            'reporter'         => $record->reporter ? ['id' => $record->reporter->id, 'name' => $record->reporter->name, 'alias' => $record->reporter->alias] : null,
            'technician'       => $record->technician ? ['id' => $record->technician->id, 'name' => $record->technician->name, 'alias' => $record->technician->alias] : null,
            'createdAt'        => $record->created_at?->toISOString(),
        ];
    }

    private function formatSchedule(TdmsMaintenanceSchedule $schedule): array
    {
        return [
            'id'              => $schedule->id,
            'maintenanceType' => $schedule->maintenance_type,
            'title'           => $schedule->title,
            'description'     => $schedule->description,
            'dueDate'         => $schedule->due_date?->format('Y-m-d'),
            'completedDate'   => $schedule->completed_date?->format('Y-m-d'),
            'status'          => $schedule->status,
            'statusLabel'     => $schedule->status_label,
            'intervalDays'    => $schedule->interval_days,
            'completionNotes' => $schedule->completion_notes,
            'asset'           => $schedule->asset ? ['id' => $schedule->asset->id, 'assetCode' => $schedule->asset->asset_code, 'name' => $schedule->asset->name] : null,
            'assignedUser'    => $schedule->assignedUser ? ['id' => $schedule->assignedUser->id, 'name' => $schedule->assignedUser->name] : null,
        ];
    }

    // ──────────────────────────────────────────────
    // Private: API Action Handlers
    // ──────────────────────────────────────────────

    private function createAsset(Request $request): array
    {
        $validated = $request->validate([
            'category_id'    => 'required|exists:tdms_categories,id',
            'name'           => 'required|string|max:200',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial_number'  => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'purchase_date'  => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'location'       => 'nullable|string|max:200',
            'assigned_to'    => 'nullable|string|max:200',
            'notes'          => 'nullable|string|max:2000',
            'status'         => 'nullable|in:active,maintenance,broken,retired',
        ]);

        $asset = TdmsAsset::create(array_merge($validated, [
            'created_by' => $request->user()->id,
            'status'     => $validated['status'] ?? 'active',
        ]));

        return ['ok' => true, 'status' => 201, 'data' => ['ok' => true, 'asset' => $this->formatAsset($asset->fresh(['category']))]];
    }

    private function updateAsset(Request $request): array
    {
        $validated = $request->validate([
            'id'             => 'required|exists:tdms_assets,id',
            'category_id'    => 'required|exists:tdms_categories,id',
            'name'           => 'required|string|max:200',
            'brand'          => 'nullable|string|max:100',
            'model'          => 'nullable|string|max:100',
            'serial_number'  => 'nullable|string|max:100',
            'specifications' => 'nullable|array',
            'purchase_date'  => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'location'       => 'nullable|string|max:200',
            'assigned_to'    => 'nullable|string|max:200',
            'notes'          => 'nullable|string|max:2000',
            'status'         => 'required|in:active,maintenance,broken,retired',
        ]);

        $asset = TdmsAsset::findOrFail($validated['id']);
        $asset->update($validated);

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'asset' => $this->formatAsset($asset->fresh(['category']))]];
    }

    private function deleteAsset(Request $request, $user): array
    {
        if (!$user->isSuperAdmin()) {
            return ['ok' => false, 'status' => 403, 'error' => 'Hanya superadmin yang dapat menghapus aset.'];
        }

        $validated = $request->validate(['id' => 'required|exists:tdms_assets,id']);
        TdmsAsset::findOrFail($validated['id'])->delete();

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function createServiceRecord(Request $request, $user): array
    {
        $validated = $request->validate([
            'asset_id'          => 'required|exists:tdms_assets,id',
            'title'             => 'required|string|max:200',
            'issue_description' => 'required|string|max:4000',
            'priority'          => 'nullable|in:low,medium,high,critical',
        ]);

        $record = TdmsServiceRecord::create(array_merge($validated, [
            'reporter_id' => $user->id,
            'priority'    => $validated['priority'] ?? 'medium',
        ]));

        // Update asset status ke 'maintenance' jika sebelumnya active
        $asset = TdmsAsset::find($validated['asset_id']);
        if ($asset && $asset->status === 'active') {
            $asset->update(['status' => 'maintenance']);
        }

        return ['ok' => true, 'status' => 201, 'data' => ['ok' => true, 'record' => $this->formatServiceRecord($record->fresh(['asset', 'reporter']))]];
    }

    private function updateServiceRecord(Request $request, $user): array
    {
        $validated = $request->validate([
            'id'                => 'required|exists:tdms_service_records,id',
            'status'            => 'required|in:open,in_progress,waiting_parts,resolved,closed,cancelled',
            'action_taken'      => 'nullable|string|max:4000',
            'parts_replaced'    => 'nullable|array',
            'parts_replaced.*.name' => 'required_with:parts_replaced|string',
            'parts_replaced.*.qty'  => 'nullable|integer|min:1',
            'parts_replaced.*.cost' => 'nullable|numeric|min:0',
            'repair_cost'       => 'nullable|numeric|min:0',
            'technician_id'     => 'nullable|exists:users,id',
        ]);

        $record = TdmsServiceRecord::findOrFail($validated['id']);
        $updateData = $validated;
        unset($updateData['id']);

        // Set tanggal selesai jika resolved/closed
        if (in_array($validated['status'], ['resolved', 'closed']) && !$record->date_out) {
            $updateData['date_out'] = now();
            // Kembalikan status aset ke active
            TdmsAsset::where('id', $record->asset_id)->where('status', 'maintenance')->update(['status' => 'active']);
        }

        $record->update($updateData);

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'record' => $this->formatServiceRecord($record->fresh(['asset', 'reporter', 'technician']))]];
    }

    private function closeServiceRecord(Request $request, $user): array
    {
        $validated = $request->validate(['id' => 'required|exists:tdms_service_records,id']);
        $record = TdmsServiceRecord::findOrFail($validated['id']);
        $record->update(['status' => 'closed', 'date_out' => $record->date_out ?? now()]);

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function createSchedule(Request $request, $user): array
    {
        $validated = $request->validate([
            'asset_id'         => 'required|exists:tdms_assets,id',
            'maintenance_type' => 'required|string|max:100',
            'title'            => 'required|string|max:200',
            'description'      => 'nullable|string|max:2000',
            'due_date'         => 'required|date',
            'interval_days'    => 'nullable|integer|min:1',
            'assigned_to_user' => 'nullable|exists:users,id',
        ]);

        $schedule = TdmsMaintenanceSchedule::create($validated);

        return ['ok' => true, 'status' => 201, 'data' => ['ok' => true, 'schedule' => $this->formatSchedule($schedule->fresh(['asset', 'assignedUser']))]];
    }

    private function completeSchedule(Request $request, $user): array
    {
        $validated = $request->validate([
            'id'               => 'required|exists:tdms_maintenance_schedules,id',
            'completion_notes' => 'nullable|string|max:2000',
        ]);

        $schedule = TdmsMaintenanceSchedule::findOrFail($validated['id']);
        $schedule->update([
            'status'           => 'completed',
            'completed_date'   => today(),
            'completion_notes' => $validated['completion_notes'] ?? null,
        ]);

        // Auto-create next schedule jika recurring
        if ($schedule->interval_days) {
            TdmsMaintenanceSchedule::create([
                'asset_id'         => $schedule->asset_id,
                'maintenance_type' => $schedule->maintenance_type,
                'title'            => $schedule->title,
                'description'      => $schedule->description,
                'due_date'         => today()->addDays($schedule->interval_days),
                'interval_days'    => $schedule->interval_days,
                'assigned_to_user' => $schedule->assigned_to_user,
            ]);
        }

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function submitReplacement(Request $request, $user): array
    {
        $validated = $request->validate([
            'asset_id'       => 'required|exists:tdms_assets,id',
            'title'          => 'required|string|max:200',
            'reason'         => 'required|string|max:4000',
            'type'           => 'required|in:repair_vs_replace,end_of_life,upgrade,lost',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        $replacement = TdmsReplacement::create(array_merge($validated, [
            'submitted_by'    => $user->id,
            'approval_status' => 'submitted',
        ]));

        return ['ok' => true, 'status' => 201, 'data' => ['ok' => true, 'id' => $replacement->id]];
    }

    private function approveReplacement(Request $request, $user): array
    {
        if (!$user->isSuperAdmin() && !in_array($user->role, ['admin', 'useradmin'])) {
            return ['ok' => false, 'status' => 403, 'error' => 'Tidak memiliki akses untuk menyetujui.'];
        }

        $validated = $request->validate([
            'id'             => 'required|exists:tdms_replacements,id',
            'decision'       => 'required|in:approved,rejected',
            'approval_notes' => 'nullable|string|max:2000',
        ]);

        $replacement = TdmsReplacement::findOrFail($validated['id']);
        $replacement->update([
            'approval_status' => $validated['decision'],
            'approved_by'     => $user->id,
            'approval_notes'  => $validated['approval_notes'] ?? null,
            'approved_at'     => now(),
        ]);

        if ($validated['decision'] === 'approved') {
            TdmsAsset::where('id', $replacement->asset_id)->update(['status' => 'retired']);
        }

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function createCategory(Request $request, $user): array
    {
        if (!$user->isSuperAdmin()) {
            return ['ok' => false, 'status' => 403, 'error' => 'Hanya superadmin yang dapat membuat kategori.'];
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:tdms_categories,name',
            'icon'        => 'nullable|string|max:10',
            'color'       => 'nullable|string|max:32',
            'description' => 'nullable|string|max:500',
        ]);

        $category = TdmsCategory::create($validated);

        return ['ok' => true, 'status' => 201, 'data' => ['ok' => true, 'category' => $category->toArray()]];
    }

    private function assetByQr(Request $request): array
    {
        $token = $request->query('token');
        if (!$token) {
            return ['ok' => false, 'status' => 422, 'error' => 'Token diperlukan.'];
        }

        $asset = TdmsAsset::where('qr_token', $token)->with('category')->first();
        if (!$asset) {
            return ['ok' => false, 'status' => 404, 'error' => 'Aset tidak ditemukan.'];
        }

        return ['ok' => true, 'status' => 200, 'data' => $this->formatAsset($asset)];
    }
}
