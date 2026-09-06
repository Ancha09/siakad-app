<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AkademikDashboard;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request, AkademikDashboard $dashboard)
    {
        $years = $dashboard->years();
        $request->validate(['tahun' => ['nullable', Rule::in($years->all())]]);
        $start = now()->month >= 7 ? now()->year : now()->year - 1;
        $year = $request->input('tahun') ?: $start.'/'.($start + 1);
        $stats = $dashboard->summary($year);
        $announcements = Pengumuman::with('penulis')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('years', 'stats', 'announcements'));
    }
}
