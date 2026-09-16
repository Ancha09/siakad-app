@extends('layouts.admin')

@section('title', 'Edit Periode KRS')

@section('content')

<div class="page-card">

    <div class="page-card-head">

        <h2>
            ✏️ Edit Periode KRS
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
            action="{{ route(
                'admin.periode-krs.update',
                $periodeKrs->id
            ) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.periode-krs" />

            @method('PUT')


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
                        value="{{ old(
                            'tahun_akademik',
                            $periodeKrs->tahun_akademik
                        ) }}"
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

                        <option
                            value="Ganjil"
                            {{ old(
                                'semester',
                                $periodeKrs->semester
                            ) === 'Ganjil'
                                ? 'selected'
                                : '' }}
                        >
                            Ganjil
                        </option>

                        <option
                            value="Genap"
                            {{ old(
                                'semester',
                                $periodeKrs->semester
                            ) === 'Genap'
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
                        value="{{ old(
                            'tanggal_mulai',
                            $periodeKrs->tanggal_mulai
                                ? $periodeKrs->tanggal_mulai->format('Y-m-d\TH:i')
                                : ''
                        ) }}"
                        required
                    >

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
                        value="{{ old(
                            'tanggal_selesai',
                            $periodeKrs->tanggal_selesai
                                ? $periodeKrs->tanggal_selesai->format('Y-m-d\TH:i')
                                : ''
                        ) }}"
                        required
                    >

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
                        value="{{ old(
                            'minimal_sks',
                            $periodeKrs->minimal_sks
                        ) }}"
                        min="0"
                        max="30"
                        required
                    >

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
                        value="{{ old(
                            'maksimal_sks',
                            $periodeKrs->maksimal_sks
                        ) }}"
                        min="1"
                        max="30"
                        required
                    >

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
                            {{ old(
                                'status',
                                $periodeKrs->status
                            ) === 'Ditutup'
                                ? 'selected'
                                : '' }}
                        >
                            🔒 Ditutup
                        </option>

                        <option
                            value="Dibuka"
                            {{ old(
                                'status',
                                $periodeKrs->status
                            ) === 'Dibuka'
                                ? 'selected'
                                : '' }}
                        >
                            🔓 Dibuka
                        </option>

                    </select>

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
                        placeholder="Keterangan periode KRS"
                    >{{ old(
                        'keterangan',
                        $periodeKrs->keterangan
                    ) }}</textarea>

                </div>


            </div>


            {{-- ===================== BUTTON ===================== --}}

            <div style="
                margin-top:25px;
            ">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    💾 Simpan Perubahan
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
