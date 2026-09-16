@extends('layouts.admin')

@section('title','Edit Ruangan')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>✏️ Edit Ruangan</h2>
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

    <form action="{{ route('admin.ruangan.update', $ruangan->id) }}" method="POST">

        @csrf
        <x-list-return-url list-route="admin.ruangan" />
        @method('PUT')

        <div class="krs-form-grid">

            <div class="form-group">
                <label>Kode Ruangan</label>
                <input type="text" name="kode_ruangan" class="form-control" value="{{ old('kode_ruangan', $ruangan->kode_ruangan) }}" required>
            </div>

            <div class="form-group">
                <label>Nama Ruangan</label>
                <input type="text" name="nama_ruangan" class="form-control" value="{{ old('nama_ruangan', $ruangan->nama_ruangan) }}" required>
            </div>

            <div class="form-group">
                <label>Gedung</label>
                <input type="text" name="gedung" class="form-control" value="{{ old('gedung', $ruangan->gedung) }}">
            </div>

            <div class="form-group">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" class="form-control" value="{{ old('kapasitas', $ruangan->kapasitas) }}">
            </div>

        </div>

        <br>

        <button type="submit" class="btn-primary">
            💾 Update
        </button>

        <a href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.ruangan') }}" class="btn-outline">
            Batal
        </a>

    </form>

</div>
```

</div>

@endsection
