@extends('layouts.dosen')

@section('title', 'Penelitian & P3M')
@section('page-title', 'Penelitian & P3M')
@section('page-subtitle', 'Kelola rekam jejak penelitian, pengabdian, artikel, dan dokumen hasil')

@push('styles')
<style>
    .research-page .research-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
    .research-page .research-stat { padding:16px; border:1px solid #e2e8f0; border-radius:13px; background:#fff; box-shadow:0 3px 12px rgba(15,42,85,.05); }
    .research-page .research-stat strong { display:block; color:#0a1f5c; font:700 25px 'Sora',sans-serif; }
    .research-page .research-stat span { color:#64748b; font-size:11px; }
    .research-page .form-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
    .research-page .span-2 { grid-column:span 2; }
    .research-page .span-all { grid-column:1/-1; }
    .research-page textarea.form-control { min-height:110px; resize:vertical; }
    .research-page .help { margin-top:4px; color:#64748b; font-size:10px; line-height:1.5; }
    .research-page .create-panel { margin-bottom:18px; overflow:hidden; }
    .research-page .create-panel > summary { display:flex; justify-content:space-between; align-items:center; padding:17px 20px; color:#0a1f5c; font-size:14px; font-weight:700; cursor:pointer; list-style:none; }
    .research-page .create-panel > summary::-webkit-details-marker { display:none; }
    .research-page .create-panel > summary::after { content:'+'; font-size:22px; }
    .research-page .create-panel[open] > summary::after { content:'−'; }
    .research-page .create-body { padding:18px 20px; border-top:1px solid #e2e8f0; }
    .research-page .filters { display:grid; grid-template-columns:minmax(190px,1.5fr) repeat(3,minmax(120px,.6fr)) auto; gap:10px; align-items:end; }
    .research-page .filter-actions { display:flex; gap:7px; }
    .research-page .research-list { display:grid; gap:15px; margin-top:18px; }
    .research-page .research-card { padding:18px; border:1px solid #e2e8f0; border-radius:14px; background:#fff; box-shadow:0 3px 14px rgba(15,42,85,.06); }
    .research-page .research-head { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; }
    .research-page .research-title { margin:7px 0 5px; color:#0a1f5c; font:700 16px/1.45 'Sora',sans-serif; }
    .research-page .research-meta { display:flex; gap:7px; align-items:center; flex-wrap:wrap; color:#64748b; font-size:11px; }
    .research-page .research-summary { margin:13px 0; color:#475569; font-size:12px; line-height:1.7; white-space:pre-line; }
    .research-page .document-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; padding-top:13px; border-top:1px solid #f1f5f9; }
    .research-page .document-row .btn-outline,.research-page .document-row .btn-edit,.research-page .document-row .btn-delete { padding:6px 11px; font-size:11px; text-decoration:none; }
    .research-page .edit-panel { margin-top:13px; border-top:1px dashed #cbd5e1; padding-top:12px; }
    .research-page .edit-panel > summary { width:max-content; color:#1d4ed8; font-size:11px; font-weight:700; cursor:pointer; }
    .research-page .edit-body { padding-top:14px; }
    .research-page .empty-state { padding:48px 20px; text-align:center; color:#64748b; }
    .research-page .empty-state strong { display:block; margin-bottom:6px; color:#334155; }
    @media(max-width:1000px) { .research-page .filters { grid-template-columns:repeat(2,minmax(0,1fr)); } .research-page .form-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:700px) { .research-page .research-stats { grid-template-columns:repeat(2,1fr); } .research-page .research-head { flex-direction:column; } }
    @media(max-width:520px) { .research-page .filters,.research-page .form-grid { grid-template-columns:1fr; } .research-page .span-2,.research-page .span-all { grid-column:auto; } }
</style>
@endpush

@section('content')
<div class="inner-page research-page">
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if(isset($errors) && $errors->any())
        <div style="margin-bottom:18px;padding:14px 16px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#991b1b;font-size:12px;">
            <strong>Data belum dapat disimpan:</strong>
            <ul style="margin:7px 0 0 18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="research-stats">
        <div class="research-stat"><strong>{{ $statistik['total'] }}</strong><span>Total Kegiatan</span></div>
        <div class="research-stat"><strong>{{ $statistik['penelitian'] }}</strong><span>Penelitian</span></div>
        <div class="research-stat"><strong>{{ $statistik['pengabdian'] }}</strong><span>Pengabdian Masyarakat</span></div>
        <div class="research-stat"><strong>{{ $statistik['selesai'] }}</strong><span>Selesai / Terbit</span></div>
    </div>

    <details class="page-card create-panel" @if(isset($errors) && $errors->any()) open @endif>
        <summary>Tambah Kegiatan Penelitian atau P3M</summary>
        <div class="create-body">
            <form method="POST" enctype="multipart/form-data" action="{{ route('dosen.penelitian.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group span-all"><label>Judul Kegiatan</label><input class="form-control" name="judul" value="{{ old('judul') }}" maxlength="255" placeholder="Masukkan judul penelitian atau pengabdian" required></div>
                    <div class="form-group"><label>Jenis</label><select class="form-control" name="jenis" required><option value="">Pilih jenis</option><option value="Penelitian" @selected(old('jenis') === 'Penelitian')>Penelitian</option><option value="Pengabdian" @selected(old('jenis') === 'Pengabdian')>Pengabdian Masyarakat</option></select></div>
                    <div class="form-group"><label>Tahun</label><input type="number" class="form-control" name="tahun" min="2000" max="{{ now()->year + 1 }}" value="{{ old('tahun', now()->year) }}" required></div>
                    <div class="form-group"><label>Status</label><select class="form-control" name="status" required>@foreach(['Draft','Berjalan','Selesai','Terbit'] as $status)<option value="{{ $status }}" @selected(old('status','Draft') === $status)>{{ $status }}</option>@endforeach</select></div>
                    <div class="form-group"><label>Sumber Dana</label><input class="form-control" name="sumber_dana" value="{{ old('sumber_dana') }}" placeholder="Contoh: Mandiri, DIPA, Hibah"></div>
                    <div class="form-group span-2"><label>Link Artikel / Publikasi</label><input type="url" class="form-control" name="link_artikel" value="{{ old('link_artikel') }}" placeholder="https://jurnal.example.com/artikel"></div>
                    <div class="form-group span-all"><label>Ringkasan</label><textarea class="form-control" name="ringkasan" maxlength="5000" placeholder="Tuliskan ringkasan kegiatan dan hasil utama">{{ old('ringkasan') }}</textarea></div>
                    <div class="form-group"><label>Dokumen Hasil</label><input type="file" class="form-control" name="hasil" accept=".pdf,.doc,.docx"><span class="help">PDF, DOC, atau DOCX. Maksimal 10 MB.</span></div>
                    <div class="form-group"><label>File Artikel</label><input type="file" class="form-control" name="artikel" accept=".pdf,.doc,.docx"><span class="help">Naskah artikel atau berkas publikasi. Maksimal 10 MB.</span></div>
                </div>
                <button class="btn-primary" type="submit" style="margin-top:15px;">Simpan Kegiatan</button>
            </form>
        </div>
    </details>

    <section class="page-card">
        <div class="page-card-head"><h2>Rekam Jejak Saya</h2><span class="badge badge-blue">{{ $penelitians->total() }} data</span></div>
        <div class="page-card-body">
            <form method="GET" class="filters">
                <div class="form-group"><label>Cari</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Judul, ringkasan, sumber dana"></div>
                <div class="form-group"><label>Jenis</label><select class="form-control" name="jenis"><option value="">Semua jenis</option><option value="Penelitian" @selected(request('jenis') === 'Penelitian')>Penelitian</option><option value="Pengabdian" @selected(request('jenis') === 'Pengabdian')>Pengabdian</option></select></div>
                <div class="form-group"><label>Tahun</label><select class="form-control" name="tahun"><option value="">Semua tahun</option>@foreach($tahunTersedia as $tahun)<option value="{{ $tahun }}" @selected((string) request('tahun') === (string) $tahun)>{{ $tahun }}</option>@endforeach</select></div>
                <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="">Semua status</option>@foreach(['Draft','Berjalan','Selesai','Terbit'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
                <div class="filter-actions"><button class="btn-primary" type="submit">Tampilkan</button>@if(request()->hasAny(['search','jenis','tahun','status']))<a class="btn-outline" href="{{ route('dosen.penelitian') }}">Reset</a>@endif</div>
            </form>

            <div class="research-list">
                @forelse($penelitians as $penelitian)
                    @php
                        $statusClass = match($penelitian->status) { 'Terbit','Selesai' => 'badge-green', 'Berjalan' => 'badge-gold', default => 'badge-gray' };
                    @endphp
                    <article class="research-card">
                        <div class="research-head">
                            <div>
                                <div class="research-meta">
                                    <span class="badge {{ $penelitian->jenis === 'Penelitian' ? 'badge-blue' : 'badge-green' }}">{{ $penelitian->jenis === 'Pengabdian' ? 'Pengabdian Masyarakat' : $penelitian->jenis }}</span>
                                    <span>{{ $penelitian->tahun }}</span>
                                    @if($penelitian->sumber_dana)<span>· {{ $penelitian->sumber_dana }}</span>@endif
                                </div>
                                <h3 class="research-title">{{ $penelitian->judul }}</h3>
                            </div>
                            <span class="badge {{ $statusClass }}">{{ $penelitian->status }}</span>
                        </div>

                        @if($penelitian->ringkasan)<p class="research-summary">{{ $penelitian->ringkasan }}</p>@endif

                        <div class="document-row">
                            @if($penelitian->hasil_path)<a class="btn-outline" href="{{ route('dosen.penelitian.download', [$penelitian, 'hasil']) }}">Unduh Hasil</a>@endif
                            @if($penelitian->artikel_path)<a class="btn-outline" href="{{ route('dosen.penelitian.download', [$penelitian, 'artikel']) }}">Unduh Artikel</a>@endif
                            @if($penelitian->link_artikel)<a class="btn-outline" href="{{ $penelitian->link_artikel }}" target="_blank" rel="noopener noreferrer">Buka Link Artikel</a>@endif
                            @unless($penelitian->hasil_path || $penelitian->artikel_path || $penelitian->link_artikel)<span style="color:#94a3b8;font-size:11px;">Belum ada dokumen atau link publikasi.</span>@endunless
                        </div>

                        <details class="edit-panel">
                            <summary>Edit kegiatan dan dokumen</summary>
                            <div class="edit-body">
                                <form method="POST" enctype="multipart/form-data" action="{{ route('dosen.penelitian.update', $penelitian) }}">
                                    @csrf @method('PUT')
                                    <div class="form-grid">
                                        <div class="form-group span-all"><label>Judul Kegiatan</label><input class="form-control" name="judul" value="{{ $penelitian->judul }}" maxlength="255" required></div>
                                        <div class="form-group"><label>Jenis</label><select class="form-control" name="jenis"><option value="Penelitian" @selected($penelitian->jenis === 'Penelitian')>Penelitian</option><option value="Pengabdian" @selected($penelitian->jenis === 'Pengabdian')>Pengabdian Masyarakat</option></select></div>
                                        <div class="form-group"><label>Tahun</label><input type="number" class="form-control" name="tahun" min="2000" max="{{ now()->year + 1 }}" value="{{ $penelitian->tahun }}" required></div>
                                        <div class="form-group"><label>Status</label><select class="form-control" name="status">@foreach(['Draft','Berjalan','Selesai','Terbit'] as $status)<option value="{{ $status }}" @selected($penelitian->status === $status)>{{ $status }}</option>@endforeach</select></div>
                                        <div class="form-group"><label>Sumber Dana</label><input class="form-control" name="sumber_dana" value="{{ $penelitian->sumber_dana }}"></div>
                                        <div class="form-group span-2"><label>Link Artikel / Publikasi</label><input type="url" class="form-control" name="link_artikel" value="{{ $penelitian->link_artikel }}"></div>
                                        <div class="form-group span-all"><label>Ringkasan</label><textarea class="form-control" name="ringkasan" maxlength="5000">{{ $penelitian->ringkasan }}</textarea></div>
                                        <div class="form-group"><label>Ganti Dokumen Hasil</label><input type="file" class="form-control" name="hasil" accept=".pdf,.doc,.docx"><span class="help">Kosongkan untuk mempertahankan file lama.</span></div>
                                        <div class="form-group"><label>Ganti File Artikel</label><input type="file" class="form-control" name="artikel" accept=".pdf,.doc,.docx"><span class="help">Kosongkan untuk mempertahankan file lama.</span></div>
                                    </div>
                                    <button class="btn-edit" type="submit" style="margin-top:14px;">Simpan Perubahan</button>
                                </form>
                                <form method="POST" action="{{ route('dosen.penelitian.destroy', $penelitian) }}" style="margin-top:8px;" onsubmit="return confirm('Hapus kegiatan beserta seluruh dokumennya?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-delete" type="submit">Hapus Kegiatan</button>
                                </form>
                            </div>
                        </details>
                    </article>
                @empty
                    <div class="empty-state"><strong>Belum ada kegiatan yang ditemukan</strong><p>Tambahkan penelitian atau pengabdian masyarakat melalui formulir di atas.</p></div>
                @endforelse
            </div>

            @if($penelitians->hasPages())<div style="margin-top:18px;">{{ $penelitians->links() }}</div>@endif
        </div>
    </section>
</div>
@endsection
