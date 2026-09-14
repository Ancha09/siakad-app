@extends('layouts.admin')
@section('title', 'Pengelolaan Pembimbing Skripsi')
@section('page-title', 'Pembimbing Skripsi')
@section('page-subtitle', 'Kelola periode, pembimbing, pengajuan, dan laporan bimbingan')
@section('content')
<div class="skripsi">
    @include('skripsi.partials.style')
    <div class="sk-page-header">
        <div><span class="sk-eyebrow">AKADEMIK · SKRIPSI</span><h1>Pengelolaan Pembimbing Skripsi</h1><p class="sk-muted">Atur periode, pantau pengajuan, dan alihkan secara manual berdasarkan beban dosen.</p></div>
        <div class="sk-actions">
            <a class="sk-link sk-excel" href="{{ route('admin.skripsi.excel', request()->query()) }}">📊 Download Excel</a>
            <a class="sk-link sk-pdf" href="{{ route('admin.skripsi.pdf', request()->query()) }}">📄 Download PDF</a>
        </div>
    </div>
    @include('skripsi.partials.period')
    <div class="sk-card">
        <h2>Template kartu bimbingan</h2>
        <p class="sk-muted">Unggah satu template aktif untuk diunduh mahasiswa dari dashboard. Format PDF, DOC, DOCX, XLS, atau XLSX; maksimal 10 MB.</p>
        @if($templateBimbingan)
            <p>Template aktif: <strong>{{ $templateBimbingan->nama_asli }}</strong> ({{ number_format($templateBimbingan->ukuran / 1024, 0, ',', '.') }} KB)</p>
            <a class="sk-link sk-secondary" href="{{ route('admin.skripsi.template.download') }}">Unduh template saat ini</a>
        @else
            <p class="sk-muted">Belum ada template khusus yang diunggah. Sistem menyediakan template PDF bawaan yang siap digunakan.</p>
            <a class="sk-link sk-pdf" href="{{ route('admin.skripsi.template.download') }}">Unduh template bawaan</a>
        @endif
        <form method="POST" action="{{ route('admin.skripsi.template.store') }}" enctype="multipart/form-data" class="sk-grid">
            @csrf
            <label>{{ $templateBimbingan ? 'Ganti template' : 'Pilih template' }}<input type="file" name="template" accept=".pdf,.doc,.docx,.xls,.xlsx" required></label>
            <button>Unggah template</button>
        </form>
    </div>
    <div class="sk-card">
        <h2>Pengaturan periode</h2>
        <p class="sk-muted">Semua waktu menggunakan {{ config('app.timezone') }}. Perpanjangan tenggat membuka kembali tindakan untuk periode tersebut dan dicatat dalam riwayat.</p>
        @if($period)
            <details @if($errors->has('mulai') || $errors->has('berakhir') || $errors->has('nama')) open @endif><summary>Ubah periode {{ $period->nama }}</summary>
                <form method="POST" action="{{ route('admin.skripsi.periode.update', $period) }}" class="sk-grid">
                    @csrf @method('PUT')
                    <label>Nama periode<input name="nama" required maxlength="255" value="{{ old('nama', $period->nama) }}"></label>
                    <label>Mulai<input type="datetime-local" name="mulai" required value="{{ old('mulai', $period->mulai->format('Y-m-d\TH:i')) }}"></label>
                    <label>Berakhir<input type="datetime-local" name="berakhir" required value="{{ old('berakhir', $period->berakhir->format('Y-m-d\TH:i')) }}"></label>
                    <button>Simpan perubahan</button>
                </form>
            </details>
        @endif
        <details @if(! $period) open @endif><summary>Tambah periode</summary>
            <form method="POST" action="{{ route('admin.skripsi.periode.store') }}" class="sk-grid">
                @csrf
                <label>Nama periode<input name="nama" required maxlength="255" value="{{ old('nama') }}"></label>
                <label>Mulai<input type="datetime-local" name="mulai" required value="{{ old('mulai') }}"></label>
                <label>Berakhir<input type="datetime-local" name="berakhir" required value="{{ old('berakhir') }}"></label>
                <button>Tambah periode</button>
            </form>
        </details>
    </div>
    <div class="sk-card"><h2>Ringkasan mahasiswa</h2>
        <p class="sk-muted">Daftar dan ringkasan ini mencakup mahasiswa semester {{ \App\Models\Mahasiswa::MIN_SEMESTER_SKRIPSI }} ke atas yang sudah memenuhi batas semester pengajuan skripsi.</p>
        <p class="sk-muted">Mengikuti periode, pencarian, prodi, dan dosen; tidak dibatasi filter status. Belum mendapat pembimbing mencakup belum mengajukan, menunggu, dan ditolak.</p>
        <div class="sk-summary">@foreach($summary as $label => $count)<div><strong>{{ $count }}</strong>{{ $label }}</div>@endforeach</div>
    </div>
    <div class="sk-card"><h2>Status mahasiswa ({{ $students->total() }})</h2>
        <p class="sk-muted">Status dan dosen pada daftar mahasiswa memakai pengajuan terakhir dalam periode terpilih. Mahasiswa yang belum mengajukan harus mengirim judul terlebih dahulu.</p>
        <div class="sk-scroll"><table>
            <thead><tr><th>Mahasiswa</th><th>Prodi</th><th>Judul / dosen terakhir</th><th>Status</th><th>Pengalihan</th></tr></thead>
            <tbody>@forelse($students as $student)
                @php($last = $student->pengajuanSkripsi->first())
                <tr><td>{{ $student->nama }}<br><span class="sk-muted">{{ $student->nim }}<br>Semester {{ $student->semester }}</span></td><td>{{ $student->prodi?->nama_prodi ?? '-' }}</td>
                    <td class="sk-title">{{ $last?->judul ?? '-' }}<p class="sk-muted">{{ $last?->dosen->nama ?? '-' }}</p></td>
                    <td><span class="sk-badge sk-{{ $last?->status }}">{{ $last?->status ?? 'Belum mengajukan' }}</span></td>
                    <td>
                        @if($last)<a href="{{ route('admin.skripsi.show', $last) }}">Lihat riwayat</a>@endif
                        @if($period?->terbuka() && $last && in_array($last->status, ['Menunggu', 'Ditolak']))
                            <details><summary>Alihkan ke dosen lain</summary>
                                <form method="POST" action="{{ route('admin.skripsi.transfer', $last) }}" class="sk-stack" onsubmit="return confirm('Alihkan pengajuan ini? Dosen baru tetap harus memberi persetujuan.')">
                                    @csrf
                                    <label>Dosen pengganti<select name="dosen_id" required><option value="">Pilih dosen lain</option>@foreach($dosens->where('id', '!=', $last->dosen_id) as $dosen)<option value="{{ $dosen->id }}">{{ $dosen->nama }}</option>@endforeach</select></label>
                                    <label>Alasan pengalihan<textarea name="alasan" required maxlength="2000">{{ old('alasan') }}</textarea></label><button>Alihkan pengajuan</button>
                                </form>
                            </details>
                        @elseif($last?->status === 'Diterima')<p class="sk-muted">Pembimbing sudah disetujui.</p>
                        @elseif(! $last)<p class="sk-muted">Menunggu mahasiswa mengirim judul.</p>@endif
                    </td>
                </tr>
            @empty<tr><td colspan="5">Tidak ada mahasiswa sesuai filter.</td></tr>@endforelse</tbody>
        </table></div>
        @include('skripsi.partials.pagination', ['paginator' => $students])
    </div>
    <div class="sk-card"><h2>Beban dan kelayakan dosen</h2>
        <p class="sk-muted">Jumlah pada periode terpilih, tanpa batas kuota. Aktifkan dosen yang berhak menerima pengajuan skripsi; dosen harus memiliki prodi dan akun dengan role dosen. Menonaktifkan dosen tidak membatalkan pembimbing yang sudah diterima.</p>
        <div class="sk-scroll"><table>
            <thead><tr><th>Dosen</th><th>Prodi</th><th>Bimbingan diterima</th><th>Menunggu</th><th>Kelayakan skripsi</th></tr></thead>
            <tbody>@forelse($loads as $dosen)
                <tr><td>{{ $dosen->nama }}</td><td>{{ $dosen->prodi?->nama_prodi ?? '-' }}</td><td>{{ $dosen->diterima_count }}</td><td>{{ $dosen->menunggu_count }}</td><td>
                    <form method="POST" action="{{ route('admin.skripsi.dosen', $dosen) }}">@csrf @method('PUT')<input type="hidden" name="aktif" value="{{ $dosen->skripsi_aktif ? 0 : 1 }}"><p>{{ $dosen->skripsi_aktif ? 'Aktif' : 'Tidak aktif' }}</p><button class="sk-secondary">{{ $dosen->skripsi_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                </td></tr>
            @empty<tr><td colspan="5">Belum ada data dosen.</td></tr>@endforelse</tbody>
        </table></div>
        @include('skripsi.partials.pagination', ['paginator' => $loads])
    </div>
    <div class="sk-card"><h2>Seluruh pengajuan dan keputusan</h2><p class="sk-muted">Mencakup pengajuan lama. Filter dosen dan status di tabel ini berlaku pada setiap pengajuan, termasuk yang sudah dialihkan.</p>@include('skripsi.partials.submissions')</div>
    <div class="sk-card"><h2>Riwayat periode dan kelayakan dosen</h2>@include('skripsi.partials.audits')</div>
</div>
@endsection
