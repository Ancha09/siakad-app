@push('styles')
<style>
    .ability-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:20px}
    .ability-summary-card{border:1px solid #dbeafe;border-radius:12px;padding:18px;background:#f8fbff}
    .ability-chart{display:grid;gap:13px}
    .ability-chart-row{display:grid;grid-template-columns:minmax(190px,1.25fr) minmax(180px,2fr) 62px;gap:12px;align-items:center}
    .ability-track{height:15px;background:#e2e8f0;border-radius:999px;overflow:hidden}
    .ability-bar{height:100%;background:linear-gradient(90deg,#0a1f5c,#0b70b7);border-radius:999px}
    .ability-score{font-weight:700;color:#0a1f5c;text-align:right}
    .ability-detail{border:1px solid #e2e8f0;border-radius:10px;margin-bottom:10px;overflow:hidden}
    .ability-detail summary{cursor:pointer;padding:14px 16px;background:#f8fafc;font-weight:700;color:#0f172a}
    .ability-detail-body{padding:14px 16px}
    @media(max-width:720px){.ability-chart-row{grid-template-columns:1fr 58px}.ability-chart-label{grid-column:1/-1}.ability-track{grid-column:1}.ability-score{grid-column:2}}
</style>
@endpush

@php
    $student = $profile['student'];
    $backRoute = $role === 'admin' ? 'admin.penilaian-cpl-mahasiswa.index' : ($role === 'dosen' ? 'dosen.penilaian-mahasiswa.index' : null);
@endphp

@if($backRoute)
    <div style="margin-bottom:16px;"><a class="btn-outline" href="{{ route($backRoute, request()->only(['tahun_akademik'])) }}">Kembali ke Daftar</a></div>
@endif

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>{{ $role === 'mahasiswa' ? 'Penilaian Mahasiswa' : 'Profil Kemampuan Mahasiswa' }}</h2></div>
    <div class="page-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;">
            <div><small style="color:#64748b;">Nama</small><div style="font-weight:700;margin-top:4px;">{{ $student->nama }}</div></div>
            <div><small style="color:#64748b;">NIM</small><div style="font-weight:700;margin-top:4px;">{{ $student->nim }}</div></div>
            <div><small style="color:#64748b;">Program Studi</small><div style="font-weight:700;margin-top:4px;">{{ $student->prodi?->nama_prodi ?? '-' }}</div></div>
            <div><small style="color:#64748b;">Angkatan</small><div style="font-weight:700;margin-top:4px;">{{ $student->angkatan ?? '-' }}</div></div>
        </div>
    </div>
</div>

@if(!$profile['supported'])
    <div class="page-card"><div class="page-card-body" style="text-align:center;padding:40px;color:#64748b;">Penilaian kemampuan untuk program studi ini belum tersedia.</div></div>
@elseif($profile['scored_aspects'] === 0)
    <div class="page-card"><div class="page-card-body" style="text-align:center;padding:40px;color:#64748b;">Belum ada nilai final yang dapat membentuk profil kemampuan.</div></div>
@else
    <div class="ability-summary">
        <div class="ability-summary-card">
            <small style="color:#64748b;">Kemampuan Paling Kuat</small>
            <h3 style="margin:7px 0;color:#0a1f5c;">{{ $profile['strongest']->label }}</h3>
            <span>{{ number_format($profile['strongest']->score, 2) }} · {{ $profile['strongest']->category }}</span>
        </div>
        <div class="ability-summary-card" style="border-color:#fed7aa;background:#fffaf5;">
            <small style="color:#64748b;">Kemampuan yang Perlu Ditingkatkan</small>
            <h3 style="margin:7px 0;color:#9a3412;">{{ $profile['weakest']->label }}</h3>
            <span>{{ number_format($profile['weakest']->score, 2) }} · {{ $profile['weakest']->category }}</span>
        </div>
    </div>

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2>Grafik Kemampuan</h2></div>
        <div class="page-card-body ability-chart" role="img" aria-label="Grafik batang profil kemampuan mahasiswa">
            @foreach($profile['aspects'] as $aspect)
                <div class="ability-chart-row">
                    <div class="ability-chart-label">{{ $showTechnical ? $aspect->kode_cpl.' · ' : '' }}{{ $aspect->label }}</div>
                    <div class="ability-track"><div class="ability-bar" style="width:{{ $aspect->score === null ? 0 : min(100, ($aspect->score / 4) * 100) }}%"></div></div>
                    <div class="ability-score">{{ $aspect->score === null ? '-' : number_format($aspect->score, 2) }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2>Ringkasan Aspek Penilaian</h2></div>
        <div class="page-card-body"><div class="table-wrap"><table>
            <thead><tr>@if($showTechnical)<th>Kode CPL</th>@endif<th>Aspek Penilaian</th><th>Skor</th><th>Kategori</th></tr></thead>
            <tbody>@foreach($profile['aspects'] as $aspect)<tr>
                @if($showTechnical)<td><strong>{{ $aspect->kode_cpl }}</strong></td>@endif
                <td>{{ $aspect->label }}</td><td>{{ $aspect->score === null ? '-' : number_format($aspect->score, 2) }}</td><td>{{ $aspect->category }}</td>
            </tr>@endforeach</tbody>
        </table></div></div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>Mata Kuliah Pendukung</h2></div>
        <div class="page-card-body">
            @foreach($profile['aspects'] as $aspect)
                @if($aspect->courses->isNotEmpty())
                    <details class="ability-detail">
                        <summary>{{ $showTechnical ? $aspect->kode_cpl.' · ' : '' }}{{ $aspect->label }} — {{ $aspect->courses->count() }} mata kuliah</summary>
                        <div class="ability-detail-body"><div class="table-wrap"><table>
                            <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>Semester</th><th>SKS</th><th>Nilai Mutu</th><th>Mutu × SKS</th></tr></thead>
                            <tbody>@foreach($aspect->courses as $course)<tr><td>{{ $loop->iteration }}</td><td>{{ $course->kode_mata_kuliah }}</td><td>{{ $course->nama_mata_kuliah }}</td><td>{{ $course->semester ?? '-' }}</td><td>{{ $course->sks }}</td><td>{{ number_format($course->bobot, 2) }}</td><td>{{ number_format($course->mutu_sks, 2) }}</td></tr>@endforeach</tbody>
                        </table></div></div>
                    </details>
                @endif
            @endforeach
        </div>
    </div>
@endif
