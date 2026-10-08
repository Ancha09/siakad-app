@extends('layouts.admin')

@section('title', 'Kurikulum OBE & RPS')
@section('page-title', 'Kurikulum OBE & RPS')
@section('page-subtitle', 'Manajemen Capaian Pembelajaran (CPL, CPMK), RPS, dan Monitoring Penilaian OBE')

@push('styles')
<style>
    .obe-nav-tabs { display: flex; gap: 8px; border-bottom: 2px solid #e2e8f0; margin-bottom: 22px; flex-wrap: wrap; }
    .obe-nav-tab { padding: 10px 18px; border-radius: 8px 8px 0 0; font-size: 13px; font-weight: 600; color: #64748b; text-decoration: none; border-bottom: 3px solid transparent; margin-bottom: -2px; display: inline-flex; align-items: center; gap: 8px; background: transparent; transition: all 0.2s; }
    .obe-nav-tab:hover { color: #1e40af; background: #f8fafc; }
    .obe-nav-tab.active { color: #2563eb; border-bottom-color: #2563eb; background: #eff6ff; }
    .obe-filter-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-bottom: 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .obe-filter-item { display: flex; align-items: center; gap: 8px; }
    .obe-filter-item label { font-size: 12px; font-weight: 600; color: #475569; margin: 0; white-space: nowrap; }
    .cpmk-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .cpmk-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px; }
    .cpl-pill { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
    .modal-backdrop-custom { position: fixed; inset: 0; background: rgba(15,23,42,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 16px; }
    .modal-box-custom { background: #fff; border-radius: 12px; width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; padding: 22px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); }
</style>
@endpush

@section('content')
<div class="inner-page">

    {{-- Notifikasi --}}
    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tab Navigasi --}}
    <div class="obe-nav-tabs">
        <a href="{{ route('admin.obe.index', ['tab' => 'cpl', 'prodi_id' => $selectedProdiId]) }}" class="obe-nav-tab {{ $tab === 'cpl' ? 'active' : '' }}">
            <x-layout-icon name="chart" />
            <span>1. Capaian Lulusan (CPL)</span>
        </a>
        <a href="{{ route('admin.obe.index', ['tab' => 'cpmk', 'prodi_id' => $selectedProdiId, 'mata_kuliah_id' => $selectedMkId]) }}" class="obe-nav-tab {{ $tab === 'cpmk' ? 'active' : '' }}">
            <x-layout-icon name="book" />
            <span>2. CPMK &amp; Sub-CPMK</span>
        </a>
        <a href="{{ route('admin.obe.index', ['tab' => 'rps', 'prodi_id' => $selectedProdiId]) }}" class="obe-nav-tab {{ $tab === 'rps' ? 'active' : '' }}">
            <x-layout-icon name="file-check" />
            <span>3. Dokumen &amp; Target RPS</span>
        </a>
        <a href="{{ route('admin.obe.index', ['tab' => 'monitoring', 'prodi_id' => $selectedProdiId]) }}" class="obe-nav-tab {{ $tab === 'monitoring' ? 'active' : '' }}">
            <x-layout-icon name="lock" />
            <span>4. Monitoring &amp; Buka Kunci Nilai</span>
        </a>
    </div>

    {{-- ========================================================
         TAB 1: CPL (CAPAIAN PEMBELAJARAN LULUSAN)
         ======================================================== --}}
    @if($tab === 'cpl')
        <div class="obe-filter-box">
            <form method="GET" action="{{ route('admin.obe.index') }}" style="display:flex; gap:16px; align-items:center; width:100%; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="cpl">
                <div class="obe-filter-item">
                    <label for="filter-prodi">Program Studi:</label>
                    <select id="filter-prodi" name="prodi_id" class="form-control" onchange="this.form.submit()" style="min-width:240px;">
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" @selected($selectedProdiId == $p->id)>{{ $p->jenjang }} {{ $p->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-left: auto;">
                    <button type="button" class="btn-primary" onclick="openModal('modal-tambah-cpl')">
                        <x-layout-icon name="plus" />
                        <span>Tambah CPL</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="page-card">
            <div class="page-card-head">
                <h2>Daftar CPL — {{ $selectedProdi?->jenjang }} {{ $selectedProdi?->nama_prodi }}</h2>
                <span class="badge badge-info">{{ $cpls->count() }} CPL Terdaftar</span>
            </div>
            <div class="page-card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th style="width: 140px;">Kode CPL</th>
                                <th>Nama / Judul CPL</th>
                                <th>Deskripsi Capaian</th>
                                <th style="width: 120px; text-align: center;">Sub-CPMK</th>
                                <th style="width: 140px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cpls as $cpl)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $cpl->kode_cpl }}</strong></td>
                                    <td>{{ $cpl->nama_cpl ?: '-' }}</td>
                                    <td style="font-size: 12px; color: #475569;">{{ $cpl->deskripsi ?: '-' }}</td>
                                    <td style="text-align: center;">
                                        <span class="cpl-pill">{{ $cpl->sub_cpmks_count }} Sub-CPMK</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 6px;">
                                            <button type="button" class="btn-sm btn-outline" onclick="editCpl({{ json_encode($cpl) }})">
                                                <x-layout-icon name="edit" />
                                            </button>
                                            <form method="POST" action="{{ route('admin.obe.cpl.destroy', $cpl) }}" onsubmit="return confirm('Hapus CPL {{ $cpl->kode_cpl }}? Sub-CPMK terkait akan kehilangan relasi CPL.')" style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-sm btn-danger">
                                                    <x-layout-icon name="trash" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #94a3b8; padding: 25px;">
                                        Belum ada CPL untuk program studi ini. Klik "Tambah CPL" untuk memulai.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Modal Tambah CPL --}}
        <div id="modal-tambah-cpl" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Tambah CPL Baru</h3>
                <form method="POST" action="{{ route('admin.obe.cpl.store') }}">
                    @csrf
                    <input type="hidden" name="prodi_id" value="{{ $selectedProdiId }}">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode CPL *</label>
                        <input type="text" name="kode_cpl" class="form-control" placeholder="Contoh: CPL 1 atau CPL-1" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Nama / Judul Singkat</label>
                        <input type="text" name="nama_cpl" class="form-control" placeholder="Contoh: Kemampuan Analisis Geologi">
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Capaian Pembelajaran</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan deskripsi capaian pembelajaran lulusan..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-tambah-cpl')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan CPL</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit CPL --}}
        <div id="modal-edit-cpl" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Edit Data CPL</h3>
                <form id="form-edit-cpl" method="POST" action="">
                    @csrf @method('PUT')
                    <input type="hidden" name="prodi_id" value="{{ $selectedProdiId }}">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode CPL *</label>
                        <input id="edit-cpl-kode" type="text" name="kode_cpl" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Nama / Judul Singkat</label>
                        <input id="edit-cpl-nama" type="text" name="nama_cpl" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Capaian Pembelajaran</label>
                        <textarea id="edit-cpl-deskripsi" name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-edit-cpl')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ========================================================
         TAB 2: CPMK & SUB-CPMK MATA KULIAH
         ======================================================== --}}
    @if($tab === 'cpmk')
        <div class="obe-filter-box">
            <form method="GET" action="{{ route('admin.obe.index') }}" style="display:flex; gap:16px; align-items:center; width:100%; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="cpmk">
                <div class="obe-filter-item">
                    <label for="filter-prodi-cpmk">Program Studi:</label>
                    <select id="filter-prodi-cpmk" name="prodi_id" class="form-control" onchange="this.form.submit()" style="min-width:200px;">
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" @selected($selectedProdiId == $p->id)>{{ $p->jenjang }} {{ $p->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="obe-filter-item">
                    <label for="filter-mk-cpmk">Mata Kuliah:</label>
                    <select id="filter-mk-cpmk" name="mata_kuliah_id" class="form-control" onchange="this.form.submit()" style="min-width:240px;">
                        @foreach($mataKuliahs as $mk)
                            <option value="{{ $mk->id }}" @selected($selectedMkId == $mk->id)>{{ $mk->kode_mk }} — {{ $mk->nama_mk }} (Sem {{ $mk->semester }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-left: auto;">
                    @if($selectedMk)
                        <button type="button" class="btn-primary" onclick="openModal('modal-tambah-cpmk')">
                            <x-layout-icon name="plus" />
                            <span>Tambah CPMK</span>
                        </button>
                    @endif
                </div>
            </form>
        </div>

        @if($selectedMk)
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0 0 6px 0;">Struktur Capaian: {{ $selectedMk->kode_mk }} — {{ $selectedMk->nama_mk }}</h3>
                <p style="margin: 0; color: #64748b; font-size: 13px;">Relasi berjenjang: Sub-CPMK dialokasikan ke CPMK dan dipetakan ke Capaian Pembelajaran Lulusan (CPL).</p>
            </div>

            @forelse($cpmks as $cpmk)
                <div class="cpmk-card">
                    <div class="cpmk-head">
                        <div>
                            <span class="badge badge-primary" style="font-size: 12px; margin-right: 8px;">{{ $cpmk->kode_cpmk }}</span>
                            <span style="font-size: 13px; font-weight: 600; color: #1e293b;">{{ $cpmk->deskripsi ?: 'Tanpa deskripsi' }}</span>
                        </div>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn-sm btn-outline" onclick="openTambahSubCpmk({{ $cpmk->id }}, '{{ $cpmk->kode_cpmk }}')">
                                <x-layout-icon name="plus" />
                                <span>Tambah Sub-CPMK</span>
                            </button>
                            <button type="button" class="btn-sm btn-outline" onclick="editCpmk({{ json_encode($cpmk) }})">
                                <x-layout-icon name="edit" />
                            </button>
                            <form method="POST" action="{{ route('admin.obe.cpmk.destroy', $cpmk) }}" onsubmit="return confirm('Hapus {{ $cpmk->kode_cpmk }} dan seluruh Sub-CPMK di dalamnya?')" style="margin: 0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">
                                    <x-layout-icon name="trash" />
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Tabel Sub-CPMK di bawah CPMK ini --}}
                    <div class="table-wrap">
                        <table style="background: #fafafa;">
                            <thead>
                                <tr style="background: #f1f5f9;">
                                    <th style="width: 140px;">Kode Sub-CPMK</th>
                                    <th>Deskripsi Sub-CPMK</th>
                                    <th style="width: 150px;">CPL Pemetaan</th>
                                    <th style="width: 110px; text-align: center;">Bobot Default</th>
                                    <th style="width: 100px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cpmk->subCpmks as $sub)
                                    <tr>
                                        <td><strong>{{ $sub->kode_sub_cpmk }}</strong></td>
                                        <td style="font-size: 12px;">{{ $sub->deskripsi ?: '-' }}</td>
                                        <td>
                                            @if($sub->cpl)
                                                <span class="cpl-pill">{{ $sub->cpl->kode_cpl }}</span>
                                            @else
                                                <span style="color: #94a3b8; font-size: 11px;">Belum dipetakan</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            {{ $sub->bobot_default !== null ? $sub->bobot_default . '%' : '-' }}
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 4px;">
                                                <button type="button" class="btn-sm btn-outline" onclick="editSubCpmk({{ json_encode($sub) }})">
                                                    <x-layout-icon name="edit" />
                                                </button>
                                                <form method="POST" action="{{ route('admin.obe.sub-cpmk.destroy', $sub) }}" onsubmit="return confirm('Hapus {{ $sub->kode_sub_cpmk }}?')" style="margin: 0;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn-sm btn-danger">
                                                        <x-layout-icon name="trash" />
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #94a3b8; font-size: 12px; padding: 12px;">
                                            Belum ada Sub-CPMK untuk {{ $cpmk->kode_cpmk }}. Klik "+ Tambah Sub-CPMK".
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="page-card">
                    <div class="page-card-body" style="text-align: center; color: #94a3b8; padding: 35px;">
                        Mata kuliah ini belum memiliki CPMK. Klik tombol "Tambah CPMK" di atas untuk menambahkan capaian.
                    </div>
                </div>
            @endforelse
        @else
            <div class="page-card">
                <div class="page-card-body" style="text-align: center; color: #94a3b8; padding: 35px;">
                    Silakan pilih Program Studi dan Mata Kuliah untuk mengelola CPMK.
                </div>
            </div>
        @endif

        {{-- Modal Tambah CPMK --}}
        <div id="modal-tambah-cpmk" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Tambah CPMK</h3>
                <form method="POST" action="{{ route('admin.obe.cpmk.store') }}">
                    @csrf
                    <input type="hidden" name="mata_kuliah_id" value="{{ $selectedMkId }}">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode CPMK *</label>
                        <input type="text" name="kode_cpmk" class="form-control" placeholder="Contoh: CPMK 1 atau CPMK-1" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Capaian Mata Kuliah</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Mahasiswa mampu menganalisis..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-tambah-cpmk')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan CPMK</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit CPMK --}}
        <div id="modal-edit-cpmk" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Edit Data CPMK</h3>
                <form id="form-edit-cpmk" method="POST" action="">
                    @csrf @method('PUT')
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode CPMK *</label>
                        <input id="edit-cpmk-kode" type="text" name="kode_cpmk" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Capaian Mata Kuliah</label>
                        <textarea id="edit-cpmk-deskripsi" name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-edit-cpmk')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Tambah Sub-CPMK --}}
        <div id="modal-tambah-sub-cpmk" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 id="tambah-sub-title" style="margin-top: 0; margin-bottom: 16px;">Tambah Sub-CPMK</h3>
                <form method="POST" action="{{ route('admin.obe.sub-cpmk.store') }}">
                    @csrf
                    <input id="tambah-sub-cpmk-id" type="hidden" name="cpmk_id" value="">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode Sub-CPMK *</label>
                        <input type="text" name="kode_sub_cpmk" class="form-control" placeholder="Contoh: Sub-CPMK 1a atau Sub-1" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>CPL Pemetaan (Induk Capaian Lulusan)</label>
                        <select name="cpl_id" class="form-control">
                            <option value="">-- Pilih CPL Terkait --</option>
                            @foreach($availableCpls as $cpl)
                                <option value="{{ $cpl->id }}">{{ $cpl->kode_cpl }} - {{ $cpl->nama_cpl ?: $cpl->deskripsi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Bobot Rekomendasi / Default (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="bobot_default" class="form-control" placeholder="Opsional, misal: 20">
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Kemampuan Akhir Mahasiswa</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Mampu mengidentifikasi struktur batuan..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-tambah-sub-cpmk')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Sub-CPMK</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit Sub-CPMK --}}
        <div id="modal-edit-sub-cpmk" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Edit Data Sub-CPMK</h3>
                <form id="form-edit-sub-cpmk" method="POST" action="">
                    @csrf @method('PUT')
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Kode Sub-CPMK *</label>
                        <input id="edit-sub-kode" type="text" name="kode_sub_cpmk" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>CPL Pemetaan</label>
                        <select id="edit-sub-cpl" name="cpl_id" class="form-control">
                            <option value="">-- Pilih CPL Terkait --</option>
                            @foreach($availableCpls as $cpl)
                                <option value="{{ $cpl->id }}">{{ $cpl->kode_cpl }} - {{ $cpl->nama_cpl ?: $cpl->deskripsi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Bobot Rekomendasi / Default (%)</label>
                        <input id="edit-sub-bobot" type="number" step="0.01" min="0" max="100" name="bobot_default" class="form-control">
                    </div>
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label>Deskripsi Kemampuan Akhir</label>
                        <textarea id="edit-sub-deskripsi" name="deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-edit-sub-cpmk')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ========================================================
         TAB 3: DOKUMEN & TARGET RPS
         ======================================================== --}}
    @if($tab === 'rps')
        <div class="obe-filter-box">
            <form method="GET" action="{{ route('admin.obe.index') }}" style="display:flex; gap:16px; align-items:center; width:100%; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="rps">
                <div class="obe-filter-item">
                    <label for="filter-prodi-rps">Program Studi:</label>
                    <select id="filter-prodi-rps" name="prodi_id" class="form-control" onchange="this.form.submit()" style="min-width:240px;">
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" @selected($selectedProdiId == $p->id)>{{ $p->jenjang }} {{ $p->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-left: auto;">
                    <button type="button" class="btn-primary" onclick="openModal('modal-tambah-rps')">
                        <x-layout-icon name="plus" />
                        <span>Upload &amp; Atur RPS Baru</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="page-card">
            <div class="page-card-head">
                <h2>Rencana Pembelajaran Semester (RPS) — {{ $selectedProdi?->nama_prodi }}</h2>
            </div>
            <div class="page-card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Mata Kuliah</th>
                                <th style="width: 120px;">Tahun Ajaran</th>
                                <th style="width: 100px; text-align: center;">Passing Grade</th>
                                <th>Target Porsi CPL</th>
                                <th style="width: 110px; text-align: center;">Dokumen RPS</th>
                                <th style="width: 90px; text-align: center;">Status</th>
                                <th style="width: 120px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rpsList as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $item->mataKuliah?->kode_mk }}</strong> — {{ $item->mataKuliah?->nama_mk }}
                                        <div style="font-size: 11px; color: #64748b;">{{ $item->mataKuliah?->sks }} SKS · Semester {{ $item->mataKuliah?->semester }}</div>
                                    </td>
                                    <td>{{ $item->tahun_akademik ?: 'Reguler' }}</td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-info">{{ $item->target_passing_grade }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($item->porsi_cpl))
                                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                                @foreach($item->porsi_cpl as $cplKey => $portion)
                                                    <span class="cpl-pill">{{ $cplKey }}: {{ $portion }}%</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 11px;">Belum ditetapkan</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($item->file_rps)
                                            <a href="{{ route('admin.obe.rps.download', $item) }}" class="btn-sm btn-outline" title="Unduh PDF RPS">
                                                <x-layout-icon name="download" />
                                                <span>PDF</span>
                                            </a>
                                        @else
                                            <span style="color: #94a3b8; font-size: 11px;">Tidak ada file</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($item->is_active)
                                            <span class="badge-success">Aktif</span>
                                        @else
                                            <span class="badge-secondary">Arsip</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 4px;">
                                            <form method="POST" action="{{ route('admin.obe.rps.destroy', $item) }}" onsubmit="return confirm('Hapus dokumen RPS ini?')" style="margin: 0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-sm btn-danger">
                                                    <x-layout-icon name="trash" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">
                                        Belum ada dokumen RPS untuk program studi ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 16px;">
                    {{ $rpsList->links() }}
                </div>
            </div>
        </div>

        {{-- Modal Tambah RPS --}}
        <div id="modal-tambah-rps" class="modal-backdrop-custom" style="display: none;">
            <div class="modal-box-custom">
                <h3 style="margin-top: 0; margin-bottom: 16px;">Upload &amp; Atur RPS Mata Kuliah</h3>
                <form method="POST" action="{{ route('admin.obe.rps.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Mata Kuliah *</label>
                        <select name="mata_kuliah_id" class="form-control" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            @foreach($mataKuliahs as $mk)
                                <option value="{{ $mk->id }}">{{ $mk->kode_mk }} — {{ $mk->nama_mk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Tahun Akademik</label>
                        <input type="text" name="tahun_akademik" class="form-control" placeholder="Contoh: 2026/2027">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>File Dokumen RPS (PDF Resmi, Maks 10 MB)</label>
                        <input type="file" name="file_rps" class="form-control" accept="application/pdf">
                        <small style="color: #64748b;">Dokumen ini akan tersedia untuk diunduh dosen dan mahasiswa.</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label>Batas Ketercapaian / Target Passing Grade (0 - 100)</label>
                        <input type="number" step="0.1" name="target_passing_grade" class="form-control" value="60.0" required>
                        <small style="color: #64748b;">Nilai ambang batas mahasiswa dianggap lulus CPL (standar: 60).</small>
                    </div>

                    <div style="margin-bottom: 14px; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; background: #f8fafc;">
                        <strong style="display:block; margin-bottom: 6px; font-size: 13px;">Ketetapan Porsi Bobot CPL (Total 100%)</strong>
                        <p style="font-size: 11px; color: #64748b; margin-top: 0;">Contoh: CPL 1 dialokasikan 60%, CPL 2 dialokasikan 40%.</p>
                        @foreach($cpls as $cpl)
                            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                                <input type="hidden" name="porsi_cpl_keys[]" value="{{ $cpl->kode_cpl }}">
                                <span style="font-size: 12px; font-weight: 600; width: 90px;">{{ $cpl->kode_cpl }}:</span>
                                <input type="number" step="0.1" min="0" max="100" name="porsi_cpl_values[]" class="form-control" placeholder="Persen (0 - 100)" style="max-width: 140px;">
                                <span style="font-size: 12px; color: #64748b;">%</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" checked>
                            <span style="font-size: 13px;">Jadikan sebagai RPS Aktif untuk mata kuliah ini</span>
                        </label>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                        <button type="button" class="btn-outline" onclick="closeModal('modal-tambah-rps')">Batal</button>
                        <button type="submit" class="btn-primary">Simpan RPS</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- ========================================================
         TAB 4: MONITORING & BUKA KUNCI NILAI (UNLOCK)
         ======================================================== --}}
    @if($tab === 'monitoring')
        <div class="obe-filter-box">
            <form method="GET" action="{{ route('admin.obe.index') }}" style="display:flex; gap:16px; align-items:center; width:100%; flex-wrap:wrap;">
                <input type="hidden" name="tab" value="monitoring">
                <div class="obe-filter-item">
                    <label for="filter-prodi-mon">Program Studi:</label>
                    <select id="filter-prodi-mon" name="prodi_id" class="form-control" onchange="this.form.submit()" style="min-width:240px;">
                        @foreach($prodis as $p)
                            <option value="{{ $p->id }}" @selected($selectedProdiId == $p->id)>{{ $p->jenjang }} {{ $p->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="page-card">
            <div class="page-card-head">
                <h2>Status Penilaian &amp; Finalisasi OBE Kelas</h2>
            </div>
            <div class="page-card-body">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px;">No</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen Pengampu</th>
                                <th>Kelas / Jadwal</th>
                                <th>Tahun / Semester</th>
                                <th style="text-align: center;">Status Finalisasi</th>
                                <th style="width: 160px; text-align: center;">Aksi Administrator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monitoringJadwals as $jadwal)
                                @php
                                    $skema = $jadwal->skemaPenilaian;
                                    $isFinal = $skema && $skema->is_finalized;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $jadwal->mataKuliah?->kode_mk }}</strong> — {{ $jadwal->mataKuliah?->nama_mk }}
                                    </td>
                                    <td>{{ $jadwal->dosen?->nama ?? '-' }}</td>
                                    <td>Kelas {{ $jadwal->kelas }} ({{ $jadwal->hari }}, {{ $jadwal->jam_mulai }})</td>
                                    <td>{{ $jadwal->tahun_akademik }} {{ $jadwal->semester_akademik }}</td>
                                    <td style="text-align: center;">
                                        @if($isFinal)
                                            <span class="badge-success">
                                                <x-layout-icon name="lock" style="width:13px; height:13px; display:inline;" />
                                                Telah Difinalisasi
                                            </span>
                                            <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                                {{ $skema->finalized_at ? $skema->finalized_at->format('d/m/Y H:i') : '' }}
                                            </div>
                                        @elseif($skema && $skema->komponens()->count() > 0)
                                            <span class="badge-warning">Draf ({{ $skema->komponens()->count() }} Instrumen)</span>
                                        @else
                                            <span class="badge-secondary">Belum Dikonfigurasi</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($isFinal)
                                            <form method="POST" action="{{ route('admin.obe.unlock-nilai', $skema) }}" onsubmit="return confirm('Buka kunci finalisasi nilai untuk kelas ini? Dosen pengampu akan dapat merevisi instrumen dan nilai kembali.')" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn-sm btn-warning" title="Buka Kunci Nilai agar Dosen dapat Merevisi">
                                                    <x-layout-icon name="refresh" />
                                                    <span>Buka Kunci</span>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size: 12px; color: #94a3b8;">Form Terbuka</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">
                                        Tidak ada jadwal perkuliahan untuk program studi ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 16px;">
                    {{ $monitoringJadwals->links() }}
                </div>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    function editCpl(cpl) {
        document.getElementById('form-edit-cpl').action = '{{ url("admin/kurikulum-obe/cpl") }}/' + cpl.id;
        document.getElementById('edit-cpl-kode').value = cpl.kode_cpl;
        document.getElementById('edit-cpl-nama').value = cpl.nama_cpl || '';
        document.getElementById('edit-cpl-deskripsi').value = cpl.deskripsi || '';
        openModal('modal-edit-cpl');
    }

    function editCpmk(cpmk) {
        document.getElementById('form-edit-cpmk').action = '{{ url("admin/kurikulum-obe/cpmk") }}/' + cpmk.id;
        document.getElementById('edit-cpmk-kode').value = cpmk.kode_cpmk;
        document.getElementById('edit-cpmk-deskripsi').value = cpmk.deskripsi || '';
        openModal('modal-edit-cpmk');
    }

    function openTambahSubCpmk(cpmkId, kodeCpmk) {
        document.getElementById('tambah-sub-cpmk-id').value = cpmkId;
        document.getElementById('tambah-sub-title').textContent = 'Tambah Sub-CPMK untuk ' + kodeCpmk;
        openModal('modal-tambah-sub-cpmk');
    }

    function editSubCpmk(sub) {
        document.getElementById('form-edit-sub-cpmk').action = '{{ url("admin/kurikulum-obe/sub-cpmk") }}/' + sub.id;
        document.getElementById('edit-sub-kode').value = sub.kode_sub_cpmk;
        document.getElementById('edit-sub-cpl').value = sub.cpl_id || '';
        document.getElementById('edit-sub-bobot').value = sub.bobot_default || '';
        document.getElementById('edit-sub-deskripsi').value = sub.deskripsi || '';
        openModal('modal-edit-sub-cpmk');
    }
</script>
@endpush
@endsection
