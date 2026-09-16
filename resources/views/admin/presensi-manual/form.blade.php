@extends('layouts.admin')

@section('title', 'Fitur Tidak Tersedia')

@section('content')
    {{-- Archived template: legacy attendance input is no longer exposed by admin routes. --}}
    <div class="page-card">
        <div class="page-card-head"><h2>Fitur input absensi lama dinonaktifkan</h2></div>
        <div class="page-card-body">
            <p>Data absensi yang sudah tersimpan tetap tersedia melalui monitoring absensi.</p>
            <a href="{{ route('admin.presensi') }}" class="btn-primary">Monitoring Absensi</a>
        </div>
    </div>
@endsection
