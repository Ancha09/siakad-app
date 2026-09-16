@extends('layouts.admin')

@section('title', 'Laporan Akademik')
@section('page-title', 'Laporan Akademik')
@section('page-subtitle', 'Rekap dan monitoring kegiatan akademik mahasiswa')

@push('styles')
<style>
    .laporan-page .report-actions { display:flex; gap:9px; flex-wrap:wrap; }
    .laporan-page .filter-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:13px; }
    .laporan-page .filter-actions { display:flex; align-items:flex-end; gap:9px; flex-wrap:wrap; }
    .laporan-page .summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(175px,1fr)); gap:14px; margin-bottom:20px; }
    .laporan-page .summary-card { border-radius:14px; padding:18px; border:1px solid #dbeafe; background:#f8fafc; }
    .laporan-page .summary-card span { color:#64748b; font-size:12px; font-weight:600; }
    .laporan-page .summary-card strong { display:block; color:#0f2a55; font-size:27px; margin:5px 0 2px; }
    .laporan-page .summary-card small { color:#64748b; }
    .laporan-page .chart-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; margin-bottom:20px; }
    .laporan-page .chart-box { min-height:330px; background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:18px; box-shadow:0 4px 16px rgba(15,42,85,.06); }
    .laporan-page .chart-box h3 { color:#0f2a55; font-size:14px; margin-bottom:4px; }
    .laporan-page .chart-box p { color:#64748b; font-size:11px; margin-bottom:14px; }
    .laporan-page .chart-wrap { position:relative; height:245px; }
    .laporan-page .fallback-bars { height:235px; display:flex; align-items:flex-end; justify-content:space-around; gap:8px; padding:18px 6px 0; border-bottom:1px solid #cbd5e1; }
    .laporan-page .fallback-bar-column { flex:1; min-width:24px; height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:5px; }
    .laporan-page .fallback-bar-value { color:#334155; font-size:10px; font-weight:700; }
    .laporan-page .fallback-bar { width:min(34px,75%); min-height:2px; border-radius:6px 6px 0 0; background:#2563eb; }
    .laporan-page .fallback-bar-label { color:#64748b; font-size:10px; white-space:nowrap; }
    .laporan-page .fallback-doughnut-layout { height:235px; display:flex; align-items:center; justify-content:center; gap:24px; }
    .laporan-page .fallback-doughnut { width:155px; height:155px; border-radius:50%; position:relative; flex-shrink:0; }
    .laporan-page .fallback-doughnut::after { content:''; position:absolute; inset:35px; border-radius:50%; background:#fff; }
    .laporan-page .fallback-legend { display:flex; flex-direction:column; gap:8px; }
    .laporan-page .fallback-legend-item { display:flex; align-items:center; gap:7px; color:#475569; font-size:11px; }
    .laporan-page .fallback-legend-dot { width:10px; height:10px; border-radius:3px; flex-shrink:0; }
    .laporan-page .fallback-horizontal { height:235px; overflow:auto; display:flex; flex-direction:column; justify-content:center; gap:10px; }
    .laporan-page .fallback-horizontal-row { display:grid; grid-template-columns:minmax(90px,1fr) 2fr 36px; gap:8px; align-items:center; font-size:10px; color:#475569; }
    .laporan-page .fallback-track { height:11px; overflow:hidden; border-radius:999px; background:#e2e8f0; }
    .laporan-page .fallback-fill { height:100%; border-radius:999px; background:#0f766e; }
    .laporan-page .fallback-empty { height:235px; display:flex; align-items:center; justify-content:center; color:#64748b; text-align:center; }
    .laporan-page .mini-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(155px,1fr)); gap:12px; margin-bottom:18px; }
    .laporan-page .mini-stat { padding:15px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; }
    .laporan-page .mini-stat span { display:block; color:#64748b; font-size:11px; }
    .laporan-page .mini-stat strong { display:block; color:#0f2a55; font-size:23px; margin-top:4px; }
    .laporan-page .grade-list { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
    .laporan-page .grade-pill { min-width:68px; padding:9px 12px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; text-align:center; }
    .laporan-page .grade-pill strong { display:block; color:#1d4ed8; font-size:18px; }
    .laporan-page .section-note { padding:12px 14px; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; border-radius:10px; margin-bottom:16px; font-size:12px; line-height:1.6; }
    .laporan-page .score { color:#1d4ed8; font-weight:800; }
    .laporan-page .danger-score { color:#dc2626; font-weight:800; }
    .laporan-page .sub-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:18px; }
    .laporan-page .sub-panel { border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; }
    .laporan-page .sub-panel h3 { padding:13px 15px; color:#0f2a55; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; }
    .laporan-page .pagination-wrap { margin-top:16px; }
    .laporan-page .empty { text-align:center; padding:28px; color:#64748b; }
    @media (max-width:1100px) { .laporan-page .chart-grid { grid-template-columns:1fr 1fr; } }
    @media (max-width:760px) {
        .laporan-page .chart-grid, .laporan-page .sub-grid { grid-template-columns:1fr; }
        .laporan-page .page-card-head { align-items:flex-start; gap:12px; flex-direction:column; }
        .laporan-page .report-actions { width:100%; }
        .laporan-page .report-actions a { flex:1; justify-content:center; text-align:center; }
    }
</style>
@endpush

@section('content')
<div class="laporan-page">
    <div class="page-card">
        <div class="page-card-head">
            <h2>📚 Laporan Akademik</h2>
            <div class="report-actions">
                <a href="{{ route('admin.laporan.excel', request()->query()) }}" class="btn-primary">📊 Download Excel (.xlsx)</a>
                <a href="{{ route('admin.laporan.pdf', request()->query()) }}" class="btn-outline">📄 Download PDF</a>
            </div>
        </div>
        <div class="page-card-body">
            <form method="GET" action="{{ route('admin.laporan.index') }}">
                <div class="filter-grid">
                    <div class="form-group"><label>Tahun Akademik</label><select name="tahun_akademik" class="form-control"><option value="">Semua Tahun</option>@foreach($tahunAkademik as $tahun)<option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Semester Akademik</label><select name="semester_akademik" class="form-control"><option value="">Semua Semester</option><option value="Ganjil" @selected(request('semester_akademik') === 'Ganjil')>Ganjil</option><option value="Genap" @selected(request('semester_akademik') === 'Genap')>Genap</option></select></div>
                    <div class="form-group"><label>Fakultas</label><select name="fakultas_id" class="form-control"><option value="">Semua Fakultas</option>@foreach($fakultas as $item)<option value="{{ $item->id }}" @selected((string) request('fakultas_id') === (string) $item->id)>{{ $item->nama_fakultas }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Program Studi</label><select name="prodi_id" class="form-control"><option value="">Semua Program Studi</option>@foreach($prodis as $item)<option value="{{ $item->id }}" @selected((string) request('prodi_id') === (string) $item->id)>{{ $item->nama_prodi }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Kelas</label><select name="kelas_id" class="form-control"><option value="">Semua Kelas</option>@foreach($kelases as $item)<option value="{{ $item->id }}" @selected((string) request('kelas_id') === (string) $item->id)>{{ $item->nama_kelas }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Mata Kuliah</label><select name="mata_kuliah_id" class="form-control"><option value="">Semua Mata Kuliah</option>@foreach($mataKuliahs as $item)<option value="{{ $item->id }}" @selected((string) request('mata_kuliah_id') === (string) $item->id)>{{ $item->kode_mk }} - {{ $item->nama_mk }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Dosen</label><select name="dosen_id" class="form-control"><option value="">Semua Dosen</option>@foreach($dosens as $item)<option value="{{ $item->id }}" @selected((string) request('dosen_id') === (string) $item->id)>{{ $item->nama }}</option>@endforeach</select></div>
                    <div class="filter-actions"><button type="submit" class="btn-primary">🔎 Tampilkan</button><a href="{{ route('admin.laporan.index') }}" class="btn-outline">Reset</a></div>
                </div>
            </form>
        </div>
    </div>

    <div class="summary-grid">
        <div class="summary-card"><span>Jumlah Mahasiswa</span><strong>{{ $ringkasan['jumlah_mahasiswa'] }}</strong><small>Mahasiswa pada hasil filter</small></div>
        <div class="summary-card"><span>Jumlah Mata Kuliah</span><strong>{{ $ringkasan['jumlah_mata_kuliah'] }}</strong><small>Mata kuliah pada hasil filter</small></div>
        <div class="summary-card"><span>Rata-rata IP</span><strong>{{ number_format($ringkasan['rata_ip'], 2) }}</strong><small>Berdasarkan SKS × bobot KHS</small></div>
        <div class="summary-card"><span>Rata-rata Kehadiran</span><strong>{{ number_format($ringkasan['rata_kehadiran'], 1) }}%</strong><small>Hadir dari seluruh catatan</small></div>
        <div class="summary-card"><span>Kehadiran &lt; 75%</span><strong>{{ $ringkasan['mahasiswa_kehadiran_rendah'] }}</strong><small>Jumlah mahasiswa unik</small></div>
    </div>

    <div class="chart-grid">
        <div class="chart-box"><h3>Distribusi Nilai</h3><p>Jumlah KHS berdasarkan nilai huruf aktual</p><div class="chart-wrap"><canvas id="chartNilaiLaporan"></canvas></div></div>
        <div class="chart-box"><h3>Rekap Kehadiran</h3><p>Komposisi seluruh status presensi</p><div class="chart-wrap"><canvas id="chartPresensiLaporan"></canvas></div></div>
        <div class="chart-box"><h3>Evaluasi per Dosen</h3><p>Maksimal 10 dosen, skala 1–5</p><div class="chart-wrap"><canvas id="chartEvaluasiLaporan"></canvas></div></div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>🎓 Rekap Nilai</h2></div>
        <div class="page-card-body">
            <div class="grade-list">@foreach($distribusiNilai as $huruf => $jumlah)<div class="grade-pill"><span>{{ $huruf }}</span><strong>{{ $jumlah }}</strong></div>@endforeach</div>
            <div class="table-wrap"><table><thead><tr><th>Mata Kuliah</th><th>Dosen</th><th>Kelas</th><th>Mahasiswa</th><th>Rata Nilai</th><th>Rata Bobot</th><th>Tertinggi</th><th>Terendah</th></tr></thead><tbody>
                @forelse($rekapNilai as $item)<tr><td><strong>{{ $item->mata_kuliah }}</strong></td><td>{{ $item->dosen }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_mahasiswa }}</td><td class="score">{{ number_format($item->rata_nilai, 2) }}</td><td>{{ number_format($item->rata_bobot, 2) }}</td><td>{{ number_format($item->tertinggi, 2) }}</td><td>{{ number_format($item->terendah, 2) }}</td></tr>@empty<tr><td colspan="8" class="empty">Belum ada data nilai pada filter ini.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>✅ Rekap Presensi</h2></div>
        <div class="page-card-body">
            <div class="mini-grid">
                <div class="mini-stat"><span>Total Hadir</span><strong>{{ $rekapPresensi['hadir'] }}</strong></div><div class="mini-stat"><span>Total Izin</span><strong>{{ $rekapPresensi['izin'] }}</strong></div><div class="mini-stat"><span>Total Sakit</span><strong>{{ $rekapPresensi['sakit'] }}</strong></div><div class="mini-stat"><span>Total Alpha</span><strong>{{ $rekapPresensi['alpha'] }}</strong></div><div class="mini-stat"><span>Rata-rata Kehadiran</span><strong>{{ number_format($ringkasan['rata_kehadiran'], 1) }}%</strong></div><div class="mini-stat"><span>Mahasiswa &lt; 75%</span><strong>{{ $ringkasan['mahasiswa_kehadiran_rendah'] }}</strong></div>
            </div>
            <h3 style="margin-bottom:12px;color:#0f2a55;">Mahasiswa dengan Kehadiran di Bawah 75%</h3>
            <div class="table-wrap"><table><thead><tr><th>No</th><th>NIM</th><th>Nama Mahasiswa</th><th>Program Studi</th><th>Kelas</th><th>Mata Kuliah</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpha</th><th>Kehadiran</th></tr></thead><tbody>
                @forelse($kehadiranRendah as $item)<tr><td>{{ $kehadiranRendah->firstItem() + $loop->index }}</td><td>{{ $item->nim }}</td><td><strong>{{ $item->nama }}</strong></td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->hadir }}</td><td>{{ $item->izin }}</td><td>{{ $item->sakit }}</td><td>{{ $item->alpha }}</td><td class="danger-score">{{ number_format($item->persentase, 1) }}%</td></tr>@empty<tr><td colspan="11" class="empty">Tidak ada mahasiswa dengan kehadiran di bawah 75%.</td></tr>@endforelse
            </tbody></table></div><div class="pagination-wrap">{{ $kehadiranRendah->appends(request()->query())->onEachSide(1)->links() }}</div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>📋 Rekap KRS</h2></div>
        <div class="page-card-body"><div class="mini-grid">
            <div class="mini-stat"><span>Menunggu</span><strong>{{ $rekapKrs['menunggu'] }}</strong></div><div class="mini-stat"><span>Disetujui</span><strong>{{ $rekapKrs['disetujui'] }}</strong></div><div class="mini-stat"><span>Ditolak</span><strong>{{ $rekapKrs['ditolak'] }}</strong></div><div class="mini-stat"><span>Mahasiswa Mengajukan</span><strong>{{ $rekapKrs['mahasiswa_mengajukan'] }}</strong></div><div class="mini-stat"><span>Total SKS Disetujui</span><strong>{{ $rekapKrs['sks_disetujui'] }}</strong></div>@if($rekapKrs['diambil_legacy'] > 0)<div class="mini-stat"><span>Diambil (Data Legacy)</span><strong>{{ $rekapKrs['diambil_legacy'] }}</strong></div>@endif
        </div></div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>📝 Rekap Evaluasi Perkuliahan</h2></div>
        <div class="page-card-body">
            <div class="mini-grid"><div class="mini-stat"><span>Jumlah Responden</span><strong>{{ $rekapKuesioner['jumlah_responden'] }}</strong></div><div class="mini-stat"><span>Sudah Mengisi</span><strong>{{ $rekapKuesioner['sudah_mengisi'] }}</strong></div><div class="mini-stat"><span>Belum Mengisi</span><strong>{{ $rekapKuesioner['belum_mengisi'] }}</strong></div><div class="mini-stat"><span>Rata-rata Skor</span><strong>{{ number_format($rekapKuesioner['rata_rata'], 2) }}/5</strong></div></div>
            <div class="section-note">Jumlah sudah/belum mengisi dihitung dari KRS berstatus Disetujui yang telah memiliki nilai KHS. Komentar tetap ditampilkan tanpa nama atau NIM mahasiswa.</div>
            <div class="table-wrap"><table><thead><tr><th>Dosen</th><th>Mata Kuliah</th><th>Kelas</th><th>Jumlah Responden</th><th>Rata-rata Skor</th></tr></thead><tbody>
                @forelse($rekapEvaluasi as $item)<tr><td><strong>{{ $item->dosen }}</strong></td><td>{{ $item->mata_kuliah }}</td><td>{{ $item->kelas }}</td><td>{{ $item->jumlah_responden }}</td><td class="score">{{ number_format($item->rata_rata, 2) }}/5</td></tr>@empty<tr><td colspan="5" class="empty">Belum ada hasil kuesioner pada filter ini.</td></tr>@endforelse
            </tbody></table></div>
            <div class="sub-grid">
                <div class="sub-panel"><h3>Rata-rata Evaluasi per Dosen</h3><div class="table-wrap"><table><thead><tr><th>Dosen</th><th>Responden</th><th>Skor</th></tr></thead><tbody>@forelse($evaluasiDosen as $item)<tr><td>{{ $item->dosen }}</td><td>{{ $item->jumlah_responden }}</td><td class="score">{{ number_format($item->rata_rata, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty">Belum ada data.</td></tr>@endforelse</tbody></table></div></div>
                <div class="sub-panel"><h3>Rata-rata Evaluasi per Mata Kuliah</h3><div class="table-wrap"><table><thead><tr><th>Mata Kuliah</th><th>Responden</th><th>Skor</th></tr></thead><tbody>@forelse($evaluasiMataKuliah as $item)<tr><td>{{ $item->mata_kuliah }}</td><td>{{ $item->jumlah_responden }}</td><td class="score">{{ number_format($item->rata_rata, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty">Belum ada data.</td></tr>@endforelse</tbody></table></div></div>
            </div>
            @if($komentarKuesioner->isNotEmpty())<h3 style="margin:20px 0 12px;color:#0f2a55;">Komentar Terbaru (Anonim)</h3><div class="table-wrap"><table><thead><tr><th>Kode Responden</th><th>Dosen</th><th>Mata Kuliah</th><th>Komentar</th><th>Tanggal</th></tr></thead><tbody>@foreach($komentarKuesioner as $item)<tr><td><strong>{{ $item->kode }}</strong></td><td>{{ $item->dosen }}</td><td>{{ $item->mata_kuliah }}</td><td style="min-width:280px;">{{ $item->komentar }}</td><td>{{ $item->tanggal?->format('d-m-Y') }}</td></tr>@endforeach</tbody></table></div>@endif
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head"><h2>👨‍🎓 Ringkasan Akademik Mahasiswa</h2></div>
        <div class="page-card-body">
            <div class="section-note">IP Akademik mengikuti data KHS pada filter aktif dengan rumus total (SKS × bobot) dibagi total SKS bernilai. Status akademik tidak ditampilkan karena tidak tersedia sebagai kolom di database.</div>
            <div class="table-wrap"><table><thead><tr><th>No</th><th>NIM</th><th>Nama Mahasiswa</th><th>Program Studi</th><th>Kelas</th><th>Total SKS Disetujui</th><th>IP Akademik</th><th>Kehadiran</th></tr></thead><tbody>
                @forelse($detailMahasiswa as $item)<tr><td>{{ $detailMahasiswa->firstItem() + $loop->index }}</td><td>{{ $item->nim }}</td><td><strong>{{ $item->nama }}</strong></td><td>{{ $item->prodi }}</td><td>{{ $item->kelas }}</td><td>{{ $item->total_sks }}</td><td class="score">{{ $item->ip === null ? '-' : number_format($item->ip, 2) }}</td><td class="{{ $item->kehadiran !== null && $item->kehadiran < 75 ? 'danger-score' : '' }}">{{ $item->kehadiran === null ? '-' : number_format($item->kehadiran, 1).'%' }}</td></tr>@empty<tr><td colspan="8" class="empty">Belum ada data akademik mahasiswa pada filter ini.</td></tr>@endforelse
            </tbody></table></div><div class="pagination-wrap">{{ $detailMahasiswa->appends(request()->query())->onEachSide(1)->links() }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function renderLaporanCharts() {
    const nilaiData = JSON.parse(atob('{{ base64_encode(json_encode($chartNilai)) }}'));
    const presensiData = JSON.parse(atob('{{ base64_encode(json_encode($chartPresensi)) }}'));
    const evaluasiData = JSON.parse(atob('{{ base64_encode(json_encode($chartEvaluasi)) }}'));

    if (typeof Chart === 'undefined') {
        renderVerticalBars('chartNilaiLaporan', nilaiData);
        renderDoughnut('chartPresensiLaporan', presensiData, ['#16a34a','#ca8a04','#2563eb','#dc2626']);
        renderHorizontalBars('chartEvaluasiLaporan', evaluasiData, 5);
        return;
    }

    const commonOptions = { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } };
    new Chart(document.getElementById('chartNilaiLaporan'), { type:'bar', data:{ labels:nilaiData.labels, datasets:[{ label:'Jumlah Nilai', data:nilaiData.data, backgroundColor:'#2563eb', borderRadius:6 }] }, options:{ ...commonOptions, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } } });
    new Chart(document.getElementById('chartPresensiLaporan'), { type:'doughnut', data:{ labels:presensiData.labels, datasets:[{ data:presensiData.data, backgroundColor:['#16a34a','#ca8a04','#2563eb','#dc2626'] }] }, options:commonOptions });
    new Chart(document.getElementById('chartEvaluasiLaporan'), { type:'bar', data:{ labels:evaluasiData.labels, datasets:[{ label:'Rata-rata Skor', data:evaluasiData.data, backgroundColor:'#0f766e', borderRadius:6 }] }, options:{ ...commonOptions, indexAxis:'y', scales:{ x:{ beginAtZero:true, max:5 } } } });
}

function fallbackHost(canvasId, className) {
    const canvas = document.getElementById(canvasId);
    canvas.style.display = 'none';
    const host = document.createElement('div');
    host.className = className;
    canvas.parentElement.appendChild(host);
    return host;
}

function renderVerticalBars(canvasId, payload) {
    const host = fallbackHost(canvasId, 'fallback-bars');
    const values = payload.data.map(Number);
    const maximum = Math.max(1, ...values);

    payload.labels.forEach(function (label, index) {
        const column = document.createElement('div');
        column.className = 'fallback-bar-column';
        const value = document.createElement('span');
        value.className = 'fallback-bar-value';
        value.textContent = values[index] || 0;
        const bar = document.createElement('div');
        bar.className = 'fallback-bar';
        bar.style.height = Math.max(2, ((values[index] || 0) / maximum) * 175) + 'px';
        const caption = document.createElement('span');
        caption.className = 'fallback-bar-label';
        caption.textContent = label;
        column.append(value, bar, caption);
        host.appendChild(column);
    });
}

function renderDoughnut(canvasId, payload, colors) {
    const host = fallbackHost(canvasId, 'fallback-doughnut-layout');
    const values = payload.data.map(Number);
    const total = values.reduce((sum, value) => sum + value, 0);
    const doughnut = document.createElement('div');
    doughnut.className = 'fallback-doughnut';

    if (total > 0) {
        let position = 0;
        const segments = values.map(function (value, index) {
            const start = position;
            position += (value / total) * 100;
            return colors[index] + ' ' + start.toFixed(2) + '% ' + position.toFixed(2) + '%';
        });
        doughnut.style.background = 'conic-gradient(' + segments.join(',') + ')';
    } else {
        doughnut.style.background = '#e2e8f0';
    }

    const legend = document.createElement('div');
    legend.className = 'fallback-legend';
    payload.labels.forEach(function (label, index) {
        const item = document.createElement('div');
        item.className = 'fallback-legend-item';
        const dot = document.createElement('span');
        dot.className = 'fallback-legend-dot';
        dot.style.background = colors[index];
        const text = document.createElement('span');
        text.textContent = label + ': ' + (values[index] || 0);
        item.append(dot, text);
        legend.appendChild(item);
    });
    host.append(doughnut, legend);
}

function renderHorizontalBars(canvasId, payload, maximum) {
    if (!payload.labels.length) {
        const empty = fallbackHost(canvasId, 'fallback-empty');
        empty.textContent = 'Belum ada data evaluasi pada filter ini.';
        return;
    }

    const host = fallbackHost(canvasId, 'fallback-horizontal');
    payload.labels.forEach(function (label, index) {
        const value = Number(payload.data[index] || 0);
        const row = document.createElement('div');
        row.className = 'fallback-horizontal-row';
        const caption = document.createElement('span');
        caption.textContent = label;
        const track = document.createElement('div');
        track.className = 'fallback-track';
        const fill = document.createElement('div');
        fill.className = 'fallback-fill';
        fill.style.width = Math.min(100, (value / maximum) * 100) + '%';
        const score = document.createElement('strong');
        score.textContent = value.toFixed(2);
        track.appendChild(fill);
        row.append(caption, track, score);
        host.appendChild(row);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderLaporanCharts);
} else {
    renderLaporanCharts();
}
</script>
@endpush
