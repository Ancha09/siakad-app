@extends('layouts.admin')

@section('title','Data KRS')

@section('content')

@if(session('success'))

    <div class="alert-success" style="margin-bottom:20px;">
        ✅ {{ session('success') }}
    </div>

@endif


@if(session('error'))

    <div style="
        background:#fee2e2;
        color:#991b1b;
        padding:15px;
        border-radius:8px;
        margin-bottom:20px;
    ">
        ❌ {{ session('error') }}
    </div>

@endif


{{-- =========================================================
     TOOLBAR / FILTER
========================================================= --}}

<div class="toolbar">

    <form
        action="{{ route('admin.krs') }}"
        method="GET"
        style="
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            width:100%;
            align-items:center;
        "
    >

        {{-- ================= SEARCH ================= --}}

        <div class="search-box">

            <span class="search-icon">🔍</span>

            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari NIM, mahasiswa, mata kuliah..."
            >

        </div>


        {{-- ================= FAKULTAS ================= --}}

        <select
            name="fakultas_id"
            class="form-control"
            style="width:180px;"
        >

            <option value="">
                Semua Fakultas
            </option>

            @foreach($fakultas as $f)

                <option
                    value="{{ $f->id }}"
                    {{ request('fakultas_id') == $f->id ? 'selected' : '' }}
                >
                    {{ $f->nama_fakultas }}
                </option>

            @endforeach

        </select>


        {{-- ================= PRODI ================= --}}

        <select
            name="prodi_id"
            class="form-control"
            style="width:190px;"
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


        {{-- ================= KELAS ================= --}}

        <select
            name="kelas_id"
            class="form-control"
            style="width:150px;"
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
                </option>

            @endforeach

        </select>


        {{-- ================= ANGKATAN ================= --}}

        <select
            name="angkatan"
            class="form-control"
            style="width:150px;"
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


        {{-- ================= DOSEN ================= --}}

        <select
            name="dosen_id"
            class="form-control"
            style="width:180px;"
        >

            <option value="">
                Semua Dosen
            </option>

            @foreach($dosens as $dosen)

                <option
                    value="{{ $dosen->id }}"
                    {{ request('dosen_id') == $dosen->id ? 'selected' : '' }}
                >
                    {{ $dosen->nama }}
                </option>

            @endforeach

        </select>


        {{-- ================= TAHUN AKADEMIK ================= --}}

        <select
            name="tahun_akademik"
            class="form-control"
            style="width:150px;"
        >

            <option value="">
                Semua Tahun
            </option>

            @foreach($tahunAkademiks as $tahun)

                <option
                    value="{{ $tahun }}"
                    {{ request('tahun_akademik') == $tahun ? 'selected' : '' }}
                >
                    {{ $tahun }}
                </option>

            @endforeach

        </select>


        {{-- ================= SEMESTER ================= --}}

        <select
            name="semester_akademik"
            class="form-control"
            style="width:140px;"
        >

            <option value="">
                Semua Semester
            </option>

            <option
                value="Ganjil"
                {{ request('semester_akademik') == 'Ganjil' ? 'selected' : '' }}
            >
                Ganjil
            </option>

            <option
                value="Genap"
                {{ request('semester_akademik') == 'Genap' ? 'selected' : '' }}
            >
                Genap
            </option>

        </select>


        {{-- ================= STATUS ================= --}}

        <select
            name="status"
            class="form-control"
            style="width:140px;"
        >

            <option value="">
                Semua Status
            </option>

            <option
                value="Diambil"
                {{ request('status') == 'Diambil' ? 'selected' : '' }}
            >
                Diambil
            </option>

            <option
                value="Disetujui"
                {{ request('status') == 'Disetujui' ? 'selected' : '' }}
            >
                Disetujui
            </option>

            <option
                value="Ditolak"
                {{ request('status') == 'Ditolak' ? 'selected' : '' }}
            >
                Ditolak
            </option>

        </select>


        {{-- ================= BUTTON FILTER ================= --}}

        <button
            type="submit"
            class="btn-primary"
        >
            🔎 Filter
        </button>


        {{-- ================= RESET ================= --}}

        <a
            href="{{ route('admin.krs') }}"
            class="btn-outline"
        >
            ↻ Reset
        </a>

    </form>

</div>



{{-- =========================================================
     DATA KRS
========================================================= --}}

<div class="page-card">

    <div class="page-card-head">

        <h2>
            📝 Data KRS Mahasiswa
        </h2>

        <a href="{{ route('admin.krs-mahasiswa.index') }}" class="btn-primary" style="display:inline-block;">
            KRS per Mahasiswa
        </a>

    </div>


    <div class="page-card-body">

        <div class="table-wrap">

            <table>

                <thead>

                <tr>

                    <th>No</th>

                    <th>Mahasiswa</th>

                    <th>Fakultas</th>

                    <th>Program Studi</th>

                    <th>Kelas</th>

                    <th>Angkatan</th>

                    <th>Mata Kuliah</th>

                    <th>Dosen</th>

                    <th>Jadwal</th>

                    <th>SKS</th>

                    <th>Status</th>

                    <th width="120">
                        Aksi
                    </th>

                </tr>

                </thead>


                <tbody>

                @forelse($krs as $item)

                    <tr>

                        {{-- ================= NO ================= --}}

                        <td>
                            {{ $krs->firstItem() + $loop->index }}
                        </td>


                        {{-- ================= MAHASISWA ================= --}}

                        <td>

                            <strong>
                                {{ $item->mahasiswa->nama ?? '-' }}
                            </strong>

                            <br>

                            <small style="color:#64748b;">
                                NIM:
                                {{ $item->mahasiswa->nim ?? '-' }}
                            </small>

                        </td>


                        {{-- ================= FAKULTAS ================= --}}

                        <td>

                            {{ $item->mahasiswa->prodi->fakultas->nama_fakultas ?? '-' }}

                        </td>


                        {{-- ================= PRODI ================= --}}

                        <td>

                            {{ $item->mahasiswa->prodi->nama_prodi ?? '-' }}

                        </td>


                        {{-- ================= KELAS ================= --}}

                        <td>

                            @if(
                                $item->mahasiswa &&
                                $item->mahasiswa->kelas
                            )

                                <span class="badge badge-blue">
                                    {{ $item->mahasiswa->kelas->nama_kelas }}
                                </span>

                            @else

                                <span class="badge badge-gray">
                                    Belum Ada Kelas
                                </span>

                            @endif

                        </td>


                        {{-- ================= ANGKATAN ================= --}}

                        <td>

                            {{ $item->mahasiswa->kelas->angkatan ?? '-' }}

                        </td>


                        {{-- ================= MATA KULIAH ================= --}}

                        <td>

                            <strong>
                                {{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}
                            </strong>

                            <br>

                            <small style="color:#64748b;">

                                {{ $item->jadwal->mataKuliah->kode_mk ?? '-' }}

                            </small>

                        </td>


                        {{-- ================= DOSEN ================= --}}

                        <td>

                            {{ $item->jadwal->dosen->nama ?? '-' }}

                        </td>


                        {{-- ================= JADWAL ================= --}}

                        <td>

                            @if($item->jadwal)

                                <strong>
                                    {{ $item->jadwal->hari }}
                                </strong>

                                <br>

                                <small>

                                    {{ $item->jadwal->jam_mulai }}
                                    -
                                    {{ $item->jadwal->jam_selesai }}

                                </small>

                                <br>

                                <small style="color:#64748b;">

                                    {{ $item->jadwal->ruangan->nama_ruangan ?? '-' }}

                                </small>

                            @else

                                -

                            @endif

                        </td>


                        {{-- ================= SKS ================= --}}

                        <td>

                            <span class="badge badge-blue">

                                {{ $item->jadwal->mataKuliah->sks ?? '-' }}
                                SKS

                            </span>

                        </td>


                        {{-- ================= STATUS ================= --}}

                        <td>

                            @if($item->status == 'Disetujui')

                                <span class="badge badge-green">
                                    Disetujui
                                </span>

                            @elseif($item->status == 'Ditolak')

                                <span
                                    class="badge"
                                    style="
                                        background:#fee2e2;
                                        color:#b91c1c;
                                    "
                                >
                                    Ditolak
                                </span>

                            @else

                                <span
                                    class="badge"
                                    style="
                                        background:#fef3c7;
                                        color:#92400e;
                                    "
                                >
                                    Diambil
                                </span>

                            @endif

                        </td>


                        {{-- ================= AKSI ================= --}}

                        <td>

                            <div class="action-buttons">

                                {{-- ADMIN HANYA BOLEH HAPUS --}}

                                <form
                                    action="{{ route(
                                        'admin.krs.destroy',
                                        $item->id
                                    ) }}"
                                    method="POST"
                                >

                                    @csrf
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus KRS ini?'
                                        )"
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
                            colspan="12"
                            style="
                                text-align:center;
                                padding:40px;
                            "
                        >

                            <div
                                style="
                                    font-size:35px;
                                    margin-bottom:10px;
                                "
                            >
                                📝
                            </div>

                            <strong>
                                Belum ada data KRS
                            </strong>

                            <br>

                            <span style="color:#64748b;">

                                @if(
                                    request()->hasAny([
                                        'search',
                                        'fakultas_id',
                                        'prodi_id',
                                        'kelas_id',
                                        'angkatan',
                                        'dosen_id',
                                        'tahun_akademik',
                                        'semester_akademik',
                                        'status'
                                    ])
                                )

                                    Tidak ada data KRS yang sesuai
                                    dengan filter yang dipilih.

                                @else

                                    Belum ada data KRS.

                                @endif

                            </span>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================== PAGINATION ===================== --}}

        <div style="margin-top:20px;">

            {{ $krs->withQueryString()->appends(request()->query())->onEachSide(1)->links() }}

        </div>

    </div>

</div>

@endsection
