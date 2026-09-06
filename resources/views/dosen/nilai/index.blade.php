@extends('layouts.dosen')

@section('title','Input Nilai')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>📝 Jadwal Mengajar - Input / Edit Nilai</h2>
</div>

{{-- Notifikasi sukses --}}
@if(session('success'))
    <div class="alert alert-success" style="margin:15px;">
        {{ session('success') }}
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
                    <th>Rata Nilai</th>
                    <th>Huruf</th>
                    <th>Mutu</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>

            @forelse($jadwals as $jadwal)

                @php
                    $khs = \App\Models\Khs::whereHas('krs', function ($q) use ($jadwal) {
                        $q->where('jadwal_id', $jadwal->id);
                    })->get();

                    $sudahDinilai = $khs->count() > 0;

                    $rataNilai = $sudahDinilai ? round($khs->avg('nilai_angka'), 2) : '-';
                    $rataMutu  = $sudahDinilai ? round($khs->avg('bobot'), 2) : '-';

                    $huruf = $sudahDinilai
                        ? $khs->groupBy('nilai_huruf')
                            ->sortByDesc(function ($group) {
                                return $group->count();
                            })
                            ->keys()
                            ->first()
                        : '-';
                @endphp

                <tr>

                    <td>{{ $loop->iteration }}</td>

                    <td>
                        {{ $jadwal->mataKuliah->kode_mk ?? '-' }} -
                        {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
                    </td>

                    <td>{{ $jadwal->hari }}</td>

                    <td>{{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</td>

                    <td>{{ $jadwal->ruangan->nama_ruangan ?? '-' }}</td>

                    <td>{{ $jadwal->kelas }}</td>

                    <td>{{ $rataNilai }}</td>

                    <td>{{ $huruf }}</td>

                    <td>{{ $rataMutu }}</td>

                    <td>
                        @if($sudahDinilai)
                            <span class="badge-success">Sudah Dinilai</span>
                        @else
                            <span class="badge-warning">Belum Dinilai</span>
                        @endif
                    </td>

                    <td>
                        <a href="{{ route('dosen.nilai.show', $jadwal->id) }}"
                           class="{{ $sudahDinilai ? 'btn-outline' : 'btn-primary' }}">

                            {{ $sudahDinilai ? 'Edit Nilai' : 'Input Nilai' }}

                        </a>
                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="11" style="text-align:center;padding:30px;">
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
