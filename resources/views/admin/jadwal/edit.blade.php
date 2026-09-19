@extends('layouts.admin')

@section('title','Edit Jadwal')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2 class="icon-heading"><x-layout-icon name="edit" /> Edit Jadwal Kuliah</h2>
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
            <x-list-return-url list-route="admin.jadwal" />
            @method('PUT')


            <div class="krs-form-grid">

                {{-- MATA KULIAH --}}
                <div class="form-group">

                    <label>Mata Kuliah</label>
                    <x-searchable-course-select :courses="$mataKuliahs" :selected="old('mata_kuliah_id', $jadwal->mata_kuliah_id)" input-id="jadwal-mata-kuliah-search" />

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
                        required
                    >

                </div>


                {{-- SEMESTER AKADEMIK --}}
                <div class="form-group">

                    <label>Semester Akademik</label>

                    <select
                        name="semester_akademik"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Semester --
                        </option>

                        @php
                            $semesterJadwal = strtolower((string) old('semester_akademik', $jadwal->semester_akademik));
                        @endphp
                        <option value="Ganjil" {{ in_array($semesterJadwal, ['ganjil', '1', 'semester 1'], true) ? 'selected' : '' }}>
                            Ganjil
                        </option>
                        <option value="Genap" {{ in_array($semesterJadwal, ['genap', '2', 'semester 2'], true) ? 'selected' : '' }}>
                            Genap
                        </option>

                    </select>

                </div>

                @include('admin.jadwal.partials.shared-fields', ['jadwal' => $jadwal])

            </div>


            <br>


            <button
                type="submit"
                class="btn-primary icon-button"
            >
                <x-layout-icon name="save" /> Update
            </button>


            <a
                href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.jadwal') }}"
                class="btn-outline"
            >
                Batal
            </a>

        </form>

    </div>

</div>

@endsection
