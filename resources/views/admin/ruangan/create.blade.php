@extends('layouts.admin')

@section('title','Tambah Ruangan')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>🏫 Tambah Ruangan</h2>
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

    <form action="{{ route('admin.ruangan.store') }}" method="POST">

        @csrf
        <x-list-return-url list-route="admin.ruangan" />

        <div class="krs-form-grid">

            <div class="form-group">
                <label>Kode Ruangan</label>
                <input type="text" name="kode_ruangan" class="form-control" value="{{ old('kode_ruangan') }}" required>
            </div>

            <div class="form-group">
                <label>Nama Ruangan</label>
                <input type="text" name="nama_ruangan" class="form-control" value="{{ old('nama_ruangan') }}" required>
            </div>

            <div class="form-group">
                <label>Gedung</label>
                <input type="text" name="gedung" class="form-control" value="{{ old('gedung') }}">
            </div>

            <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" class="form-control" value="{{ old('kapasitas') }}">
            </div>

        </div>

        <br>

        <button type="submit" class="btn-primary">
            💾 Simpan
        </button>

        <a href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.ruangan') }}" class="btn-outline">
            Batal
        </a>

    </form>

</div>
```

</div>

@endsection
