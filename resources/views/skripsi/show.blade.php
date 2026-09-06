@extends('layouts.'.$role)
@section('title', 'Riwayat Pengajuan Skripsi')
@section('content')
<div class="skripsi">
    @include('skripsi.partials.style')
    <div><h1>Riwayat Pengajuan #{{ $submission->id }}</h1><a href="{{ route($role.'.skripsi', ['periode_id' => $submission->periode_skripsi_id]) }}">Kembali ke pengajuan</a></div>
    <div class="sk-card">
        <h2>{{ $submission->judul }}</h2>
        <p>{{ $submission->mahasiswa->nama }} · {{ $submission->mahasiswa->nim }} · {{ $submission->mahasiswa->prodi?->nama_prodi }}</p>
        <p>Dosen tujuan: {{ $submission->dosen->nama }}</p>
        <p>Periode: {{ $submission->periode->nama }} · {{ $submission->periode->mulai->format('d/m/Y H:i') }} – {{ $submission->periode->berakhir->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
        <p>Diajukan {{ $submission->created_at->format('d/m/Y H:i') }} oleh {{ $submission->pembuat->name }}</p>
        <span class="sk-badge sk-{{ $submission->status }}">{{ $submission->status }}</span>
        @if($submission->alasan_keputusan)<p>Alasan: {{ $submission->alasan_keputusan }}</p>@endif
        @if($submission->status === 'Menunggu' && now()->gt($submission->periode->berakhir))<p>Belum diputuskan; periode sudah ditutup.</p>@endif
    </div>
    <div class="sk-card"><h2>Jejak aktivitas</h2>@include('skripsi.partials.audits')</div>
</div>
@endsection
