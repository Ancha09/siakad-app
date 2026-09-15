@extends('layouts.mahasiswa')

@section('title', 'Presensi Mahasiswa')

@push('styles')
<style>
    .student-attendance .attendance-toolbar { display:flex; justify-content:space-between; align-items:end; gap:16px; margin-bottom:20px; }
    .student-attendance .attendance-filter { display:flex; align-items:end; gap:9px; }
    .student-attendance .attendance-filter .form-control { min-width:190px; }
    .student-attendance .course-card { margin-bottom:18px; overflow:hidden; }
    .student-attendance .course-heading { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; width:100%; }
    .student-attendance .course-code { display:inline-block; margin-bottom:6px; color:#2563eb; font-size:11px; font-weight:700; }
    .student-attendance .course-meta { margin-top:5px; color:#64748b; font-size:11px; line-height:1.6; }
    .student-attendance .course-percent { min-width:88px; text-align:right; }
    .student-attendance .course-percent strong { display:block; color:#0a1f5c; font:700 24px 'Sora',sans-serif; }
    .student-attendance .course-percent span { color:#64748b; font-size:10px; }
    .student-attendance .attendance-counts { display:grid; grid-template-columns:repeat(5,minmax(85px,1fr)); gap:10px; margin-bottom:16px; }
    .student-attendance .count-box { padding:11px 12px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; text-align:center; }
    .student-attendance .count-box strong { display:block; color:#0a1f5c; font-size:18px; }
    .student-attendance .count-box span { color:#64748b; font-size:10px; }
    .student-attendance details { border-top:1px solid #e2e8f0; padding-top:13px; }
    .student-attendance summary { width:max-content; color:#1d4ed8; font-size:12px; font-weight:700; cursor:pointer; }
    .student-attendance .history { margin-top:13px; }
    .student-attendance .status-empty { color:#94a3b8; font-size:11px; }
    .student-attendance .empty-state { padding:48px 18px; text-align:center; color:#64748b; }
    .student-attendance .empty-state strong { display:block; margin-bottom:6px; color:#334155; font-size:15px; }
    .student-attendance .note { margin-top:8px; color:#64748b; font-size:11px; }
    .student-attendance .attendance-progress { display:block; width:100%; height:7px; margin:4px 0; border:0; border-radius:10px; overflow:hidden; appearance:none; background:var(--gray-200); }
    .student-attendance .attendance-progress::-webkit-progress-bar { background:var(--gray-200); border-radius:10px; }
    .student-attendance .attendance-progress::-webkit-progress-value { background:linear-gradient(90deg,#16a34a,#4ade80); border-radius:10px; }
    .student-attendance .attendance-progress::-moz-progress-bar { background:linear-gradient(90deg,#16a34a,#4ade80); border-radius:10px; }
    .student-attendance .attendance-progress-low::-webkit-progress-value { background:linear-gradient(90deg,#dc2626,#f97316); }
    .student-attendance .attendance-progress-low::-moz-progress-bar { background:linear-gradient(90deg,#dc2626,#f97316); }
    @media(max-width:800px) {
        .student-attendance .attendance-counts { grid-template-columns:repeat(3,1fr); }
        .student-attendance .attendance-toolbar { align-items:stretch; flex-direction:column; }
    }
    @media(max-width:520px) {
        .student-attendance .attendance-counts { grid-template-columns:repeat(2,1fr); }
        .student-attendance .course-heading { flex-direction:column; }
        .student-attendance .course-percent { text-align:left; }
        .student-attendance .attendance-filter { align-items:stretch; flex-direction:column; }
        .student-attendance .attendance-filter .form-control { min-width:0; width:100%; }
    }
</style>
@endpush

@section('content')
<div class="inner-page student-attendance">
    <div class="attendance-toolbar">
        <div>
            <h2 style="margin:0;color:#0a1f5c;">Presensi Mata Kuliah</h2>
            <p style="margin-top:5px;color:#64748b;font-size:12px;">
                {{ $mahasiswa->nama }} · {{ $mahasiswa->prodi->nama_prodi ?? 'Program studi belum tersedia' }}
            </p>
        </div>

        @if($tahunAkademik->isNotEmpty())
            <form method="GET" class="attendance-filter">
                <div>
                    <label style="display:block;margin-bottom:5px;font-size:11px;font-weight:600;">Tahun Akademik</label>
                    <select name="tahun_akademik" class="form-control">
                        <option value="">Semua tahun akademik</option>
                        @foreach($tahunAkademik as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-primary">Tampilkan</button>
                @if(request()->filled('tahun_akademik'))<a href="{{ route('mahasiswa.presensi') }}" class="btn-outline">Reset</a>@endif
            </form>
        @endif
    </div>

    <div class="presensi-grid">
        <div class="presensi-stat"><h3>{{ $rataKehadiran === null ? '-' : number_format($rataKehadiran, 1).'%' }}</h3><p>Rata-rata Kehadiran</p></div>
        <div class="presensi-stat"><h3>{{ $mataKuliahs->count() }}</h3><p>Mata Kuliah Diambil</p></div>
        <div class="presensi-stat"><h3 style="color:#16a34a;">{{ $totalHadir }}</h3><p>Total Hadir</p></div>
        <div class="presensi-stat"><h3 style="color:var(--red);">{{ $totalAlpha }}</h3><p>Total Alpha</p></div>
    </div>

    @forelse($mataKuliahs as $item)
        @php
            $mk = $item->krs->mata_kuliah_efektif;
            $percentage = $item->persentase ?? 0;
        @endphp
        <section class="page-card course-card">
            <div class="page-card-head">
                <div class="course-heading">
                    <div>
                        <span class="course-code">{{ $mk->kode_mk }}</span>
                        <h2>{{ $mk->nama_mk }}</h2>
                        <div class="course-meta">
                            {{ $mk->sks }} SKS · {{ $item->krs->dosen_efektif?->nama ?? 'Dosen belum ditentukan' }}<br>
                            {{ $item->jadwal?->hari ?? 'Jadwal historis' }}, {{ $item->jadwal?->jam_mulai ? substr($item->jadwal->jam_mulai, 0, 5) : '-' }}–{{ $item->jadwal?->jam_selesai ? substr($item->jadwal->jam_selesai, 0, 5) : '-' }}
                            · {{ $item->krs->tahun_akademik ?? $item->jadwal->tahun_akademik ?? '-' }} {{ $item->krs->semester_akademik ?? $item->jadwal->semester_akademik ?? '' }}
                        </div>
                    </div>
                    <div class="course-percent">
                        <strong>{{ $item->persentase === null ? '-' : number_format($item->persentase, 1).'%' }}</strong>
                        <span>Kehadiran tercatat</span>
                    </div>
                </div>
            </div>
            <div class="page-card-body">
                <div class="attendance-counts">
                    <div class="count-box"><strong>{{ $item->hadir }}</strong><span>Hadir</span></div>
                    <div class="count-box"><strong>{{ $item->izin }}</strong><span>Izin</span></div>
                    <div class="count-box"><strong>{{ $item->sakit }}</strong><span>Sakit</span></div>
                    <div class="count-box"><strong>{{ $item->alpha }}</strong><span>Alpha</span></div>
                    <div class="count-box"><strong>{{ $item->total }}</strong><span>Total Tercatat</span></div>
                </div>

                <progress class="attendance-progress {{ $percentage < 75 ? 'attendance-progress-low' : '' }}"
                          value="{{ min(100, max(0, $percentage)) }}" max="100"
                          aria-label="Persentase kehadiran {{ number_format($percentage, 1) }} persen">
                    {{ number_format($percentage, 1) }}%
                </progress>
                <div class="note">
                    @if($item->persentase === null)
                        Dosen belum mencatat presensi pada mata kuliah ini.
                    @elseif($item->persentase >= 75)
                        <span class="badge badge-green">Kehadiran aman</span>
                    @else
                        <span class="badge badge-red">Kehadiran di bawah 75%</span>
                    @endif
                </div>

                <details>
                    <summary>Lihat riwayat pertemuan ({{ $item->riwayat->count() }})</summary>
                    <div class="table-wrap history">
                        <table>
                            <thead><tr><th>Pertemuan</th><th>Tanggal</th><th>Materi</th><th>Status</th><th>Keterangan</th></tr></thead>
                            <tbody>
                                @forelse($item->riwayat as $riwayat)
                                    @php($status = $riwayat->presensi?->status)
                                    <tr>
                                        <td>{{ $riwayat->pertemuan }}</td>
                                        <td>{{ $riwayat->tanggal ? \Carbon\Carbon::parse($riwayat->tanggal)->format('d/m/Y') : '-' }}</td>
                                        <td>
                                            @if($riwayat->materi)
                                                <a class="btn-outline" style="padding:4px 9px;font-size:10px;" href="{{ asset('storage/'.$riwayat->materi) }}" target="_blank" rel="noopener">Lihat Materi</a>
                                            @else
                                                <span class="status-empty">Tidak ada</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($status)
                                                <span class="badge {{ $status === 'Hadir' ? 'badge-green' : ($status === 'Alpha' ? 'badge-red' : 'badge-gold') }}">{{ $status }}</span>
                                            @else
                                                <span class="badge badge-gray">Belum dicatat</span>
                                            @endif
                                        </td>
                                        <td>{{ $riwayat->presensi?->keterangan ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="empty-state">Belum ada pertemuan yang dicatat dosen.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </section>
    @empty
        <div class="page-card">
            <div class="empty-state">
                <strong>Belum ada mata kuliah</strong>
                <p>Tidak ada KRS berstatus disetujui{{ request()->filled('tahun_akademik') ? ' pada tahun akademik yang dipilih' : '' }}.</p>
            </div>
        </div>
    @endforelse

    @if($totalTercatat > 0)
        <p class="note" style="text-align:right;">Persentase dihitung dari jumlah Hadir dibanding seluruh presensi yang sudah dicatat dosen. Izin: {{ $totalIzin }}, Sakit: {{ $totalSakit }}.</p>
    @endif
</div>
@endsection
