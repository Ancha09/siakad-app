@extends('layouts.admin')

@section('title','Edit Dosen')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Data Dosen</h2>
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

                    <h2>🎓 Mahasiswa Wali</h2>

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
                    💾 Update Dosen
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

@endsection
