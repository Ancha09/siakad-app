@extends('layouts.mahasiswa')
@section('title', 'Pengajuan Skripsi')
@section('content')
<div class="skripsi">
    @include('skripsi.partials.style')
    <div><h1>Pengajuan Skripsi</h1><p class="sk-muted">Ajukan judul dan pilih calon dosen pembimbing.</p></div>
    @include('skripsi.partials.period')
    <div class="sk-card sk-download-card"><div><h2>Template kartu bimbingan</h2><p class="sk-muted">{{ $templateBimbingan?->nama_asli ?? 'Template PDF bawaan STTMI' }}</p></div><a class="sk-link" href="{{ route('mahasiswa.skripsi.template.download') }}">Unduh template</a></div>
    <div class="sk-alert">
        Pengajuan skripsi tersedia mulai semester {{ \App\Models\Mahasiswa::MIN_SEMESTER_SKRIPSI }} ke atas.
        Semester Anda: <strong>{{ $student->semester ?? 'Belum diisi' }}</strong>.
        @if(! $student->memenuhiSyaratSemesterSkripsi())
            Anda belum memenuhi syarat semester untuk mengajukan skripsi. Jika data semester tidak sesuai, hubungi admin untuk memperbaruinya.
        @endif
    </div>
    <div class="sk-card">
        <h2>{{ $last?->status === 'Diterima' ? 'Pembimbing resmi' : 'Pengajuan saat ini' }}</h2>
        @if($last)
            <p><span class="sk-badge sk-{{ $last->status }}">{{ $last->status }}</span> {{ $last->judul }}</p>
            <p>{{ $last->status === 'Diterima' ? 'Pembimbing' : 'Dosen tujuan' }}: <strong>{{ $last->dosen->nama }}</strong></p>
            @if($last->alasan_keputusan)<p>Alasan: {{ $last->alasan_keputusan }}</p>@endif
        @else<p>Belum mengajukan pada periode ini.</p>@endif
        @if($last && in_array($last->status, ['Menunggu', 'Diterima']) && $period?->terbuka())
            <details @if($errors->has('judul')) open @endif><summary>Ganti judul skripsi</summary>
                <form method="POST" action="{{ route('mahasiswa.skripsi.title.update', $last) }}" class="sk-stack" onsubmit="return confirm('Simpan perubahan judul ini? Perubahan akan dicatat dalam riwayat.')">
                    @csrf @method('PUT')
                    <label>Judul baru<textarea name="judul" required maxlength="1000">{{ old('judul', $last->judul) }}</textarea></label>
                    <p class="sk-muted">Pembimbing dan status pengajuan tidak berubah. Judul lama tetap tersimpan dalam riwayat.</p>
                    <button>Simpan judul baru</button>
                </form>
            </details>
        @endif
        @if($student->memenuhiSyaratSemesterSkripsi() && $period?->terbuka() && (! $last || $last->status === 'Ditolak'))
            <form method="POST" action="{{ route('mahasiswa.skripsi.store') }}" class="sk-stack">
                @csrf <input type="hidden" name="periode_skripsi_id" value="{{ $period->id }}">
                <label>Judul skripsi<textarea name="judul" required maxlength="1000">{{ old('judul', $last?->judul) }}</textarea></label>
                <label>Calon dosen pembimbing<select name="dosen_id" required><option value="">Pilih dosen</option>@foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(old('dosen_id') == $dosen->id)>{{ $dosen->nama }} — {{ $dosen->nidn }}</option>@endforeach</select></label>
                @if($dosens->isEmpty())<p class="sk-muted">Belum ada dosen lain yang aktif dan memenuhi syarat. Hubungi admin.</p>@endif
                <p class="sk-muted">Pengajuan ulang setelah ditolak harus memilih dosen lain. Selama periode terbuka, judul pengajuan aktif dapat diganti dan perubahan tetap tercatat. Pilihan dosen baru menjadi pembimbing resmi setelah diterima.</p>
                <button>Kirim pengajuan{{ $last ? ' ulang' : '' }}</button>
            </form>
        @endif
    </div>
    <div class="sk-card"><h2>Riwayat pengajuan saya</h2>@include('skripsi.partials.submissions')</div>
</div>
@endsection
