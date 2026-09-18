@extends('layouts.admin')

@section('title', 'Tambah Periode KRS')

@section('content')

<div class="page-card">

    <div class="page-card-head">

        <h2>
            📅 Tambah Periode KRS
        </h2>

    </div>


    <div class="page-card-body">


        {{-- ===================== ERROR ===================== --}}

        @if($errors->any())

            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px;
                border-radius:8px;
                margin-bottom:20px;
            ">

                <strong>
                    Terjadi kesalahan:
                </strong>

                <ul style="margin-top:10px;">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- ===================== FORM ===================== --}}

        <form
            action="{{ route('admin.periode-krs.store') }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.periode-krs" />


            <div class="krs-form-grid">


                {{-- TAHUN AKADEMIK --}}

                <div class="form-group">

                    <label>
                        Tahun Akademik
                    </label>

                    <input
                        type="text"
                        name="tahun_akademik"
                        class="form-control"
                        value="{{ old('tahun_akademik') }}"
                        placeholder="Contoh: 2026/2027"
                        required
                    >

                </div>


                {{-- SEMESTER --}}

                <div class="form-group">

                    <label>
                        Semester
                    </label>

                    <select
                        name="semester"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Semester --
                        </option>

                        <option
                            value="Ganjil"
                            {{ old('semester') === 'Ganjil'
                                ? 'selected'
                                : '' }}
                        >
                            Ganjil
                        </option>

                        <option
                            value="Genap"
                            {{ old('semester') === 'Genap'
                                ? 'selected'
                                : '' }}
                        >
                            Genap
                        </option>

                    </select>

                </div>


                {{-- TANGGAL MULAI --}}

                <div class="form-group">

                    <label>
                        Tanggal & Jam Mulai
                    </label>

                    <input
                        type="datetime-local"
                        name="tanggal_mulai"
                        class="form-control"
                        value="{{ old('tanggal_mulai') }}"
                        required
                    >

                    <small style="color:#64748b;">
                        Waktu mulai mahasiswa diperbolehkan mengisi KRS.
                    </small>

                </div>


                {{-- TANGGAL SELESAI --}}

                <div class="form-group">

                    <label>
                        Tanggal & Jam Selesai
                    </label>

                    <input
                        type="datetime-local"
                        name="tanggal_selesai"
                        class="form-control"
                        value="{{ old('tanggal_selesai') }}"
                        required
                    >

                    <small style="color:#64748b;">
                        Setelah waktu ini KRS otomatis dianggap berakhir.
                    </small>

                </div>


                {{-- MINIMAL SKS --}}

                <div class="form-group">

                    <label>
                        Minimal SKS
                    </label>

                    <input
                        type="number"
                        name="minimal_sks"
                        class="form-control"
                        value="{{ old('minimal_sks', 0) }}"
                        min="0"
                        max="30"
                        required
                    >

                    <small style="color:#64748b;">
                        Minimal SKS yang harus diambil mahasiswa.
                    </small>

                </div>


                {{-- MAKSIMAL SKS --}}

                <div class="form-group">

                    <label>
                        Maksimal SKS
                    </label>

                    <input
                        type="number"
                        name="maksimal_sks"
                        class="form-control"
                        value="{{ old('maksimal_sks', 24) }}"
                        min="1"
                        max="30"
                        required
                    >

                    <small style="color:#64748b;">
                        Batas maksimal SKS yang dapat diambil mahasiswa.
                    </small>

                </div>


                {{-- STATUS --}}

                <div class="form-group">

                    <label>
                        Status KRS
                    </label>

                    <select
                        name="status"
                        class="form-control"
                        required
                    >

                        <option
                            value="Ditutup"
                            {{ old('status', 'Ditutup') === 'Ditutup'
                                ? 'selected'
                                : '' }}
                        >
                            🔒 Ditutup
                        </option>

                        <option
                            value="Dibuka"
                            {{ old('status') === 'Dibuka'
                                ? 'selected'
                                : '' }}
                        >
                            🔓 Dibuka
                        </option>

                    </select>

                    <small style="color:#64748b;">
                        Gunakan status Dibuka agar mahasiswa dapat mengisi KRS.
                    </small>

                </div>


                {{-- KETERANGAN --}}

                <div class="form-group">

                    <label>
                        Keterangan
                    </label>

                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                        placeholder="Contoh: Pengisian KRS Semester Ganjil 2026/2027"
                    >{{ old('keterangan') }}</textarea>

                </div>


            </div>

            @include('admin.periode_krs.partials.access-settings')

            {{-- ===================== BUTTON ===================== --}}

            <div style="
                margin-top:25px;
            ">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    💾 Simpan Periode
                </button>


                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.periode-krs') }}"
                    class="btn-outline"
                >
                    Batal
                </a>

            </div>


        </form>

    </div>

</div>

@endsection
