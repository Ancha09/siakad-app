@extends('layouts.admin')

@section('title','Data Jadwal')

@section('content')

@if(session('success'))

    <div class="alert-success">
        ✅ {{ session('success') }}
    </div>

@endif


<div class="page-card">

    {{-- ===================== HEADER ===================== --}}

    <div class="page-card-head">

        <h2>🗓️ Data Jadwal Kuliah</h2>

        <a
            href="{{ route('admin.jadwal.create') }}"
            class="btn-primary"
        >
            ➕ Tambah Jadwal
        </a>

    </div>


    <div class="page-card-body">


        {{-- ===================== FILTER ===================== --}}

        <form
            method="GET"
            action="{{ route('admin.jadwal') }}"
            style="
                margin-bottom:25px;
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            "
        >

            <div
                style="
                    display:grid;
                    grid-template-columns:repeat(4, minmax(0, 1fr));
                    gap:15px;
                    align-items:end;
                "
            >


                {{-- ================= SEARCH ================= --}}

                <div
                    class="form-group"
                    style="grid-column:span 2;"
                >

                    <label>Cari</label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Mata kuliah / dosen / kelas..."
                    >

                </div>


                {{-- ================= FAKULTAS ================= --}}

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


                {{-- ================= PRODI ================= --}}

                <div class="form-group">

                    <label>Program Studi</label>

                    <select
                        name="prodi_id"
                        class="form-control"
                    >

                        <option value="">
                            Semua Prodi
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


                {{-- ================= DOSEN ================= --}}

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


                {{-- ================= KELAS ================= --}}

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

                                -
                                
                                {{ $kelas->prodi->nama_prodi ?? '-' }}

                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ================= HARI ================= --}}

                <div class="form-group">

                    <label>Hari</label>

                    <select
                        name="hari"
                        class="form-control"
                    >

                        <option value="">
                            Semua Hari
                        </option>

                        @foreach([
                            'Senin',
                            'Selasa',
                            'Rabu',
                            'Kamis',
                            'Jumat',
                            'Sabtu'
                        ] as $hari)

                            <option
                                value="{{ $hari }}"
                                {{ request('hari') == $hari ? 'selected' : '' }}
                            >
                                {{ $hari }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ================= SEMESTER ================= --}}

                <div class="form-group">

                    <label>Semester</label>

                    <select
                        name="semester_akademik"
                        class="form-control"
                    >

                        <option value="">
                            Semua Semester
                        </option>

                        @for($i = 1; $i <= 14; $i++)

                            <option
                                value="{{ $i }}"
                                {{ request('semester_akademik') == $i ? 'selected' : '' }}
                            >
                                Semester {{ $i }}
                            </option>

                        @endfor

                    </select>

                </div>


                {{-- ================= BUTTON ================= --}}

                <div>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        🔍 Filter
                    </button>

                    <a
                        href="{{ route('admin.jadwal') }}"
                        class="btn-outline"
                        style="margin-left:5px;"
                    >
                        Reset
                    </a>

                </div>


            </div>

        </form>


        {{-- ===================== TABLE ===================== --}}

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>No</th>

                        <th>Mata Kuliah</th>

                        <th>Dosen</th>

                        <th>Kelas</th>

                        <th>Ruangan</th>

                        <th>Hari</th>

                        <th>Jam</th>

                        <th>Aksi</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($jadwals as $jadwal)

                        <tr>


                            {{-- NO --}}

                            <td>

                                {{ $jadwals->firstItem() + $loop->index }}

                            </td>


                            {{-- MATA KULIAH --}}

                            <td>

                                <strong>
                                    {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
                                </strong>

                                <br>

                                <small style="color:#64748b;">

                                    {{ $jadwal->mataKuliah->kode_mk ?? '-' }}

                                </small>

                            </td>


                            {{-- DOSEN --}}

                            <td>

                                {{ $jadwal->dosen->nama ?? '-' }}

                            </td>


                            {{-- KELAS --}}

                            <td>

                                <strong>

                                    {{ $jadwal->kelas->nama_kelas ?? '-' }}

                                </strong>


                                @if($jadwal->kelas)

                                    <br>

                                    <small style="color:#64748b;">

                                        {{ $jadwal->kelas->prodi->nama_prodi ?? '-' }}

                                    </small>

                                @endif

                            </td>


                            {{-- RUANGAN --}}

                            <td>

                                {{ $jadwal->ruangan->nama_ruangan ?? '-' }}

                            </td>


                            {{-- HARI --}}

                            <td>

                                <span class="badge badge-blue">

                                    {{ $jadwal->hari }}

                                </span>

                            </td>


                            {{-- JAM --}}

                            <td>

                                {{ $jadwal->jam_mulai }}

                                -

                                {{ $jadwal->jam_selesai }}

                            </td>


                            {{-- AKSI --}}

                            <td>

                                <div class="action-buttons">


                                    <a
                                        href="{{ route('admin.jadwal.edit', $jadwal->id) }}"
                                        class="btn-edit"
                                    >

                                        ✏ Edit

                                    </a>


                                    <form
                                        action="{{ route('admin.jadwal.destroy', $jadwal->id) }}"
                                        method="POST"
                                    >

                                        @csrf

                                        @method('DELETE')


                                        <button
                                            type="submit"
                                            class="btn-delete"
                                            onclick="return confirm('Yakin ingin menghapus jadwal ini?')"
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
                                colspan="8"
                                style="
                                    text-align:center;
                                    padding:40px;
                                "
                            >

                                📅

                                <br>

                                <strong>
                                    Tidak ada data jadwal
                                </strong>

                                <br>

                                <span style="color:#64748b;">
                                    Coba ubah filter atau tambahkan jadwal baru.
                                </span>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================== PAGINATION ===================== --}}

        <div style="margin-top:20px;">

            {{ $jadwals->links() }}

        </div>


    </div>

</div>

@endsection