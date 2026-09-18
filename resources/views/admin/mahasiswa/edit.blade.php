@extends('layouts.admin')

@section('title','Edit Mahasiswa')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Data Mahasiswa</h2>
    </div>

    <div class="page-card-body">

        {{-- ERROR VALIDASI --}}
        @if ($errors->any())
            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px;
                border-radius:8px;
                margin-bottom:20px;
            ">
                <strong>Terjadi kesalahan:</strong>

                <ul style="margin-top:10px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ERROR --}}
        @if(session('error'))
            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px;
                border-radius:8px;
                margin-bottom:20px;
            ">
                ❌ {{ session('error') }}
            </div>
        @endif


        <form
            action="{{ route('admin.mahasiswa.update', $mahasiswa->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.mahasiswa" />
            @method('PUT')


            <div class="krs-form-grid">

                {{-- NIM --}}
                <div class="form-group">
                    <label>NIM</label>

                    <input
                        type="text"
                        name="nim"
                        class="form-control"
                        value="{{ old('nim', $mahasiswa->nim) }}"
                        required
                    >
                </div>


                {{-- NAMA --}}
                <div class="form-group">
                    <label>Nama Mahasiswa</label>

                    <input
                        type="text"
                        name="nama"
                        class="form-control"
                        value="{{ old('nama', $mahasiswa->nama) }}"
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
                        value="{{ old('email', $mahasiswa->email) }}"
                    >
                </div>


                {{-- TELEPON --}}
                <div class="form-group">
                    <label>Telepon</label>

                    <input
                        type="text"
                        name="telepon"
                        class="form-control"
                        value="{{ old('telepon', $mahasiswa->telepon) }}"
                    >
                </div>


                {{-- ANGKATAN --}}
                <div class="form-group">
                    <label>Angkatan</label>

                    <input
                        type="number"
                        name="angkatan"
                        class="form-control"
                        value="{{ old('angkatan', $mahasiswa->angkatan) }}"
                    >
                </div>


                {{-- SEMESTER --}}
                <div class="form-group">
                    <label>Semester</label>

                    <select
                        name="semester"
                        class="form-control"
                    >

                        @for($i = 1; $i <= 14; $i++)

                            <option
                                value="{{ $i }}"
                                {{ old('semester', $mahasiswa->semester) == $i ? 'selected' : '' }}
                            >
                                Semester {{ $i }}
                            </option>

                        @endfor

                    </select>
                </div>


                {{-- PROGRAM STUDI --}}
                <div class="form-group">

                    <label>Program Studi</label>

                    <select
                        name="prodi_id"
                        class="form-control"
                        required
                    >

                        @foreach($prodis as $prodi)

                            <option
                                value="{{ $prodi->id }}"
                                {{ old('prodi_id', $mahasiswa->prodi_id) == $prodi->id ? 'selected' : '' }}
                            >
                                {{ $prodi->nama_prodi }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- DOSEN WALI --}}
                <div class="form-group">

                    <label>Dosen Wali</label>

                    <select
                        name="dosen_wali_id"
                        class="form-control"
                    >

                        <option value="">
                            -- Belum Ditentukan --
                        </option>

                        @foreach($dosens as $dosen)

                            <option
                                value="{{ $dosen->id }}"
                                {{ old('dosen_wali_id', $mahasiswa->dosen_wali_id) == $dosen->id ? 'selected' : '' }}
                            >
                                {{ $dosen->nama }}{{ $dosen->nidn ? ' - '.$dosen->nidn : '' }}
                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">
                        Opsional. Dosen ini yang akan memproses persetujuan KRS mahasiswa.
                    </small>

                </div>


                {{-- PASSWORD --}}
                <div class="form-group">

                    <label>Password Baru</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                    >

                    <small>
                        Kosongkan jika password tidak ingin diubah.
                    </small>

                </div>


                {{-- KONFIRMASI PASSWORD --}}
                <div class="form-group">

                    <label>Konfirmasi Password</label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                    >

                </div>

                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $mahasiswa->is_active))>
                        Akun dan data mahasiswa aktif
                    </label>
                    <small>Nonaktifkan untuk menutup akses login tanpa menghapus riwayat akademik.</small>
                </div>

            </div>


            {{-- INFO WALI --}}
            @if($mahasiswa->dosenWali)

                <div
                    style="
                        margin-top:20px;
                        padding:15px;
                        background:#f0fdf4;
                        border:1px solid #bbf7d0;
                        border-radius:10px;
                        color:#166534;
                    "
                >
                    👨‍🏫

                    <strong>Dosen Wali:</strong>

                    {{ $mahasiswa->dosenWali->nama }}

                    <br>

                    <small>
                        Dosen wali dipilih langsung pada Data Mahasiswa.
                    </small>

                </div>

            @endif


            <br>


            <button
                type="submit"
                class="btn-primary"
            >
                💾 Update
            </button>


            <a
                href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.mahasiswa') }}"
                class="btn-outline"
            >
                Batal
            </a>

        </form>

    </div>

</div>

@endsection
