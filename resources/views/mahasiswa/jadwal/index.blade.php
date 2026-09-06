@extends('layouts.mahasiswa')

@section('title', 'Jadwal Kuliah')

@section('content')

<div class="inner-page">

    <div class="info-alert">

        <span>📅</span>

        <div>
            <strong>Jadwal Kuliah</strong>
            <br>
            Jadwal perkuliahan berdasarkan KRS yang telah diambil.
        </div>

    </div>


    <div class="page-card">

        <div class="page-card-head">

            <h2>📚 Jadwal Kuliah Saya</h2>

            <span class="badge badge-green">
                Semester Aktif
            </span>

        </div>


        <div class="page-card-body">

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>Kode MK</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Kelas</th>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Ruangan</th>
                            <th>Dosen</th>
                        </tr>

                    </thead>


                    <tbody>

                        @forelse($jadwals as $jadwal)

                            <tr>

                                <td>
                                    {{ $loop->iteration }}
                                </td>

                                <td>

                                    <span class="badge badge-blue">
                                        {{ $jadwal->mataKuliah->kode ?? '-' }}
                                    </span>

                                </td>

                                <td>
                                    <strong>
                                        {{ $jadwal->mataKuliah->nama ?? '-' }}
                                    </strong>
                                </td>

                                <td>
                                    {{ $jadwal->mataKuliah->sks ?? '-' }}
                                </td>

                                <td>
                                    {{ $jadwal->kelas ?? '-' }}
                                </td>

                                <td>
                                    {{ $jadwal->hari ?? '-' }}
                                </td>

                                <td>
                                    {{ $jadwal->jam_mulai ?? '-' }}
                                    -
                                    {{ $jadwal->jam_selesai ?? '-' }}
                                </td>

                                <td>
                                    {{ $jadwal->ruangan->nama ?? '-' }}
                                </td>

                                <td>
                                    {{ $jadwal->dosen->nama ?? '-' }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    style="text-align:center;padding:40px;"
                                >

                                    <div style="font-size:36px;margin-bottom:10px;">
                                        📅
                                    </div>

                                    <strong>
                                        Belum ada jadwal kuliah
                                    </strong>

                                    <p
                                        style="
                                            margin-top:8px;
                                            color:#64748b;
                                        "
                                    >
                                        Belum terdapat KRS aktif yang memiliki
                                        jadwal kuliah.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection