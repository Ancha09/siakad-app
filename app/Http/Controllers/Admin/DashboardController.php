<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AkademikDashboard;
use App\Services\AttendanceAnalyticsService;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request, AkademikDashboard $dashboard, AttendanceAnalyticsService $attendanceAnalytics)
    {
        $years = $dashboard->years();
        $request->validate(['tahun' => ['nullable', Rule::in($years->all())]]);
        $start = now()->month >= 7 ? now()->year : now()->year - 1;
        $year = $request->input('tahun') ?: $start.'/'.($start + 1);
        $stats = $dashboard->summary($year);
        $attendanceData = $attendanceAnalytics->getAnalytics('30_days', null, null, $year);
        $announcements = Pengumuman::with('penulis')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('years', 'stats', 'attendanceData', 'announcements'));
    }

    public function attendanceAnalytics(Request $request, AttendanceAnalyticsService $attendanceAnalytics)
    {
        $range = $request->input('range', '30_days');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $tahun = $request->input('tahun');

        $data = $attendanceAnalytics->getAnalytics($range, $startDate, $endDate, $tahun);

        return response()->json($data);
    }
}
