@extends('layouts.admin')

@section('title', 'Detail KRS Mahasiswa')
@section('page-subtitle', $mahasiswa->nama.' · '.$tahunAkademik.' '.$semesterAkademik)

@section('content')
    @if(session('success'))
        <div class="alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background:#fef2f2;color:#991b1b;padding:14px 16px;border-radius:9px;margin-bottom:18px;">
            <strong>Data belum dapat disimpan.</strong>
            <ul style="margin:7px 0 0 18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:18px;">
        <a href="{{ $returnUrl }}" class="btn-outline">← Kembali</a>
        @include('krs.partials.pdf-download-form', [
            'action' => route('admin.krs-mahasiswa.pdf', $mahasiswa),
            'tahunAkademik' => $tahunAkademik,
            'semesterAkademik' => $semesterAkademik,
            'buttonLabel' => 'Download Kartu KRS PDF',
        ])
    </div>

    @if(blank($mahasiswa->prodi?->ketua_program_studi_nama))
        <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:12px 14px;border-radius:9px;margin-bottom:18px;">
            Ketua Program Studi belum diisi pada Data Program Studi. PDF tetap dapat diunduh dan akan menampilkan placeholder tanda tangan.
        </div>
    @endif

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2>Identitas dan Periode</h2></div>
        <div class="page-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:13px;">
                @foreach([
                    'Nama Mahasiswa' => $mahasiswa->nama,
                    'NIM' => $mahasiswa->nim,
                    'Program Studi' => trim(($mahasiswa->prodi?->jenjang ?? '').' '.($mahasiswa->prodi?->nama_prodi ?? '-')),
                    'Kelas' => $mahasiswa->kelas?->nama_kelas ?? '-',
                    'Angkatan' => $mahasiswa->angkatan ?? $mahasiswa->kelas?->angkatan ?? '-',
                    'Semester Studi' => $semesterStudi ? 'Semester '.$semesterStudi : '-',
                    'Tahun Akademik' => $tahunAkademik,
                    'Semester Akademik' => $semesterAkademik,
                    'Dosen Wali' => $dosenWali?->nama ?? '-',
                    'Status Persetujuan' => $approvalStatus,
                ] as $label => $value)
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                        <small style="color:#64748b;">{{ $label }}</small>
                        <div style="font-weight:700;margin-top:4px;">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="page-card">
        <div class="page-card-head" style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <h2>Daftar Mata Kuliah</h2>
            <strong>{{ $totalSks }} SKS</strong>
        </div>
        <div class="page-card-body">
            <div class="table-wrap">
                <table>
                    <thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Jadwal / Ruangan</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach($krsRecords as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->mata_kuliah_efektif?->kode_mk ?? '-' }}</td>
                            <td><strong>{{ $item->mata_kuliah_efektif?->nama_mk ?? '-' }}</strong></td>
                            <td>{{ $item->mata_kuliah_efektif?->sks ?? 0 }}</td>
                            <td>{{ $item->dosen_efektif?->nama ?? '-' }}</td>
                            <td>
                                @if($item->jadwal)
                                    {{ $item->jadwal->hari ?? '-' }}, {{ substr((string) $item->jadwal->jam_mulai, 0, 5) }}–{{ substr((string) $item->jadwal->jam_selesai, 0, 5) }}<br>
                                    <small style="color:#64748b;">{{ $item->jadwal->ruangan?->nama_ruangan ?? '-' }}</small>
                                @else - @endif
                            </td>
                            <td>{{ $item->status }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
