@if($errors->any())
    <div class="alert-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form method="GET" action="{{ route($resetRoute) }}" style="margin-bottom:20px">
    <div class="krs-form-grid">
        <div class="form-group"><label for="filter-search">NIM / Nama mahasiswa</label><input id="filter-search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari mahasiswa"></div>
        <div class="form-group"><label for="filter-class">Kelas</label><select id="filter-class" name="kelas_id" class="form-control"><option value="">Semua kelas</option>@foreach($kelases as $kelas)<option value="{{ $kelas->id }}" @selected(request('kelas_id') == $kelas->id)>{{ $kelas->nama_kelas }}</option>@endforeach</select></div>
        <div class="form-group"><label for="filter-prodi">Program Studi</label><select id="filter-prodi" name="prodi_id" class="form-control"><option value="">Semua prodi</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(request('prodi_id') == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach</select></div>
        <div class="form-group"><label for="filter-course">Mata Kuliah</label><select id="filter-course" name="mata_kuliah_id" class="form-control"><option value="">Semua mata kuliah</option>@foreach($mataKuliahs as $mk)<option value="{{ $mk->id }}" @selected(request('mata_kuliah_id') == $mk->id)>{{ $mk->kode_mk }} — {{ $mk->nama_mk }}</option>@endforeach</select></div>
        <div class="form-group"><label for="filter-lecturer">Dosen Pengampu</label><select id="filter-lecturer" name="dosen_id" class="form-control"><option value="">Semua dosen</option>@foreach($dosens as $dosen)<option value="{{ $dosen->id }}" @selected(request('dosen_id') == $dosen->id)>{{ $dosen->nama }}</option>@endforeach</select></div>
        <div class="form-group"><label for="filter-year">Tahun Ajaran</label><input id="filter-year" name="tahun_akademik" class="form-control" value="{{ request('tahun_akademik') }}" placeholder="2020/2021"></div>
        <div class="form-group"><label for="filter-term">Semester Akademik</label><select id="filter-term" name="semester_akademik" class="form-control"><option value="">Semua semester</option>@foreach(['Ganjil', 'Genap'] as $term)<option value="{{ $term }}" @selected(request('semester_akademik') === $term)>{{ $term }}</option>@endforeach</select></div>
        <div class="form-group"><label for="filter-cohort">Angkatan</label><input id="filter-cohort" type="number" name="angkatan" min="1900" class="form-control" value="{{ request('angkatan') }}"></div>
        <div class="form-group"><label for="filter-semester">Semester Mahasiswa</label><input id="filter-semester" type="number" name="semester" min="1" max="14" class="form-control" value="{{ request('semester') }}"></div>
        @if($attendance)
            <div class="form-group"><label for="filter-status">Status</label><select id="filter-status" name="status" class="form-control"><option value="">Semua status</option>@foreach(['Hadir', 'Izin', 'Sakit', 'Alpha'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status === 'Alpha' ? 'Alpa' : $status }}</option>@endforeach</select></div>
            <div class="form-group"><label for="filter-start">Tanggal Mulai</label><input id="filter-start" type="date" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}"></div>
            <div class="form-group"><label for="filter-end">Tanggal Selesai</label><input id="filter-end" type="date" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}"></div>
        @endif
    </div>
    <button type="submit" class="btn-primary">Terapkan Filter</button> <a href="{{ route($resetRoute) }}" class="btn-outline">Reset</a>
</form>
