@extends('layouts.admin')

@section('title','Edit Jadwal')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Jadwal Kuliah</h2>
    </div>

    <div class="page-card-body">

        @if ($errors->any())
            <div style="background:#fee2e2;color:#b91c1c;padding:15px;border-radius:8px;margin-bottom:20px;">
                <strong>Terjadi kesalahan:</strong>

                <ul style="margin-top:10px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif


        <form
            action="{{ route('admin.jadwal.update', $jadwal->id) }}"
            method="POST"
        >

            @csrf
            @method('PUT')


            <div class="krs-form-grid">

                {{-- MATA KULIAH --}}
                <div class="form-group">

                    <label>Mata Kuliah</label>

                    <select
                        name="mata_kuliah_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Mata Kuliah --
                        </option>

                        @foreach($mataKuliahs as $mk)

                            <option
                                value="{{ $mk->id }}"
                                {{ old('mata_kuliah_id', $jadwal->mata_kuliah_id) == $mk->id ? 'selected' : '' }}
                            >

                                {{ $mk->kode_mk }} -
                                {{ $mk->nama_mk }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- DOSEN --}}
                <div class="form-group">

                    <label>Dosen Pengampu</label>

                    <select
                        name="dosen_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Dosen --
                        </option>

                        @foreach($dosens as $dosen)

                            <option
                                value="{{ $dosen->id }}"
                                {{ old('dosen_id', $jadwal->dosen_id) == $dosen->id ? 'selected' : '' }}
                            >

                                {{ $dosen->nama }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- RUANGAN --}}
                <div class="form-group">

                    <label>Ruangan</label>

                    <select
                        name="ruangan_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Ruangan --
                        </option>

                        @foreach($ruangans as $ruangan)

                            <option
                                value="{{ $ruangan->id }}"
                                {{ old('ruangan_id', $jadwal->ruangan_id) == $ruangan->id ? 'selected' : '' }}
                            >

                                {{ $ruangan->kode_ruangan }} -
                                {{ $ruangan->nama_ruangan }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- KELAS --}}
                <div class="form-group">

                    <label>Kelas</label>

                    <select
                        name="kelas_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Kelas --
                        </option>

                        @foreach($kelases as $kelas)

                            <option
                                value="{{ $kelas->id }}"
                                {{ old('kelas_id', $jadwal->kelas_id) == $kelas->id ? 'selected' : '' }}
                            >

                                {{ $kelas->nama_kelas }}
                                -
                                {{ $kelas->prodi->nama_prodi ?? '-' }}
                                (Angkatan {{ $kelas->angkatan }})

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">
                        Kelas terhubung dengan Program Studi dan angkatan.
                    </small>

                </div>


                {{-- HARI --}}
                <div class="form-group">

                    <label>Hari</label>

                    <select
                        name="hari"
                        class="form-control"
                        required
                    >

                        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $hari)

                            <option
                                value="{{ $hari }}"
                                {{ old('hari', $jadwal->hari) == $hari ? 'selected' : '' }}
                            >

                                {{ $hari }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- JAM MULAI --}}
                <div class="form-group">

                    <label>Jam Mulai</label>

                    <input
                        type="time"
                        name="jam_mulai"
                        class="form-control"
                        value="{{ old('jam_mulai', $jadwal->jam_mulai) }}"
                        required
                    >

                </div>


                {{-- JAM SELESAI --}}
                <div class="form-group">

                    <label>Jam Selesai</label>

                    <input
                        type="time"
                        name="jam_selesai"
                        class="form-control"
                        value="{{ old('jam_selesai', $jadwal->jam_selesai) }}"
                        required
                    >

                </div>


                {{-- TAHUN AKADEMIK --}}
                <div class="form-group">

                    <label>Tahun Akademik</label>

                    <input
                        type="text"
                        name="tahun_akademik"
                        class="form-control"
                        value="{{ old('tahun_akademik', $jadwal->tahun_akademik) }}"
                        placeholder="Contoh: 2026/2027"
                    >

                </div>


                {{-- SEMESTER AKADEMIK --}}
                <div class="form-group">

                    <label>Semester Akademik</label>

                    <select
                        name="semester_akademik"
                        class="form-control"
                    >

                        <option value="">
                            -- Pilih Semester --
                        </option>

                        @for($i = 1; $i <= 14; $i++)

                            <option
                                value="{{ $i }}"
                                {{ old('semester_akademik', $jadwal->semester_akademik) == $i ? 'selected' : '' }}
                            >

                                Semester {{ $i }}

                            </option>

                        @endfor

                    </select>

                </div>

            </div>


            <br>


            <button
                type="submit"
                class="btn-primary"
            >
                💾 Update
            </button>


            <a
                href="{{ route('admin.jadwal') }}"
                class="btn-outline"
            >
                Batal
            </a>

        </form>

    </div>

</div>

@endsection