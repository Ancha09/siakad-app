@extends('layouts.dosen')

@section('title','Presensi Mahasiswa')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>📋 Presensi Mahasiswa</h2>
    </div>


    @if(session('success'))

        <div
            class="alert alert-success"
            style="margin:15px;"
        >
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div
            class="alert"
            style="
                margin:15px;
                padding:12px 15px;
                border-radius:8px;
                background:#fee2e2;
                color:#991b1b;
            "
        >
            {{ session('error') }}
        </div>

    @endif


    <div class="page-card-body">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Mata Kuliah</th>

                        <th>Hari</th>

                        <th>Jam</th>

                        <th>Ruangan</th>

                        <th>Kelas</th>

                        <th>Status</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>

                @forelse($jadwals as $jadwal)

                    @php

                        /*
                        |--------------------------------------------------------------------------
                        | Cek apakah sudah ada sesi presensi
                        |--------------------------------------------------------------------------
                        |
                        | Sekarang pengecekan dilakukan melalui
                        | tabel presensi_pertemuans.
                        |
                        */

                        $sesiPresensi = $jadwal->presensiPertemuans;


                        $jumlahSesi =
                            $sesiPresensi->count();


                        $sudahPresensi =
                            $jumlahSesi > 0;


                        $pertemuanTerakhir =
                            $sesiPresensi->first();

                    @endphp


                    <tr>

                        {{-- NO --}}

                        <td>
                            {{ $loop->iteration }}
                        </td>


                        {{-- MATA KULIAH --}}

                        <td>

                            <strong>
                                {{ $jadwal->mataKuliah->kode_mk ?? '-' }}
                            </strong>

                            <br>

                            <small
                                style="color:#64748b;"
                            >
                                {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
                            </small>

                        </td>


                        {{-- HARI --}}

                        <td>
                            {{ $jadwal->hari }}
                        </td>


                        {{-- JAM --}}

                        <td>

                            {{ $jadwal->jam_mulai }}

                            -

                            {{ $jadwal->jam_selesai }}

                        </td>


                        {{-- RUANGAN --}}

                        <td>

                            {{ $jadwal->ruangan->nama_ruangan ?? '-' }}

                        </td>


                        {{-- KELAS --}}

                        <td>

                            {{ $jadwal->kelas->nama_kelas ?? $jadwal->kelas ?? '-' }}

                        </td>


                        {{-- STATUS --}}

                        <td>

                            @if($sudahPresensi)

                                <span
                                    class="badge-success"
                                    style="
                                        display:inline-block;
                                        padding:6px 10px;
                                        border-radius:20px;
                                        white-space:nowrap;
                                    "
                                >
                                    🔒 {{ $jumlahSesi }} Pertemuan
                                </span>

                                @if($pertemuanTerakhir)

                                    <div
                                        style="
                                            margin-top:5px;
                                            font-size:11px;
                                            color:#64748b;
                                        "
                                    >
                                        Terakhir:
                                        Pertemuan
                                        {{ $pertemuanTerakhir->pertemuan }}
                                    </div>

                                @endif

                            @else

                                <span
                                    class="badge-warning"
                                    style="
                                        display:inline-block;
                                        padding:6px 10px;
                                        border-radius:20px;
                                        white-space:nowrap;
                                    "
                                >
                                    ⏳ Belum Presensi
                                </span>

                            @endif

                        </td>


                        {{-- AKSI --}}

                        <td>

                            <a
                                href="{{ route('dosen.presensi.show', $jadwal->id) }}"
                                class="{{ $sudahPresensi ? 'btn-outline' : 'btn-primary' }}"
                            >

                                @if($sudahPresensi)

                                    📋 Kelola Presensi

                                @else

                                    📝 Input Presensi

                                @endif

                            </a>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="8"
                            style="
                                text-align:center;
                                padding:30px;
                            "
                        >

                            Belum ada jadwal mengajar.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
