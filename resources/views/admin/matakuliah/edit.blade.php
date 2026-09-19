@extends('layouts.admin')

@section('title', 'Edit Mata Kuliah')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Mata Kuliah</h2>
    </div>

    <div class="page-card-body">

        @if ($errors->any())

            <div
                style="
                    background:#fee2e2;
                    color:#b91c1c;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <strong>Terjadi kesalahan:</strong>

                <ul style="margin-top:10px;">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        <form
            action="{{ route('admin.matakuliah.update', $matakuliah->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.matakuliah" />
            @method('PUT')

            <div class="krs-form-grid">

                {{-- KODE --}}
                <div class="form-group">

                    <label>Kode Mata Kuliah</label>

                    <input
                        type="text"
                        name="kode_mk"
                        class="form-control"
                        value="{{ old('kode_mk', $matakuliah->kode_mk) }}"
                        required
                    >

                </div>


                {{-- NAMA --}}
                <div class="form-group">

                    <label>Nama Mata Kuliah</label>

                    <input
                        type="text"
                        name="nama_mk"
                        class="form-control"
                        value="{{ old('nama_mk', $matakuliah->nama_mk) }}"
                        required
                    >

                </div>


                {{-- SKS --}}
                <div class="form-group">

                    <label>SKS</label>

                    <select
                        name="sks"
                        class="form-control"
                        required
                    >

                        @for($i = 1; $i <= 6; $i++)

                            <option
                                value="{{ $i }}"
                                {{ old('sks', $matakuliah->sks) == $i ? 'selected' : '' }}
                            >
                                {{ $i }} SKS
                            </option>

                        @endfor

                    </select>

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
                                {{ old('semester', $matakuliah->semester) == $i ? 'selected' : '' }}
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
                    >

                        <option value="" {{ old('prodi_id', $matakuliah->prodi_id) === null ? 'selected' : '' }}>
                            Umum / MKU - Semua Program Studi
                        </option>

                        @foreach($prodis as $prodi)

                            <option
                                value="{{ $prodi->id }}"
                                {{ old('prodi_id', $matakuliah->prodi_id) == $prodi->id ? 'selected' : '' }}
                            >

                                {{ $prodi->nama_prodi }}

                                @if($prodi->fakultas)
                                    - {{ $prodi->fakultas->nama_fakultas }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>


            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    💾 Update
                </button>

                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.matakuliah') }}"
                    class="btn-outline"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</div>

@endsection
