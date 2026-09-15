@extends('layouts.admin')

@section('title', 'Data Mahasiswa')

@section('content')

<div class="page-card">

    {{-- ================= HEADER ================= --}}
    <div class="page-card-head">

        <h2>🎓 Data Mahasiswa</h2>

        <a
            href="{{ route('admin.mahasiswa.create') }}"
            class="btn-primary"
        >
            + Tambah Mahasiswa
        </a>

    </div>


    <div class="page-card-body">

        {{-- ================= SUCCESS ================= --}}
        @if(session('success'))

            <div
                style="
                    background:#dcfce7;
                    color:#166534;
                    border:1px solid #86efac;
                    padding:14px 18px;
                    border-radius:10px;
                    margin-bottom:20px;
                "
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
                    border:1px solid #fecaca;
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
                    display:flex;
                    align-items:center;
                    gap:8px;
                    margin-bottom:15px;
                    font-weight:700;
                    font-size:15px;
                    color:#1e293b;
                "
            >
                🔎 Filter Data Mahasiswa
            </div>


            <form
                action="{{ route('admin.mahasiswa') }}"
                method="GET"
            >

                {{-- BARIS FILTER 1 --}}
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

                        <label>Cari Mahasiswa</label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="NIM atau nama mahasiswa..."
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


                {{-- BARIS FILTER 2 --}}
                <div
                    style="
                        display:grid;
                        grid-template-columns:1fr 1fr 1fr;
                        gap:12px;
                        margin-top:12px;
                    "
                >

                    {{-- KELAS --}}
                    <div class="form-group">

                        <label>Kelas</label>

                        <select
                            name="kelas_id"
                            class="form-control"
                        >

                            <option value="">
                                Semua Kelas
                            </option>

                            @foreach($kelases as $kelas)

                                <option
                                    value="{{ $kelas->id }}"
                                    {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}
                                >
                                    {{ $kelas->nama_kelas }}

                                    @if($kelas->prodi)
                                        - {{ $kelas->prodi->nama_prodi }}
                                    @endif

                                </option>

                            @endforeach

                        </select>

                    </div>


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
                        href="{{ route('admin.mahasiswa') }}"
                        class="btn-outline"
                    >
                        ↻ Reset
                    </a>

                </div>

            </form>

        </div>


        {{-- ================= INFO HASIL ================= --}}
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

                Menampilkan

                <strong style="color:#1e293b;">
                    {{ $mahasiswas->total() }}
                </strong>

                mahasiswa

                @if(
                    request('search') ||
                    request('fakultas_id') ||
                    request('prodi_id') ||
                    request('kelas_id') ||
                    request('angkatan') ||
                    request('semester')
                )

                    <span>
                        berdasarkan filter yang dipilih.
                    </span>

                @endif

            </div>

        </div>


        {{-- ================= TABLE ================= --}}
        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>NO</th>
                        <th>STATUS</th>
                        <th>NIM</th>
                        <th>NAMA MAHASISWA</th>

                        {{-- TAMBAHAN FAKULTAS --}}
                        <th>FAKULTAS</th>

                        <th>PROGRAM STUDI</th>
                        <th>KELAS</th>
                        <th>DOSEN WALI</th>
                        <th>ANGKATAN</th>
                        <th>SEMESTER</th>
                        <th>AKSI</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($mahasiswas as $mahasiswa)

                        <tr>

                            {{-- NO --}}
                            <td>

                                {{
                                    $loop->iteration +
                                    (
                                        ($mahasiswas->currentPage() - 1)
                                        * $mahasiswas->perPage()
                                    )
                                }}

                            </td>

                            <td>
                                @if($mahasiswa->is_active)
                                    <span class="badge badge-green">Aktif</span>
                                @else
                                    <span class="badge badge-gold">Nonaktif</span>
                                @endif
                            </td>


                            {{-- NIM --}}
                            <td>

                                <strong>
                                    {{ $mahasiswa->nim }}
                                </strong>

                            </td>


                            {{-- NAMA --}}
                            <td>

                                <div>

                                    <strong>
                                        {{ $mahasiswa->nama }}
                                    </strong>

                                    @if($mahasiswa->email)

                                        <div
                                            style="
                                                font-size:11px;
                                                color:#64748b;
                                                margin-top:3px;
                                            "
                                        >
                                            {{ $mahasiswa->email }}
                                        </div>

                                    @endif

                                </div>

                            </td>


                            {{-- ================= FAKULTAS ================= --}}
                            <td>

                                @if(
                                    $mahasiswa->prodi &&
                                    $mahasiswa->prodi->fakultas
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
                                        {{ $mahasiswa->prodi->fakultas->nama_fakultas }}
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


                            {{-- ================= PRODI ================= --}}
                            <td>

                                {{ $mahasiswa->prodi->nama_prodi ?? '-' }}

                            </td>


                            {{-- ================= KELAS ================= --}}
                            <td>

                                @if($mahasiswa->kelas)

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
                                        {{ $mahasiswa->kelas->nama_kelas }}
                                    </span>

                                @else

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            background:#f1f5f9;
                                            color:#64748b;
                                            font-size:12px;
                                        "
                                    >
                                        Belum Ditentukan
                                    </span>

                                @endif

                            </td>


                            {{-- ================= DOSEN WALI ================= --}}
                            <td>

                                @if(
                                    $mahasiswa->kelas &&
                                    $mahasiswa->kelas->dosenWali
                                )

                                    <span
                                        style="
                                            display:inline-block;
                                            padding:5px 10px;
                                            border-radius:20px;
                                            background:#dcfce7;
                                            color:#166534;
                                            font-weight:600;
                                            font-size:12px;
                                        "
                                    >
                                        {{ $mahasiswa->kelas->dosenWali->nama }}
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


                            {{-- ================= ANGKATAN ================= --}}
                            <td>

                                {{ $mahasiswa->angkatan ?? '-' }}

                            </td>


                            {{-- ================= SEMESTER ================= --}}
                            <td>

                                {{
                                    $mahasiswa->semester
                                    ? 'Semester '.$mahasiswa->semester
                                    : '-'
                                }}

                            </td>


                            {{-- ================= AKSI ================= --}}
                            <td>

                                <div
                                    style="
                                        display:flex;
                                        gap:6px;
                                        align-items:center;
                                    "
                                >

                                    <a
                                        href="{{ route('admin.mahasiswa.edit', $mahasiswa->id) }}"
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
                                        action="{{ route('admin.mahasiswa.destroy', $mahasiswa->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Nonaktifkan mahasiswa ini? Akun tidak dapat login, tetapi riwayat akademik tetap tersimpan.')"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            @disabled(! $mahasiswa->is_active)
                                            class="btn-outline"
                                            style="
                                                padding:6px 10px;
                                                font-size:12px;
                                                color:#dc2626;
                                                cursor:pointer;
                                            "
                                        >
                                            Nonaktifkan
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="11"
                                style="
                                    text-align:center;
                                    padding:50px 20px;
                                "
                            >

                                <div
                                    style="
                                        font-size:42px;
                                        margin-bottom:10px;
                                    "
                                >
                                    🎓
                                </div>

                                <strong>
                                    Tidak ada data mahasiswa
                                </strong>

                                <div
                                    style="
                                        color:#64748b;
                                        margin-top:5px;
                                    "
                                >
                                    Tidak ditemukan mahasiswa
                                    sesuai filter yang dipilih.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ================= PAGINATION ================= --}}
        @if($mahasiswas->hasPages())

            <div style="margin-top:20px;">

                {{ $mahasiswas->links() }}

            </div>

        @endif


    </div>

</div>

@endsection
