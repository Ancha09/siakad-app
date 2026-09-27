@extends('layouts.mahasiswa')
@section('title', 'Penilaian Mahasiswa')
@section('content')
    @include('cpl-assessment.partials.profile', ['role' => 'mahasiswa', 'showTechnical' => false])
@endsection
