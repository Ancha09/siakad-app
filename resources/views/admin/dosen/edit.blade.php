@extends('layouts.admin')

@section('title','Edit Dosen')

@section('content')

<div class="inner-page">

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;">
            {{ session('info') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">
            {{ session('error') }}
        </div>
    @endif

    {{-- ===================== FOTO PROFIL DOSEN (ADMIN VIEW & RESET) ===================== --}}
    <div class="page-card" style="margin-bottom: 22px;">
        <div class="page-card-head">
            <h2>
                <x-layout-icon name="user" />
                <span>Foto Profil Dosen</span>
            </h2>
        </div>
        <div class="page-card-body">
            @if($dosen->foto)
                <div style="display:flex;gap:22px;align-items:center;flex-wrap:wrap;">
                    <div style="width:100px;height:100px;border-radius:50%;overflow:hidden;box-shadow:0 3px 10px rgba(0,0,0,0.1);border:3px solid #ffffff;flex-shrink:0;">
                        <img src="{{ asset('storage/' . $dosen->foto) }}" alt="{{ $dosen->nama }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                    </div>
                    <div style="flex:1;min-width:240px;">
                        <div style="margin-bottom:8px;">
                            <span class="badge-status-pill badge-status-green">
                                <x-layout-icon name="check" />
                                <span>Foto Profil Terpasang (Terkunci bagi Dosen)</span>
                            </span>
                        </div>
                        <p style="margin:0 0 12px 0;color:#64748b;font-size:12.5px;line-height:1.5;">
                            Foto profil ini diunggah secara mandiri oleh dosen. Sebagai Administrator, Anda memiliki akses untuk melihat dan melakukan reset foto jika diperlukan pergantian foto baru.
                        </p>
                        <form action="{{ route('admin.dosen.reset-foto', $dosen->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin me-reset foto profil dosen ini? File foto akan dihapus permanen dan dosen bersangkutan dapat mengunggah kembali foto baru.')">
                            @csrf
                            <button type="submit" class="btn-outline btn-sm btn-table-action" style="color:#dc2626;border-color:#fca5a5;background:#fef2f2;">
                                <x-layout-icon name="trash" />
                                <span>Reset Foto Profil Dosen</span>
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
                    <div style="width:80px;height:80px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:28px;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($dosen->nama ?? 'D', 0, 1)) }}
                    </div>
                    <div style="flex:1;min-width:240px;">
                        <div style="margin-bottom:6px;">
                            <span class="badge-status-pill badge-status-gray">
                                Belum Ada Foto Profil
                            </span>
                        </div>
                        <p style="margin:0;color:#64748b;font-size:12.5px;line-height:1.5;">
                            Dosen bersangkutan belum mengunggah foto profil. Sesuai kebijakan sistem, pengunggahan foto dilakukan secara mandiri oleh dosen melalui Portal Dosen (maksimal 1 kali unggah). Administrator tidak dapat mengunggah foto secara langsung.
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="page-card">

        <div class="page-card-head">
            <h2><x-layout-icon name="edit" /> <span>Edit Data Dosen</span></h2>
        </div>

    <div class="page-card-body">

        <form
            action="{{ route('admin.dosen.update', $dosen->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.dosen" />
            @method('PUT')


            <div class="krs-form-grid">


                {{-- NIDN --}}
                <div class="form-group">

                    <label>NIDN</label>

                    <input
                        type="text"
                        name="nidn"
                        class="form-control"
                        value="{{ old('nidn', $dosen->nidn) }}"
                        required
                    >

                </div>


                {{-- NAMA --}}
                <div class="form-group">

                    <label>Nama Lengkap</label>

                    <input
                        type="text"
                        name="nama"
                        class="form-control"
                        value="{{ old('nama', $dosen->nama) }}"
                        required
                    >

                </div>


                {{-- EMAIL --}}
                <div class="form-group">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="{{ old('email', $dosen->email) }}"
                    >

                </div>


                {{-- TELEPON --}}
                <div class="form-group">

                    <label>Telepon</label>

                    <input
                        type="text"
                        name="telepon"
                        class="form-control"
                        value="{{ old('telepon', $dosen->telepon) }}"
                    >

                </div>


                {{-- JABATAN --}}
                <div class="form-group">

                    <label>Jabatan</label>

                    <input
                        type="text"
                        name="jabatan"
                        class="form-control"
                        value="{{ old('jabatan', $dosen->jabatan) }}"
                    >

                </div>


                {{-- GOLONGAN --}}
                <div class="form-group">

                    <label>Golongan</label>

                    <input
                        type="text"
                        name="golongan"
                        class="form-control"
                        value="{{ old('golongan', $dosen->golongan) }}"
                    >

                </div>


                {{-- PROGRAM STUDI --}}
                <div class="form-group">

                    <label>Program Studi</label>

                    <select
                        name="prodi_id"
                        class="form-control"
                    >

                        <option value="">
                            -- Pilih Program Studi --
                        </option>

                        @foreach($prodis as $prodi)

                            <option
                                value="{{ $prodi->id }}"
                                {{ $dosen->prodi_id == $prodi->id ? 'selected' : '' }}
                            >

                                {{ $prodi->nama_prodi }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- PASSWORD --}}
                <div class="form-group">

                    <label>Password Baru</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        autocomplete="new-password"
                    >

                    <small>
                        Kosongkan jika tidak ingin mengubah password.
                    </small>

                </div>


                {{-- KONFIRMASI PASSWORD --}}
                <div class="form-group">

                    <label>Konfirmasi Password</label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        autocomplete="new-password"
                    >

                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $dosen->is_active))>
                        Akun dan data dosen aktif
                    </label>
                    <small>Nonaktifkan untuk menutup akses login tanpa menghapus jadwal atau riwayat akademik.</small>
                </div>


            </div>


            {{-- ================================================= --}}
            {{-- MAHASISWA WALI --}}
            {{-- ================================================= --}}

            <div
                class="page-card"
                style="
                    margin-top:25px;
                    border:1px solid #e5e7eb;
                    box-shadow:none;
                "
            >

                <div class="page-card-head">

                    <h2><x-layout-icon name="graduation" /> <span>Mahasiswa Wali</span></h2>

                    <span class="badge badge-blue">
                        Opsional
                    </span>

                </div>


                <div class="page-card-body">

                    <div
                        class="info-alert"
                        style="margin-bottom:20px;"
                    >

                        <span>ℹ️</span>

                        <div>

                            <strong>Penugasan Dosen Wali</strong>

                            <br>

                            Pilih mahasiswa yang menjadi tanggung jawab
                            dosen ini. Dosen tidak wajib memiliki mahasiswa wali.

                        </div>

                    </div>


                    @if(isset($mahasiswas) && $mahasiswas->count() > 0)

                        <div
                            style="
                                max-height:350px;
                                overflow-y:auto;
                                border:1px solid #e5e7eb;
                                border-radius:8px;
                            "
                        >

                            @foreach($mahasiswas as $mahasiswa)

                                <label
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:12px;
                                        padding:12px 15px;
                                        border-bottom:1px solid #f0f0f0;
                                        cursor:pointer;
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        name="mahasiswa_wali[]"
                                        value="{{ $mahasiswa->id }}"

                                        {{ $mahasiswa->dosen_wali_id == $dosen->id ? 'checked' : '' }}
                                    >

                                    <div>

                                        <strong>
                                            {{ $mahasiswa->nama }}
                                        </strong>

                                        <div
                                            style="
                                                font-size:12px;
                                                color:#777;
                                                margin-top:2px;
                                            "
                                        >

                                            NIM:
                                            {{ $mahasiswa->nim ?? '-' }}

                                            &nbsp; • &nbsp;

                                            {{ $mahasiswa->prodi->nama_prodi ?? '-' }}

                                        </div>

                                    </div>

                                </label>

                            @endforeach

                        </div>

                    @else

                        <div
                            style="
                                text-align:center;
                                padding:25px;
                                color:#777;
                            "
                        >

                            Belum ada data mahasiswa.

                        </div>

                    @endif


                </div>

            </div>


            {{-- ================================================= --}}
            {{-- TOMBOL --}}
            {{-- ================================================= --}}

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    <x-layout-icon name="save" />
                    <span>Update Dosen</span>
                </button>


                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.dosen') }}"
                    class="btn-outline"
                >
                    Batal
                </a>

            </div>


        </form>

    </div>

</div>

</div>

@endsection
