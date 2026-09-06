@extends('layouts.dosen')

@section('title','Rekap Nilai')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>📊 Rekap Nilai Per Kelas</h2>
</div>

<div class="page-card-body">

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>No</th>
                    <th>Mata Kuliah</th>
                    <th>Kelas</th>
                    <th>Jumlah Mahasiswa</th>
                    <th>Rata-rata</th>
                    <th>Tertinggi</th>
                    <th>Terendah</th>
                    <th>Lulus</th>
                    <th>Tidak Lulus</th>
                </tr>
            </thead>

            <tbody>

            @forelse($jadwals as $jadwal)

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>
                        {{ $jadwal->mataKuliah->kode_mk ?? '-' }} -
                        {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
                    </td>

                    <td>{{ $jadwal->kelas }}</td>

                    <td>{{ $jadwal->jumlah }}</td>

                    <td>{{ $jadwal->rata }}</td>

                    <td>{{ $jadwal->tertinggi }}</td>

                    <td>{{ $jadwal->terendah }}</td>

                    <td>
                        <span class="badge-success">
                            {{ $jadwal->lulus }}
                        </span>
                    </td>

                    <td>
                        <span class="badge-warning">
                            {{ $jadwal->tidak_lulus }}
                        </span>
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="9" style="text-align:center;padding:30px;">
                        Belum ada data nilai.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <br>

    <a href="{{ route('dosen.nilai') }}" class="btn-outline">
        Kembali ke Input Nilai
    </a>

</div>
```

</div>

@endsection
