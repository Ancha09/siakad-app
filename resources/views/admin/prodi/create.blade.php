@extends('layouts.admin')

@section('title','Tambah Program Studi')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>🎓 Tambah Program Studi</h2>
    </div>

    <div class="page-card-body">

        {{-- ERROR VALIDASI --}}
        @if ($errors->any())

            <div style="
                background:#fee2e2;
                color:#b91c1c;
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


        {{-- FORM --}}

        <form
            action="{{ route('admin.prodi.store') }}"
            method="POST"
        >

            @csrf


            <div class="krs-form-grid">


                {{-- KODE PRODI --}}

                <div class="form-group">

                    <label>Kode Prodi</label>

                    <input
                        type="text"
                        name="kode_prodi"
                        class="form-control"
                        value="{{ old('kode_prodi') }}"
                        required
                    >

                </div>


                {{-- NAMA PRODI --}}

                <div class="form-group">

                    <label>Nama Program Studi</label>

                    <input
                        type="text"
                        name="nama_prodi"
                        class="form-control"
                        value="{{ old('nama_prodi') }}"
                        required
                    >

                </div>


                {{-- JENJANG --}}

                <div class="form-group">

                    <label>Jenjang</label>

                    <select
                        name="jenjang"
                        class="form-control"
                        required
                    >

                        <option
                            value="S1"
                            {{ old('jenjang') == 'S1' ? 'selected' : '' }}
                        >
                            S1
                        </option>

                        <option
                            value="D3"
                            {{ old('jenjang') == 'D3' ? 'selected' : '' }}
                        >
                            D3
                        </option>

                        <option
                            value="S2"
                            {{ old('jenjang') == 'S2' ? 'selected' : '' }}
                        >
                            S2
                        </option>

                    </select>

                </div>


                {{-- ================= FAKULTAS ================= --}}

                <div class="form-group">

                    <label>Fakultas</label>

                    <select
                        name="fakultas_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Fakultas --
                        </option>


                        @foreach($fakultas as $item)

                            <option
                                value="{{ $item->id }}"
                                {{ old('fakultas_id') == $item->id ? 'selected' : '' }}
                            >

                                {{ $item->nama_fakultas }}

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">

                        Pilih fakultas tempat program studi ini berada.

                    </small>

                </div>


            </div>


            <br>


            {{-- SIMPAN --}}

            <button
                type="submit"
                class="btn-primary"
            >

                💾 Simpan

            </button>


            {{-- BATAL --}}

            <a
                href="{{ route('admin.prodi') }}"
                class="btn-outline"
            >

                Batal

            </a>


        </form>

    </div>

</div>

@endsection