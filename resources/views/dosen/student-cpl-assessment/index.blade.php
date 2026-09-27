@extends('layouts.dosen')
@section('title', 'Penilaian Mahasiswa')
@section('content')
    @include('cpl-assessment.partials.student-list', ['role' => 'dosen'])
@endsection
