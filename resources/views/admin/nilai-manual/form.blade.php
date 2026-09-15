@extends('layouts.admin')

@php
    $editing = isset($khs);
    $recordKrs = $editing ? $khs->krs : null;
    $selectedCourse = $recordKrs?->mata_kuliah_id ?? $recordKrs?->jadwal?->mata_kuliah_id;
@endphp

@section('title', $editing ? 'Edit Nilai Lama' : 'Input Nilai Lama')

@section('content')
<div class="page-card">
    <div class="page-card-head"><h2>{{ $editing ? 'Edit' : 'Input' }} Nilai Lama / Manual</h2></div>
    <div class="page-card-body">
        @if($errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin-bottom:20px"><strong>Periksa input:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ $editing ? route('admin.nilai-manual.update', $khs) : route('admin.nilai-manual.store') }}">
            @csrf @if($editing) @method('PUT') @endif
            <div class="krs-form-grid">
                <div class="form-group"><label>Mahasiswa *</label><select name="mahasiswa_id" class="form-control" required><option value="">Pilih mahasiswa</option>@foreach($mahasiswas as $m)<option value="{{ $m->id }}" @selected(old('mahasiswa_id', $recordKrs?->mahasiswa_id) == $m->id)>{{ $m->nim }} — {{ $m->nama }}{{ $m->is_active ? '' : ' (nonaktif)' }}</option>@endforeach</select></div>
                <div class="form-group"><label>Angkatan</label><input type="number" name="angkatan" class="form-control" min="1900" value="{{ old('angkatan', $recordKrs?->angkatan ?? $recordKrs?->mahasiswa?->angkatan) }}"></div>
                <div class="form-group"><label>Semester Mahasiswa</label><input type="number" name="semester" class="form-control" min="1" max="14" value="{{ old('semester', $recordKrs?->semester) }}"></div>
                <div class="form-group"><label>Tahun Ajaran *</label><input type="text" name="tahun_akademik" class="form-control" placeholder="2020/2021" value="{{ old('tahun_akademik', $khs->tahun_akademik ?? '') }}" required></div>
                <div class="form-group"><label>Semester Akademik *</label><select name="semester_akademik" class="form-control" required><option value="">Pilih semester</option>@foreach(['Ganjil','Genap'] as $semester)<option value="{{ $semester }}" @selected(old('semester_akademik', $khs->semester_akademik ?? '') === $semester)>{{ $semester }}</option>@endforeach</select></div>
                <div class="form-group"><label>Program Studi</label><select name="prodi_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(old('prodi_id', $recordKrs?->prodi_id ?? $recordKrs?->mahasiswa?->prodi_id) == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach</select></div>
                <div class="form-group"><label>Mata Kuliah *</label><select name="mata_kuliah_id" class="form-control" required><option value="">Pilih mata kuliah</option>@foreach($mataKuliahs as $mk)<option value="{{ $mk->id }}" @selected(old('mata_kuliah_id', $selectedCourse) == $mk->id)>{{ $mk->kode_mk }} — {{ $mk->nama_mk }} ({{ $mk->sks }} SKS)</option>@endforeach</select></div>
                <div class="form-group"><label>Dosen Pengampu</label><select name="dosen_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(old('dosen_id', ($editing ? $khs->dosen_efektif?->id : null)) == $dosen->id)>{{ $dosen->nama }}</option>@endforeach</select><small>Pilih dosen yang benar untuk data historis; boleh berbeda dari dosen pada jadwal.</small></div>
                <div class="form-group"><label>Jadwal</label><select name="jadwal_id" class="form-control"><option value="">Tidak ada jadwal</option>@foreach($jadwals as $jadwal)<option value="{{ $jadwal->id }}" @selected(old('jadwal_id', $recordKrs?->jadwal_id) == $jadwal->id)>{{ $jadwal->mataKuliah?->kode_mk ?? '-' }} · {{ $jadwal->tahun_akademik ?? '-' }} · {{ $jadwal->dosen?->nama ?? 'tanpa dosen' }}</option>@endforeach</select><small>Jika dipilih, jadwal harus sesuai mata kuliah, periode, dan kelas. Jadwal asli tidak diubah.</small></div>
                <div class="form-group"><label>Kelas</label><select name="kelas_id" class="form-control"><option value="">Tidak diketahui</option>@foreach($kelases as $kelas)<option value="{{ $kelas->id }}" @selected(old('kelas_id', $recordKrs?->kelas_id ?? $recordKrs?->jadwal?->kelas_id) == $kelas->id)>{{ $kelas->nama_kelas }}</option>@endforeach</select></div>
                <div class="form-group"><label>Nilai Angka *</label><input type="number" step="0.01" min="0" max="100" name="nilai_angka" class="form-control" value="{{ old('nilai_angka', $khs->nilai_angka ?? '') }}" required></div>
                <div class="form-group"><label>Nilai Huruf</label><select name="nilai_huruf" class="form-control"><option value="">Hitung otomatis</option>@foreach($gradeLetters as $letter)<option value="{{ $letter }}" @selected(old('nilai_huruf', $khs->nilai_huruf ?? '') === $letter)>{{ $letter }}</option>@endforeach</select></div>
                <div class="form-group"><label>SKS</label><input type="number" min="1" max="30" name="sks" class="form-control" value="{{ old('sks', $khs->sks ?? '') }}" placeholder="Ikuti mata kuliah"></div>
                <div class="form-group"><label>Bobot</label><input type="number" step="0.01" min="0" max="4" name="bobot" class="form-control" value="{{ old('bobot', $khs->bobot ?? '') }}" placeholder="Hitung otomatis"></div>
            </div>
            <p style="color:#64748b">* Wajib. Angkatan, semester mahasiswa, prodi, dosen, jadwal, kelas, nilai huruf, SKS, dan bobot boleh kosong.</p>
            <p style="color:#64748b">Dosen pengampu tersimpan khusus untuk entri nilai ini. Identitas akademik pada KRS manual berlaku bersama untuk mata kuliah/periode yang sama. Untuk mengganti nama pada master dosen, gunakan menu Data Dosen.</p>
            <button class="btn-primary">Simpan Nilai</button> <a href="{{ route('admin.nilai-manual.index') }}" class="btn-outline">Batal</a>
        </form>
    </div>
</div>
@endsection
