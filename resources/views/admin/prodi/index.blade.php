@extends('layouts.admin')

@section('title','Data Program Studi')

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
    <input type="text" placeholder="Cari kode atau nama program studi...">
</div>

<a href="{{ route('admin.prodi.create') }}" class="btn-primary">
    ➕ Tambah Program Studi
</a>
```

</div>

<div class="page-card">

```
<div class="page-card-head">
    <h2>🎓 Data Program Studi</h2>
</div>

<div class="page-card-body">

    <div class="table-wrap">

        <table>

            <thead>

            <tr>
                <th>No</th>
                <th>Kode Prodi</th>
                <th>Nama Program Studi</th>
                <th>Jenjang</th>
                <th width="180">Aksi</th>
            </tr>

            </thead>

            <tbody>

            @forelse($prodis as $prodi)

                <tr>

                    <td>{{ $prodis->firstItem() + $loop->index }}</td>

                    <td>{{ $prodi->kode_prodi }}</td>

                    <td>{{ $prodi->nama_prodi }}</td>

                    <td>{{ $prodi->jenjang }}</td>

                    <td>

                        <div class="action-buttons">

                            <a href="{{ route('admin.prodi.edit', $prodi->id) }}" class="btn-edit">
                                ✏ Edit
                            </a>

                            <form action="{{ route('admin.prodi.destroy', $prodi->id) }}" method="POST">

                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus program studi ini?')">
                                    🗑 Hapus
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="5" style="text-align:center;padding:35px">
                        Belum ada data program studi.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div style="margin-top:20px">

        {{ $prodis->links() }}

    </div>

</div>
```

</div>

@endsection
