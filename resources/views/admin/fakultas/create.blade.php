@extends('layouts.admin')

@section('title','Tambah Fakultas')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>🏛️ Tambah Fakultas</h2>
</div>

<div class="page-card-body">

    @if ($errors->any())
        <div style="background:#fee2e2;color:#b91c1c;padding:15px;border-radius:8px;margin-bottom:20px;">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin-top:10px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.fakultas.store') }}" method="POST">

        @csrf
        <x-list-return-url list-route="admin.fakultas" />

        <div class="krs-form-grid">

            <div class="form-group">
                <label>Kode Fakultas</label>
                <input type="text" name="kode_fakultas" class="form-control" value="{{ old('kode_fakultas') }}" required>
            </div>

            <div class="form-group">
                <label>Nama Fakultas</label>
                <input type="text" name="nama_fakultas" class="form-control" value="{{ old('nama_fakultas') }}" required>
            </div>

        </div>

        <br>

        <button type="submit" class="btn-primary">
            💾 Simpan
        </button>

        <a href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.fakultas') }}" class="btn-outline">
            Batal
        </a>

    </form>

</div>
```

</div>

@endsection
