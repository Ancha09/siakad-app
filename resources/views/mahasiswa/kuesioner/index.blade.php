@extends('layouts.mahasiswa')

@section('title', 'Kuesioner Evaluasi Dosen')

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <h2>📝 Kuesioner Evaluasi Dosen</h2>
    </div>

    <div class="page-card-body">
        @if(session('info'))
            <div style="background:#eff6ff;color:#1d4ed8;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
                {{ session('info') }}
            </div>
        @endif

        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:15px 17px;border-radius:10px;margin-bottom:20px;line-height:1.6;">
            Jawaban Anda bersifat rahasia. Admin dan dosen hanya melihat jawaban menggunakan kode responden anonim, bukan nama atau NIM Anda.
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun/Semester</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($krs as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->tahun_akademik }} · {{ $item->semester_akademik }}</td>
                            <td>
                                <strong>{{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}</strong><br>
                                <small>{{ $item->jadwal->mataKuliah->kode_mk ?? '-' }}</small>
                            </td>
                            <td>{{ $item->jadwal->dosen->nama ?? '-' }}</td>
                            <td>
                                @if($item->kuesioner)
                                    <span class="badge-success">Sudah diisi</span>
                                @else
                                    <span class="badge-warning">Belum diisi</span>
                                @endif
                            </td>
                            <td>
                                @if($item->kuesioner)
                                    <span style="color:#64748b;">Dikirim {{ $item->kuesioner->submitted_at?->format('d-m-Y H:i') }}</span>
                                @else
                                    <a href="{{ route('mahasiswa.kuesioner.create', $item) }}"
                                       class="btn-primary"
                                       style="display:inline-block;padding:8px 13px;">
                                        Isi Kuesioner
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:35px;">
                                Belum ada mata kuliah bernilai yang perlu dievaluasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
