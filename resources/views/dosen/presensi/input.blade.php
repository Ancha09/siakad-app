@extends('layouts.dosen')

@section('title', 'Presensi Mahasiswa')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>📋 Presensi Mahasiswa</h2>
    </div>

    {{-- ===================== ALERT ===================== --}}

    @if(session('success'))

        <div class="alert alert-success" style="margin:15px;">
            {{ session('success') }}
        </div>

    @endif

    @if(session('error'))

        <div
            class="alert"
            style="
                margin:15px;
                padding:12px 15px;
                border-radius:8px;
                background:#fee2e2;
                color:#991b1b;
            "
        >
            {{ session('error') }}
        </div>

    @endif

    @if($errors->any())

        <div
            class="alert"
            style="
                margin:15px;
                padding:12px 15px;
                border-radius:8px;
                background:#fee2e2;
                color:#991b1b;
            "
        >

            <strong>Terdapat kesalahan:</strong>

            <ul style="margin:8px 0 0 20px;">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="page-card-body">

        {{-- =====================================================
             INFORMASI JADWAL
        ====================================================== --}}

        <div
            style="
                background:#f8fafc;
                border:1px solid #e2e8f0;
                border-radius:10px;
                padding:18px;
                margin-bottom:25px;
            "
        >

            <p>
                <strong>Mata Kuliah:</strong>
                {{ $jadwal->mataKuliah->nama_mk ?? '-' }}
            </p>

            <p>
                <strong>Kode:</strong>
                {{ $jadwal->mataKuliah->kode_mk ?? '-' }}
            </p>

            <p>
                <strong>Kelas:</strong>
                {{ $jadwal->kelas->nama_kelas ?? $jadwal->kelas ?? '-' }}
            </p>

            <p>
                <strong>Jadwal:</strong>
                {{ $jadwal->hari }}
                |
                {{ $jadwal->jam_mulai }}
                -
                {{ $jadwal->jam_selesai }}
            </p>

            <p style="margin-bottom:0;">
                <strong>Ruangan:</strong>
                {{ $jadwal->ruangan->nama_ruangan ?? '-' }}
            </p>

        </div>


        {{-- =====================================================
             PILIH PERTEMUAN
        ====================================================== --}}

        <div
            style="
                background:#f8fafc;
                border:1px solid #e2e8f0;
                border-radius:10px;
                padding:18px;
                margin-bottom:25px;
            "
        >

            <form
                action="{{ route('dosen.presensi.show', $jadwal->id) }}"
                method="GET"
            >

                <div class="form-group">

                    <label>
                        <strong>Pilih Pertemuan</strong>
                    </label>

                    <select
                        name="pertemuan"
                        class="form-control"
                        onchange="this.form.submit()"
                    >

                        @for($i = 1; $i <= 16; $i++)

                            <option
                                value="{{ $i }}"
                                {{ $pertemuanDipilih == $i ? 'selected' : '' }}
                            >

                                Pertemuan {{ $i }}

                                @if(in_array($i, $pertemuanSudahAda))
                                    - Sudah dibuat
                                @else
                                    - Belum dibuat
                                @endif

                            </option>

                        @endfor

                    </select>

                </div>

            </form>

        </div>


        {{-- =====================================================
             PERTEMUAN BELUM DIBUAT
        ====================================================== --}}

        @if(!$pertemuanAktif)

            <div
                style="
                    background:#eff6ff;
                    border:1px solid #bfdbfe;
                    border-radius:10px;
                    padding:18px;
                    margin-bottom:25px;
                    color:#1e40af;
                "
            >

                <strong style="font-size:16px;">
                    📝 Buat Sesi Presensi
                </strong>

                <div style="margin-top:6px;">

                    Pertemuan
                    <strong>
                        {{ $pertemuanDipilih }}
                    </strong>

                    belum dibuat.

                    Silakan isi data pertemuan dan presensi mahasiswa.

                </div>

            </div>


            <form
                action="{{ route('dosen.presensi.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                <input
                    type="hidden"
                    name="jadwal_id"
                    value="{{ $jadwal->id }}"
                >

                <input
                    type="hidden"
                    name="pertemuan"
                    value="{{ $pertemuanDipilih }}"
                >


                {{-- ===================== PERTEMUAN ===================== --}}

                <div
                    class="krs-form-grid"
                    style="margin-bottom:20px;"
                >

                    <div class="form-group">

                        <label>
                            Pertemuan
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="Pertemuan {{ $pertemuanDipilih }}"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Tanggal
                        </label>

                        <input
                            type="date"
                            name="tanggal"
                            class="form-control"
                            value="{{ old('tanggal', date('Y-m-d')) }}"
                            required
                        >

                    </div>

                </div>


                <div class="form-group" style="margin-bottom:20px;">
                    <label for="materi_kuliah">
                        Materi Kuliah <span style="color:#dc2626;" aria-hidden="true">*</span>
                    </label>

                    <textarea
                        id="materi_kuliah"
                        name="materi_kuliah"
                        class="form-control"
                        rows="3"
                        maxlength="2000"
                        required
                        placeholder="Tuliskan materi yang dibahas pada pertemuan ini"
                    >{{ old('materi_kuliah') }}</textarea>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label for="keterangan">
                        Keterangan <span style="color:#dc2626;" aria-hidden="true">*</span>
                    </label>

                    <textarea
                        id="keterangan"
                        name="keterangan"
                        class="form-control"
                        rows="3"
                        maxlength="2000"
                        required
                        placeholder="Tuliskan keterangan pelaksanaan perkuliahan"
                    >{{ old('keterangan') }}</textarea>
                </div>


                {{-- ===================== FOTO ===================== --}}

                <div
                    class="form-group"
                    style="margin-bottom:20px;"
                >

                    <label>
                        Foto Absen
                        <span style="color:#64748b;">
                            (Wajib)
                        </span>
                    </label>

                    <input
                        type="file"
                        name="foto"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                        required
                    >

                    <small
                        style="
                            display:block;
                            margin-top:6px;
                            color:#64748b;
                        "
                    >
                        JPG, JPEG, PNG. Maksimal 2 MB.
                    </small>

                </div>


                {{-- ===================== MATERI ===================== --}}

                <div
                    class="form-group"
                    style="margin-bottom:25px;"
                >

                    <label>
                        File Materi
                        <span style="color:#64748b;">
                            (Opsional)
                        </span>
                    </label>

                    <input
                        type="file"
                        name="materi"
                        class="form-control"
                        accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx"
                    >

                    <small
                        style="
                            display:block;
                            margin-top:6px;
                            color:#64748b;
                        "
                    >
                        PDF, PPT, PPTX, DOC, DOCX, XLS, XLSX.
                    </small>

                </div>


                {{-- ===================== MAHASISWA ===================== --}}

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>
                                <th>NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Status Presensi</th>
                            </tr>

                        </thead>

                        <tbody>

                        @forelse($krs as $item)

                            <tr>

                                <td>
                                    {{ $item->mahasiswa->nim ?? '-' }}
                                </td>

                                <td>
                                    {{ $item->mahasiswa->nama ?? '-' }}
                                </td>

                                <td>

                                    <input
                                        type="hidden"
                                        name="krs_id[]"
                                        value="{{ $item->id }}"
                                    >

                                    <select
                                        name="status[]"
                                        class="form-control"
                                    >

                                        <option value="Hadir">
                                            Hadir
                                        </option>

                                        <option value="Izin">
                                            Izin
                                        </option>

                                        <option value="Sakit">
                                            Sakit
                                        </option>

                                        <option value="Alpha">
                                            Alpha
                                        </option>

                                    </select>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="3"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                    "
                                >
                                    Belum ada mahasiswa mengambil
                                    mata kuliah ini.
                                </td>

                            </tr>

                        @endforelse

                        </tbody>

                    </table>

                </div>


                <br>


                <button
                    type="submit"
                    class="btn-primary"
                >
                    💾 Simpan Pertemuan {{ $pertemuanDipilih }}
                </button>


                <a
                    href="{{ route('dosen.presensi') }}"
                    class="btn-outline"
                >
                    Kembali
                </a>

            </form>


        @else


            {{-- =====================================================
                 PERTEMUAN SUDAH DIBUAT
            ====================================================== --}}

            <div
                style="
                    background:#ecfdf5;
                    border:1px solid #a7f3d0;
                    border-radius:10px;
                    padding:18px;
                    margin-bottom:25px;
                    color:#065f46;
                "
            >

                <div
                    style="
                        font-size:16px;
                        font-weight:700;
                        margin-bottom:8px;
                    "
                >
                    🔒 Sesi Presensi Sudah Dibuat
                </div>

                <div>

                    Pertemuan:

                    <strong>
                        {{ $pertemuanAktif->pertemuan }}
                    </strong>

                    |

                    Tanggal:

                    <strong>
                        {{ \Carbon\Carbon::parse($pertemuanAktif->tanggal)->format('d-m-Y') }}
                    </strong>

                </div>

                <div
                    style="
                        margin-top:8px;
                        font-size:13px;
                    "
                >

                    Data sesi pertemuan telah dibuat.

                    Nomor pertemuan, tanggal, foto, dan materi
                    tidak dapat diubah.

                    Status presensi mahasiswa tetap dapat diedit.

                </div>

            </div>


            {{-- =====================================================
                 DOKUMENTASI
            ====================================================== --}}

            <div
                style="
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    margin-bottom:25px;
                "
            >

                <h3 style="margin-top:0;">

                    📎 Dokumentasi Pertemuan
                    {{ $pertemuanAktif->pertemuan }}

                </h3>


                <div style="margin-bottom:12px;">
                    <strong>Materi Kuliah:</strong>
                    <div style="margin-top:5px;white-space:pre-line;">{{ $pertemuanAktif->materi_kuliah ?: '-' }}</div>
                </div>

                <div style="margin-bottom:12px;">
                    <strong>Keterangan:</strong>
                    <div style="margin-top:5px;white-space:pre-line;">{{ $pertemuanAktif->keterangan ?: '-' }}</div>
                </div>


                <div style="margin-bottom:12px;">

                    <strong>
                        Foto:
                    </strong>

                    @if($pertemuanAktif->foto)

                        <a
                            href="{{ asset('storage/' . $pertemuanAktif->foto) }}"
                            target="_blank"
                            class="btn-outline"
                            style="margin-left:10px;"
                        >
                            📷 Lihat Foto
                        </a>

                    @else

                        <span style="color:#64748b;">
                            Tidak ada foto.
                        </span>

                    @endif

                </div>


                <div>

                    <strong>
                        File Materi:
                    </strong>

                    @if($pertemuanAktif->materi)

                        <a
                            href="{{ asset('storage/' . $pertemuanAktif->materi) }}"
                            target="_blank"
                            class="btn-outline"
                            style="margin-left:10px;"
                        >
                            📚 Lihat Materi
                        </a>

                    @else

                        <span style="color:#64748b;">
                            Tidak ada materi.
                        </span>

                    @endif

                </div>

            </div>


            {{-- =====================================================
                 EDIT STATUS MAHASISWA
            ====================================================== --}}

            <div
                style="
                    background:#fff;
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    margin-bottom:25px;
                "
            >

                <h3 style="margin-top:0;">
                    ✏️ Edit Status Presensi Mahasiswa
                </h3>


                <p
                    style="
                        color:#64748b;
                        margin-bottom:20px;
                    "
                >

                    Mengubah status mahasiswa pada

                    <strong>
                        Pertemuan
                        {{ $pertemuanAktif->pertemuan }}
                    </strong>.

                    Data sesi pertemuan tetap terkunci.

                </p>


                <form
                    action="{{ route('dosen.presensi.store') }}"
                    method="POST"
                >

                    @csrf

                    <input
                        type="hidden"
                        name="jadwal_id"
                        value="{{ $jadwal->id }}"
                    >

                    <input
                        type="hidden"
                        name="pertemuan"
                        value="{{ $pertemuanAktif->pertemuan }}"
                    >

                    <input
                        type="hidden"
                        name="tanggal"
                        value="{{ $pertemuanAktif->tanggal }}"
                    >


                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>
                                    <th>NIM</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Status Presensi</th>
                                </tr>

                            </thead>


                            <tbody>

                            @forelse($krs as $item)

                                @php

                                    $presensi =
                                        $item->presensis
                                            ->where(
                                                'pertemuan',
                                                $pertemuanAktif->pertemuan
                                            )
                                            ->first();

                                @endphp


                                <tr>

                                    <td>
                                        {{ $item->mahasiswa->nim ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->mahasiswa->nama ?? '-' }}
                                    </td>

                                    <td>

                                        <input
                                            type="hidden"
                                            name="krs_id[]"
                                            value="{{ $item->id }}"
                                        >


                                        <select
                                            name="status[]"
                                            class="form-control"
                                        >

                                            <option
                                                value="Hadir"
                                                {{ ($presensi->status ?? 'Hadir') == 'Hadir' ? 'selected' : '' }}
                                            >
                                                Hadir
                                            </option>

                                            <option
                                                value="Izin"
                                                {{ ($presensi->status ?? '') == 'Izin' ? 'selected' : '' }}
                                            >
                                                Izin
                                            </option>

                                            <option
                                                value="Sakit"
                                                {{ ($presensi->status ?? '') == 'Sakit' ? 'selected' : '' }}
                                            >
                                                Sakit
                                            </option>

                                            <option
                                                value="Alpha"
                                                {{ ($presensi->status ?? '') == 'Alpha' ? 'selected' : '' }}
                                            >
                                                Alpha
                                            </option>

                                        </select>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="3"
                                        style="
                                            text-align:center;
                                            padding:30px;
                                        "
                                    >
                                        Belum ada mahasiswa.
                                    </td>

                                </tr>

                            @endforelse

                            </tbody>

                        </table>

                    </div>


                    <br>


                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        💾 Simpan Perubahan Status
                    </button>


                    <a
                        href="{{ route('dosen.presensi') }}"
                        class="btn-outline"
                    >
                        Kembali
                    </a>

                </form>

            </div>

        @endif


        {{-- =====================================================
             REKAP PRESENSI MAHASISWA
        ====================================================== --}}

        <hr style="margin:30px 0;">


        <h3>
            📊 Rekap Presensi Mahasiswa
        </h3>


        <div class="table-wrap">

            <table>

                <thead>

                    <tr>
                        <th>NIM</th>
                        <th>Nama Mahasiswa</th>
                        <th>Hadir</th>
                        <th>Izin</th>
                        <th>Sakit</th>
                        <th>Alpha</th>
                        <th>% Kehadiran</th>
                    </tr>

                </thead>


                <tbody>

                @forelse($krs as $item)

                    <tr>

                        <td>
                            {{ $item->mahasiswa->nim ?? '-' }}
                        </td>

                        <td>
                            {{ $item->mahasiswa->nama ?? '-' }}
                        </td>

                        <td>
                            {{ $item->hadir }}
                        </td>

                        <td>
                            {{ $item->izin }}
                        </td>

                        <td>
                            {{ $item->sakit }}
                        </td>

                        <td>
                            {{ $item->alpha }}
                        </td>

                        <td>

                            @if($item->persentase >= 75)

                                <span class="badge-success">
                                    {{ $item->persentase }}%
                                </span>

                            @else

                                <span class="badge-warning">
                                    {{ $item->persentase }}%
                                </span>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td
                            colspan="7"
                            style="
                                text-align:center;
                                padding:30px;
                            "
                        >
                            Belum ada data presensi.
                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
