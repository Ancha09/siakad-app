@extends('layouts.dosen')

@section('title', 'Detail Pengajuan KRS')

@section('content')
<div class="inner-page">
    <div class="toolbar">
        <div>
            <h2 style="margin:0;">Detail Pengajuan KRS</h2>
            <p style="margin:5px 0 0;color:#64748b;">Periksa mata kuliah mahasiswa sebelum memberi keputusan.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;">
            @include('krs.partials.pdf-download-form', [
                'action' => route('dosen.krs.pdf', $mahasiswa),
                'tahunAkademik' => $year,
                'semesterAkademik' => $semester,
                'buttonLabel' => 'Download KRS PDF',
            ])
            <a href="{{ $listUrl }}" class="btn-outline">Kembali ke Daftar Mahasiswa</a>
        </div>
    </div>

    @if($errors->any())
        <div class="info-alert" style="margin-bottom:20px;color:#991b1b;">{{ $errors->first() }}</div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px;margin-bottom:20px;">
        <div class="page-card"><div class="page-card-body">
            <small style="color:#64748b;">Mahasiswa</small>
            <div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->nama }}</div>
            <div style="color:#64748b;font-size:13px;">{{ $mahasiswa->nim }}</div>
        </div></div>
        <div class="page-card"><div class="page-card-body">
            <small style="color:#64748b;">Program Studi</small>
            <div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</div>
            <div style="color:#64748b;font-size:13px;">Semester {{ $mahasiswa->semester ?? '-' }} · Angkatan {{ $mahasiswa->angkatan ?? '-' }}</div>
        </div></div>
        <div class="page-card"><div class="page-card-body">
            <small style="color:#64748b;">Dosen Wali</small>
            <div style="font-weight:700;margin-top:5px;">{{ $mahasiswa->dosenWali?->nama ?? '-' }}</div>
        </div></div>
        <div class="page-card"><div class="page-card-body">
            <small style="color:#64748b;">Periode KRS</small>
            <div style="font-weight:700;margin-top:5px;">{{ $year }} · {{ $semester }}</div>
            @if($periodeKrs)
                <div style="color:#64748b;font-size:13px;">
                    {{ $periodeKrs->tanggal_mulai->format('d/m/Y') }}–{{ $periodeKrs->tanggal_selesai->format('d/m/Y') }}
                </div>
            @endif
        </div></div>
    </div>

    <div class="page-card">
        <div class="page-card-head">
            <h3 style="margin:0;">Mata Kuliah yang Diajukan</h3>
            <span class="badge badge-blue">Total {{ $totalSks }} SKS</span>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Dosen Pengampu</th>
                            <th>Jadwal</th>
                            <th>Ruangan</th>
                            <th>Status</th>
                            <th style="min-width:180px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($krs as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->jadwal?->mataKuliah?->kode_mk ?? '-' }}</td>
                                <td><strong>{{ $item->jadwal?->mataKuliah?->nama_mk ?? '-' }}</strong></td>
                                <td>{{ (int) ($item->jadwal?->mataKuliah?->sks ?? 0) }}</td>
                                <td>{{ $item->jadwal?->dosen?->nama ?? '-' }}</td>
                                <td>
                                    {{ $item->jadwal?->hari ?? '-' }}<br>
                                    <small style="color:#64748b;">
                                        {{ $item->jadwal?->jam_mulai ?? '-' }}–{{ $item->jadwal?->jam_selesai ?? '-' }}
                                    </small>
                                </td>
                                <td>{{ $item->jadwal?->ruangan?->nama_ruangan ?? '-' }}</td>
                                <td>
                                    @if(in_array($item->status, ['Menunggu', 'Diambil'], true))
                                        <span class="badge badge-gold">Menunggu</span>
                                    @elseif($item->status === 'Disetujui')
                                        <span class="badge badge-green">Disetujui</span>
                                    @elseif($item->status === 'Ditolak')
                                        <span class="badge" style="background:#fee2e2;color:#b91c1c;">Ditolak</span>
                                        @if($item->alasan_penolakan)
                                            <br><small style="color:#991b1b;">{{ $item->alasan_penolakan }}</small>
                                        @endif
                                    @else
                                        <span class="badge badge-gray">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($item->status, ['Menunggu', 'Diambil'], true))
                                        <div style="display:flex;gap:7px;align-items:flex-start;flex-wrap:wrap;">
                                            <form method="POST" action="{{ route('dosen.krs.setujui', $item) }}" onsubmit="return confirm('Setujui mata kuliah ini?')">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="return_url" value="{{ $listUrl }}">
                                                <button type="submit" class="btn-primary" style="padding:6px 10px;font-size:11px;">Setujui</button>
                                            </form>

                                            <details>
                                                <summary class="btn-outline" style="padding:6px 10px;font-size:11px;cursor:pointer;list-style:none;">Tolak</summary>
                                                <form method="POST" action="{{ route('dosen.krs.tolak', $item) }}" style="margin-top:8px;width:240px;">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="return_url" value="{{ $listUrl }}">
                                                    <textarea class="form-control" name="alasan_penolakan" rows="3" minlength="5" maxlength="1000" required placeholder="Alasan penolakan"></textarea>
                                                    <button type="submit" class="btn-delete" style="margin-top:7px;padding:6px 10px;font-size:11px;" onclick="return confirm('Tolak mata kuliah ini?')">Simpan Penolakan</button>
                                                </form>
                                            </details>
                                        </div>
                                    @else
                                        <span style="color:#64748b;font-size:12px;">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align:right;">Total SKS</th>
                            <th>{{ $totalSks }}</th>
                            <th colspan="5"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
