@extends('layouts.dosen')

@section('title', 'Profil Saya')

@section('content')

<div class="inner-page">

    <div class="page-card">

        <div class="page-card-head">
            <h2>👤 Profil Saya</h2>
        </div>

        <div class="page-card-body">

            {{-- DATA PRIBADI --}}
            <div class="page-section">

                <div class="section-title">
                    <h3>📋 Data Pribadi</h3>
                </div>

                <div class="profile-detail-grid">

                    <div>

                        <div class="profile-field">
                            <label>Nama Lengkap</label>
                            <p>{{ $dosen->nama ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>NIDN</label>
                            <p>{{ $dosen->nidn ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Program Studi</label>
                            <p>{{ $dosen->prodi->nama_prodi ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Jabatan Fungsional</label>
                            <p>{{ $dosen->jabatan ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Golongan / Ruang</label>
                            <p>{{ $dosen->golongan ?? '-' }}</p>
                        </div>

                    </div>

                    <div>

                        <div class="profile-field">
                            <label>Email</label>
                            <p>{{ $dosen->email ?? '-' }}</p>
                        </div>

                        <div class="profile-field">

                            <label>No. Telepon</label>

                            <form
                                action="{{ route('dosen.profil.update') }}"
                                method="POST"
                            >

                                @csrf
                                @method('PUT')

                                <div style="display:flex;gap:10px;align-items:center;">

                                    <input
                                        type="text"
                                        name="telepon"
                                        class="form-control"
                                        value="{{ old('telepon', $dosen->telepon) }}"
                                        placeholder="Masukkan nomor telepon"
                                        style="max-width:280px;"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-primary"
                                    >
                                        💾 Simpan
                                    </button>

                                </div>

                            </form>

                        </div>

                        <div class="profile-field">
                            <label>Status</label>
                            <p style="color:var(--blue);font-weight:600;">
                                Aktif
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            <hr style="margin:30px 0;border:0;border-top:1px solid #e5e7eb;">


            {{-- KEAMANAN AKUN --}}
            <div class="page-section">

                <div class="section-title">
                    <h3>🔐 Keamanan Akun</h3>
                </div>

                <form
                    action="{{ route('dosen.profil.password') }}"
                    method="POST"
                >

                    @csrf
                    @method('PUT')

                    <div class="profile-detail-grid">

                        <div>

                            <div class="profile-field">

                                <label>Password Lama</label>

                                <input
                                    type="password"
                                    name="password_lama"
                                    class="form-control"
                                    placeholder="Masukkan password lama"
                                    required
                                >

                            </div>

                        </div>

                        <div>

                            <div class="profile-field">

                                <label>Password Baru</label>

                                <input
                                    type="password"
                                    name="password_baru"
                                    class="form-control"
                                    placeholder="Minimal 8 karakter"
                                    minlength="8"
                                    required
                                >

                            </div>

                            <div class="profile-field">

                                <label>Konfirmasi Password Baru</label>

                                <input
                                    type="password"
                                    name="password_baru_confirmation"
                                    class="form-control"
                                    placeholder="Ulangi password baru"
                                    minlength="8"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn-primary"
                        style="margin-top:15px;"
                    >
                        🔐 Ubah Password
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

@endsection