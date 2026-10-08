@extends('layouts.admin')

@section('title', 'Pratinjau Ekstraksi Dokumen RPS')
@section('page-title', 'Pratinjau Ekstraksi RPS (OBE)')
@section('page-subtitle', 'Tinjau dan verifikasi data hasil ekstraksi otomatis dari dokumen RPS PDF sebelum diterapkan ke kurikulum')

@push('styles')
<style>
    .preview-header-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .preview-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed #cbd5e1;
    }
    .preview-meta-item label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
        display: block;
    }
    .preview-meta-item .val {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
    }
    .section-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 22px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .section-card-head {
        padding: 16px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .section-card-head h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-card-body {
        padding: 20px;
    }
    .badge-cpl {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        background: #e0f2fe;
        color: #0369a1;
    }
    .badge-cpmk {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
    }
    .sticky-action-bar {
        position: sticky;
        bottom: 20px;
        background: #0f172a;
        color: #ffffff;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 100;
        margin-top: 30px;
        flex-wrap: wrap;
        gap: 14px;
    }
    .table-compact th, .table-compact td {
        padding: 10px 12px;
        font-size: 13px;
    }
</style>
@endpush

@section('content')
<div class="inner-page">

    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.obe.rps.apply') }}" id="form-apply-rps">
        @csrf
        <input type="hidden" name="temp_path" value="{{ $tempPath }}">

        {{-- Header Card: Ringkasan Ekstraksi Dokumen --}}
        <div class="preview-header-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #ecfdf5; color: #047857; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; margin-bottom: 8px;">
                        <x-layout-icon name="check" />
                        <span>Dokumen RPS Berhasil Diekstrak</span>
                    </div>
                    <h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;">
                        {{ $extracted['metadata']['nama_mk'] ?: 'Mata Kuliah Belum Terdeteksi' }}
                        @if($extracted['metadata']['kode_mk'])
                            <span style="color: #64748b; font-weight: 600;">({{ $extracted['metadata']['kode_mk'] }})</span>
                        @endif
                    </h2>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
                        File: <strong>{{ basename($tempPath) }}</strong> &bull; Total Halaman: {{ $extracted['total_pages'] }} Halaman
                    </p>
                </div>

                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('admin.obe.index', ['tab' => 'rps']) }}" class="btn-outline">
                        <x-layout-icon name="arrow-left" />
                        <span>Batalkan</span>
                    </a>
                    <button type="submit" class="btn-primary" id="btn-submit-top">
                        <x-layout-icon name="check" />
                        <span>Terapkan ke Kurikulum</span>
                    </button>
                </div>
            </div>

            <div class="preview-meta-grid">
                <div class="preview-meta-item">
                    <label>Program Studi (Teks PDF)</label>
                    <div class="val">{{ $extracted['metadata']['prodi'] ?: '-' }}</div>
                </div>
                <div class="preview-meta-item">
                    <label>Bobot SKS</label>
                    <div class="val">{{ $extracted['metadata']['sks'] ? $extracted['metadata']['sks'] . ' SKS' : '-' }}</div>
                </div>
                <div class="preview-meta-item">
                    <label>Semester</label>
                    <div class="val">{{ $extracted['metadata']['semester'] ?: '-' }}</div>
                </div>
                <div class="preview-meta-item">
                    <label>Tahun Akademik</label>
                    <div class="val">{{ $extracted['metadata']['tahun_akademik'] ?: '-' }}</div>
                </div>
                <div class="preview-meta-item">
                    <label>Kode Dokumen</label>
                    <div class="val">{{ $extracted['metadata']['kode_dokumen'] ?: '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Section 1: Konfirmasi Mata Kuliah & Pengaturan RPS --}}
        <div class="section-card">
            <div class="section-card-head">
                <h3>
                    <x-layout-icon name="book" />
                    <span>1. Penetapan Mata Kuliah &amp; Konfigurasi RPS</span>
                </h3>
            </div>
            <div class="section-card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                    <div class="form-group">
                        <label for="select-prodi" style="font-weight: 600; font-size: 13px;">Program Studi *</label>
                        <select name="prodi_id" id="select-prodi" class="form-control" required>
                            <option value="">-- Pilih Program Studi --</option>
                            @foreach($allProdis as $p)
                                <option value="{{ $p->id }}" @selected($selectedProdiId == $p->id)>
                                    {{ $p->jenjang }} {{ $p->nama_prodi }}
                                </option>
                            @endforeach
                        </select>
                        <small style="color: #64748b;">CPL yang disinkronkan akan ditautkan ke program studi ini.</small>
                    </div>

                    <div class="form-group">
                        <label for="select-mk" style="font-weight: 600; font-size: 13px;">Mata Kuliah Tujuan *</label>
                        <select name="mata_kuliah_id" id="select-mk" class="form-control" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            @foreach($allMataKuliahs as $mk)
                                <option value="{{ $mk->id }}"
                                        data-prodi="{{ $mk->prodi_id }}"
                                        @selected($selectedMkId == $mk->id)>
                                    {{ $mk->kode_mk }} — {{ $mk->nama_mk }} ({{ $mk->prodi?->nama_prodi ?? 'Umum' }})
                                </option>
                            @endforeach
                        </select>
                        @if($matchedMk)
                            <small style="color: #059669; font-weight: 600;">Otomatis dicocokkan dengan kode/nama mata kuliah di database.</small>
                        @else
                            <small style="color: #d97706; font-weight: 600;">Silakan pilih mata kuliah yang sesuai di database.</small>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="input-tahun" style="font-weight: 600; font-size: 13px;">Tahun Akademik RPS</label>
                        <input type="text"
                               name="tahun_akademik"
                               id="input-tahun"
                               class="form-control"
                               placeholder="Contoh: 2025/2026"
                               value="{{ $extracted['metadata']['tahun_akademik'] ?? date('Y') . '/' . (date('Y') + 1) }}">
                    </div>

                    <div class="form-group">
                        <label for="input-passing" style="font-weight: 600; font-size: 13px;">Target Passing Grade (0 - 100) *</label>
                        <input type="number"
                               step="0.1"
                               min="0"
                               max="100"
                               name="target_passing_grade"
                               id="input-passing"
                               class="form-control"
                               value="{{ $extracted['target_passing_grade'] ?? 60.0 }}"
                               required>
                        <small style="color: #64748b;">Standar STTMI adalah nilai 60 (Grade C+).</small>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <label style="display: inline-flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span style="font-size: 13px; font-weight: 600;">Jadikan sebagai Dokumen RPS Aktif untuk mata kuliah ini (menggantikan RPS lama)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Section 2: Komponen & Bobot Instrumen Penilaian Default --}}
        <div class="section-card">
            <div class="section-card-head">
                <div>
                    <h3>
                        <x-layout-icon name="services" />
                        <span>2. Komponen &amp; Bobot Penilaian Default (Template Dosen)</span>
                    </h3>
                    <p style="margin: 3px 0 0; font-size: 12px; color: #64748b;">
                        Komponen ini akan otomatis menjadi template dasar penilaian setiap kelas mata kuliah ini di portal dosen.
                    </p>
                </div>
                <div>
                    <span id="badge-total-bobot" class="badge badge-success" style="font-size: 13px; padding: 5px 12px;">
                        Total Bobot: <span id="label-total-bobot">{{ $extracted['total_bobot'] }}</span>%
                    </span>
                </div>
            </div>
            <div class="section-card-body">
                <div class="table-wrap">
                    <table class="table-compact" style="width: 100%;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 50px; text-align: center;">No</th>
                                <th>Nama Instrumen Penilaian</th>
                                <th style="width: 180px;">Bobot (%)</th>
                                <th style="width: 70px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="komponen-rows">
                            @forelse($extracted['komponen_bobot'] as $kompNama => $kompBobot)
                                <tr class="komponen-row">
                                    <td class="row-num" style="text-align: center; font-weight: 600;">{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="text"
                                               name="komponen_nama[]"
                                               class="form-control"
                                               value="{{ $kompNama }}"
                                               placeholder="Contoh: UAS / UTS / Tugas"
                                               required>
                                    </td>
                                    <td>
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               name="komponen_bobot[]"
                                               class="form-control input-bobot-komponen"
                                               value="{{ $kompBobot }}"
                                               required>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-sm btn-outline btn-remove-komponen" style="color: #dc2626; border-color: #fca5a5;">
                                            <x-layout-icon name="trash" />
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr class="komponen-row">
                                    <td class="row-num" style="text-align: center; font-weight: 600;">1</td>
                                    <td>
                                        <input type="text" name="komponen_nama[]" class="form-control" value="UAS" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="komponen_bobot[]" class="form-control input-bobot-komponen" value="25" required>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn-sm btn-outline btn-remove-komponen" style="color: #dc2626; border-color: #fca5a5;">
                                            <x-layout-icon name="trash" />
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 14px;">
                    <button type="button" class="btn-outline" id="btn-add-komponen">
                        <x-layout-icon name="plus" />
                        <span>Tambah Instrumen Penilaian</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Section 3: Target Porsi Bobot CPL --}}
        <div class="section-card">
            <div class="section-card-head">
                <h3>
                    <x-layout-icon name="file-chart" />
                    <span>3. Target Porsi Bobot CPL (% Ketercapaian Mata Kuliah)</span>
                </h3>
            </div>
            <div class="section-card-body">
                <p style="font-size: 13px; color: #64748b; margin-top: 0; margin-bottom: 16px;">
                    Proporsi beban capaian pembelajaran lulusan (CPL) untuk mata kuliah ini dihitung proporsional dari jumlah Sub-CPMK pendukung.
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                    @forelse($extracted['porsi_cpl'] as $cplKey => $cplPercent)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <span class="badge-cpl">{{ $cplKey }}</span>
                                <input type="hidden" name="porsi_cpl_keys[]" value="{{ $cplKey }}">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       name="porsi_cpl_values[]"
                                       class="form-control"
                                       value="{{ $cplPercent }}">
                                <span style="font-size: 13px; font-weight: 600; color: #64748b;">%</span>
                            </div>
                        </div>
                    @empty
                        <div style="color: #94a3b8; font-size: 13px;">Tidak ada estimasi porsi CPL yang terdeteksi.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Section 4: CPL Terbaca dari RPS --}}
        <div class="section-card">
            <div class="section-card-head">
                <div>
                    <h3>
                        <x-layout-icon name="layers" />
                        <span>4. Capaian Pembelajaran Lulusan (CPL) PRODI yang Dibebankan</span>
                    </h3>
                    <p style="margin: 3px 0 0; font-size: 12px; color: #64748b;">
                        Terdeteksi {{ count($extracted['cpls']) }} CPL. Sistem akan memperbarui atau menambahkan CPL ini ke database prodi.
                    </p>
                </div>
            </div>
            <div class="section-card-body">
                <div class="table-wrap">
                    <table class="table-compact" style="width: 100%;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 110px;">Kode CPL</th>
                                <th>Deskripsi Capaian Pembelajaran Lulusan</th>
                                <th style="width: 180px;">CPMK Terkait</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($extracted['cpls'] as $idx => $cpl)
                                <tr>
                                    <td>
                                        <input type="text"
                                               name="cpls[{{ $idx }}][kode_cpl]"
                                               class="form-control"
                                               value="{{ $cpl['kode_cpl'] }}"
                                               required>
                                    </td>
                                    <td>
                                        <textarea name="cpls[{{ $idx }}][deskripsi]"
                                                  class="form-control"
                                                  rows="2"
                                                  required>{{ $cpl['deskripsi'] }}</textarea>
                                    </td>
                                    <td>
                                        @if(!empty($cpl['cpmk_terkait']))
                                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                                @foreach($cpl['cpmk_terkait'] as $rel)
                                                    <span class="badge-cpmk">{{ $rel }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 11px;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">
                                        Tidak ada CPL yang berhasil terdeteksi dari file.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section 5: CPMK Terbaca dari RPS --}}
        <div class="section-card">
            <div class="section-card-head">
                <div>
                    <h3>
                        <x-layout-icon name="graduation" />
                        <span>5. Capaian Pembelajaran Mata Kuliah (CPMK)</span>
                    </h3>
                    <p style="margin: 3px 0 0; font-size: 12px; color: #64748b;">
                        Terdeteksi {{ count($extracted['cpmks']) }} CPMK untuk mata kuliah ini.
                    </p>
                </div>
            </div>
            <div class="section-card-body">
                <div class="table-wrap">
                    <table class="table-compact" style="width: 100%;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 120px;">Kode CPMK</th>
                                <th>Deskripsi Capaian Pembelajaran Mata Kuliah</th>
                                <th style="width: 140px;">CPL Terkait</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($extracted['cpmks'] as $idx => $cpmk)
                                <tr>
                                    <td>
                                        <input type="text"
                                               name="cpmks[{{ $idx }}][kode_cpmk]"
                                               class="form-control"
                                               value="{{ $cpmk['kode_cpmk'] }}"
                                               required>
                                    </td>
                                    <td>
                                        <textarea name="cpmks[{{ $idx }}][deskripsi]"
                                                  class="form-control"
                                                  rows="2"
                                                  required>{{ $cpmk['deskripsi'] }}</textarea>
                                    </td>
                                    <td>
                                        @if(!empty($cpmk['cpl_terkait']))
                                            <span class="badge-cpl">{{ $cpmk['cpl_terkait'] }}</span>
                                        @else
                                            <span style="color: #94a3b8; font-size: 11px;">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">
                                        Tidak ada CPMK yang berhasil terdeteksi dari file.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Section 6: Sub-CPMK Terbaca dari RPS --}}
        <div class="section-card">
            <div class="section-card-head">
                <div>
                    <h3>
                        <x-layout-icon name="clipboard" />
                        <span>6. Kemampuan Akhir Tiap Tahapan Belajar (Sub-CPMK)</span>
                    </h3>
                    <p style="margin: 3px 0 0; font-size: 12px; color: #64748b;">
                        Terdeteksi {{ count($extracted['sub_cpmks']) }} Sub-CPMK beserta korelasi ke CPMK dan CPL.
                    </p>
                </div>
            </div>
            <div class="section-card-body">
                <div class="table-wrap">
                    <table class="table-compact" style="width: 100%;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 120px;">Kode Sub-CPMK</th>
                                <th style="width: 130px;">CPMK Induk</th>
                                <th style="width: 120px;">CPL Terkait</th>
                                <th>Deskripsi Sub-CPMK</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($extracted['sub_cpmks'] as $idx => $sub)
                                <tr>
                                    <td>
                                        <input type="text"
                                               name="sub_cpmks[{{ $idx }}][kode_sub_cpmk]"
                                               class="form-control"
                                               value="{{ $sub['kode_sub_cpmk'] }}"
                                               required>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="sub_cpmks[{{ $idx }}][cpmk_kode]"
                                               class="form-control"
                                               value="{{ $sub['cpmk_kode'] ?: 'CPMK 1' }}"
                                               placeholder="CPMK 1"
                                               required>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="sub_cpmks[{{ $idx }}][cpl_kode]"
                                               class="form-control"
                                               value="{{ $sub['cpl_kode'] ?: '' }}"
                                               placeholder="CPL 1">
                                    </td>
                                    <td>
                                        <textarea name="sub_cpmks[{{ $idx }}][deskripsi]"
                                                  class="form-control"
                                                  rows="2"
                                                  required>{{ $sub['deskripsi'] }}</textarea>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">
                                        Tidak ada Sub-CPMK yang berhasil terdeteksi dari file.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Sticky Bottom Action Bar --}}
        <div class="sticky-action-bar">
            <div>
                <strong style="display: block; font-size: 14px;">Siap menerapkan hasil ekstraksi ke kurikulum?</strong>
                <span style="font-size: 12px; color: #94a3b8;">
                    Pastikan program studi dan mata kuliah tujuan telah dipilih dengan benar.
                </span>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <a href="{{ route('admin.obe.index', ['tab' => 'rps']) }}" class="btn-outline" style="color: #ffffff; border-color: #475569;">
                    <span>Batal</span>
                </a>
                <button type="submit" class="btn-primary" style="background: #2563eb; border-color: #2563eb;">
                    <x-layout-icon name="check" />
                    <span>Simpan &amp; Terapkan ke Kurikulum</span>
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectProdi = document.getElementById('select-prodi');
    const selectMk = document.getElementById('select-mk');
    const komponenRows = document.getElementById('komponen-rows');
    const btnAddKomponen = document.getElementById('btn-add-komponen');
    const labelTotalBobot = document.getElementById('label-total-bobot');
    const badgeTotalBobot = document.getElementById('badge-total-bobot');

    // Filter Mata Kuliah berdasarkan Program Studi yang dipilih
    function filterMataKuliah() {
        const prodiId = selectProdi.value;
        const currentSelectedMk = selectMk.value;
        let hasSelectedVisible = false;

        Array.from(selectMk.options).forEach(opt => {
            if (!opt.value) return; // skip placeholder
            const optProdi = opt.getAttribute('data-prodi');
            if (!prodiId || optProdi === prodiId) {
                opt.style.display = '';
                if (opt.value === currentSelectedMk) {
                    hasSelectedVisible = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });

        if (!hasSelectedVisible && prodiId) {
            // Pilih opsi pertama yang terlihat
            const firstVisible = Array.from(selectMk.options).find(opt => opt.value && opt.style.display !== 'none');
            if (firstVisible) {
                selectMk.value = firstVisible.value;
            }
        }
    }

    if (selectProdi && selectMk) {
        selectProdi.addEventListener('change', filterMataKuliah);
    }

    // Hitung total bobot instrumen penilaian
    function updateTotalBobot() {
        let total = 0;
        document.querySelectorAll('.input-bobot-komponen').forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
        });

        const rounded = Math.round(total * 100) / 100;
        labelTotalBobot.textContent = rounded;

        if (rounded === 100) {
            badgeTotalBobot.className = 'badge badge-success';
            badgeTotalBobot.style.background = '#059669';
            badgeTotalBobot.style.color = '#ffffff';
        } else {
            badgeTotalBobot.className = 'badge badge-warning';
            badgeTotalBobot.style.background = '#d97706';
            badgeTotalBobot.style.color = '#ffffff';
        }
    }

    // Refresh row numbers
    function refreshRowNumbers() {
        document.querySelectorAll('.komponen-row').forEach((row, idx) => {
            const numCell = row.querySelector('.row-num');
            if (numCell) numCell.textContent = idx + 1;
        });
    }

    // Tambah baris instrumen
    if (btnAddKomponen) {
        btnAddKomponen.addEventListener('click', function () {
            const tr = document.createElement('tr');
            tr.className = 'komponen-row';
            tr.innerHTML = `
                <td class="row-num" style="text-align: center; font-weight: 600;"></td>
                <td>
                    <input type="text" name="komponen_nama[]" class="form-control" placeholder="Contoh: Kuis 1" required>
                </td>
                <td>
                    <input type="number" step="0.01" min="0" max="100" name="komponen_bobot[]" class="form-control input-bobot-komponen" value="10" required>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-sm btn-outline btn-remove-komponen" style="color: #dc2626; border-color: #fca5a5;">
                        <x-layout-icon name="trash" />
                    </button>
                </td>
            `;
            komponenRows.appendChild(tr);
            refreshRowNumbers();
            updateTotalBobot();
        });
    }

    // Hapus baris instrumen (Event delegation)
    if (komponenRows) {
        komponenRows.addEventListener('click', function (e) {
            const btnRemove = e.target.closest('.btn-remove-komponen');
            if (btnRemove) {
                const row = btnRemove.closest('.komponen-row');
                if (document.querySelectorAll('.komponen-row').length > 1) {
                    row.remove();
                    refreshRowNumbers();
                    updateTotalBobot();
                } else {
                    alert('Minimal harus ada 1 komponen penilaian.');
                }
            }
        });

        komponenRows.addEventListener('input', function (e) {
            if (e.target.classList.contains('input-bobot-komponen')) {
                updateTotalBobot();
            }
        });
    }

    updateTotalBobot();

    // Validasi saat submit form
    const formApply = document.getElementById('form-apply-rps');
    if (formApply) {
        formApply.addEventListener('submit', function (e) {
            let total = 0;
            document.querySelectorAll('.input-bobot-komponen').forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            const rounded = Math.round(total * 100) / 100;

            if (rounded !== 100) {
                if (!confirm(`Perhatian: Total bobot instrumen penilaian adalah ${rounded}% (belum tepat 100%). Apakah Anda yakin ingin melanjutkan dan menyesuaikannya nanti?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>
@endpush
