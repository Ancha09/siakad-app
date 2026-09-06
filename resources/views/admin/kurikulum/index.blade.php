@extends('layouts.admin')

@section('title', 'Kurikulum & Silabus')
@section('page-title', 'Kurikulum & Silabus')
@section('page-subtitle', 'Kelola kurikulum, susunan mata kuliah, dan dokumen silabus')

@push('styles')
<style>
    .curriculum-admin .admin-grid { display:grid; grid-template-columns:minmax(270px, .75fr) minmax(0, 1.8fr); gap:18px; align-items:start; }
    .curriculum-admin .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
    .curriculum-admin .full { grid-column:1/-1; }
    .curriculum-admin .curriculum-list { display:grid; gap:8px; }
    .curriculum-admin .curriculum-link { display:block; padding:12px; border:1px solid #e2e8f0; border-radius:10px; color:#334155; text-decoration:none; }
    .curriculum-admin .curriculum-link.active { border-color:#3b82f6; background:#eff6ff; }
    .curriculum-admin .curriculum-link span { display:block; margin-top:3px; color:#64748b; font-size:11px; }
    .curriculum-admin .course-editor { padding:14px 0; border-bottom:1px solid #e2e8f0; }
    .curriculum-admin .course-editor:last-child { border-bottom:0; }
    .curriculum-admin .course-title { display:flex; justify-content:space-between; gap:12px; margin-bottom:10px; }
    .curriculum-admin .course-grid { display:grid; grid-template-columns:minmax(180px,1fr) 100px 110px minmax(180px,1fr) auto; gap:9px; align-items:end; }
    .curriculum-admin .field-label { display:block; margin-bottom:5px; color:#475569; font-size:11px; font-weight:600; }
    .curriculum-admin .actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .curriculum-admin .empty-state { padding:35px 15px; color:#64748b; text-align:center; }
    @media(max-width:1000px) { .curriculum-admin .admin-grid { grid-template-columns:1fr; } .curriculum-admin .course-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:600px) { .curriculum-admin .form-grid,.curriculum-admin .course-grid { grid-template-columns:1fr; } .curriculum-admin .full { grid-column:auto; } }
</style>
@endpush

@section('content')
<div class="inner-page curriculum-admin">
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if(isset($errors) && $errors->any())
        <div style="background:#fee2e2;color:#991b1b;padding:14px;border-radius:9px;margin-bottom:18px;">
            <strong>Data belum dapat disimpan:</strong>
            <ul style="margin:8px 0 0 18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="admin-grid">
        <div>
            <section class="page-card" style="margin-bottom:18px;">
                <div class="page-card-head"><h2>Tambah Kurikulum</h2></div>
                <div class="page-card-body">
                    <form method="POST" action="{{ route('admin.kurikulum.store') }}">
                        @csrf
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Program Studi</label>
                                <select class="form-control" name="prodi_id" required>
                                    <option value="">Pilih program studi</option>
                                    @foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(old('prodi_id') == $prodi->id)>{{ $prodi->jenjang }} {{ $prodi->nama_prodi }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group full"><label>Nama Kurikulum</label><input class="form-control" name="nama_kurikulum" value="{{ old('nama_kurikulum') }}" placeholder="Contoh: Kurikulum 2026" required></div>
                            <div class="form-group"><label>Tahun Mulai</label><input type="number" class="form-control" name="tahun_mulai" value="{{ old('tahun_mulai', now()->year) }}" required></div>
                            <div class="form-group"><label>Tahun Selesai</label><input type="number" class="form-control" name="tahun_selesai" value="{{ old('tahun_selesai') }}"></div>
                            <div class="form-group full"><label>Status</label><select class="form-control" name="status"><option>Aktif</option><option>Tidak Aktif</option></select></div>
                        </div>
                        <button class="btn-primary" style="margin-top:14px;" type="submit">Simpan Kurikulum</button>
                    </form>
                </div>
            </section>

            <section class="page-card">
                <div class="page-card-head"><h2>Daftar Kurikulum</h2></div>
                <div class="page-card-body">
                    <form method="GET" style="margin-bottom:12px;">
                        <select class="form-control" name="prodi_id" onchange="this.form.submit()">
                            <option value="">Semua program studi</option>
                            @foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(request('prodi_id') == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach
                        </select>
                    </form>
                    <div class="curriculum-list">
                        @forelse($kurikulums as $kurikulum)
                            <a class="curriculum-link {{ $selectedKurikulum?->id === $kurikulum->id ? 'active' : '' }}" href="{{ route('admin.kurikulum.index', ['kurikulum' => $kurikulum->id, 'prodi_id' => request('prodi_id')]) }}">
                                <strong>{{ $kurikulum->nama_kurikulum }}</strong>
                                <span>{{ $kurikulum->prodi->nama_prodi }} · {{ $kurikulum->status }}</span>
                            </a>
                        @empty
                            <div class="empty-state">Belum ada kurikulum.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <div>
            @if($selectedKurikulum)
                <section class="page-card" style="margin-bottom:18px;">
                    <div class="page-card-head"><h2>Pengaturan {{ $selectedKurikulum->nama_kurikulum }}</h2><span class="badge {{ $selectedKurikulum->status === 'Aktif' ? 'badge-green' : 'badge-gray' }}">{{ $selectedKurikulum->status }}</span></div>
                    <div class="page-card-body">
                        <form method="POST" action="{{ route('admin.kurikulum.update', $selectedKurikulum) }}">
                            @csrf @method('PUT')
                            <div class="form-grid">
                                <div class="form-group full">
                                    <label>Program Studi</label>
                                    @if($selectedKurikulum->mataKuliahKurikulum->isNotEmpty())
                                        <input type="hidden" name="prodi_id" value="{{ $selectedKurikulum->prodi_id }}">
                                        <input class="form-control" value="{{ $selectedKurikulum->prodi->jenjang }} {{ $selectedKurikulum->prodi->nama_prodi }}" disabled>
                                        <small style="color:#64748b;">Program studi terkunci karena susunan mata kuliah sudah diisi.</small>
                                    @else
                                        <select class="form-control" name="prodi_id" required>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected($selectedKurikulum->prodi_id === $prodi->id)>{{ $prodi->jenjang }} {{ $prodi->nama_prodi }}</option>@endforeach</select>
                                    @endif
                                </div>
                                <div class="form-group full"><label>Nama Kurikulum</label><input class="form-control" name="nama_kurikulum" value="{{ $selectedKurikulum->nama_kurikulum }}" required></div>
                                <div class="form-group"><label>Tahun Mulai</label><input type="number" class="form-control" name="tahun_mulai" value="{{ $selectedKurikulum->tahun_mulai }}" required></div>
                                <div class="form-group"><label>Tahun Selesai</label><input type="number" class="form-control" name="tahun_selesai" value="{{ $selectedKurikulum->tahun_selesai }}"></div>
                                <div class="form-group full"><label>Status</label><select class="form-control" name="status"><option @selected($selectedKurikulum->status === 'Aktif')>Aktif</option><option @selected($selectedKurikulum->status === 'Tidak Aktif')>Tidak Aktif</option></select></div>
                            </div>
                            <div class="actions" style="margin-top:14px;"><button class="btn-primary" type="submit">Simpan Perubahan</button></div>
                        </form>
                        <form method="POST" action="{{ route('admin.kurikulum.destroy', $selectedKurikulum) }}" style="margin-top:8px;" onsubmit="return confirm('Hapus kurikulum beserta susunan mata kuliahnya?')">@csrf @method('DELETE')<button class="btn-delete" type="submit">Hapus Kurikulum</button></form>
                    </div>
                </section>

                <section class="page-card" style="margin-bottom:18px;">
                    <div class="page-card-head"><h2>Tambah Mata Kuliah</h2></div>
                    <div class="page-card-body">
                        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.kurikulum.mata-kuliah.store', $selectedKurikulum) }}">
                            @csrf
                            <div class="course-grid">
                                <div><label class="field-label">Mata Kuliah</label><select class="form-control" name="mata_kuliah_id" required><option value="">Pilih mata kuliah</option>@foreach($mataKuliahs as $mk)<option value="{{ $mk->id }}">{{ $mk->kode_mk }} — {{ $mk->nama_mk }}</option>@endforeach</select></div>
                                <div><label class="field-label">Semester</label><input type="number" min="1" max="14" class="form-control" name="semester" required></div>
                                <div><label class="field-label">Jenis</label><select class="form-control" name="jenis"><option>Wajib</option><option>Pilihan</option></select></div>
                                <div><label class="field-label">Silabus/RPS PDF</label><input type="file" accept="application/pdf" class="form-control" name="silabus"></div>
                                <button class="btn-primary" type="submit">Tambahkan</button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="page-card">
                    <div class="page-card-head"><h2>Susunan Mata Kuliah</h2><span class="badge badge-blue">{{ $selectedKurikulum->mataKuliahKurikulum->count() }} mata kuliah</span></div>
                    <div class="page-card-body">
                        @forelse($selectedKurikulum->mataKuliahKurikulum as $item)
                            <div class="course-editor">
                                <div class="course-title"><div><strong>{{ $item->mataKuliah->kode_mk }} — {{ $item->mataKuliah->nama_mk }}</strong><div style="font-size:11px;color:#64748b;margin-top:3px;">{{ $item->mataKuliah->sks }} SKS · {{ $item->silabus_path ? 'Silabus tersedia' : 'Silabus belum tersedia' }}</div></div></div>
                                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.kurikulum.mata-kuliah.update', $item) }}">
                                    @csrf @method('PUT')
                                    <div class="course-grid">
                                        <div><label class="field-label">Mata Kuliah</label><select class="form-control" name="mata_kuliah_id">@foreach($mataKuliahs as $mk)<option value="{{ $mk->id }}" @selected($item->mata_kuliah_id === $mk->id)>{{ $mk->kode_mk }} — {{ $mk->nama_mk }}</option>@endforeach</select></div>
                                        <div><label class="field-label">Semester</label><input type="number" min="1" max="14" class="form-control" name="semester" value="{{ $item->semester }}" required></div>
                                        <div><label class="field-label">Jenis</label><select class="form-control" name="jenis"><option @selected($item->jenis === 'Wajib')>Wajib</option><option @selected($item->jenis === 'Pilihan')>Pilihan</option></select></div>
                                        <div><label class="field-label">Ganti silabus PDF</label><input type="file" accept="application/pdf" class="form-control" name="silabus"></div>
                                        <button class="btn-edit" type="submit">Simpan</button>
                                    </div>
                                </form>
                                <div class="actions" style="margin-top:8px;">
                                    @if($item->silabus_path)<a class="btn-outline" href="{{ route('admin.kurikulum.silabus', $item) }}">Unduh Silabus</a>@endif
                                    <form method="POST" action="{{ route('admin.kurikulum.mata-kuliah.destroy', $item) }}" onsubmit="return confirm('Hapus mata kuliah ini dari kurikulum?')">@csrf @method('DELETE')<button class="btn-delete" type="submit">Hapus</button></form>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Tambahkan mata kuliah untuk menyusun kurikulum ini.</div>
                        @endforelse
                    </div>
                </section>
            @else
                <div class="page-card"><div class="empty-state"><strong>Pilih atau buat kurikulum</strong><p style="margin-top:6px;">Detail pengelolaan akan tampil di bagian ini.</p></div></div>
            @endif
        </div>
    </div>
</div>
@endsection
