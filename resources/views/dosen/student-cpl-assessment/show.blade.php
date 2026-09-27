@extends('layouts.dosen')
@section('title', 'Detail Penilaian Mahasiswa')
@section('content')
    <div class="page-card" style="margin-bottom:20px;"><div class="page-card-body"><form method="GET"><div class="form-group" style="max-width:320px;"><label for="tahun_akademik">Tahun Akademik Nilai</label><select id="tahun_akademik" name="tahun_akademik" class="form-control" onchange="this.form.submit()"><option value="">Semua tahun akademik</option>@foreach($academicYears as $year)<option value="{{ $year }}" @selected(($filters['tahun_akademik'] ?? '') === $year)>{{ $year }}</option>@endforeach</select></div></form></div></div>
    @include('cpl-assessment.partials.profile', ['role' => 'dosen', 'showTechnical' => true])
@endsection
