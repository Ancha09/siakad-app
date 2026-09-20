@extends('layouts.admin')

@section('title', 'IPK CPL')

@push('styles')
<style>
    .ipk-cpl-table thead tr {
        background: #1d4ed8;
    }

    .ipk-cpl-table thead th {
        color: #ffffff;
        border-bottom-color: #1e40af;
    }

    .ipk-cpl-table tbody tr:nth-child(even) td {
        background: #f8fbff;
    }

    .ipk-cpl-table tbody tr:hover td {
        background: #eff6ff;
    }
</style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <div>
            <h2 class="icon-heading"><x-layout-icon name="chart" /> IPK CPL</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">Rekap rata-rata nilai mata kuliah berdasarkan data nilai mahasiswa.</p>
        </div>
        <a href="{{ route('admin.ipk-cpl.excel', request()->except('page')) }}" class="btn-primary icon-button">
            <x-layout-icon name="download" /> Download Excel
        </a>
    </div>

    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.ipk-cpl.index') }}" style="background:#f8fafc;padding:18px;border-radius:10px;margin-bottom:22px;">
            <div class="krs-form-grid">
                <div class="form-group">
                    <label for="tahun-akademik">Tahun Akademik</label>
                    <select id="tahun-akademik" name="tahun_akademik" class="form-control">
                        <option value="">Semua Tahun</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester-akademik">Semester Akademik</label>
                    <select id="semester-akademik" name="semester_akademik" class="form-control">
                        <option value="">Semua Semester</option>
                        @foreach(['Ganjil', 'Genap'] as $semester)
                            <option value="{{ $semester }}" @selected(request('semester_akademik') === $semester)>{{ $semester }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="prodi">Program Studi</label>
                    <select id="prodi" name="prodi_id" class="form-control">
                        <option value="">Semua Program Studi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->jenjang }} {{ $prodi->nama_prodi }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="semester-angka">Semester Angka Mata Kuliah</label>
                    <select id="semester-angka" name="semester_angka" class="form-control">
                        <option value="">Semua semester angka</option>
                        @foreach($semesterAngkas as $semesterAngka)
                            <option value="{{ $semesterAngka }}" @selected((string) request('semester_angka') === (string) $semesterAngka)>Semester {{ $semesterAngka }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="angkatan">Angkatan</label>
                    <select id="angkatan" name="angkatan" class="form-control">
                        <option value="">Semua Angkatan</option>
                        @foreach($angkatans as $angkatan)
                            <option value="{{ $angkatan }}" @selected((string) request('angkatan') === (string) $angkatan)>{{ $angkatan }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="mata-kuliah">Mata Kuliah</label>
                    <select id="mata-kuliah" name="mata_kuliah_id" class="form-control">
                        <option value="">Semua Mata Kuliah</option>
                        @foreach($mataKuliahs as $mataKuliah)
                            <option value="{{ $mataKuliah->id }}" @selected((string) request('mata_kuliah_id') === (string) $mataKuliah->id)>{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="dosen">Dosen Pengampu</label>
                    <select id="dosen" name="dosen_id" class="form-control">
                        <option value="">Semua Dosen</option>
                        @foreach($dosens as $dosen)
                            <option value="{{ $dosen->id }}" @selected((string) request('dosen_id') === (string) $dosen->id)>{{ $dosen->nama }}{{ $dosen->nidn ? ' - '.$dosen->nidn : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;">
                <button type="submit" class="btn-primary icon-button"><x-layout-icon name="search" /> Tampilkan</button>
                <a href="{{ route('admin.ipk-cpl.index') }}" class="btn-outline">Reset Filter</a>
            </div>
        </form>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin-bottom:22px;">
            <div style="padding:17px;border:1px solid #dbeafe;background:#eff6ff;border-radius:10px;">
                <small style="color:#64748b;">Total Mata Kuliah</small>
                <div style="font-size:26px;font-weight:700;color:#1d4ed8;">{{ $summary['total_mata_kuliah'] }}</div>
            </div>
            <div style="padding:17px;border:1px solid #dcfce7;background:#f0fdf4;border-radius:10px;">
                <small style="color:#64748b;">Total Data Nilai</small>
                <div style="font-size:26px;font-weight:700;color:#15803d;">{{ $summary['total_data_nilai'] }}</div>
            </div>
            <div style="padding:17px;border:1px solid #ede9fe;background:#f5f3ff;border-radius:10px;">
                <small style="color:#64748b;">Rata-rata Keseluruhan</small>
                <div style="font-size:26px;font-weight:700;color:#6d28d9;">{{ $summary['rata_rata_keseluruhan'] === null ? '-' : number_format($summary['rata_rata_keseluruhan'], 2) }}</div>
            </div>
        </div>

        <div style="padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:8px;margin-bottom:15px;font-size:13px;">
            IPK CPL adalah rekap evaluasi mata kuliah dan tidak mengubah IPK mahasiswa. Relasi CPL khusus belum tersedia dan dapat ditambahkan kemudian.
        </div>

        <div class="table-wrap" style="overflow-x:auto;">
            <table class="ipk-cpl-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun</th>
                        <th>Semester</th>
                        <th>Program Studi</th>
                        <th>Semester Angka</th>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Mahasiswa</th>
                        <th>Rata Nilai</th>
                        <th>Rata Bobot</th>
                        <th>A</th>
                        <th>B</th>
                        <th>C</th>
                        <th>D</th>
                        <th>E</th>
                        <th>Kelulusan</th>
                        <th>Keterangan</th>
                        <th style="width:145px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $rows->firstItem() + $loop->index }}</td>
                            <td>{{ $row->tahun_akademik }}</td>
                            <td>{{ $row->semester_akademik }}</td>
                            <td>{{ $row->prodi }}</td>
                            <td>{{ $row->semester_angka ? 'Semester '.$row->semester_angka : '-' }}</td>
                            <td><strong>{{ $row->kode_mata_kuliah }}</strong></td>
                            <td>{{ $row->nama_mata_kuliah }}</td>
                            <td>{{ $row->dosen }}</td>
                            <td>{{ $row->jumlah_mahasiswa }}</td>
                            <td>{{ $row->rata_nilai === null ? '-' : number_format($row->rata_nilai, 2) }}</td>
                            <td>{{ $row->rata_bobot === null ? '-' : number_format($row->rata_bobot, 2) }}</td>
                            <td>{{ $row->nilai_a }}</td>
                            <td>{{ $row->nilai_b }}</td>
                            <td>{{ $row->nilai_c }}</td>
                            <td>{{ $row->nilai_d }}</td>
                            <td>{{ $row->nilai_e }}</td>
                            <td>{{ $row->persentase_lulus === null ? '-' : number_format($row->persentase_lulus, 2).'%' }}</td>
                            <td>{{ $row->keterangan }}</td>
                            <td>
                                <a class="btn-primary" style="display:inline-block;padding:7px 11px;white-space:nowrap;" href="{{ route('admin.ipk-cpl.show', [
                                    'mataKuliah' => $row->mata_kuliah_id,
                                    'tahun_akademik' => $row->tahun_akademik,
                                    'semester_akademik' => $row->semester_akademik,
                                    'prodi_id' => $row->prodi_id,
                                    'semester_angka' => $row->semester_angka,
                                    'angkatan' => request('angkatan'),
                                    'dosen_id' => request('dosen_id'),
                                    'return_url' => request()->fullUrl(),
                                ]) }}">Detail Mahasiswa</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="19" style="text-align:center;padding:35px;color:#64748b;">Belum ada data untuk filter yang dipilih.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div style="margin-top:18px;">{{ $rows->appends(request()->query())->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
