@extends('layouts.dosen')

@section('title', 'Jadwal Mengajar')

@section('content')

<div class="inner-page">

    <div class="page-card">

        <div class="page-card-head">
            <h2>📅 Jadwal Mengajar Semester Gasal 2024/2025</h2>
            <span style="font-size:12px;color:rgba(255,255,255,0.7)">
                T.A 2024/2025 Gasal
            </span>
        </div>

        <div class="page-card-body">

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Kode</th>
                            <th>Mata Kuliah</th>
                            <th>SKS</th>
                            <th>Kelas</th>
                            <th>Ruang</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td><strong>Senin</strong></td>
                            <td>07:30 – 09:00</td>
                            <td>TM601</td>
                            <td>Mekanika Fluida</td>
                            <td>3</td>
                            <td>TM-6A</td>
                            <td>A-201</td>
                            <td><span class="badge badge-green">Aktif</span></td>
                        </tr>

                        <tr>
                            <td></td>
                            <td>10:00 – 12:00</td>
                            <td>TM602</td>
                            <td>Mekanika Fluida</td>
                            <td>3</td>
                            <td>TM-6B</td>
                            <td>B-103</td>
                            <td><span class="badge badge-green">Aktif</span></td>
                        </tr>

                        <tr>
                            <td><strong>Rabu</strong></td>
                            <td>08:00 – 10:00</td>
                            <td>TM701</td>
                            <td>Mekanika Lanjut</td>
                            <td>3</td>
                            <td>TM-7A</td>
                            <td>C-201</td>
                            <td><span class="badge badge-green">Aktif</span></td>
                        </tr>

                        <tr>
                            <td></td>
                            <td>13:00 – 15:00</td>
                            <td>TM801</td>
                            <td>Seminar Tugas Akhir</td>
                            <td>2</td>
                            <td>TM-8</td>
                            <td>Ruang Seminar</td>
                            <td><span class="badge badge-green">Aktif</span></td>
                        </tr>

                        <tr>
                            <td><strong>Kamis</strong></td>
                            <td>09:00 – 11:00</td>
                            <td>TM603</td>
                            <td>Praktikum Mek. Fluida</td>
                            <td>2</td>
                            <td>TM-6A</td>
                            <td>Lab. Fluida</td>
                            <td><span class="badge badge-green">Aktif</span></td>
                        </tr>

                        <tr>
                            <td><strong>Jumat</strong></td>
                            <td>07:30 – 09:00</td>
                            <td>TM701</td>
                            <td>Mekanika Lanjut</td>
                            <td>3</td>
                            <td>TM-7B</td>
                            <td>A-104</td>
                            <td><span class="badge badge-gold">Pengganti</span></td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection