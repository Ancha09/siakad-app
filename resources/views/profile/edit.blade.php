@extends('layouts.admin')

@section('title', 'Profil Admin')
@section('page-subtitle', 'Kelola identitas dan keamanan akun administrator')

@push('styles')
<style>
    .admin-profile { padding:24px; }
    .admin-profile-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr); gap:20px; }
    .admin-profile .profile-header { margin-bottom:20px; }
    .admin-profile .profile-card { height:100%; }
    .admin-profile .profile-card .page-card-body { display:grid; gap:17px; }
    .admin-profile .profile-help { color:#64748b; font-size:12px; line-height:1.6; margin-bottom:4px; }
    .admin-profile .form-error { color:#b91c1c; font-size:12px; margin-top:4px; }
    .admin-profile .success-note { color:#166534; font-size:12px; font-weight:600; }
    .admin-profile .account-facts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; margin-bottom:20px; }
    .admin-profile .account-fact { padding:15px; border:1px solid #dbeafe; border-radius:12px; background:#f8fafc; }
    .admin-profile .account-fact span { display:block; color:#64748b; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .admin-profile .account-fact strong { display:block; margin-top:5px; color:#0f2a55; font-size:13px; overflow-wrap:anywhere; }
    @media(max-width:850px) { .admin-profile-grid { grid-template-columns:1fr; } }
    @media(max-width:600px) { .admin-profile { padding:14px; } .admin-profile .account-facts { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="admin-profile">
    <section class="profile-header" aria-label="Identitas administrator">
        <div class="profile-avatar-lg">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        <div class="profile-info">
            <h2>{{ $user->name }}</h2>
            <p>Administrator Sistem Informasi Akademik</p>
            <div class="profile-tags">
                <span>🏫 SIAKAD STTMI</span>
                <span>🔑 Akses Administrator</span>
                <span>✅ Aktif</span>
            </div>
        </div>
    </section>

    <div class="account-facts">
        <div class="account-fact"><span>ID Login</span><strong>{{ $user->login ?: '-' }}</strong></div>
        <div class="account-fact"><span>Peran</span><strong>{{ ucfirst($user->role) }}</strong></div>
        <div class="account-fact"><span>Email</span><strong>{{ $user->email ?: '-' }}</strong></div>
        <div class="account-fact"><span>Akun Dibuat</span><strong>{{ $user->created_at?->format('d M Y') ?: '-' }}</strong></div>
    </div>

    <div class="admin-profile-grid">
        <section class="page-card profile-card">
            <div class="page-card-head"><h2>👤 Informasi Profil</h2></div>
            <form class="page-card-body" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')
                <p class="profile-help">Nama ini ditampilkan pada sidebar dan bagian kanan atas dashboard admin.</p>

                <div class="form-group">
                    <label for="name">Nama Administrator</label>
                    <input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                    @error('email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <button class="btn-primary" type="submit">Simpan Profil</button>
                    @if(session('status') === 'profile-updated')<span class="success-note">Profil berhasil diperbarui.</span>@endif
                </div>
            </form>
        </section>

        <section class="page-card profile-card">
            <div class="page-card-head"><h2>🔒 Keamanan Akun</h2></div>
            <form class="page-card-body" method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')
                <p class="profile-help">Gunakan kata sandi yang panjang dan tidak digunakan pada layanan lain.</p>

                <div class="form-group">
                    <label for="current_password">Kata Sandi Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" class="form-control" required autocomplete="current-password">
                    @if($errors->updatePassword->has('current_password'))<p class="form-error">{{ $errors->updatePassword->first('current_password') }}</p>@endif
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi Baru</label>
                    <input id="password" name="password" type="password" class="form-control" required autocomplete="new-password">
                    @if($errors->updatePassword->has('password'))<p class="form-error">{{ $errors->updatePassword->first('password') }}</p>@endif
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                </div>

                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <button class="btn-primary" type="submit">Perbarui Kata Sandi</button>
                    @if(session('status') === 'password-updated')<span class="success-note">Kata sandi berhasil diperbarui.</span>@endif
                </div>
            </form>
        </section>
    </div>
</div>
@endsection
