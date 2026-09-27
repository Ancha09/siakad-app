@php
    $isAdmin = $role === 'admin';
    $indexRoute = $isAdmin ? 'admin.penilaian-cpl-mahasiswa.index' : 'dosen.penilaian-mahasiswa.index';
    $showRoute = $isAdmin ? 'admin.penilaian-cpl-mahasiswa.show' : 'dosen.penilaian-mahasiswa.show';
@endphp

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-head"><h2>Filter Penilaian CPL Mahasiswa</h2></div>
    <div class="page-card-body">
        <form method="GET" action="{{ route($indexRoute) }}">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label for="search">Mahasiswa / NIM</label>
                    <input id="search" class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Ketik nama atau NIM">
                </div>
                @if($isAdmin)
                    <div class="form-group">
                        <label for="prodi_id">Program Studi</label>
                        <select id="prodi_id" class="form-control" name="prodi_id">
                            <option value="">Semua program studi tersedia</option>
                            @foreach($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) ($filters['prodi_id'] ?? '') === (string) $program->id)>{{ $program->nama_prodi }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" class="form-control" name="angkatan">
                        <option value="">Semua angkatan</option>
                        @foreach($cohorts as $cohort)
                            <option value="{{ $cohort }}" @selected((string) ($filters['angkatan'] ?? '') === (string) $cohort)>{{ $cohort }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="tahun_akademik">Tahun Akademik Nilai</label>
                    <select id="tahun_akademik" class="form-control" name="tahun_akademik">
                        <option value="">Semua tahun akademik</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" @selected(($filters['tahun_akademik'] ?? '') === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button type="submit" class="btn-primary">Terapkan Filter</button>
                <a href="{{ route($indexRoute) }}" class="btn-outline">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="page-card">
    <div class="page-card-head"><h2>Daftar Profil Kemampuan Mahasiswa</h2></div>
    <div class="page-card-body">
        <div class="table-wrap" style="overflow-x:auto;">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Nama</th><th>Program Studi</th><th>Angkatan</th><th>Kemampuan Terkuat</th><th>Perlu Ditingkatkan</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($students as $student)
                        @php($studentProfile = $student->getAttribute('cpl_profile'))
                        <tr>
                            <td>{{ ($students->firstItem() ?? 1) + $loop->index }}</td>
                            <td>{{ $student->nim }}</td>
                            <td><strong>{{ $student->nama }}</strong></td>
                            <td>{{ $student->prodi?->nama_prodi ?? '-' }}</td>
                            <td>{{ $student->angkatan ?? '-' }}</td>
                            <td>
                                @if($studentProfile['strongest'] ?? null)
                                    {{ $studentProfile['strongest']->label }}<br><small>{{ number_format($studentProfile['strongest']->score, 2) }} · {{ $studentProfile['strongest']->category }}</small>
                                @else - @endif
                            </td>
                            <td>
                                @if($studentProfile['weakest'] ?? null)
                                    {{ $studentProfile['weakest']->label }}<br><small>{{ number_format($studentProfile['weakest']->score, 2) }} · {{ $studentProfile['weakest']->category }}</small>
                                @else - @endif
                            </td>
                            <td><a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route($showRoute, ['mahasiswa' => $student, 'tahun_akademik' => $filters['tahun_akademik'] ?? null]) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:36px;color:#64748b;">Belum ada mahasiswa untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div style="margin-top:18px;">{{ $students->appends(request()->query())->links() }}</div>
        @endif
    </div>
</div>
