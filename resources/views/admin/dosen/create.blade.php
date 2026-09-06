@extends('layouts.admin')

@section('title','Tambah Dosen')

@section('content')

<div class="inner-page">

    <div class="page-card">

        <div class="page-card-head">
            <h2>➕ Tambah Data Dosen</h2>
        </div>

        <div class="page-card-body">

            <form action="{{ route('admin.dosen.store') }}" method="POST">

                @csrf

                <div class="krs-form-grid">

                    <div class="form-group">
                        <label>NIDN</label>
                        <input type="text"
                               name="nidn"
                               class="form-control"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text"
                               name="nama"
                               class="form-control"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                               name="email"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Telepon</label>
                        <input type="text"
                               name="telepon"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text"
                               name="jabatan"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Golongan</label>
                        <input type="text"
                               name="golongan"
                               class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Program Studi</label>

                        <select name="prodi_id" class="form-control">

                            @foreach($prodis as $prodi)

                                <option value="{{ $prodi->id }}">
                                    {{ $prodi->nama_prodi }}
                                </option>
                                
                            @endforeach

                        </select>

                    </div>
<div class="form-group">
    <label>Password</label>
    <input
        type="password"
        name="password"
        class="form-control"
        required
    >
</div>

<div class="form-group">
    <label>Konfirmasi Password</label>
    <input
        type="password"
        name="password_confirmation"
        class="form-control"
        required
    >
</div>
                </div>

                <br>

                <button class="btn-primary">

                    Simpan

                </button>

                <a href="{{ route('admin.dosen') }}"
                   class="btn-outline">

                    Batal

                </a>

            </form>

        </div>

    </div>

</div>

@endsection