@extends('layouts.admin')

@section('title', 'Rekap Presensi')

@push('styles')
<style>
    .presensi-admin-page .page-card-head h2,
    .presensi-admin-page .page-card-head h3 {
        color: #ffffff !important;
    }
</style>
@endpush

@section('content')

<div class="page-card presensi-admin-page">

    <div class="page-card-head">

        <h2>📊 Rekap Presensi Mahasiswa</h2>

        <div style="display:flex;gap:10px;">

            <a
                href="{{ route('admin.presensi.excel', request()->query()) }}"
                class="btn-primary"
            >
                📊 Download Excel (.xlsx)
            </a>

            <a
                href="{{ route('admin.presensi.pdf', request()->query()) }}"
                class="btn-primary"
            >
                📄 Download PDF
            </a>

        </div>

    </div>


    <div class="page-card-body">


        {{-- =====================================================
             FILTER
        ====================================================== --}}

        <div
            style="
                background:#f8fafc;
                border:1px solid #e2e8f0;
                border-radius:12px;
                padding:18px;
                margin-bottom:25px;
            "
        >

            <div
                style="
                    font-weight:700;
                    margin-bottom:15px;
                    color:#1e293b;
                "
            >
                🔎 Filter Rekap Presensi
            </div>


            <form
                action="{{ route('admin.presensi') }}"
                method="GET"
            >

                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(3, 1fr);
                        gap:15px;
                    "
                >


                    {{-- PRODI --}}

                    <div class="form-group">

                        <label>Program Studi</label>

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

                            @foreach($kelas as $item)

                                <option
                                    value="{{ $item->id }}"
                                    {{ request('kelas_id') == $item->id ? 'selected' : '' }}
                                >
                                    {{ $item->nama_kelas }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- DOSEN --}}

                    <div class="form-group">

                        <label>Dosen</label>

                        <select
                            name="dosen_id"
                            class="form-control"
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

                    </div>


                    {{-- MATA KULIAH --}}

                    <div class="form-group">

                        <label>Mata Kuliah</label>

                        <select
                            name="mata_kuliah_id"
                            class="form-control"
                        >

                            <option value="">
                                Semua Mata Kuliah
                            </option>

                            @foreach($mataKuliahs as $mk)

                                <option
                                    value="{{ $mk->id }}"
                                    {{ request('mata_kuliah_id') == $mk->id ? 'selected' : '' }}
                                >
                                    {{ $mk->kode_mk }}
                                    -
                                    {{ $mk->nama_mk }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- PERTEMUAN --}}

                    <div class="form-group">

                        <label>Pertemuan</label>

                        <select
                            name="pertemuan"
                            class="form-control"
                        >

                            <option value="">
                                Semua Pertemuan
                            </option>

                            @for($i = 1; $i <= 16; $i++)

                                <option
                                    value="{{ $i }}"
                                    {{ request('pertemuan') == $i ? 'selected' : '' }}
                                >
                                    Pertemuan {{ $i }}
                                </option>

                            @endfor

                        </select>

                    </div>


                    {{-- TOMBOL --}}

                    <div
                        style="
                            display:flex;
                            align-items:end;
                            gap:10px;
                        "
                    >

                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            🔍 Tampilkan
                        </button>


                        <a
                            href="{{ route('admin.presensi') }}"
                            class="btn-outline"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>


        {{-- =====================================================
             STATISTIK
        ====================================================== --}}

        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(5, 1fr);
                gap:15px;
                margin-bottom:25px;
            "
        >

            <div class="page-card" style="margin:0;">

                <div class="page-card-body">

                    <small>Total Presensi</small>

                    <h2 style="margin:5px 0;">
                        {{ $totalPresensi }}
                    </h2>

                </div>

            </div>


            <div class="page-card" style="margin:0;">

                <div class="page-card-body">

                    <small>Hadir</small>

                    <h2
                        style="
                            margin:5px 0;
                            color:#16a34a;
                        "
                    >
                        {{ $totalHadir }}
                    </h2>

                </div>

            </div>


            <div class="page-card" style="margin:0;">

                <div class="page-card-body">

                    <small>Izin</small>

                    <h2
                        style="
                            margin:5px 0;
                            color:#ca8a04;
                        "
                    >
                        {{ $totalIzin }}
                    </h2>

                </div>

            </div>


            <div class="page-card" style="margin:0;">

                <div class="page-card-body">

                    <small>Sakit</small>

                    <h2
                        style="
                            margin:5px 0;
                            color:#2563eb;
                        "
                    >
                        {{ $totalSakit }}
                    </h2>

                </div>

            </div>


            <div class="page-card" style="margin:0;">

                <div class="page-card-body">

                    <small>Alpha</small>

                    <h2
                        style="
                            margin:5px 0;
                            color:#dc2626;
                        "
                    >
                        {{ $totalAlpha }}
                    </h2>

                </div>

            </div>

        </div>


        {{-- =====================================================
             REKAP MAHASISWA
        ====================================================== --}}

        <div class="page-card">

            <div class="page-card-head">

                <h3>
                    👨‍🎓 Rekap Kehadiran Mahasiswa
                </h3>

            </div>


            <div class="page-card-body">

                <div
                    class="table-wrap"
                    style="overflow-x:auto;"
                >

                    <table>

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>NIM</th>

                                <th>Nama Mahasiswa</th>

                                <th>Program Studi</th>

                                <th>Kelas</th>

                                <th>Mata Kuliah</th>

                                <th>Dosen</th>

                                <th>Hadir</th>

                                <th>Izin</th>

                                <th>Sakit</th>

                                <th>Alpha</th>

                                <th>Kehadiran</th>

                            </tr>

                        </thead>


                        <tbody>

                        @forelse($rekap as $item)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                <td>
                                    {{ $item->krs->mahasiswa->nim ?? '-' }}
                                </td>


                                <td>

                                    <strong>
                                        {{ $item->krs->mahasiswa->nama ?? '-' }}
                                    </strong>

                                </td>


                                <td>
                                    {{ $item->krs->mahasiswa->prodi->nama_prodi ?? '-' }}
                                </td>


                                <td>
                                    {{ $item->krs->mahasiswa->kelas->nama_kelas ?? '-' }}
                                </td>


                                <td>
                                    {{ $item->krs->jadwal->mataKuliah->nama_mk ?? '-' }}
                                </td>


                                <td>
                                    {{ $item->krs->jadwal->dosen->nama ?? '-' }}
                                </td>


                                <td>

                                    <span class="badge-success">
                                        {{ $item->hadir }}
                                    </span>

                                </td>


                                <td>
                                    {{ $item->izin }}
                                </td>


                                <td>
                                    {{ $item->sakit }}
                                </td>


                                <td>

                                    <span class="badge-warning">
                                        {{ $item->alpha }}
                                    </span>

                                </td>


                                <td>

                                    @if($item->persentase >= 75)

                                        <span class="badge-success">
                                            {{ $item->persentase }}%
                                        </span>

                                    @else

                                        <span class="badge-warning">
                                            {{ $item->persentase }}%
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="12"
                                    style="
                                        text-align:center;
                                        padding:35px;
                                    "
                                >

                                    📭 Belum ada data presensi.

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- =====================================================
             SESI PERTEMUAN
        ====================================================== --}}

        <div
            class="page-card"
            style="margin-top:25px;"
        >

            <div class="page-card-head">

                <h3>
                    📚 Sesi Pertemuan & Dokumentasi
                </h3>

            </div>


            <div class="page-card-body">

                <div
                    class="table-wrap"
                    style="overflow-x:auto;"
                >

                    <table>

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Pertemuan</th>

                                <th>Tanggal</th>

                                <th>Foto</th>

                                <th>Materi</th>

                            </tr>

                        </thead>


                        <tbody>

                        @forelse($pertemuans as $item)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                <td>

                                    <strong>
                                        Pertemuan
                                        {{ $item->pertemuan }}
                                    </strong>

                                </td>


                                <td>
                                    {{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}
                                </td>


                                <td>

                                    @if($item->foto)

                                        <a
                                            href="{{ asset('storage/' . $item->foto) }}"
                                            target="_blank"
                                            class="btn-outline"
                                        >
                                            📷 Lihat Foto
                                        </a>

                                    @else

                                        <span style="color:#64748b;">
                                            Tidak ada foto
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($item->materi)

                                        <a
                                            href="{{ asset('storage/' . $item->materi) }}"
                                            target="_blank"
                                            class="btn-outline"
                                        >
                                            📚 Lihat Materi
                                        </a>

                                    @else

                                        <span style="color:#64748b;">
                                            Tidak ada materi
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                    "
                                >

                                    Belum ada sesi pertemuan.

                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
