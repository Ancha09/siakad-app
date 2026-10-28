<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Services\ObeCplAnalyticsService;
use Illuminate\Http\Request;

class AnalisisCplController extends Controller
{
    public function index(Request $request, ObeCplAnalyticsService $analyticsService)
    {
        $prodis = $analyticsService->getProdisWithCplStatus();

        $defaultProdi = $prodis->firstWhere('has_cpl_data', true) ?? $prodis->first();
        $selectedProdiId = (int) ($request->query('prodi_id') ?: ($defaultProdi?->id ?? 0));
        /** @var Prodi|null $selectedProdi */
        $selectedProdi = $prodis->firstWhere('id', $selectedProdiId) ?? $defaultProdi;

        $cohorts = $selectedProdi
            ? $analyticsService->getCohortsForProdi($selectedProdi->id)
            : collect();

        $rawAngkatan = $request->query('angkatan');
        $selectedAngkatan = ($rawAngkatan !== null && $rawAngkatan !== '' && $rawAngkatan !== 'all')
            ? (int) $rawAngkatan
            : null;

        $mode = in_array($request->query('mode'), ['weighted_average', 'persen_ketercapaian'], true)
            ? $request->query('mode')
            : 'weighted_average';

        $ambang = $request->filled('ambang')
            ? max(0.0, min(100.0, (float) $request->query('ambang')))
            : 60.0;

        $target = $request->filled('target')
            ? max(0.0, min(100.0, (float) $request->query('target')))
            : 75.0;

        $analytics = $selectedProdi
            ? $analyticsService->buildAnalytics($selectedProdi, $selectedAngkatan, $ambang, $target)
            : [
                'prodi' => null,
                'angkatan' => null,
                'ambang' => $ambang,
                'target_pct' => $target,
                'total_students_in_cohort' => 0,
                'cpls' => [],
            ];

        return view('admin.analisis_cpl.index', compact(
            'prodis',
            'selectedProdi',
            'cohorts',
            'selectedAngkatan',
            'mode',
            'ambang',
            'target',
            'analytics'
        ));
    }
}

