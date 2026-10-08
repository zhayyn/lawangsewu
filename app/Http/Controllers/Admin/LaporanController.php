<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DocumentStylerService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

/**
 * LaporanController
 *
 * Mengelola halaman laporan superadmin Lawangsewu:
 *  - index()    : tampilan halaman laporan (Vue/Inertia)
 *  - generate() : menerima konten Markdown, menghasilkan PDF via DocumentStylerService
 *
 * Hanya dapat diakses oleh superadmin (middleware 'superadmin' + 'permission:admin.laporan').
 */
class LaporanController extends Controller
{
    public function __construct(
        private readonly DocumentStylerService $styler
    ) {}

    /**
     * Menampilkan halaman Laporan di superadmin dashboard.
     */
    public function index(): \Inertia\Response
    {
        // Ambil data statistik widget native
        $visitorToday = \App\Models\WidgetVisitor::where('visit_date', now()->toDateString())->count();
        $visitorThisMonth = \App\Models\WidgetVisitor::whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();
        $widgetStats = \App\Models\WidgetVisitor::selectRaw('widget_name, count(*) as total')
            ->groupBy('widget_name')
            ->orderByDesc('total')
            ->get();
            
        $last7Days = \App\Models\WidgetVisitor::select('visit_date', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->where('visit_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('visit_date')
            ->orderBy('visit_date')
            ->get();

        return Inertia::render('Admin/Laporan', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'visitorStats' => [
                'today' => $visitorToday,
                'this_month' => $visitorThisMonth,
                'breakdown' => $widgetStats,
                'chart_data' => $last7Days,
            ],
        ]);
    }

    /**
     * Menerima request generate laporan dan mengembalikan file PDF untuk diunduh.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \InvalidArgumentException jika konten kosong
     */
    public function generate(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'content'     => ['required', 'string', 'max:50000'],
            'orientation' => ['sometimes', 'in:portrait,landscape'],
        ], [
            'title.required'   => 'Judul laporan wajib diisi.',
            'content.required' => 'Konten laporan tidak boleh kosong.',
            'content.max'      => 'Konten laporan terlalu panjang (maks. 50.000 karakter).',
        ]);

        try {
            $pdf      = $this->styler->createInteractiveReport(
                markdownContent: $validated['content'],
                title: $validated['title'],
                orientation: $validated['orientation'] ?? 'portrait',
            );

            $filename = $this->styler->buildFilename($validated['title']);

            return response($pdf->output(), 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'X-Generated-By'      => 'Lawangsewu DocumentStyler',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
