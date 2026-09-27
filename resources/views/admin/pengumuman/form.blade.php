@extends('layouts.admin')
@section('title', $pengumuman->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')
@section('page-title', $pengumuman->exists ? 'Edit Pengumuman' : 'Buat Pengumuman')
@section('page-subtitle', 'Publikasikan informasi sesuai penerimanya')
@section('content')
<div class="inner-page">
    <div class="page-card announcement-editor">
        <div class="page-card-head"><h2 class="icon-heading"><x-layout-icon :name="$pengumuman->exists ? 'edit' : 'megaphone'" /> {{ $pengumuman->exists ? 'Edit' : 'Buat' }} pengumuman {{ ucfirst($pengumuman->penerima) }}</h2></div>
        <div class="page-card-body">
            @if($errors->any())<div class="announcement-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <form method="POST" action="{{ $pengumuman->exists ? route('admin.pengumuman.update', $pengumuman) : route('admin.pengumuman.store') }}" class="announcement-form">
                @csrf
                @if($pengumuman->exists) @method('PUT') @endif
                <label>Penerima<select name="penerima" required>@foreach(['mahasiswa', 'dosen'] as $target)<option value="{{ $target }}" @selected(old('penerima', $pengumuman->penerima) === $target)>{{ ucfirst($target) }}</option>@endforeach</select></label>
                <p class="announcement-muted">Pengumuman tampil pada dashboard dan pemberitahuan penerima yang dipilih.</p>
                <label>Target mahasiswa
                    <select name="target_type">
                        @foreach(['all' => 'Semua mahasiswa', 'period' => 'Mahasiswa dalam periode KRS', 'prodi' => 'Program studi', 'angkatan' => 'Angkatan', 'student' => 'Mahasiswa tertentu'] as $type => $label)
                            <option value="{{ $type }}" @selected(old('target_type', $pengumuman->target_type ?? 'all') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="announcement-muted">Target khusus berlaku untuk penerima Mahasiswa. Penerima Dosen tetap dikirim ke semua dosen.</p>
                <div class="announcement-fields">
                    <label>Periode KRS
                        <select name="target_periode_krs_id"><option value="">Pilih jika target periode</option>
                            @foreach($periodes as $periode)<option value="{{ $periode->id }}" @selected((string) old('target_periode_krs_id', $pengumuman->target_periode_krs_id) === (string) $periode->id)>{{ $periode->tahun_akademik }} {{ $periode->semester }}</option>@endforeach
                        </select>
                    </label>
                    <label>Program studi
                        <select name="target_prodi_id"><option value="">Pilih jika target prodi</option>
                            @foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected((string) old('target_prodi_id', $pengumuman->target_prodi_id) === (string) $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach
                        </select>
                    </label>
                    <label>Angkatan<input type="number" name="target_angkatan" min="1900" max="2100" value="{{ old('target_angkatan', $pengumuman->target_angkatan) }}" placeholder="Contoh: 2026"></label>
                    <label>NIM mahasiswa tertentu<input name="target_nim" maxlength="50" value="{{ old('target_nim', $targetNim) }}" placeholder="Isi NIM tepat"></label>
                </div>
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
                <div class="action-buttons"><button class="announcement-button icon-button"><x-layout-icon name="save" /> Simpan pengumuman</button><a class="announcement-button secondary icon-button" href="{{ route('admin.pengumuman.index', ['penerima' => $pengumuman->penerima]) }}"><x-layout-icon name="arrow-left" /> Kembali</a></div>
            </form>
        </div>
    </div>
</div>
@endsection
