@extends('layouts.mahasiswa')

@section('title', 'Profil Saya')

@push('styles')
<style>
    .student-profile .profile-notice { display:flex; gap:11px; align-items:flex-start; margin-bottom:18px; padding:13px 15px; border:1px solid #bfdbfe; border-radius:11px; background:#eff6ff; color:#1e40af; font-size:12px; line-height:1.6; }
    .student-profile .profile-notice strong { display:block; }
    .student-profile .profile-section-title { margin-bottom:4px; color:#0a1f5c; font-size:14px; }
    .student-profile .profile-field p.empty-value { color:#94a3b8; font-weight:400; }
    .student-profile .profile-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }
    .student-profile .profile-card { height:100%; }
    .student-profile .profile-card .page-card-body { padding-top:8px; }
    .student-profile .identity-line { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    @media(max-width:760px) { .student-profile .profile-grid { grid-template-columns:1fr; } }
    @media(max-width:520px) { .student-profile .profile-header { align-items:flex-start; padding:20px; } }
</style>
@endpush

@section('content')
<div class="inner-page student-profile">
    <section class="profile-header" aria-label="Identitas mahasiswa">
        <div class="profile-avatar-lg">{{ strtoupper(substr($mahasiswa->nama, 0, 1)) }}</div>
        <div class="profile-info">
            <h2>{{ $mahasiswa->nama }}</h2>
            <p>NIM: {{ $mahasiswa->nim }}</p>
            <div class="profile-tags">
                @if($mahasiswa->prodi)<span>{{ $mahasiswa->prodi->jenjang }} {{ $mahasiswa->prodi->nama_prodi }}</span>@endif
                @if($mahasiswa->semester)<span>Semester {{ $mahasiswa->semester }}</span>@endif
                @if($mahasiswa->kelas)<span>Kelas {{ $mahasiswa->kelas->nama_kelas }}</span>@endif
                <span>Mahasiswa</span>
            </div>
        </div>
    </section>

    <div class="profile-notice">
        <x-layout-icon name="help" style="flex-shrink:0;margin-top:1px;" />
        <div>
            <strong>Profil ini hanya dapat dilihat.</strong>
            Jika ada data yang tidak sesuai, hubungi administrator akademik. Perubahan data dan password akun mahasiswa hanya dapat dilakukan oleh administrator.
        </div>
    </div>

    <div class="profile-grid">
        <section class="page-card profile-card">
            <div class="page-card-head"><h2>Data Pribadi</h2></div>
            <div class="page-card-body">
                <div class="profile-field"><label>Nama Lengkap</label><p>{{ $mahasiswa->nama }}</p></div>
                <div class="profile-field"><label>NIM</label><p>{{ $mahasiswa->nim }}</p></div>
                <div class="profile-field"><label>Email</label><p class="{{ $mahasiswa->email ? '' : 'empty-value' }}">{{ $mahasiswa->email ?: 'Belum tersedia' }}</p></div>
                <div class="profile-field"><label>Nomor Telepon</label><p class="{{ $mahasiswa->telepon ? '' : 'empty-value' }}">{{ $mahasiswa->telepon ?: 'Belum tersedia' }}</p></div>
                <div class="profile-field"><label>ID Login</label><p>{{ $mahasiswa->user->login ?? '-' }}</p></div>
            </div>
        </section>

        <section class="page-card profile-card">
            <div class="page-card-head"><h2>Data Akademik</h2></div>
            <div class="page-card-body">
                <div class="profile-field"><label>Fakultas</label><p class="{{ $mahasiswa->prodi?->fakultas ? '' : 'empty-value' }}">{{ $mahasiswa->prodi?->fakultas?->nama_fakultas ?? 'Belum ditentukan' }}</p></div>
                <div class="profile-field"><label>Program Studi</label><p class="{{ $mahasiswa->prodi ? '' : 'empty-value' }}">{{ $mahasiswa->prodi ? $mahasiswa->prodi->jenjang.' '.$mahasiswa->prodi->nama_prodi : 'Belum ditentukan' }}</p></div>
                <div class="profile-field"><label>Kelas</label><p class="{{ $mahasiswa->kelas ? '' : 'empty-value' }}">{{ $mahasiswa->kelas->nama_kelas ?? 'Belum ditentukan' }}</p></div>
                <div class="profile-field"><label>Angkatan</label><p class="{{ $mahasiswa->angkatan ? '' : 'empty-value' }}">{{ $mahasiswa->angkatan ?? 'Belum tersedia' }}</p></div>
                <div class="profile-field"><label>Semester Aktif</label><p class="{{ $mahasiswa->semester ? '' : 'empty-value' }}">{{ $mahasiswa->semester ? 'Semester '.$mahasiswa->semester : 'Belum ditentukan' }}</p></div>
                <div class="profile-field"><label>Dosen Wali</label><p class="{{ $dosenWali ? '' : 'empty-value' }}">{{ $dosenWali->nama ?? 'Belum ditentukan' }}</p></div>
            </div>
        </section>
    </div>
</div>
@endsection
