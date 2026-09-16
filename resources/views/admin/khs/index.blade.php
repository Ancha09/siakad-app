@extends('layouts.admin')

@section('title','Data KHS')

@section('content')

@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


{{-- =====================================================
     FILTER DATA KHS
===================================================== --}}

<div class="page-card" style="margin-bottom:20px;">

    <div class="page-card-head">
        <h2>🔎 Filter Data KHS</h2>
    </div>

    <div class="page-card-body">

        <form
            action="{{ route('admin.khs') }}"
            method="GET"
        >

            <div class="krs-form-grid">

                {{-- ===================== SEARCH ===================== --}}

                <div class="form-group">

                    <label>Pencarian</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Cari NIM, mahasiswa, atau mata kuliah..."
                    >

                </div>


                {{-- ===================== FAKULTAS ===================== --}}

                <div class="form-group">

                    <label>Fakultas</label>

                    <select
                        name="fakultas_id"
                        class="form-control"
                    >

                        <option value="">
                            Semua Fakultas
                        </option>

                        @foreach($fakultas as $item)

                            <option
                                value="{{ $item->id }}"
                                {{ request('fakultas_id') == $item->id ? 'selected' : '' }}
                            >
                                {{ $item->nama_fakultas }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ===================== PROGRAM STUDI ===================== --}}

                <div class="form-group">

                    <label>Program Studi</label>

                    <select
                        name="prodi_id"
                        class="form-control"
                    >

                        <option value="">
                            Semua Program Studi
                        </option>

                        @foreach($prodis as $prodi)

                            <option
                                value="{{ $prodi->id }}"
                                {{ request('prodi_id') == $prodi->id ? 'selected' : '' }}
                            >
                                {{ $prodi->nama_prodi }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ===================== KELAS ===================== --}}

                <div class="form-group">

                    <label>Kelas</label>

                    <select
                        name="kelas_id"
                        class="form-control"
                    >

                        <option value="">
                            Semua Kelas
                        </option>

                        @foreach($kelases as $kelas)

                            <option
                                value="{{ $kelas->id }}"
                                {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}
                            >

                                {{ $kelas->nama_kelas }}

                                @if($kelas->angkatan)
                                    - Angkatan {{ $kelas->angkatan }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ===================== ANGKATAN ===================== --}}

                <div class="form-group">

                    <label>Angkatan</label>

                    <select
                        name="angkatan"
                        class="form-control"
                    >

                        <option value="">
                            Semua Angkatan
                        </option>

                        @foreach($angkatans as $angkatan)

                            <option
                                value="{{ $angkatan }}"
                                {{ request('angkatan') == $angkatan ? 'selected' : '' }}
                            >
                                {{ $angkatan }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ===================== DOSEN ===================== --}}

                <div class="form-group">

                    <label>Dosen</label>

                    <select
                        name="dosen_id"
                        class="form-control"
                    >

                        <option value="">
                            Semua Dosen
                        </option>

                        @foreach($dosens as $dosen)

                            <option
                                value="{{ $dosen->id }}"
                                {{ request('dosen_id') == $dosen->id ? 'selected' : '' }}
                            >
                                {{ $dosen->nama }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ===================== TAHUN AKADEMIK ===================== --}}

                <div class="form-group">

                    <label>Tahun Akademik</label>

                    <input
                        type="text"
                        name="tahun_akademik"
                        class="form-control"
                        value="{{ request('tahun_akademik') }}"
                        placeholder="Contoh: 2026/2027"
                    >

                </div>


                {{-- ===================== SEMESTER ===================== --}}

                <div class="form-group">

                    <label>Semester</label>

                    <select
                        name="semester_akademik"
                        class="form-control"
                    >

                        <option value="">
                            Semua Semester
                        </option>

                        <option
                            value="Ganjil"
                            {{ request('semester_akademik') == 'Ganjil' ? 'selected' : '' }}
                        >
                            Ganjil
                        </option>

                        <option
                            value="Genap"
                            {{ request('semester_akademik') == 'Genap' ? 'selected' : '' }}
                        >
                            Genap
                        </option>

                    </select>

                </div>

            </div>


            {{-- ===================== BUTTON ===================== --}}

            <div style="margin-top:20px;">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    🔍 Terapkan Filter
                </button>

                <a
                    href="{{ route('admin.khs') }}"
                    class="btn-outline"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>

</div>


{{-- =====================================================
     DATA KHS
===================================================== --}}

<div class="page-card">

    <div class="page-card-head">

        <h2>📑 Data Kartu Hasil Studi (KHS)</h2>

    </div>


    <div class="page-card-body">

        <div class="table-wrap">

            <table>

                <thead>

                <tr>

                    <th>No</th>
                    <th>NIM</th>
                    <th>Mahasiswa</th>
                    <th>Program Studi</th>
                    <th>Mata Kuliah</th>
                    <th>Dosen</th>
                    <th>Nilai Angka</th>
                    <th>Nilai Huruf</th>
                    <th>Bobot</th>
                    <th>Tahun Akademik</th>
                    <th>Semester</th>
                    <th width="120">Aksi</th>

                </tr>

                </thead>


                <tbody>

                @forelse($khs as $item)

                    <tr>

                        {{-- ===================== NO ===================== --}}

                        <td>
                            {{ $khs->firstItem() + $loop->index }}
                        </td>


                        {{-- ===================== NIM ===================== --}}

                        <td>
                            {{ $item->krs->mahasiswa->nim ?? '-' }}
                        </td>


                        {{-- ===================== MAHASISWA ===================== --}}

                        <td>
                            {{ $item->krs->mahasiswa->nama ?? '-' }}
                        </td>


                        {{-- ===================== PROGRAM STUDI ===================== --}}

                        <td>
                            {{ $item->krs?->prodi_efektif?->nama_prodi ?? '-' }}
                        </td>


                        {{-- ===================== MATA KULIAH ===================== --}}

                        <td>

                            {{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}

                            <br>

                            <small style="color:#64748b;">

                                {{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}

                            </small>

                        </td>


                        {{-- ===================== DOSEN ===================== --}}

                        <td>
                            {{ $item->dosen_efektif?->nama ?? '-' }}
                        </td>


                        {{-- ===================== NILAI ANGKA ===================== --}}

                        <td>
                            {{ $item->nilai_angka ?? '-' }}
                        </td>


                        {{-- ===================== NILAI HURUF ===================== --}}

                        <td>

                            <strong>
                                {{ $item->nilai_huruf ?? '-' }}
                            </strong>

                        </td>


                        {{-- ===================== BOBOT ===================== --}}

                        <td>
                            {{ $item->bobot ?? '-' }}
                        </td>


                        {{-- ===================== TAHUN AKADEMIK ===================== --}}

                        <td>
                            {{ $item->tahun_akademik ?? '-' }}
                        </td>


                        {{-- ===================== SEMESTER ===================== --}}

                        <td>
                            {{ $item->semester_akademik ?? '-' }}
                        </td>


                        {{-- ===================== AKSI ===================== --}}

                        <td>

                            <div class="action-buttons">

                                {{-- ADMIN HANYA BOLEH HAPUS --}}

                                <form
                                    action="{{ route('admin.khs.destroy', $item->id) }}"
                                    method="POST"
                                >

                                    @csrf
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                        onclick="return confirm('Yakin ingin menghapus data KHS ini?')"
                                    >
                                        🗑 Hapus
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="12"
                            style="text-align:center;padding:35px"
                        >

                            @if(request()->hasAny([
                                'search',
                                'fakultas_id',
                                'prodi_id',
                                'kelas_id',
                                'angkatan',
                                'dosen_id',
                                'tahun_akademik',
                                'semester_akademik'
                            ]))

                                Data KHS tidak ditemukan
                                berdasarkan filter yang dipilih.

                            @else

                                Belum ada data KHS.

                            @endif

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================== PAGINATION ===================== --}}

        <div style="margin-top:20px">

            {{ $khs->appends(request()->query())->onEachSide(1)->links() }}

        </div>

    </div>

</div>

@endsection
