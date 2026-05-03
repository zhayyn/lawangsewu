<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WidgetVisitor;
use App\Support\LawangsewuPortal;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        
        // Ringkasan Harian
        $visitorToday = WidgetVisitor::where('visit_date', $today)->count();
        $visitorYesterday = WidgetVisitor::where('visit_date', now()->subDay()->toDateString())->count();
        
        // Ringkasan Bulanan
        $visitorThisMonth = WidgetVisitor::whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();
            
        // 7 Hari Terakhir untuk Grafik (dengan padding 0 untuk hari kosong)
        $last7DaysData = WidgetVisitor::select('visit_date', DB::raw('count(*) as total'))
            ->where('visit_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('visit_date')
            ->orderBy('visit_date')
            ->get()
            ->pluck('total', 'visit_date');

        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $last7Days->push([
                'visit_date' => $date,
                'total'      => $last7DaysData->get($date, 0),
            ]);
        }
            
        // Top Widgets
        $topWidgets = WidgetVisitor::select('widget_name', DB::raw('count(*) as total'))
            ->groupBy('widget_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Analytics', [
            'appMeta'   => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'stats'     => [
                'today'      => $visitorToday,
                'yesterday'  => $visitorYesterday,
                'this_month' => $visitorThisMonth,
                'chart_data' => $last7Days,
                'top_widgets'=> $topWidgets,
            ]
        ]);
    }
}
