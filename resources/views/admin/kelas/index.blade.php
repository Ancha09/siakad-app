@extends('layouts.admin')

@section('title', 'Data Kelas')

@section('content')

<div class="page-card">

    {{-- ================= HEADER ================= --}}

    <div class="page-card-head">

        <h2>🏫 Data Kelas</h2>

        <a
            href="{{ route('admin.kelas.create', ['return_url' => request()->fullUrl()]) }}"
            class="btn-primary"
        >
            + Tambah Kelas
        </a>

    </div>


    <div class="page-card-body">


        {{-- ================= SUCCESS ================= --}}

        @if(session('success'))

            <div
                class="alert-success"
                style="margin-bottom:20px;"
            >

                ✅ {{ session('success') }}

            </div>

        @endif


        {{-- ================= ERROR ================= --}}

        @if(session('error'))

            <div
                style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:14px 18px;
                    border-radius:10px;
                    margin-bottom:20px;
                "
            >

                ❌ {{ session('error') }}

            </div>

        @endif


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
                    margin-bottom:15px;
                    color:#1e293b;
                "
            >

                🔎 Filter Data Kelas

            </div>


            <form
                action="{{ route('admin.kelas') }}"
                method="GET"
            >

                {{-- BARIS 1 --}}

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            1.5fr
                            1fr
                            1fr;
                        gap:12px;
                    "
                >

                    {{-- SEARCH --}}

                    <div class="form-group">

                        <label>Cari Kelas</label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Nama kelas..."
                        >

                    </div>


                    {{-- FAKULTAS --}}

                    <div class="form-group">

                        <label>Fakultas</label>

                        <select
                            name="fakultas_id"
                            class="form-control"
                        >

                            <option value="">
                                Semua Fakultas
                            </option>

                            @foreach($fakultas as $item)

                                <option
                                    value="{{ $item->id }}"
                                    {{ request('fakultas_id') == $item->id ? 'selected' : '' }}
                                >

                                    {{ $item->nama_fakultas }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- PRODI --}}

                    <div class="form-group">

                        <label>Program Studi</label>

                        <select
                            name="prodi_id"
                            class="form-control"
                        >

                            <option value="">
                                Semua Prodi
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

                </div>


                {{-- BARIS 2 --}}

                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr;
                        gap:12px;
                        margin-top:12px;
                    "
                >

                    {{-- ANGKATAN --}}

                    <div class="form-group">

                        <label>Angkatan</label>

                        <select
                            name="angkatan"
                            class="form-control"
                        >

                            <option value="">
                                Semua Angkatan
                            </option>

                            @foreach($angkatans as $angkatan)

                                <option
                                    value="{{ $angkatan }}"
                                    {{ request('angkatan') == $angkatan ? 'selected' : '' }}
                                >

                                    {{ $angkatan }}

                                </option>

                            @endforeach

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
                        href="{{ route('admin.kelas') }}"
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
                margin-bottom:15px;
                color:#64748b;
                font-size:13px;
            "
        >

            Menampilkan

            <strong style="color:#1e293b;">
                {{ $kelases->total() }}
            </strong>

            kelas

            @if(
                request('search') ||
                request('fakultas_id') ||
                request('prodi_id') ||
                request('angkatan') ||
                request('semester')
            )

                berdasarkan filter yang dipilih.

            @endif

        </div>


        {{-- ================= TABLE ================= --}}

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Nama Kelas</th>

                        <th>Fakultas</th>

                        <th>Program Studi</th>

                        <th>Angkatan</th>

                        <th>Semester</th>

                        <th>Dosen Wali</th>

                        <th>Mahasiswa</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($kelases as $kelas)

                        <tr>


                            {{-- NO --}}

                            <td>

                                {{
                                    $loop->iteration +
                                    (
                                        ($kelases->currentPage() - 1)
                                        * $kelases->perPage()
                                    )
                                }}

                            </td>


                            {{-- NAMA KELAS --}}

                            <td>

                                <strong>

                                    {{ $kelas->nama_kelas }}

                                </strong>

                            </td>


                            {{-- FAKULTAS --}}

                            <td>

                                {{ $kelas->prodi->fakultas->nama_fakultas ?? '-' }}

                            </td>


                            {{-- PRODI --}}

                            <td>

                                {{ $kelas->prodi->nama_prodi ?? '-' }}

                            </td>


                            {{-- ANGKATAN --}}

                            <td>

                                {{ $kelas->angkatan }}

                            </td>


                            {{-- SEMESTER --}}

                            <td>

                                {{ $kelas->semester ?? '-' }}

                            </td>


                            {{-- DOSEN WALI --}}

                            <td>

                                @if($kelas->dosenWali)

                                    <span class="badge badge-green">

                                        {{ $kelas->dosenWali->nama }}

                                    </span>

                                @else

                                    <span class="badge badge-gray">

                                        Belum Ditentukan

                                    </span>

                                @endif

                            </td>


                            {{-- MAHASISWA --}}

                            <td>

                                <span class="badge badge-blue">

                                    {{ $kelas->mahasiswas_count }}

                                    Mahasiswa

                                </span>

                            </td>


                            {{-- AKSI --}}

                            <td>

                                <div
                                    style="
                                        display:flex;
                                        gap:6px;
                                    "
                                >

                                    <a
                                        href="{{ route('admin.kelas.edit', ['kelas' => $kelas->id, 'return_url' => request()->fullUrl()]) }}"
                                        class="btn-outline"
                                        style="
                                            padding:5px 10px;
                                            font-size:11px;
                                        "
                                    >

                                        ✏️ Edit

                                    </a>


                                    <form
                                        action="{{ route('admin.kelas.destroy', $kelas->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus kelas ini?')"
                                    >

                                        @csrf
                                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="btn-outline"
                                            style="
                                                padding:5px 10px;
                                                font-size:11px;
                                                color:#dc2626;
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
                                colspan="9"
                                style="
                                    text-align:center;
                                    padding:40px;
                                "
                            >

                                <div style="font-size:32px;">
                                    🏫
                                </div>

                                <strong>
                                    Belum ada data kelas
                                </strong>

                                <br>

                                <span style="color:#777;">
                                    Tidak ada kelas yang sesuai dengan filter.
                                </span>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ================= PAGINATION ================= --}}

        <div style="margin-top:20px;">

            {{ $kelases->appends(request()->query())->onEachSide(1)->links() }}

        </div>


    </div>

</div>

@endsection
