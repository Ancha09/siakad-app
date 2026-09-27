@extends('layouts.admin')
@section('title', 'Penilaian CPL Mahasiswa')
@section('page-subtitle', 'Profil kemampuan mahasiswa berdasarkan nilai mata kuliah dan mapping CPL aktif')
@section('content')
    @include('cpl-assessment.partials.student-list', ['role' => 'admin'])
@endsection
