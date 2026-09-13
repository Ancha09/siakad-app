@extends('layouts.admin')

@section('title','Data Dosen')

@section('content')

@if(session('success'))
<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="toolbar">

    <form method="GET" action="{{ route('admin.dosen') }}" class="search-box" role="search">
        <span class="search-icon">🔍</span>

        <input
            name="q"
            type="text"
            value="{{ request('q') }}"
            aria-label="Cari data dosen"
            placeholder="Cari NIDN, nama, prodi, email, atau jabatan...">
        @if(request()->filled('q'))
            <a href="{{ route('admin.dosen') }}" class="search-clear" aria-label="Hapus pencarian">&times;</a>
        @endif
    </form>

    <a href="{{ route('admin.dosen.create') }}" class="btn-primary">
        ➕ Tambah Dosen
    </a>

</div>

<div class="page-card">

    <div class="page-card-head">
        <h2>👨‍🏫 Data Dosen</h2>
    </div>

    <div class="page-card-body">

        <div class="table-wrap">

            <table>

                <thead>

                <tr>
                    <th>No</th>
                    <th>NIDN</th>
                    <th>Nama</th>
                    <th>Program Studi</th>
                    <th>Jabatan</th>
                    <th>Status</th>
                    <th width="180">Aksi</th>
                </tr>

                </thead>

                <tbody>

                @forelse($dosens as $dosen)

                    <tr>

                        <td>{{ $dosens->firstItem() + $loop->index }}</td>

                        <td>{{ $dosen->nidn }}</td>

                        <td>{{ $dosen->nama }}</td>

                        <td>{{ $dosen->prodi->nama_prodi ?? '-' }}</td>

                        <td>{{ $dosen->jabatan ?? '-' }}</td>

                        <td>

                            @if(($dosen->status ?? 'Tetap') == 'Tetap')

                                <span class="badge badge-green">
                                    Tetap
                                </span>

                            @else

                                <span class="badge badge-gold">
                                    {{ $dosen->status }}
                                </span>

                            @endif

                        </td>

                        <td>

                            <div class="action-buttons">

                                <a href="{{ route('admin.dosen.edit',$dosen->id) }}"
                                   class="btn-edit">

                                    ✏ Edit

                                </a>

                                <form action="{{ route('admin.dosen.destroy',$dosen->id) }}"
                                      method="POST">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus data dosen ini?')">

                                        🗑 Hapus

                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7" style="text-align:center;padding:35px">

                            {{ request()->filled('q') ? 'Data dosen tidak ditemukan.' : 'Belum ada data dosen.' }}

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px">

            {{ $dosens->links() }}

        </div>

    </div>

</div>

@endsection
