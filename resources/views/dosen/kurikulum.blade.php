@extends('layouts.dosen')

@section('title', 'Kurikulum & Silabus Program Studi')
@section('page-title', 'Kurikulum & Silabus')
@section('page-subtitle', 'Susunan mata kuliah dan dokumen silabus program studi')

@section('content')
    @include('kurikulum.catalog')
@endsection
