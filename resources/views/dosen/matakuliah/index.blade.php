@extends('layouts.dosen')

@section('title', 'Mata Kuliah Ampu')

@section('content')

<div class="inner-page">

    {{-- ================= INFORMASI ================= --}}
    <div class="info-alert">

        <span>ℹ️</span>

        <div>

            <strong>
                Mata Kuliah yang Anda Ampu
            </strong>

            <br>

            Silakan lihat daftar mata kuliah berdasarkan jadwal mengajar Anda.

        </div>

    </div>


    {{-- ================= DAFTAR MATA KULIAH ================= --}}
    <div class="page-card">

        <div class="page-card-head">

            <h2>📋 Daftar Mata Kuliah yang Diampu</h2>

            <span class="badge badge-green">
                Dosen Aktif
            </span>

        </div>


        <div class="page-card-body">

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>Kode MK</th>

                            <th>Nama Mata Kuliah</th>

                            <th>SKS</th>

                            <th>Kelas</th>

                            <th>Hari</th>

                            <th>Jam</th>

                            <th>Ruangan</th>

                            <th>Jml Mhs</th>

                            <th>Tahun Akademik</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    @forelse($jadwals as $jadwal)

                        @php

                            /*
                             * Menghitung jumlah mahasiswa
                             * yang mengambil jadwal ini.
                             *
                             * Relasi KRS diasumsikan menggunakan
                             * jadwal_id.
                             */

                            $jumlahMahasiswa = \App\Models\Krs::where(
                                'jadwal_id',
                                $jadwal->id
                            )->count();

                        @endphp


                        <tr>

                            {{-- NO --}}

                            <td>
                                {{ $loop->iteration }}
                            </td>


                            {{-- KODE MK --}}

                            <td>

                                <span class="badge badge-blue">

                                    {{ $jadwal->mataKuliah->kode_mk ?? '-' }}

                                </span>

                            </td>


                            {{-- NAMA MK --}}

                            <td>

                                <strong>

                                    {{ $jadwal->mataKuliah->nama_mk ?? '-' }}

                                </strong>

                            </td>


                            {{-- SKS --}}

                            <td>

                                {{ $jadwal->mataKuliah->sks ?? '-' }}

                            </td>


                            {{-- KELAS --}}

                            <td>

                                {{ $jadwal->kelas ?? '-' }}

                            </td>


                            {{-- HARI --}}

                            <td>

                                {{ $jadwal->hari ?? '-' }}

                            </td>


                            {{-- JAM --}}

                            <td>

                                {{ $jadwal->jam_mulai ?? '-' }}

                                -

                                {{ $jadwal->jam_selesai ?? '-' }}

                            </td>


                            {{-- RUANGAN --}}

                            <td>

                                {{ $jadwal->ruangan->nama_ruangan ?? '-' }}

                            </td>


                            {{-- JUMLAH MAHASISWA --}}

                            <td>

                                <span class="badge badge-green">

                                    {{ $jumlahMahasiswa }}

                                    Mahasiswa

                                </span>

                            </td>


                            {{-- TAHUN AKADEMIK --}}

                            <td>

                                {{ $jadwal->tahun_akademik ?? '-' }}

                            </td>


                            {{-- AKSI --}}

                            <td>

                                <a
                                    href="{{ route('dosen.jadwal') }}"
                                    class="btn-outline"
                                    style="padding:5px 12px;font-size:11px;"
                                >

                                    Detail

                                </a>

                            </td>

                        </tr>


                    @empty


                        <tr>

                            <td
                                colspan="11"
                                style="text-align:center;padding:40px;"
                            >

                                <div style="font-size:35px;margin-bottom:10px;">
                                    📚
                                </div>

                                <strong>
                                    Belum ada mata kuliah yang diampu
                                </strong>

                                <p style="margin-top:6px;color:#64748b;">
                                    Jadwal mengajar Anda belum tersedia.
                                </p>

                            </td>

                        </tr>


                    @endforelse

                    </tbody>

                </table>

            </div>


            {{-- ================= INFORMASI BAWAH ================= --}}

            @if($jadwals->count() > 0)

                <div
                    style="
                        margin-top:20px;
                        padding:14px 16px;
                        background:#f8fafc;
                        border-radius:10px;
                        color:#64748b;
                        font-size:13px;
                    "
                >

                    📌 Total terdapat

                    <strong style="color:#1e293b;">
                        {{ $jadwals->count() }}
                    </strong>

                    jadwal mata kuliah yang Anda ampu.

                </div>

            @endif


        </div>

    </div>

</div>

@endsection