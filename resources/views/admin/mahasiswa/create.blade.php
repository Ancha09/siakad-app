@extends('layouts.admin')

@section('title','Tambah Mahasiswa')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>🎓 Tambah Data Mahasiswa</h2>
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


        {{-- ERROR DARI CONTROLLER --}}
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
            action="{{ route('admin.mahasiswa.store') }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.mahasiswa" />

            <div class="krs-form-grid">


                {{-- NIM --}}
                <div class="form-group">

                    <label>NIM</label>

                    <input
                        type="text"
                        name="nim"
                        class="form-control"
                        value="{{ old('nim') }}"
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
                        value="{{ old('nama') }}"
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
                        value="{{ old('email') }}"
                    >

                </div>


                {{-- TELEPON --}}
                <div class="form-group">

                    <label>Telepon</label>

                    <input
                        type="text"
                        name="telepon"
                        class="form-control"
                        value="{{ old('telepon') }}"
                    >

                </div>


                {{-- ANGKATAN --}}
                <div class="form-group">

                    <label>Angkatan</label>

                    <input
                        type="number"
                        name="angkatan"
                        class="form-control"
                        value="{{ old('angkatan') }}"
                        placeholder="Contoh: 2026"
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
                                {{ old('semester', 1) == $i ? 'selected' : '' }}
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

                        <option value="">
                            -- Pilih Program Studi --
                        </option>

                        @foreach($prodis as $prodi)

                            <option
                                value="{{ $prodi->id }}"
                                {{ old('prodi_id') == $prodi->id ? 'selected' : '' }}
                            >

                                {{ $prodi->nama_prodi }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ================= KELAS ================= --}}
                <div class="form-group">

                    <label>Kelas</label>

                    <select
                        name="kelas_id"
                        class="form-control"
                    >

                        <option value="">
                            -- Tidak Ada Kelas --
                        </option>

                        @foreach($kelases as $kelas)

                            <option
                                value="{{ $kelas->id }}"
                                {{ old('kelas_id') == $kelas->id ? 'selected' : '' }}
                            >

                                {{ $kelas->nama_kelas }}

                                -
                                {{ $kelas->prodi->nama_prodi ?? '-' }}

                                ({{ $kelas->angkatan }})

                                @if($kelas->dosenWali)

                                    | Wali:
                                    {{ $kelas->dosenWali->nama }}

                                @else

                                    | Wali: Belum ditentukan

                                @endif

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">
                        Dosen Wali mengikuti kelas yang dipilih.
                    </small>

                </div>


                {{-- PASSWORD --}}
                <div class="form-group">

                    <label>Password Login</label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >

                </div>


                {{-- KONFIRMASI PASSWORD --}}
                <div class="form-group">

                    <label>Konfirmasi Password</label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        required
                    >

                </div>


            </div>


            <br>


            {{-- SIMPAN --}}
            <button
                type="submit"
                class="btn-primary"
            >

                💾 Simpan Mahasiswa

            </button>


            {{-- BATAL --}}
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
