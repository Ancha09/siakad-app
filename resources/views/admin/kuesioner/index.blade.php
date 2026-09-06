@extends('layouts.admin')

@section('title', 'Rekap Kuesioner')
@section('page-title', 'Evaluasi Dosen')
@section('page-subtitle', 'Rekap kuesioner, hasil evaluasi, dan status pengisian mahasiswa')

@push('styles')
<style>
    .question-score { display:flex; justify-content:space-between; gap:12px; padding:7px 0; border-bottom:1px solid #e2e8f0; }
    .question-score:last-child { border-bottom:0; }
    .score-pill { min-width:46px; text-align:center; border-radius:999px; padding:4px 8px; background:#dbeafe; color:#1d4ed8; font-weight:700; }
    .status-filled { background:#dcfce7; color:#166534; padding:6px 10px; border-radius:999px; font-weight:700; white-space:nowrap; }
    .status-empty { background:#fee2e2; color:#991b1b; padding:6px 10px; border-radius:999px; font-weight:700; white-space:nowrap; }
</style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <h2>📊 Rekap Kuesioner & Evaluasi Dosen</h2>
    </div>
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.kuesioner') }}" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;margin-bottom:22px;">
            <div style="font-weight:700;margin-bottom:14px;">🔎 Filter Rekap</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:13px;">
                <div class="form-group">
                    <label>Tahun Akademik</label>
                    <select name="tahun_akademik" class="form-control">
                        <option value="">Semua Tahun</option>
                        @foreach($tahunAkademik as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') == $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester_akademik" class="form-control">
                        <option value="">Semua Semester</option>
                        <option value="Ganjil" @selected(request('semester_akademik') === 'Ganjil')>Ganjil</option>
                        <option value="Genap" @selected(request('semester_akademik') === 'Genap')>Genap</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dosen</label>
                    <select name="dosen_id" class="form-control">
                        <option value="">Semua Dosen</option>
                        @foreach($dosens as $dosen)
                            <option value="{{ $dosen->id }}" @selected((string) request('dosen_id') === (string) $dosen->id)>{{ $dosen->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Mata Kuliah</label>
                    <select name="mata_kuliah_id" class="form-control">
                        <option value="">Semua Mata Kuliah</option>
                        @foreach($mataKuliahs as $mk)
                            <option value="{{ $mk->id }}" @selected((string) request('mata_kuliah_id') === (string) $mk->id)>{{ $mk->kode_mk }} - {{ $mk->nama_mk }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Program Studi</label>
                    <select name="prodi_id" class="form-control">
                        <option value="">Semua Prodi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-control">
                        <option value="">Semua Kelas</option>
                        @foreach($kelases as $kelas)
                            <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Status Pengisian</label>
                    <select name="status_pengisian" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="sudah" @selected(request('status_pengisian') === 'sudah')>Sudah Mengisi</option>
                        <option value="belum" @selected(request('status_pengisian') === 'belum')>Belum Mengisi</option>
                    </select>
                </div>
                <div style="display:flex;align-items:end;gap:9px;">
                    <button class="btn-primary" type="submit">Terapkan</button>
                    <a class="btn-outline" href="{{ route('admin.kuesioner') }}">Reset</a>
                </div>
            </div>
        </form>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:25px;">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px;">
                <small style="color:#475569;">Wajib Mengisi</small><div style="font-size:28px;font-weight:800;color:#1d4ed8;">{{ $totalWajib }}</div>
            </div>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:18px;">
                <small style="color:#475569;">Sudah Mengisi</small><div style="font-size:28px;font-weight:800;color:#15803d;">{{ $totalSudah }}</div>
            </div>
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:18px;">
                <small style="color:#475569;">Belum Mengisi</small><div style="font-size:28px;font-weight:800;color:#b91c1c;">{{ $totalBelum }}</div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>📈 Ringkasan Evaluasi Dosen</h2></div>
    <div class="page-card-body">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Dosen</th><th>Mata Kuliah/Kelas</th><th>Responden</th><th>Nilai Rata-rata</th><th>Rincian Indikator</th></tr></thead>
                <tbody>
                    @forelse($rekapDosen as $rekap)
                        <tr>
                            <td><strong>{{ $rekap->jadwal->dosen->nama ?? '-' }}</strong></td>
                            <td>{{ $rekap->jadwal->mataKuliah->nama_mk ?? '-' }}<br><small>{{ $rekap->jadwal->kelas->nama_kelas ?? '-' }}</small></td>
                            <td>{{ $rekap->jumlah_responden }}</td>
                            <td><span class="score-pill">{{ number_format($rekap->rata_rata, 2) }}</span> / 5</td>
                            <td style="min-width:310px;">
                                @foreach($pertanyaan as $kolom => $label)
                                    <div class="question-score"><span>{{ $label }}</span><strong>{{ number_format($rekap->rata_pertanyaan[$kolom], 2) }}</strong></div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:30px;">Belum ada hasil evaluasi pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>🔐 Jawaban Kuesioner Anonim</h2></div>
    <div class="page-card-body">
        <div style="background:#eff6ff;color:#1e40af;padding:13px 16px;border-radius:10px;margin-bottom:16px;">
            Nama dan NIM tidak ditampilkan pada bagian jawaban untuk menjaga kerahasiaan responden.
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode Responden</th><th>Dosen/Mata Kuliah</th><th>Skor</th><th>Rincian Jawaban</th><th>Komentar</th><th>Dikirim</th></tr></thead>
                <tbody>
                    @forelse($jawaban as $item)
                        <tr>
                            <td><strong>{{ $item->kode_responden }}</strong></td>
                            <td>{{ $item->krs->jadwal->dosen->nama ?? '-' }}<br><small>{{ $item->krs->jadwal->mataKuliah->nama_mk ?? '-' }} · {{ $item->krs->jadwal->kelas->nama_kelas ?? '-' }}</small></td>
                            <td><span class="score-pill">{{ number_format($item->rata_rata, 2) }}</span></td>
                            <td style="min-width:310px;">
                                @foreach($pertanyaan as $kolom => $label)
                                    <div class="question-score"><span>{{ $label }}</span><strong>{{ $item->{$kolom} }}/5</strong></div>
                                @endforeach
                            </td>
                            <td style="min-width:250px;">{{ $item->komentar ?: '—' }}</td>
                            <td>{{ $item->submitted_at?->format('d-m-Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:30px;">Belum ada jawaban kuesioner.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $jawaban->links() }}</div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>✅ Status Pengisian Mahasiswa</h2></div>
    <div class="page-card-body">
        <div style="background:#fff7ed;color:#9a3412;padding:13px 16px;border-radius:10px;margin-bottom:16px;">
            Nama hanya ditampilkan di daftar status ini untuk membantu admin memantau mahasiswa yang belum mengisi. Jawabannya tetap anonim.
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>NIM</th><th>Nama Mahasiswa</th><th>Prodi/Kelas</th><th>Mata Kuliah</th><th>Dosen</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($statusMahasiswa as $item)
                        <tr>
                            <td>{{ $item->mahasiswa->nim ?? '-' }}</td>
                            <td><strong>{{ $item->mahasiswa->nama ?? '-' }}</strong></td>
                            <td>{{ $item->mahasiswa->prodi->nama_prodi ?? '-' }}<br><small>{{ $item->mahasiswa->kelas->nama_kelas ?? '-' }}</small></td>
                            <td>{{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}</td>
                            <td>{{ $item->jadwal->dosen->nama ?? '-' }}</td>
                            <td>
                                @if($item->kuesioner)<span class="status-filled">Sudah Mengisi</span>
                                @else<span class="status-empty">Belum Mengisi</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:30px;">Tidak ada data status pengisian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $statusMahasiswa->links() }}</div>
    </div>
</div>
@endsection
