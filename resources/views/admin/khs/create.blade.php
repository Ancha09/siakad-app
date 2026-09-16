@extends('layouts.admin')

@section('title','Tambah KHS')

@section('content')

<div class="page-card">

```
<div class="page-card-head">
    <h2>📑 Tambah Data KHS</h2>
</div>

<div class="page-card-body">

    @if ($errors->any())
        <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin-bottom:20px;">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin-top:10px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.khs.store') }}" method="POST">

        @csrf
        <x-list-return-url list-route="admin.khs" />

        <div class="krs-form-grid">

            <div class="form-group">
                <label>Pilih KRS</label>

                <select name="krs_id" class="form-control" required>

                    @foreach($krs as $item)

                        <option value="{{ $item->id }}">
                            {{ $item->mahasiswa->nim ?? '-' }} -
                            {{ $item->mahasiswa->nama ?? '-' }} |
                            {{ $item->jadwal->mataKuliah->kode_mk ?? '-' }} -
                            {{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}
                        </option>

                    @endforeach

                </select>

            </div>

            <div class="form-group">
                <label>Nilai Angka</label>

                <input
                    type="number"
                    name="nilai_angka"
                    class="form-control"
                    min="0"
                    max="100"
                    step="0.01"
                    required>

            </div>

            <div class="form-group">
                <label>Tahun Akademik</label>

                <input
                    type="text"
                    name="tahun_akademik"
                    class="form-control"
                    placeholder="Contoh: 2026/2027"
                    required>

            </div>

            <div class="form-group">
                <label>Semester Akademik</label>

                <select name="semester_akademik" class="form-control">

                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>

                </select>

            </div>

        </div>

        <br>

        <button type="submit" class="btn-primary">
            💾 Simpan
        </button>

        <a href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.khs') }}" class="btn-outline">
            Batal
        </a>

    </form>

</div>
```

</div>

@endsection
