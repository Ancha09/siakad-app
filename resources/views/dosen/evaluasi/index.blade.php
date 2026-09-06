@extends('layouts.dosen')

@section('title', 'Hasil Evaluasi')
@section('page-title', 'Evaluasi Pengajaran')
@section('page-subtitle', 'Ringkasan penilaian dan saran mahasiswa secara anonim')

@section('content')
<div class="page-card">
    <div class="page-card-head"><h2>📊 Hasil Evaluasi Pengajaran</h2></div>
    <div class="page-card-body">
        <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;padding:14px 16px;border-radius:10px;margin-bottom:20px;line-height:1.6;">
            Hasil ditampilkan secara anonim. Identitas mahasiswa tidak tersedia pada halaman evaluasi ini.
        </div>

        <form method="GET" action="{{ route('dosen.evaluasi') }}" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:17px;margin-bottom:22px;">
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
                    <label>Mata Kuliah</label>
                    <select name="mata_kuliah_id" class="form-control">
                        <option value="">Semua Mata Kuliah</option>
                        @foreach($jadwals as $jadwal)
                            <option value="{{ $jadwal->mata_kuliah_id }}" @selected((string) request('mata_kuliah_id') === (string) $jadwal->mata_kuliah_id)>
                                {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:end;gap:9px;">
                    <button type="submit" class="btn-primary">Terapkan</button>
                    <a href="{{ route('dosen.evaluasi') }}" class="btn-outline">Reset</a>
                </div>
            </div>
        </form>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:24px;">
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:19px;">
                <small style="color:#475569;">Jumlah Responden</small>
                <div style="font-size:30px;font-weight:800;color:#1d4ed8;margin-top:5px;">{{ $jumlahResponden }}</div>
            </div>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:19px;">
                <small style="color:#475569;">Rata-rata Keseluruhan</small>
                <div style="font-size:30px;font-weight:800;color:#15803d;margin-top:5px;">{{ number_format($rataRata, 2) }} <small>/ 5</small></div>
            </div>
        </div>

        <h3 style="margin-bottom:14px;">Rata-rata Setiap Indikator</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:12px;margin-bottom:28px;">
            @foreach($pertanyaan as $kolom => $label)
                <div style="border:1px solid #e2e8f0;border-radius:10px;padding:14px;display:flex;justify-content:space-between;gap:12px;">
                    <span>{{ $label }}</span>
                    <strong style="color:#1d4ed8;white-space:nowrap;">{{ number_format($rataPertanyaan[$kolom], 2) }} / 5</strong>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>💬 Saran Mahasiswa (Anonim)</h2></div>
    <div class="page-card-body">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Kode Responden</th><th>Mata Kuliah/Kelas</th><th>Skor</th><th>Saran atau Komentar</th><th>Tanggal</th></tr></thead>
                <tbody>
                    @forelse($komentar as $item)
                        <tr>
                            <td><strong>{{ $item->kode_responden }}</strong></td>
                            <td>{{ $item->krs->jadwal->mataKuliah->nama_mk ?? '-' }}<br><small>{{ $item->krs->jadwal->kelas->nama_kelas ?? '-' }}</small></td>
                            <td>{{ number_format($item->rata_rata, 2) }} / 5</td>
                            <td style="min-width:300px;">{{ $item->komentar }}</td>
                            <td>{{ $item->submitted_at?->format('d-m-Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:30px;">Belum ada komentar pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $komentar->links() }}</div>
    </div>
</div>
@endsection
