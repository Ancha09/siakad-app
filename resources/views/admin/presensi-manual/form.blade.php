@extends('layouts.admin')

@php
    $editing = isset($presensi);
    $recordKrs = $editing ? $presensi->krs : null;
    $selectedCourse = $recordKrs?->mata_kuliah_id ?? $recordKrs?->jadwal?->mata_kuliah_id;
@endphp

@section('title', $editing ? 'Edit Absensi Lama' : 'Input Absensi Lama')

@section('content')
<div class="page-card"><div class="page-card-head"><h2>{{ $editing ? 'Edit' : 'Input' }} Absensi Lama / Manual</h2></div><div class="page-card-body">
    @if($errors->any())<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin-bottom:20px"><strong>Periksa input:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $editing ? route('admin.presensi-manual.update', $presensi) : route('admin.presensi-manual.store') }}">@csrf @if($editing) @method('PUT') @endif
        <div class="krs-form-grid">
            <div class="form-group"><label>Mahasiswa *</label><select name="mahasiswa_id" class="form-control" required><option value="">Pilih mahasiswa</option>@foreach($mahasiswas as $m)<option value="{{ $m->id }}" @selected(old('mahasiswa_id', $recordKrs?->mahasiswa_id) == $m->id)>{{ $m->nim }} — {{ $m->nama }}{{ $m->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
            <div class="form-group"><label>Angkatan</label><input type="number" name="angkatan" min="1900" class="form-control" value="{{ old('angkatan', $recordKrs?->angkatan ?? $recordKrs?->mahasiswa?->angkatan) }}"></div>
            <div class="form-group"><label>Semester Mahasiswa</label><input type="number" name="semester" min="1" max="14" class="form-control" value="{{ old('semester', $recordKrs?->semester) }}"></div>
            <div class="form-group"><label>Tahun Ajaran *</label><input type="text" name="tahun_akademik" class="form-control" placeholder="2020/2021" value="{{ old('tahun_akademik', $recordKrs?->tahun_akademik) }}" required></div>
            <div class="form-group"><label>Semester Akademik *</label><select name="semester_akademik" class="form-control" required><option value="">Pilih semester</option>@foreach(['Ganjil','Genap'] as $semester)<option value="{{ $semester }}" @selected(old('semester_akademik', $recordKrs?->semester_akademik) === $semester)>{{ $semester }}</option>@endforeach</select></div>
            <div class="form-group"><label>Program Studi</label><select name="prodi_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(old('prodi_id', $recordKrs?->prodi_id ?? $recordKrs?->mahasiswa?->prodi_id) == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach</select></div>
            <div class="form-group"><label>Mata Kuliah *</label><select name="mata_kuliah_id" class="form-control" required><option value="">Pilih mata kuliah</option>@foreach($mataKuliahs as $mk)<option value="{{ $mk->id }}" @selected(old('mata_kuliah_id', $selectedCourse) == $mk->id)>{{ $mk->kode_mk }} — {{ $mk->nama_mk }}</option>@endforeach</select></div>
            <div class="form-group"><label>Dosen Pengampu</label><select name="dosen_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(old('dosen_id', $recordKrs?->dosen_id ?? $recordKrs?->jadwal?->dosen_id) == $dosen->id)>{{ $dosen->nama }}</option>@endforeach</select></div>
            <div class="form-group"><label>Jadwal / Pertemuan Terjadwal</label><select name="jadwal_id" class="form-control"><option value="">Tidak ada jadwal</option>@foreach($jadwals as $jadwal)<option value="{{ $jadwal->id }}" @selected(old('jadwal_id', $recordKrs?->jadwal_id) == $jadwal->id)>{{ $jadwal->mataKuliah?->kode_mk ?? '-' }} · {{ $jadwal->tahun_akademik ?? '-' }} · {{ $jadwal->dosen?->nama ?? 'tanpa dosen' }}</option>@endforeach</select></div>
            <div class="form-group"><label>Kelas</label><select name="kelas_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($kelases as $kelas)<option value="{{ $kelas->id }}" @selected(old('kelas_id', $recordKrs?->kelas_id ?? $recordKrs?->jadwal?->kelas_id) == $kelas->id)>{{ $kelas->nama_kelas }}</option>@endforeach</select></div>
            <div class="form-group"><label>Tanggal Pertemuan *</label><input type="date" name="tanggal" class="form-control" max="{{ today()->toDateString() }}" value="{{ old('tanggal', isset($presensi) ? $presensi->tanggal?->toDateString() : '') }}" required></div>
            <div class="form-group"><label>Pertemuan Ke-</label><input type="number" name="pertemuan" min="1" max="255" class="form-control" value="{{ old('pertemuan', $presensi->pertemuan ?? '') }}"></div>
            <div class="form-group"><label>Status *</label><select name="status" class="form-control" required>@foreach(['Hadir','Izin','Sakit','Alpha'] as $status)<option value="{{ $status }}" @selected(old('status', $presensi->status ?? 'Hadir') === $status)>{{ $status === 'Alpha' ? 'Alpa' : $status }}</option>@endforeach</select></div>
            <div class="form-group"><label>Keterangan</label><textarea name="keterangan" class="form-control" rows="3">{{ old('keterangan', $presensi->keterangan ?? '') }}</textarea></div>
        </div>
        <p style="color:#64748b">* Wajib. Dosen, jadwal, kelas, program studi, angkatan, semester mahasiswa, nomor pertemuan, dan keterangan boleh kosong.</p>
        <button class="btn-primary">Simpan Absensi</button> <a href="{{ route('admin.presensi-manual.index') }}" class="btn-outline">Batal</a>
    </form>
</div></div>
@endsection
