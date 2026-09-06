@extends('layouts.dosen')
@section('title', 'Pengajuan Bimbingan Skripsi')
@section('content')
<div class="skripsi">
    @include('skripsi.partials.style')
    <div><h1>Pengajuan Bimbingan Skripsi</h1><p class="sk-muted">Keputusan dan daftar mahasiswa bimbingan Anda.</p></div>
    @include('skripsi.partials.period')
    <div class="sk-card"><h2>Pengajuan dan riwayat keputusan</h2>@include('skripsi.partials.submissions')</div>
    <div class="sk-card"><h2>Mahasiswa bimbingan resmi ({{ $accepted->total() }})</h2>@include('skripsi.partials.submissions', ['submissions' => $accepted])</div>
</div>
@endsection
