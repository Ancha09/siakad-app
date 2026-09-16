@extends('layouts.admin')

@section('title','Data Fakultas')

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
    <input type="text" placeholder="Cari kode atau nama fakultas...">
</div>

<a href="{{ route('admin.fakultas.create', ['return_url' => request()->fullUrl()]) }}" class="btn-primary">
    ➕ Tambah Fakultas
</a>
```

</div>

<div class="page-card">

```
<div class="page-card-head">
    <h2>🏛️ Data Fakultas</h2>
</div>

<div class="page-card-body">

    <div class="table-wrap">

        <table>

            <thead>

            <tr>
                <th>No</th>
                <th>Kode Fakultas</th>
                <th>Nama Fakultas</th>
                <th width="180">Aksi</th>
            </tr>

            </thead>

            <tbody>

            @forelse($fakultas as $item)

                <tr>

                    <td>{{ $fakultas->firstItem() + $loop->index }}</td>

                    <td>{{ $item->kode_fakultas }}</td>

                    <td>{{ $item->nama_fakultas }}</td>

                    <td>

                        <div class="action-buttons">

                            <a href="{{ route('admin.fakultas.edit', ['fakulta' => $item->id, 'return_url' => request()->fullUrl()]) }}" class="btn-edit">
                                ✏ Edit
                            </a>

                            <form action="{{ route('admin.fakultas.destroy', $item->id) }}" method="POST">

                                @csrf
                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                @method('DELETE')

                                <button type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus fakultas ini?')">
                                    🗑 Hapus
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="4" style="text-align:center;padding:35px">
                        Belum ada data fakultas.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div style="margin-top:20px">

        {{ $fakultas->appends(request()->query())->onEachSide(1)->links() }}

    </div>

</div>
```

</div>

@endsection
