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
        <a href="{{ route('admin.krs-mahasiswa.pdf', [
            'mahasiswa' => $mahasiswa,
            'tahun_akademik' => $tahunAkademik,
            'semester_akademik' => $semesterAkademik,
        ]) }}" class="btn-primary">Download Kartu KRS PDF</a>
    </div>

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
                ] as $label => $value)
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px;">
                        <small style="color:#64748b;">{{ $label }}</small>
                        <div style="font-weight:700;margin-top:4px;">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="page-card" style="margin-bottom:20px;">
        <div class="page-card-head"><h2>Status Pembayaran Manual</h2></div>
        <div class="page-card-body">
            <div style="margin-bottom:15px;">
                @if($pembayaran?->status_bayar === 'lunas')
                    <span class="badge badge-green">Sudah Bayar / Lunas</span>
                    <small style="margin-left:8px;color:#64748b;">Diverifikasi {{ $pembayaran->verifier?->name ?? 'Admin' }}{{ $pembayaran->tanggal_bayar ? ' pada '.$pembayaran->tanggal_bayar->format('d-m-Y') : '' }}</small>
                @else
                    <span class="badge" style="background:#fef3c7;color:#92400e;">Belum Bayar</span>
                @endif
            </div>

            <form method="POST" action="{{ route('admin.krs-mahasiswa.payment', $mahasiswa) }}"
                  style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="tahun_akademik" value="{{ $tahunAkademik }}">
                <input type="hidden" name="semester_akademik" value="{{ $semesterAkademik }}">
                <input type="hidden" name="return_url" value="{{ $returnUrl }}">
                <div class="form-group" style="margin:0;">
                    <label for="semester">Semester Studi</label>
                    <select id="semester" class="form-control" name="semester" required>
                        @foreach(range(1, 14) as $semester)
                            <option value="{{ $semester }}" @selected((int) old('semester', $pembayaran?->semester ?? $semesterStudi) === $semester)>Semester {{ $semester }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="status_bayar">Status Pembayaran</label>
                    <select id="status_bayar" class="form-control" name="status_bayar" required>
                        <option value="belum_bayar" @selected(old('status_bayar', $pembayaran?->status_bayar ?? 'belum_bayar') === 'belum_bayar')>Belum Bayar</option>
                        <option value="lunas" @selected(old('status_bayar', $pembayaran?->status_bayar) === 'lunas')>Sudah Bayar / Lunas</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label for="tanggal_bayar">Tanggal Bayar</label>
                    <input id="tanggal_bayar" class="form-control" type="date" name="tanggal_bayar" value="{{ old('tanggal_bayar', $pembayaran?->tanggal_bayar?->format('Y-m-d')) }}">
                </div>
                <div class="form-group" style="margin:0;grid-column:span 2;">
                    <label for="catatan">Catatan</label>
                    <input id="catatan" class="form-control" type="text" name="catatan" maxlength="2000" value="{{ old('catatan', $pembayaran?->catatan) }}" placeholder="Opsional">
                </div>
                <div><button type="submit" class="btn-primary">Simpan Status Bayar</button></div>
            </form>
            <p style="margin:12px 0 0;color:#64748b;font-size:12px;">Pencatatan ini hanya verifikasi manual admin dan tidak terhubung ke payment gateway.</p>
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
