@extends('layouts.admin')

@section('title','Data Ruangan')

@section('content')

@if(session('success'))

<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="toolbar">

```
<div class="search-box">
    <span class="search-icon">🔍</span>
    <input type="text" placeholder="Cari kode atau nama ruangan...">
</div>

<a href="{{ route('admin.ruangan.create', ['return_url' => request()->fullUrl()]) }}" class="btn-primary">
    ➕ Tambah Ruangan
</a>
```

</div>

<div class="page-card">

```
<div class="page-card-head">
    <h2>🏫 Data Ruangan</h2>
</div>

<div class="page-card-body">

    <div class="table-wrap">

        <table>

            <thead>

            <tr>
                <th>No</th>
                <th>Kode Ruangan</th>
                <th>Nama Ruangan</th>
                <th>Gedung</th>
                <th>Kapasitas</th>
                <th width="180">Aksi</th>
            </tr>

            </thead>

            <tbody>

            @forelse($ruangans as $ruangan)

                <tr>

                    <td>{{ $ruangans->firstItem() + $loop->index }}</td>

                    <td>{{ $ruangan->kode_ruangan }}</td>

                    <td>{{ $ruangan->nama_ruangan }}</td>

                    <td>{{ $ruangan->gedung ?? '-' }}</td>

                    <td>{{ $ruangan->kapasitas ?? '-' }}</td>

                    <td>

                        <div class="action-buttons">

                            <a href="{{ route('admin.ruangan.edit', ['ruangan' => $ruangan->id, 'return_url' => request()->fullUrl()]) }}" class="btn-edit">
                                ✏ Edit
                            </a>

                            <form action="{{ route('admin.ruangan.destroy', $ruangan->id) }}" method="POST">

                                @csrf
                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                @method('DELETE')

                                <button type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus ruangan ini?')">
                                    🗑 Hapus
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" style="text-align:center;padding:35px">
                        Belum ada data ruangan.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div style="margin-top:20px">

        {{ $ruangans->appends(request()->query())->onEachSide(1)->links() }}

    </div>

</div>
```

</div>

@endsection
