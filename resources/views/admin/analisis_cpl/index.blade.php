@extends('layouts.admin')
@section('title', 'Analisis CPL')
@section('page-title', 'Analisis Ketercapaian CPL')
@section('page-subtitle', 'Evaluasi Weighted Average dan Persentase Ketercapaian CPL per Program Studi & Kohort')

@section('content')
<style>
    .cpl-analytics-wrap {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .cpl-top-bar {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 22px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .cpl-top-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 16px;
    }
    .cpl-filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: flex-end;
    }
    .cpl-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .cpl-field label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    .cpl-field select,
    .cpl-field input {
        font: inherit;
        font-size: 13px;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s ease;
    }
    .cpl-field select:focus,
    .cpl-field input:focus {
        border-color: #2563eb;
    }
    .cpl-mode-tabs {
        display: inline-flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        gap: 4px;
        border: 1px solid #e2e8f0;
    }
    .cpl-mode-btn {
        border: none;
        background: transparent;
        color: #475569;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 7px;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .cpl-mode-btn.active {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 1px 3px rgba(37, 99, 235, 0.25);
    }
    .cpl-mode-btn .num-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        font-size: 11px;
        background: rgba(15, 23, 42, 0.08);
        color: inherit;
    }
    .cpl-mode-btn.active .num-pill {
        background: rgba(255, 255, 255, 0.22);
        color: #ffffff;
    }
    .cpl-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
        gap: 12px;
    }
    .cpl-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 14px 16px;
        cursor: pointer;
        border: 1.5px solid #e2e8f0;
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .cpl-card:hover {
        border-color: #93c5fd;
        transform: translateY(-1px);
    }
    .cpl-card.sel {
        border: 2px solid #2563eb;
        background: #f8fafc;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.10);
    }
    .cpl-card-code {
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cpl-card-main-val {
        font-size: 26px;
        font-weight: 700;
        color: #0f172a;
        margin: 6px 0 2px;
        line-height: 1.15;
    }
    .cpl-card-sub {
        font-size: 12px;
        color: #64748b;
        margin-bottom: 8px;
    }
    .cpl-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-block;
        width: fit-content;
    }
    .cpl-badge.ok {
        background: #dcfce7;
        color: #166534;
    }
    .cpl-badge.no {
        background: #fee2e2;
        color: #991b1b;
    }
    .cpl-badge.neutral {
        background: #f1f5f9;
        color: #475569;
    }
    .cpl-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 22px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .cpl-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .cpl-panel-title {
        font-size: 17px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }
    .cpl-panel-sub {
        font-size: 13px;
        color: #64748b;
    }
    .cpl-legend {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        margin-top: 10px;
        font-size: 12px;
        color: #475569;
    }
    .cpl-legend span.k {
        display: inline-block;
        width: 11px;
        height: 11px;
        margin-right: 5px;
        vertical-align: -1px;
    }
    .cpl-detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-top: 14px;
    }
    .mk-contrib-row {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .mk-contrib-row:last-child {
        border-bottom: none;
    }
    .cpl-formula-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-top: 10px;
    }
    .cpl-formula-table th,
    .cpl-formula-table td {
        padding: 9px 12px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .cpl-formula-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #334155;
        font-size: 12px;
    }
    .cpl-formula-table tfoot td {
        font-weight: 700;
        background: #f8fafc;
        border-top: 2px solid #cbd5e1;
    }
    .cpl-summary-callout {
        margin-top: 14px;
        padding: 12px 16px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        color: #1e293b;
        line-height: 1.6;
    }
    .cpl-note-box {
        font-size: 12px;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 10px;
        padding: 12px 16px;
        line-height: 1.6;
    }
    .stu-mk-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
    }
    .stu-mk-modal-box {
        background: #ffffff;
        border-radius: 14px;
        max-width: 680px;
        width: 100%;
        padding: 22px 24px;
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.18);
        max-height: 88vh;
        overflow-y: auto;
    }
</style>

<div class="inner-page cpl-analytics-wrap"
     id="cpl-analytics-root"
     data-analytics="{{ json_encode($analytics) }}"
     data-initial-mode="{{ $mode }}">

    {{-- =========================================================
         HEADER FILTER: PILIH PRODI -> PILIH MODE (1. WEIGHTED AVERAGE / 2. PERSEN KETERCAPAIAN)
    ========================================================== --}}
    <div class="cpl-top-bar">
        <div class="cpl-top-row">
            <div>
                <h2 style="margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #0f172a;">
                    Dashboard Ketercapaian CPL
                </h2>
                <div class="cpl-panel-sub">
                    @if($selectedProdi)
                        Prodi {{ $selectedProdi->jenjang }} {{ $selectedProdi->nama_prodi }}
                        &middot;
                        {{ $selectedAngkatan ? 'Kohort Angkatan ' . $selectedAngkatan : 'Semua Kohort Angkatan' }}
                        &middot;
                        {{ $analytics['total_students_in_cohort'] ?? 0 }} mahasiswa terdaftar
                    @else
                        Pilih Program Studi untuk melihat analisis ketercapaian CPL
                    @endif
                </div>
            </div>

            {{-- Pilihan 1: Weighted Average | Pilihan 2: Persen Ketercapaian --}}
            <div class="cpl-mode-tabs" role="tablist" aria-label="Metode Analisis CPL">
                <button type="button"
                        class="cpl-mode-btn {{ $mode === 'weighted_average' ? 'active' : '' }}"
                        id="btn-mode-weighted"
                        data-mode="weighted_average">
                    <span class="num-pill">1</span>
                    <span>Weighted Average (Nilai Rata-rata CPL)</span>
                </button>
                <button type="button"
                        class="cpl-mode-btn {{ $mode === 'persen_ketercapaian' ? 'active' : '' }}"
                        id="btn-mode-persen"
                        data-mode="persen_ketercapaian">
                    <span class="num-pill">2</span>
                    <span>Persen Ketercapaian (% Lulus CPL)</span>
                </button>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.analisis-cpl.index') }}" id="cpl-filter-form" class="cpl-top-row" style="border-top: 1px solid #f1f5f9; padding-top: 14px;">
            <input type="hidden" name="mode" id="input-mode" value="{{ $mode }}">

            <div class="cpl-filter-group">
                <div class="cpl-field">
                    <label for="select-prodi">1. Pilih Program Studi</label>
                    <select id="select-prodi" name="prodi_id" onchange="this.form.submit()" style="min-width: 240px;">
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" @selected($selectedProdi && $selectedProdi->id === $p->id)>
                                {{ $p->jenjang }} {{ $p->nama_prodi }}
                                {{ $p->has_cpl_data ? ' (Aktif CPL/OBE)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="cpl-field">
                    <label for="select-angkatan">Kohort / Angkatan</label>
                    <select id="select-angkatan" name="angkatan" onchange="this.form.submit()" style="min-width: 165px;">
                        <option value="all" @selected($selectedAngkatan === null)>Semua Angkatan</option>
                        @foreach($cohorts as $yr)
                            <option value="{{ $yr }}" @selected($selectedAngkatan === (int) $yr)>Angkatan {{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="cpl-filter-group">
                <div class="cpl-field">
                    <label for="amb">Ambang Lulus CPL (Batas Minimal)</label>
                    <input id="amb" name="ambang" type="number" min="0" max="100" step="1" value="{{ (int) $ambang }}" style="width: 90px;">
                </div>
                <div class="cpl-field">
                    <label for="tgt">Target % Mahasiswa Lulus</label>
                    <input id="tgt" name="target" type="number" min="0" max="100" step="1" value="{{ (int) $target }}" style="width: 90px;">
                </div>
            </div>
        </form>
    </div>

    @if(empty($analytics['cpls']))
        <div class="cpl-panel" style="text-align: center; padding: 48px 24px;">
            <h3 style="margin: 0 0 8px; color: #1e293b;">Belum Ada Pemetaan CPL / RPS pada Program Studi Ini</h3>
            <p style="margin: 0 auto 16px; max-width: 560px; color: #64748b; font-size: 13px;">
                Program studi <strong>{{ $selectedProdi?->nama_prodi }}</strong> belum memiliki data CPL atau dokumen RPS dengan bobot porsi CPL.
                Silakan kelola CPL atau unggah dokumen RPS pada menu <strong>Kurikulum OBE &amp; RPS</strong> terlebih dahulu.
            </p>
            <a href="{{ route('admin.obe.index', ['prodi_id' => $selectedProdi?->id, 'tab' => 'rps']) }}"
               class="btn-primary"
               style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 9px 16px; border-radius: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 600;">
                Buka Kurikulum OBE &amp; RPS &rarr;
            </a>
        </div>
    @else
        {{-- =========================================================
             KARTU RINGKASAN TIAP CPL (KLIK UNTUK MEMILIH CPL)
        ========================================================== --}}
        <div id="cards" class="cpl-cards-grid"></div>

        {{-- =========================================================
             GRAFIK KOMBINASI: % KETERCAPAIAN & WEIGHTED AVERAGE
        ========================================================== --}}
        <div class="cpl-panel">
            <div class="cpl-panel-head">
                <div>
                    <h3 class="cpl-panel-title" id="chart-main-title">Grafik Capaian Seluruh CPL</h3>
                    <div class="cpl-panel-sub" id="chart-main-sub">
                        Klik pada salah satu kartu atau batang CPL untuk melihat rincian mata kuliah dan mahasiswa di bawah.
                    </div>
                </div>
            </div>
            <div style="position: relative; height: 270px;">
                <canvas id="bar"></canvas>
            </div>
            <div class="cpl-legend">
                <span><span class="k" style="background:#378ADD;border-radius:2px"></span>% ketercapaian mahasiswa &ge; ambang (sumbu kiri)</span>
                <span><span class="k" style="background:#D85A30;border-radius:50%"></span>Rata-rata nilai capaian / Weighted Average (sumbu kanan)</span>
                <span><span class="k" style="width:16px;height:0;border-top:2px dashed #888780"></span>Target % ketercapaian</span>
            </div>
        </div>

        {{-- =========================================================
             PANEL RINCIAN CPL TERPILIH
        ========================================================== --}}
        <div class="cpl-panel">
            <div class="cpl-panel-head">
                <div>
                    <h3 class="cpl-panel-title" id="dTitle">Rincian CPL</h3>
                    <div class="cpl-panel-sub" id="dDesc" style="margin-top: 2px;"></div>
                </div>
                <div id="dStat" class="cpl-panel-sub" style="font-weight: 600; color: #1e293b;"></div>
            </div>

            <div class="cpl-detail-grid">
                {{-- Kiri: Sebaran Nilai Capaian Mahasiswa (Histogram) --}}
                <div>
                    <div class="cpl-panel-sub" style="margin-bottom: 8px; font-weight: 600; color: #334155;">
                        Sebaran Nilai Capaian Mahasiswa
                    </div>
                    <div style="position: relative; height: 220px;">
                        <canvas id="hist"></canvas>
                    </div>
                    <div style="margin-top: 16px;">
                        <div class="cpl-panel-sub" style="margin-bottom: 6px; font-weight: 600; color: #991b1b;">
                            Mahasiswa di Bawah Ambang (<span id="low-amb-label">60</span>)
                        </div>
                        <div id="low" style="font-size: 13px; line-height: 1.9;"></div>
                    </div>
                </div>

                {{-- Kanan: Kontribusi Mata Kuliah (w x sks & Rata-rata Skor CPL di MK) --}}
                <div>
                    <div class="cpl-panel-sub" style="margin-bottom: 8px; font-weight: 600; color: #334155;">
                        Kontribusi Mata Kuliah (w &times; SKS dan Rata-rata Skor CPL di MK)
                    </div>
                    <div id="mk"></div>
                </div>
            </div>

            {{-- Tabel Perhitungan Weighted Average per Mata Kuliah (Persis Contoh Gambar 1) --}}
            <div style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;" id="formula-mk-title">
                        Tabel Perhitungan Nilai Rata-rata (Weighted Average) Mata Kuliah
                    </h4>
                    <span class="cpl-panel-sub">Rumus: Nilai Akhir CPL = &Sigma; Kontribusi / &Sigma; (w &times; SKS)</span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="cpl-formula-table" id="formula-mk-table">
                        <thead>
                            <tr>
                                <th>Mata Kuliah (MK)</th>
                                <th>Porsi CPL di RPS (w)</th>
                                <th>SKS</th>
                                <th>w &times; SKS</th>
                                <th id="th-skor-cpl">Rata-rata Skor CPL</th>
                                <th>Kontribusi ((w &times; SKS) &times; Skor)</th>
                            </tr>
                        </thead>
                        <tbody id="formula-mk-tbody"></tbody>
                        <tfoot id="formula-mk-tfoot"></tfoot>
                    </table>
                </div>
                <div class="cpl-summary-callout" id="formula-mk-summary"></div>
            </div>

            {{-- Tabel Evaluasi Capaian CPL per Mahasiswa Kohort (Persis Contoh Gambar 2) --}}
            <div style="margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;" id="cohort-table-title">
                        Evaluasi Nilai Capaian &amp; Kelulusan CPL Mahasiswa
                    </h4>
                    <span class="cpl-panel-sub" id="cohort-table-subtitle"></span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="cpl-formula-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Mahasiswa (NIM &amp; Nama)</th>
                                <th style="width: 100px;">Angkatan</th>
                                <th style="width: 130px;">&Sigma; (w &times; SKS)</th>
                                <th style="width: 140px;">&Sigma; Kontribusi</th>
                                <th style="width: 160px;" id="th-mhs-cpl">Nilai Capaian CPL</th>
                                <th style="width: 130px;" id="th-mhs-pass">&ge; 60?</th>
                                <th style="width: 140px; text-align: center;">Rincian MK</th>
                            </tr>
                        </thead>
                        <tbody id="cohort-mhs-tbody"></tbody>
                    </table>
                </div>
                <div class="cpl-summary-callout" id="cohort-mhs-summary"></div>
            </div>
        </div>

        <div class="cpl-note-box">
            <strong>Catatan Rumus Perhitungan:</strong><br>
            1. <strong>Weighted Average (Nilai Capaian CPL Mahasiswa)</strong> = &Sigma;(w &times; SKS &times; Skor CPL di MK) / &Sigma;(w &times; SKS), di mana <em>w</em> adalah proporsi bobot CPL pada dokumen RPS mata kuliah (contoh: Statistika memiliki 66% CPL 1 &rarr; w = 0,66 dan 34% CPL 6 &rarr; w = 0,34).<br>
            2. <strong>Persen Ketercapaian (% Mahasiswa Lolos CPL)</strong> = (Jumlah mahasiswa dengan Nilai Capaian CPL &ge; Ambang Lulus / Jumlah mahasiswa kohort yang dinilai) &times; 100%. Mahasiswa dapat lulus mata kuliah (nilai B atau C), namun jika skor komponen CPL-nya di bawah ambang (60), nilai tersebut tetap berkontribusi secara proporsional terhadap nilai CPL total.
        </div>
    @endif
</div>

{{-- Modal Rincian Perhitungan CPL per Mahasiswa --}}
<div class="stu-mk-modal-backdrop" id="stu-mk-modal">
    <div class="stu-mk-modal-box">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;" id="modal-stu-title">Rincian Perhitungan CPL Mahasiswa</h3>
                <div class="cpl-panel-sub" id="modal-stu-sub"></div>
            </div>
            <button type="button" id="modal-stu-close" style="border: none; background: #f1f5f9; border-radius: 8px; width: 32px; height: 32px; font-size: 18px; cursor: pointer; color: #475569;">&times;</button>
        </div>
        <div style="overflow-x: auto;">
            <table class="cpl-formula-table">
                <thead>
                    <tr>
                        <th>MK</th>
                        <th>w &times; SKS</th>
                        <th id="modal-th-skor">Skor CPL</th>
                        <th>Kontribusi</th>
                    </tr>
                </thead>
                <tbody id="modal-stu-tbody"></tbody>
                <tfoot id="modal-stu-tfoot"></tfoot>
            </table>
        </div>
        <div class="cpl-summary-callout" id="modal-stu-summary"></div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
(function () {
    const root = document.getElementById('cpl-analytics-root');
    if (!root) return;

    const rawAnalytics = root.getAttribute('data-analytics');
    const analytics = rawAnalytics ? JSON.parse(rawAnalytics) : { cpls: [] };
    const cpls = Array.isArray(analytics.cpls) ? analytics.cpls : [];
    if (!cpls.length) return;

    let currentMode = root.getAttribute('data-initial-mode') || 'weighted_average';
    let sel = 0;
    let barChart = null;
    let histChart = null;

    const $ = function (id) { return document.getElementById(id); };
    const fmt = function (num, dec) {
        if (num === null || num === undefined || Number.isNaN(Number(num))) return '—';
        return Number(num).toFixed(dec).replace('.', ',');
    };
    const esc = function (str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    const getAmbang = function () {
        const el = $('amb');
        return el ? ( parseFloat(el.value) || 0 ) : 60;
    };
    const getTarget = function () {
        const el = $('tgt');
        return el ? ( parseFloat(el.value) || 0 ) : 75;
    };

    function computeStatsForCpl(cplItem, ambang, target) {
        const students = Array.isArray(cplItem.students) ? cplItem.students : [];
        const n = students.length;
        let passCount = 0;
        let sumScore = 0;

        for (let i = 0; i < n; i++) {
            const sc = Number(students[i].score || 0);
            sumScore += sc;
            if (sc >= ambang) passCount++;
        }

        let avg = n > 0 ? (sumScore / n) : (cplItem.weighted_average !== null ? Number(cplItem.weighted_average) : null);
        let pct = n > 0 ? (passCount / n * 100) : null;

        return {
            n: n,
            pass: passCount,
            avg: avg,
            pct: pct,
            met: pct !== null && pct >= target
        };
    }

    function openStudentModal(cplItem, studentRow, ambang) {
        const modal = $('stu-mk-modal');
        if (!modal) return;

        $('modal-stu-title').textContent = 'Rincian ' + cplItem.kode_cpl + ' — ' + studentRow.nama + ' (' + studentRow.nim + ')';
        $('modal-stu-sub').textContent = 'Angkatan ' + (studentRow.angkatan || '-') + ' · Ambang Lulus CPL: ' + ambang;
        $('modal-th-skor').textContent = 'Skor ' + cplItem.kode_cpl;

        const breakdown = Array.isArray(studentRow.mk_breakdown) ? studentRow.mk_breakdown : [];
        let sumWs = 0;
        let sumKontrib = 0;

        $('modal-stu-tbody').innerHTML = breakdown.map(function (b) {
            const ws = Number(b.w) * Number(b.sks);
            const k = ws * Number(b.skor);
            sumWs += ws;
            sumKontrib += k;
            return '<tr>' +
                '<td><strong>' + esc(b.nama_mk) + '</strong> <span style="color:#64748b;font-size:12px;">(' + esc(b.kode_mk) + ')</span></td>' +
                '<td>' + fmt(b.w, 4) + ' &times; ' + b.sks + ' = <strong>' + fmt(ws, 2) + '</strong></td>' +
                '<td><strong>' + fmt(b.skor, 1) + '</strong></td>' +
                '<td>' + fmt(ws, 2) + ' &times; ' + fmt(b.skor, 1) + ' = <strong>' + fmt(k, 1) + '</strong></td>' +
                '</tr>';
        }).join('');

        $('modal-stu-tfoot').innerHTML = '<tr>' +
            '<td>Total</td>' +
            '<td>' + fmt(sumWs, 2) + '</td>' +
            '<td></td>' +
            '<td>' + fmt(sumKontrib, 1) + '</td>' +
            '</tr>';

        const finalVal = sumWs > 0 ? (sumKontrib / sumWs) : 0;
        const statusHtml = finalVal >= ambang
            ? '<span class="cpl-badge ok">Lolos (&ge; ' + ambang + ')</span>'
            : '<span class="cpl-badge no">Belum Lolos (&lt; ' + ambang + ')</span>';

        $('modal-stu-summary').innerHTML =
            'Nilai akhir <strong>' + esc(cplItem.kode_cpl) + '</strong> = &Sigma; kontribusi / &Sigma; (w &times; sks) = ' +
            '<strong>' + fmt(sumKontrib, 1) + ' / ' + fmt(sumWs, 2) + ' = ' + fmt(finalVal, 1) + '</strong> &nbsp; ' + statusHtml;

        modal.style.display = 'flex';
    }

    function render() {
        const amb = getAmbang();
        const tgt = getTarget();
        const statsList = cpls.map(function (c) { return computeStatsForCpl(c, amb, tgt); });

        // Update Mode Buttons
        const btnWeighted = $('btn-mode-weighted');
        const btnPersen = $('btn-mode-persen');
        if (btnWeighted && btnPersen) {
            btnWeighted.classList.toggle('active', currentMode === 'weighted_average');
            btnPersen.classList.toggle('active', currentMode === 'persen_ketercapaian');
        }
        const inputMode = $('input-mode');
        if (inputMode) inputMode.value = currentMode;

        // 1. Render CPL Cards
        $('cards').innerHTML = cpls.map(function (c, i) {
            const s = statsList[i];
            const isSel = i === sel;
            let mainText = '—';
            let subText = '';
            let badgeHtml = '';

            if (currentMode === 'weighted_average') {
                mainText = s.avg !== null ? fmt(s.avg, 1) : '—';
                subText = 'Weighted Avg · ' + (s.pct !== null ? fmt(s.pct, 1) + '% lulus' : 'Belum ada nilai');
                if (s.avg === null) {
                    badgeHtml = '<span class="cpl-badge neutral">' + c.course_count + ' MK (&Sigma;w&times;sks ' + fmt(c.total_ws, 2) + ')</span>';
                } else {
                    badgeHtml = '<span class="cpl-badge ' + (s.avg >= amb ? 'ok' : 'no') + '">' +
                        (s.avg >= amb ? 'Rata-rata &ge; ' + amb : 'Rata-rata &lt; ' + amb) + '</span>';
                }
            } else {
                mainText = s.pct !== null ? fmt(s.pct, 1) + '%' : '—';
                subText = '% ketercapaian · rata-rata ' + (s.avg !== null ? fmt(s.avg, 1) : '—');
                if (s.pct === null) {
                    badgeHtml = '<span class="cpl-badge neutral">Belum ada nilai</span>';
                } else {
                    badgeHtml = '<span class="cpl-badge ' + (s.met ? 'ok' : 'no') + '">' +
                        (s.met ? 'Tercapai' : 'Belum tercapai') + '</span>';
                }
            }

            return '<div class="cpl-card ' + (isSel ? 'sel' : '') + '" data-i="' + i + '">' +
                '<div class="cpl-card-code"><span>' + esc(c.kode_cpl) + '</span><span style="font-size:11px;font-weight:500;color:#64748b;">' + c.course_count + ' MK</span></div>' +
                '<div class="cpl-card-main-val">' + mainText + '</div>' +
                '<div class="cpl-card-sub">' + subText + '</div>' +
                badgeHtml +
                '</div>';
        }).join('');

        document.querySelectorAll('.cpl-card').forEach(function (card) {
            card.onclick = function () {
                sel = parseInt(card.getAttribute('data-i'), 10) || 0;
                render();
            };
        });

        // 2. Render Main Bar + Line Chart
        const labels = cpls.map(function (c) { return c.kode_cpl; });
        const pctData = statsList.map(function (s) { return s.pct !== null ? Number(s.pct.toFixed(1)) : 0; });
        const avgData = statsList.map(function (s) { return s.avg !== null ? Number(s.avg.toFixed(1)) : 0; });

        const datasets = [
            {
                type: 'bar',
                label: '% ketercapaian',
                data: pctData,
                backgroundColor: statsList.map(function (_, i) {
                    if (currentMode === 'persen_ketercapaian') {
                        return i === sel ? '#185FA5' : '#85B7EB';
                    }
                    return i === sel ? '#6499E9' : '#BDCDD6';
                }),
                borderRadius: 6,
                yAxisID: 'y',
                order: 2
            },
            {
                type: 'line',
                label: 'Rata-rata nilai capaian (Weighted Average)',
                data: avgData,
                borderColor: '#D85A30',
                backgroundColor: '#D85A30',
                showLine: currentMode === 'weighted_average',
                borderWidth: 2,
                pointRadius: statsList.map(function (_, i) { return i === sel ? 8 : 6; }),
                yAxisID: 'y2',
                order: 1
            },
            {
                type: 'line',
                label: 'Target % Ketercapaian',
                data: labels.map(function () { return tgt; }),
                borderColor: '#888780',
                borderDash: [5, 4],
                pointRadius: 0,
                borderWidth: 1.5,
                yAxisID: 'y',
                order: 0
            }
        ];

        if (!barChart) {
            barChart = new Chart($('bar'), {
                data: { labels: labels, datasets: datasets },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    onClick: function (e, el) {
                        if (el && el.length) {
                            sel = el[0].index;
                            render();
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            title: { display: true, text: '% mahasiswa ≥ ambang', color: '#64748b' },
                            ticks: { color: '#64748b', callback: function (v) { return v + '%'; } },
                            grid: { color: 'rgba(148, 163, 184, 0.2)' }
                        },
                        y2: {
                            position: 'right',
                            min: 0,
                            max: 100,
                            title: { display: true, text: 'Rata-rata nilai (Weighted Avg)', color: '#64748b' },
                            ticks: { color: '#64748b' },
                            grid: { display: false }
                        },
                        x: {
                            ticks: { color: '#334155', font: { weight: '600' } },
                            grid: { display: false }
                        }
                    }
                }
            });
        } else {
            barChart.data.labels = labels;
            barChart.data.datasets = datasets;
            barChart.update();
        }

        // 3. Render Selected CPL Detail Panel
        const curCpl = cpls[sel];
        const curStat = statsList[sel];
        const students = Array.isArray(curCpl.students) ? curCpl.students : [];
        const courses = Array.isArray(curCpl.courses) ? curCpl.courses : [];

        $('dTitle').textContent = 'Rincian ' + curCpl.kode_cpl;
        $('dDesc').textContent = curCpl.deskripsi || curCpl.nama_cpl || '';
        $('dStat').textContent = curStat.n > 0
            ? (curStat.pass + ' dari ' + curStat.n + ' mahasiswa ≥ ' + amb + ' (' + fmt(curStat.pct, 1) + '%) · Rata-rata Weighted Average: ' + fmt(curStat.avg, 1))
            : ('Total Bobot (w × SKS): ' + fmt(curCpl.total_ws, 2) + ' dari ' + curCpl.course_count + ' Mata Kuliah');

        $('low-amb-label').textContent = amb;

        // 3a. Histogram
        const bins = [0, 20, 30, 40, 50, 60, 70, 80, 90];
        const binLabels = ['<20', '20–29', '30–39', '40–49', '50–59', '60–69', '70–79', '80–89', '90–100'];
        const counts = bins.map(function (b, idx) {
            const lowBound = b;
            const highBound = idx === 0 ? 20 : (idx === bins.length - 1 ? 100.01 : b + 10);
            return students.filter(function (st) {
                const val = Number(st.score || 0);
                return val >= lowBound && val < highBound;
            }).length;
        });

        const histDatasets = [{
            data: counts,
            borderRadius: 4,
            backgroundColor: bins.map(function (b, idx) {
                const upper = idx === 0 ? 20 : b + 10;
                if (upper <= amb) return '#F09595';
                if (b >= amb) return '#5DCAA5';
                return '#FAC775';
            })
        }];

        if (!histChart) {
            histChart = new Chart($('hist'), {
                type: 'bar',
                data: { labels: binLabels, datasets: histDatasets },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Jumlah mhs', color: '#64748b' },
                            ticks: { color: '#64748b', precision: 0 },
                            grid: { color: 'rgba(148, 163, 184, 0.2)' }
                        },
                        x: {
                            ticks: { color: '#64748b', font: { size: 11 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        } else {
            histChart.data.labels = binLabels;
            histChart.data.datasets = histDatasets;
            histChart.update();
        }

        // 3b. Kontribusi Mata Kuliah List (Right Column)
        if (!courses.length) {
            $('mk').innerHTML = '<div class="cpl-panel-sub">Belum ada mata kuliah yang dipetakan ke ' + esc(curCpl.kode_cpl) + '.</div>';
        } else {
            $('mk').innerHTML = courses.map(function (m) {
                const sc = m.avg_score !== null ? Number(m.avg_score) : null;
                const barWidth = sc !== null ? Math.max(0, Math.min(100, sc)) : 0;
                const barColor = sc === null ? '#cbd5e1' : (sc < amb ? '#E24B4A' : '#1D9E75');
                return '<div class="mk-contrib-row">' +
                    '<div style="flex:1;min-width:0">' +
                        '<strong>' + esc(m.nama_mk) + '</strong> <span style="color:#64748b;font-size:12px;">(' + esc(m.kode_mk) + ')</span>' +
                        '<div class="cpl-panel-sub" style="font-size:11px">' +
                            m.sks + ' SKS &middot; w = ' + fmt(m.w, 4) + ' (' + fmt(m.w_percent, 2) + '%) &middot; w&times;sks = ' + fmt(m.ws, 2) + ' &middot; jatah ' + fmt(m.jatah_percent, 0) + '%' +
                        '</div>' +
                    '</div>' +
                    '<div style="width:80px;height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden">' +
                        '<div style="width:' + barWidth + '%;height:7px;border-radius:4px;background:' + barColor + '"></div>' +
                    '</div>' +
                    '<div style="width:42px;text-align:right;font-weight:600;">' + (sc !== null ? fmt(sc, 1) : '—') + '</div>' +
                '</div>';
            }).join('');
        }

        // 3c. Mahasiswa di bawah ambang
        const lowStudents = students
            .filter(function (st) { return Number(st.score) < amb; })
            .sort(function (a, b) { return Number(a.score) - Number(b.score); });

        if (!students.length) {
            $('low').innerHTML = '<span class="cpl-panel-sub">Belum ada data nilai mahasiswa pada CPL ini.</span>';
        } else if (!lowStudents.length) {
            $('low').innerHTML = '<span class="cpl-badge ok">Tidak ada (Seluruh mahasiswa &ge; ' + amb + ')</span>';
        } else {
            $('low').innerHTML = lowStudents.map(function (st, idx) {
                return '<button type="button" class="cpl-badge no btn-low-stu" data-stu-id="' + st.mahasiswa_id + '" style="margin:0 6px 6px 0;border:none;cursor:pointer;" title="Klik untuk lihat rincian MK">' +
                    esc(st.nim) + ' · ' + esc(st.nama) + ' · ' + fmt(st.score, 1) +
                '</button>';
            }).join('');

            document.querySelectorAll('.btn-low-stu').forEach(function (btn) {
                btn.onclick = function () {
                    const stuId = parseInt(btn.getAttribute('data-stu-id'), 10);
                    const found = students.find(function (s) { return s.mahasiswa_id === stuId; });
                    if (found) openStudentModal(curCpl, found, amb);
                };
            });
        }

        // 4. Tabel Perhitungan Weighted Average per Mata Kuliah (Screenshot 1)
        $('formula-mk-title').textContent = '1. Cara Menghitung Nilai Rata-rata ' + curCpl.kode_cpl + ' (Weighted Average Mata Kuliah)';
        $('th-skor-cpl').textContent = 'Skor ' + curCpl.kode_cpl;

        let totalMkWsAll = 0;
        let totalMkWsGraded = 0;
        let totalMkKontrib = 0;

        $('formula-mk-tbody').innerHTML = courses.map(function (m) {
            const ws = Number(m.ws);
            totalMkWsAll += ws;
            const hasScore = m.avg_score !== null;
            const skor = hasScore ? Number(m.avg_score) : null;
            const kontrib = hasScore ? (ws * skor) : null;

            if (hasScore) {
                totalMkWsGraded += ws;
                totalMkKontrib += kontrib;
            }

            return '<tr>' +
                '<td><strong>' + esc(m.nama_mk) + '</strong> <span style="color:#64748b;font-size:12px;">(' + esc(m.kode_mk) + ')</span></td>' +
                '<td>' + fmt(m.w_percent, 2) + '% (' + fmt(m.w, 4) + ')</td>' +
                '<td>' + m.sks + '</td>' +
                '<td>' + fmt(m.w, 4) + ' &times; ' + m.sks + ' = <strong>' + fmt(ws, 2) + '</strong></td>' +
                '<td>' + (hasScore ? ('<strong>' + fmt(skor, 1) + '</strong> <span style="color:#64748b;font-size:11px;">(' + m.student_count + ' mhs)</span>') : '<span style="color:#94a3b8;">Belum ada nilai</span>') + '</td>' +
                '<td>' + (hasScore ? (fmt(ws, 2) + ' &times; ' + fmt(skor, 1) + ' = <strong>' + fmt(kontrib, 1) + '</strong>') : '—') + '</td>' +
            '</tr>';
        }).join('');

        $('formula-mk-tfoot').innerHTML = '<tr>' +
            '<td colspan="3">Total</td>' +
            '<td>' + fmt(totalMkWsGraded > 0 ? totalMkWsGraded : totalMkWsAll, 2) + '</td>' +
            '<td></td>' +
            '<td>' + (totalMkWsGraded > 0 ? fmt(totalMkKontrib, 1) : '—') + '</td>' +
        '</tr>';

        if (totalMkWsGraded > 0) {
            const finalMkAvg = totalMkKontrib / totalMkWsGraded;
            $('formula-mk-summary').innerHTML =
                'Nilai akhir rata-rata <strong>' + esc(curCpl.kode_cpl) + '</strong> = &Sigma; kontribusi / &Sigma; (w &times; sks) = ' +
                '<strong>' + fmt(totalMkKontrib, 1) + ' / ' + fmt(totalMkWsGraded, 2) + ' = ' + fmt(finalMkAvg, 1) + '</strong>.';
        } else {
            $('formula-mk-summary').innerHTML =
                'Total bobot terpetakan pada <strong>' + esc(curCpl.kode_cpl) + '</strong> adalah <strong>&Sigma;(w &times; sks) = ' + fmt(totalMkWsAll, 2) + '</strong>. Nilai rata-rata akan otomatis dihitung setelah nilai mata kuliah mahasiswa diinput.';
        }

        // 5. Tabel Evaluasi Persen Ketercapaian Kohort Mahasiswa (Screenshot 2)
        $('cohort-table-title').textContent = '2. Perhitungan Persen Ketercapaian ' + curCpl.kode_cpl + ' (Kohort Mahasiswa)';
        $('cohort-table-subtitle').textContent = 'Ambang kelulusan ' + curCpl.kode_cpl + ' ≥ ' + amb + ' · Target kelulusan kohort: ' + tgt + '%';
        $('th-mhs-cpl').textContent = 'Nilai Capaian ' + curCpl.kode_cpl;
        $('th-mhs-pass').textContent = '≥ ' + amb + '?';

        if (!students.length) {
            $('cohort-mhs-tbody').innerHTML = '<tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:24px;">Belum ada mahasiswa pada kohort ini yang memiliki nilai pada mata kuliah pengampu ' + esc(curCpl.kode_cpl) + '.</td></tr>';
            $('cohort-mhs-summary').innerHTML = 'Belum ada data mahasiswa yang dapat dievaluasi untuk <strong>' + esc(curCpl.kode_cpl) + '</strong>.';
        } else {
            $('cohort-mhs-tbody').innerHTML = students.map(function (st, idx) {
                const passed = Number(st.score) >= amb;
                return '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td><strong>' + esc(st.nama) + '</strong><div style="font-size:11px;color:#64748b;">NIM: ' + esc(st.nim) + '</div></td>' +
                    '<td>' + (st.angkatan || '-') + '</td>' +
                    '<td>' + fmt(st.sum_ws, 2) + '</td>' +
                    '<td>' + fmt(st.sum_kontribusi, 1) + '</td>' +
                    '<td><strong style="font-size:14px;color:' + (passed ? '#166534' : '#991b1b') + ';">' + fmt(st.score, 1) + '</strong></td>' +
                    '<td><span class="cpl-badge ' + (passed ? 'ok' : 'no') + '">' + (passed ? 'Ya' : 'Tidak') + '</span></td>' +
                    '<td style="text-align:center;">' +
                        '<button type="button" class="btn-detail-stu" data-stu-id="' + st.mahasiswa_id + '" style="padding:5px 10px;font-size:12px;border-radius:6px;border:1px solid #cbd5e1;background:#fff;cursor:pointer;color:#1e293b;font-weight:600;">Rincian MK</button>' +
                    '</td>' +
                '</tr>';
            }).join('');

            document.querySelectorAll('.btn-detail-stu').forEach(function (btn) {
                btn.onclick = function () {
                    const stuId = parseInt(btn.getAttribute('data-stu-id'), 10);
                    const found = students.find(function (s) { return s.mahasiswa_id === stuId; });
                    if (found) openStudentModal(curCpl, found, amb);
                };
            });

            const statusText = curStat.met
                ? '<strong style="color:#166534;">tercapai</strong>'
                : '<strong style="color:#991b1b;">belum tercapai</strong>';

            $('cohort-mhs-summary').innerHTML =
                'Yang lolos <strong>' + curStat.pass + '</strong> dari <strong>' + curStat.n + '</strong> mahasiswa, jadi ' +
                '<strong>% ketercapaian ' + esc(curCpl.kode_cpl) + ' = ' + curStat.pass + '/' + curStat.n + ' &times; 100% = ' + fmt(curStat.pct, 1) + '%</strong>. ' +
                'Dengan target <strong>' + fmt(tgt, 0) + '%</strong>, ' + esc(curCpl.kode_cpl) + ' ' + statusText + '.';
        }
    }

    // Event Listeners
    const btnWeighted = $('btn-mode-weighted');
    const btnPersen = $('btn-mode-persen');
    if (btnWeighted) {
        btnWeighted.onclick = function () {
            currentMode = 'weighted_average';
            render();
        };
    }
    if (btnPersen) {
        btnPersen.onclick = function () {
            currentMode = 'persen_ketercapaian';
            render();
        };
    }

    const ambInput = $('amb');
    const tgtInput = $('tgt');
    if (ambInput) ambInput.oninput = render;
    if (tgtInput) tgtInput.oninput = render;

    const modalClose = $('modal-stu-close');
    const modalBackdrop = $('stu-mk-modal');
    if (modalClose && modalBackdrop) {
        modalClose.onclick = function () { modalBackdrop.style.display = 'none'; };
        modalBackdrop.onclick = function (e) {
            if (e.target === modalBackdrop) modalBackdrop.style.display = 'none';
        };
    }

    render();
})();
</script>
@endsection

