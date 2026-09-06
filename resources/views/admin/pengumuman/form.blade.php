@extends('layouts.admin')
@section('title', $pengumuman->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')
@section('page-title', $pengumuman->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')
@section('page-subtitle', 'Publikasikan informasi sesuai penerimanya')
@section('content')
<div class="inner-page">
    <div class="page-card announcement-editor">
        <div class="page-card-head"><h2>{{ $pengumuman->exists ? 'Edit' : 'Buat' }} pengumuman {{ ucfirst($pengumuman->penerima) }}</h2></div>
        <div class="page-card-body">
            @if($errors->any())<div class="announcement-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ $pengumuman->exists ? route('admin.pengumuman.update', $pengumuman) : route('admin.pengumuman.store') }}" class="announcement-form">
                @csrf
                @if($pengumuman->exists) @method('PUT') @endif
                <label>Penerima<select name="penerima" required>@foreach(['mahasiswa', 'dosen'] as $target)<option value="{{ $target }}" @selected(old('penerima', $pengumuman->penerima) === $target)>{{ ucfirst($target) }}</option>@endforeach</select></label>
                <p class="announcement-muted">Pengumuman hanya tampil pada dashboard dan pemberitahuan kelompok penerima yang dipilih.</p>
                <label>Judul<input name="judul" maxlength="180" required value="{{ old('judul', $pengumuman->judul) }}" placeholder="Contoh: Jadwal pengisian KRS semester ganjil"></label>
                <label>Isi pengumuman<textarea name="isi" rows="8" maxlength="20000" required placeholder="Tuliskan informasi, jadwal, dan langkah yang perlu dilakukan...">{{ old('isi', $pengumuman->isi) }}</textarea></label>
                <label>Tautan informasi (opsional)<input name="tautan" type="url" maxlength="2048" value="{{ old('tautan', $pengumuman->tautan) }}" placeholder="https://..."></label>
                <label class="announcement-checkbox"><input type="checkbox" name="penting" value="1" @checked(old('penting', $pengumuman->penting))> Tandai sebagai pengumuman penting</label>
                <div class="announcement-fields">
                    <label>Status<select name="status"><option value="draft" @selected(old('status', $pengumuman->status) === 'draft')>Simpan sebagai draft</option><option value="terbit" @selected(old('status', $pengumuman->status) === 'terbit')>Terbitkan</option></select></label>
                    <label>Terbit pada (opsional)<input type="datetime-local" name="terbit_pada" value="{{ old('terbit_pada', $pengumuman->terbit_pada?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}"></label>
                    <label>Berakhir pada (opsional)<input type="datetime-local" name="berakhir_pada" value="{{ old('berakhir_pada', $pengumuman->berakhir_pada?->timezone('Asia/Jakarta')->format('Y-m-d\TH:i')) }}"></label>
                </div>
                <p class="announcement-muted">Pilih Terbitkan dan kosongkan waktu terbit untuk langsung tampil. Waktu menggunakan WIB (Jakarta). Pengumuman yang berakhir otomatis tidak ditampilkan.</p>
                <div class="action-buttons"><button class="announcement-button">Simpan pengumuman</button><a class="announcement-button secondary" href="{{ route('admin.pengumuman.index', ['penerima' => $pengumuman->penerima]) }}">Kembali</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
