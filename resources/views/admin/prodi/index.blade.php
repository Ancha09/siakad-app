@extends('layouts.admin')

@section('title','Data Program Studi')

@section('content')

@if(session('success'))

<div class="alert-success">
    {{ session('success') }}
</div>
@endif

<div class="toolbar">

<div class="search-box">
    <span class="search-icon"><x-layout-icon name="search" /></span>
    <input type="text" placeholder="Cari kode atau nama program studi...">
</div>

<a href="{{ route('admin.prodi.create', ['return_url' => request()->fullUrl()]) }}" class="btn-primary icon-button">
    <x-layout-icon name="plus" /> Tambah Program Studi
</a>

</div>

<div class="page-card">

<div class="page-card-head">
    <h2 class="icon-heading"><x-layout-icon name="school" /> Data Program Studi</h2>
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
                <th>Ketua Program Studi</th>
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
                        <strong>{{ $prodi->ketua_program_studi_nama ?? 'Belum diisi' }}</strong>
                        @if($prodi->ketua_program_studi_nip)
                            <br><small style="color:#64748b;">NIP/NIDN: {{ $prodi->ketua_program_studi_nip }}</small>
                        @endif
                    </td>

                    <td>

                        <div class="action-buttons">

                            <a href="{{ route('admin.prodi.edit', ['prodi' => $prodi->id, 'return_url' => request()->fullUrl()]) }}" class="btn-edit">
                                <span class="icon-inline"><x-layout-icon name="edit" /> Edit</span>
                            </a>

                            <form action="{{ route('admin.prodi.destroy', $prodi->id) }}" method="POST">

                                @csrf
                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                @method('DELETE')

                                <button type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus program studi ini?')">
                                    <span class="icon-inline"><x-layout-icon name="trash" /> Hapus</span>
                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="6" style="text-align:center;padding:35px">
                        Belum ada data program studi.
                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

    <div style="margin-top:20px">

        {{ $prodis->appends(request()->query())->onEachSide(1)->links() }}

    </div>

</div>
</div>

@endsection
