@extends('layouts.admin')

@section('title', 'Edit Kelas')

@section('content')

<div class="page-card">

    <div class="page-card-head">

        <h2>✏️ Edit Kelas</h2>

    </div>


    <div class="page-card-body">


        {{-- ERROR VALIDASI --}}

        @if($errors->any())

            <div
                style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <strong>Terjadi kesalahan:</strong>

                <ul style="margin:8px 0 0 20px;">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        <form
            action="{{ route('admin.kelas.update', $kelas->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.kelas" />

            @method('PUT')


            <div class="krs-form-grid">


                {{-- NAMA KELAS --}}

                <div class="form-group">

                    <label>Nama Kelas</label>

                    <input
                        type="text"
                        name="nama_kelas"
                        class="form-control"
                        value="{{ old('nama_kelas', $kelas->nama_kelas) }}"
                        required
                    >

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
                                {{ old('prodi_id', $kelas->prodi_id) == $prodi->id ? 'selected' : '' }}
                            >

                                {{ $prodi->nama_prodi }}

                                @if($prodi->fakultas)
                                    - {{ $prodi->fakultas->nama_fakultas }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ANGKATAN --}}

                <div class="form-group">

                    <label>Angkatan</label>

                    <input
                        type="number"
                        name="angkatan"
                        class="form-control"
                        value="{{ old('angkatan', $kelas->angkatan) }}"
                        min="2000"
                        max="2100"
                        required
                    >

                </div>


                {{-- SEMESTER --}}

                <div class="form-group">

                    <label>Semester</label>

                    <select
                        name="semester"
                        class="form-control"
                    >

                        <option value="">
                            -- Pilih Semester --
                        </option>

                        @for($i = 1; $i <= 14; $i++)

                            <option
                                value="{{ $i }}"
                                {{ old('semester', $kelas->semester) == $i ? 'selected' : '' }}
                            >

                                Semester {{ $i }}

                            </option>

                        @endfor

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
                            -- Tidak Ada Dosen Wali --
                        </option>

                        @foreach($dosens as $dosen)

                            <option
                                value="{{ $dosen->id }}"
                                {{ old('dosen_wali_id', $kelas->dosen_wali_id) == $dosen->id ? 'selected' : '' }}
                            >

                                {{ $dosen->nama }}

                                @if($dosen->nidn)
                                    ({{ $dosen->nidn }})
                                @endif

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">
                        Dosen Wali dapat diubah kapan saja.
                    </small>

                </div>


            </div>


            {{-- INFO KELAS --}}

            <div
                style="
                    margin-top:25px;
                    padding:15px;
                    background:#eff6ff;
                    border:1px solid #bfdbfe;
                    border-radius:10px;
                    color:#1e40af;
                "
            >

                <div>

                    ℹ️

                    <strong>
                        {{ $kelas->mahasiswas()->count() }} mahasiswa
                    </strong>

                    terdaftar pada kelas ini.

                </div>

                <div style="margin-top:5px;">

                    Dosen Wali yang dipilih akan menjadi wali
                    bagi mahasiswa yang berada di kelas ini.

                </div>

            </div>


            {{-- BUTTON --}}

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary"
                >

                    💾 Simpan Perubahan

                </button>


                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.kelas') }}"
                    class="btn-outline"
                >

                    Batal

                </a>

            </div>


        </form>

    </div>

</div>

@endsection
