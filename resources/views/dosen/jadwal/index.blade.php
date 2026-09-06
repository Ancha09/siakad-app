@extends('layouts.dosen')

@section('title','Jadwal Mengajar')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>📅 Jadwal Mengajar Dosen</h2>
</div>

<div class="page-card-body">

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode MK</th>
                    <th>Mata Kuliah</th>
                    <th>Kelas</th>
                    <th>Hari</th>
                    <th>Jam</th>
                    <th>Ruangan</th>
                    <th>Jumlah Mahasiswa</th>
                </tr>
            </thead>

            <tbody>

            @forelse($jadwals as $jadwal)

                @php
                    $jumlahMahasiswa = \App\Models\Krs::where('jadwal_id', $jadwal->id)->count();
                @endphp

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>{{ $jadwal->mataKuliah->kode_mk ?? '-' }}</td>

                    <td>{{ $jadwal->mataKuliah->nama_mk ?? '-' }}</td>

                    <td>{{ $jadwal->kelas }}</td>

                    <td>{{ $jadwal->hari }}</td>

                    <td>{{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</td>

                    <td>{{ $jadwal->ruangan->nama_ruangan ?? '-' }}</td>

                    <td>
                        <span class="badge-success">
                            {{ $jumlahMahasiswa }} Mahasiswa
                        </span>
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="8" style="text-align:center;padding:30px;">
                        Belum ada jadwal mengajar.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>
```

</div>

@endsection
