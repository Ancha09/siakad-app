@extends('layouts.admin')

@section('title','Data Mata Kuliah')

@section('content')

{{-- ================= SUCCESS ================= --}}
@if(session('success'))

    <div
        class="alert-success"
        style="margin-bottom:20px;"
    >
        ✅ {{ session('success') }}
    </div>

@endif


{{-- ================= HEADER ================= --}}
<div class="page-card">

    <div class="page-card-head">

        <h2>📚 Data Mata Kuliah</h2>

        <a
            href="{{ route('admin.matakuliah.create') }}"
            class="btn-primary"
        >
            ➕ Tambah Mata Kuliah
        </a>

    </div>


    <div class="page-card-body">


        {{-- ================= FILTER ================= --}}
        <div
            style="
                background:#f8fafc;
                border:1px solid #e2e8f0;
                border-radius:12px;
                padding:18px;
                margin-bottom:20px;
            "
        >

            <div
                style="
                    font-weight:700;
                    font-size:15px;
                    color:#1e293b;
                    margin-bottom:15px;
                "
            >
                🔎 Filter Mata Kuliah
            </div>


            <form
                action="{{ route('admin.matakuliah') }}"
                method="GET"
            >

                <div
                    style="
                        display:grid;
                        grid-template-columns:1.5fr 1fr 1fr;
                        gap:12px;
                        align-items:end;
                    "
                >

                    {{-- SEARCH --}}
                    <div class="form-group">

                        <label>
                            Cari Mata Kuliah
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Kode atau nama mata kuliah..."
                        >

                    </div>


                    {{-- PRODI --}}
                    <div class="form-group">

                        <label>
                            Program Studi
                        </label>

                        <select
                            name="prodi_id"
                            class="form-control"
                        >

                            <option value="">
                                Semua Program Studi
                            </option>

                            @foreach($prodis as $prodi)

                                <option
                                    value="{{ $prodi->id }}"
                                    {{ request('prodi_id') == $prodi->id ? 'selected' : '' }}
                                >
                                    {{ $prodi->nama_prodi }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- SEMESTER --}}
                    <div class="form-group">

                        <label>
                            Semester
                        </label>

                        <select
                            name="semester"
                            class="form-control"
                        >

                            <option value="">
                                Semua Semester
                            </option>

                            @for($i = 1; $i <= 14; $i++)

                                <option
                                    value="{{ $i }}"
                                    {{ request('semester') == $i ? 'selected' : '' }}
                                >
                                    Semester {{ $i }}
                                </option>

                            @endfor

                        </select>

                    </div>

                </div>


                {{-- BUTTON --}}
                <div
                    style="
                        display:flex;
                        gap:10px;
                        margin-top:15px;
                    "
                >

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        🔍 Terapkan Filter
                    </button>


                    <a
                        href="{{ route('admin.matakuliah') }}"
                        class="btn-outline"
                    >
                        ↻ Reset
                    </a>

                </div>

            </form>

        </div>


        {{-- ================= INFO ================= --}}
        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:15px;
                color:#64748b;
                font-size:13px;
            "
        >

            <div>

                Total Mata Kuliah:

                <strong style="color:#1e293b;">
                    {{ $matakuliahs->total() }}
                </strong>

                @if(
                    request('search') ||
                    request('prodi_id') ||
                    request('semester')
                )

                    <span>
                        — berdasarkan filter
                    </span>

                @endif

            </div>

        </div>


        {{-- ================= TABLE ================= --}}
        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Kode MK</th>

                        <th>Nama Mata Kuliah</th>

                        <th>Fakultas</th>

                        <th>Program Studi</th>

                        <th>SKS</th>

                        <th>Semester</th>

                        <th width="180">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>

                @forelse($matakuliahs as $mk)

                    <tr>

                        {{-- NO --}}
                        <td>
                            {{
                                $matakuliahs->firstItem()
                                + $loop->index
                            }}
                        </td>


                        {{-- KODE --}}
                        <td>

                            <strong>
                                {{ $mk->kode_mk }}
                            </strong>

                        </td>


                        {{-- NAMA --}}
                        <td>

                            {{ $mk->nama_mk }}

                        </td>


                        {{-- FAKULTAS --}}
                        <td>

                            @if(
                                $mk->prodi &&
                                $mk->prodi->fakultas
                            )

                                <span
                                    style="
                                        display:inline-block;
                                        padding:5px 10px;
                                        border-radius:20px;
                                        background:#f3e8ff;
                                        color:#7e22ce;
                                        font-weight:600;
                                        font-size:12px;
                                    "
                                >
                                    {{ $mk->prodi->fakultas->nama_fakultas }}
                                </span>

                            @else

                                <span
                                    style="
                                        color:#94a3b8;
                                        font-size:12px;
                                    "
                                >
                                    Belum Ditentukan
                                </span>

                            @endif

                        </td>


                        {{-- PRODI --}}
                        <td>

                            {{ $mk->prodi->nama_prodi ?? '-' }}

                        </td>


                        {{-- SKS --}}
                        <td>

                            <span
                                style="
                                    display:inline-block;
                                    padding:5px 10px;
                                    border-radius:20px;
                                    background:#dbeafe;
                                    color:#1d4ed8;
                                    font-weight:600;
                                    font-size:12px;
                                "
                            >
                                {{ $mk->sks }} SKS
                            </span>

                        </td>


                        {{-- SEMESTER --}}
                        <td>

                            @if($mk->semester)

                                Semester {{ $mk->semester }}

                            @else

                                <span style="color:#94a3b8;">
                                    -

                                </span>

                            @endif

                        </td>


                        {{-- AKSI --}}
                        <td>

                            <div
                                style="
                                    display:flex;
                                    gap:6px;
                                    align-items:center;
                                "
                            >

                                <a
                                    href="{{ route('admin.matakuliah.edit',$mk->id) }}"
                                    class="btn-outline"
                                    style="
                                        padding:6px 10px;
                                        font-size:12px;
                                        text-decoration:none;
                                    "
                                >
                                    ✏️ Edit
                                </a>


                                <form
                                    action="{{ route('admin.matakuliah.destroy',$mk->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?')"
                                >

                                    @csrf

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-outline"
                                        style="
                                            padding:6px 10px;
                                            font-size:12px;
                                            color:#dc2626;
                                            cursor:pointer;
                                        "
                                    >
                                        🗑 Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="8"
                            style="
                                text-align:center;
                                padding:45px 20px;
                            "
                        >

                            <div
                                style="
                                    font-size:40px;
                                    margin-bottom:10px;
                                "
                            >
                                📚
                            </div>

                            <strong>
                                Tidak ada data mata kuliah
                            </strong>

                            <div
                                style="
                                    color:#64748b;
                                    margin-top:5px;
                                "
                            >
                                Belum ada mata kuliah yang
                                sesuai dengan filter.
                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- ================= PAGINATION ================= --}}
        @if($matakuliahs->hasPages())

            <div style="margin-top:20px;">

                {{ $matakuliahs->links() }}

            </div>

        @endif


    </div>

</div>

@endsection