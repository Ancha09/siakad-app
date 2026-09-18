@extends('layouts.mahasiswa')

@section('title', 'KRS Mahasiswa')

@section('content')

<div class="page-card">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-card-head">

        <div>
            <h2>📝 Kartu Rencana Studi (KRS)</h2>

            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">
                {{ $mahasiswa->nim }} -
                {{ $mahasiswa->nama }}

                @if($mahasiswa->prodi)
                    | {{ $mahasiswa->prodi->nama_prodi }}
                @endif
            </p>
        </div>

        @if($periodeKrs && $aksesKrsDibuka)

            <span class="badge badge-green">
                🟢 KRS Dibuka
            </span>

        @elseif($periodeKrs)

            <span class="badge" style="background:#fef3c7;color:#92400e;">
                Akses Belum Dibuka
            </span>

        @else

            <span class="badge badge-gray">
                🔴 KRS Ditutup
            </span>

        @endif

    </div>


    <div class="page-card-body">


        {{-- =====================================================
             SUCCESS
        ====================================================== --}}

        @if(session('success'))

            <div
                class="alert-success"
                style="margin-bottom:20px;"
            >
                ✅ {{ session('success') }}
            </div>

        @endif


        {{-- =====================================================
             ERROR
        ====================================================== --}}

        @if(session('error'))

            <div
                style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >
                ❌ {{ session('error') }}
            </div>

        @endif


        {{-- =====================================================
             VALIDATION ERROR
        ====================================================== --}}

        @if($errors->any())

            <div
                style="
                    background:#fee2e2;
                    color:#991b1b;
                    padding:15px;
                    border-radius:8px;
                    margin-bottom:20px;
                "
            >

                <strong>Terjadi kesalahan:</strong>

                <ul style="margin:8px 0 0 20px;">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- =====================================================
             INFORMASI PERIODE KRS
        ====================================================== --}}

        @if($periodeKrs)

            <div
                class="info-alert"
                style="
                    margin-bottom:25px;
                    display:flex;
                    align-items:flex-start;
                    gap:12px;
                "
            >

                <span style="font-size:22px;">
                    📅
                </span>

                <div>

                    <strong>
                        Periode KRS {{ $periodeKrs->tahun_akademik }}
                        - {{ $periodeKrs->semester }}
                    </strong>

                    <div
                        style="
                            margin-top:6px;
                            color:#475569;
                            font-size:13px;
                        "
                    >

                        Pengisian KRS dibuka:

                        <strong>
                            {{ $periodeKrs->tanggal_mulai->format('d/m/Y H:i') }}
                        </strong>

                        sampai

                        <strong>
                            {{ $periodeKrs->tanggal_selesai->format('d/m/Y H:i') }}
                        </strong>

                    </div>

                    @if($periodeKrs->keterangan)

                        <div
                            style="
                                margin-top:6px;
                                color:#64748b;
                                font-size:13px;
                            "
                        >

                            {{ $periodeKrs->keterangan }}

                        </div>

                    @endif

                </div>

            </div>

            @if(!$aksesKrsDibuka)
                <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:16px;border-radius:8px;margin-bottom:25px;">
                    <strong>{{ $pesanAksesKrs }}</strong>
                </div>
            @endif

        @else

            <div
                style="
                    background:#fef2f2;
                    border:1px solid #fecaca;
                    color:#991b1b;
                    padding:16px;
                    border-radius:8px;
                    margin-bottom:25px;
                "
            >

                <strong>
                    🔴 {{ $pesanAksesKrs }}
                </strong>

                <div
                    style="
                        margin-top:5px;
                        color:#7f1d1d;
                        font-size:13px;
                    "
                >

                    Saat ini tidak terdapat periode KRS yang sedang dibuka.
                    Silakan menunggu informasi dari pihak akademik.

                </div>

            </div>

        @endif


        {{-- =====================================================
             RINGKASAN KRS
        ====================================================== --}}

        <div
            style="
                display:grid;
                grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
                gap:15px;
                margin-bottom:25px;
            "
        >

            {{-- IPK KUMULATIF --}}

            <div
                style="
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    background:#f8fafc;
                "
            >
                <div style="color:#64748b;font-size:13px;">
                    IPK Kumulatif
                </div>

                <div style="font-size:26px;font-weight:700;margin-top:5px;">
                    @if($ipkTerlihat !== null)
                        {{ number_format($ipkTerlihat, 2) }}
                    @elseif($jumlahKuesionerTertunda > 0)
                        <span style="font-size:14px;font-weight:500;">{{ \App\Services\MahasiswaNilaiService::LOCKED_PLACEHOLDER }}</span>
                    @else
                        -
                    @endif
                </div>

                @if($jumlahKuesionerTertunda > 0)
                    <small style="display:block;margin-top:7px;color:#92400e;">
                        Selesaikan {{ $jumlahKuesionerTertunda }} kuesioner untuk membuka IPK.
                    </small>
                    <a href="{{ route('mahasiswa.kuesioner') }}" class="btn-outline" style="display:inline-block;margin-top:10px;padding:6px 10px;font-size:11px;">
                        Isi Kuesioner
                    </a>
                @elseif($jumlahNilai === 0)
                    <small style="display:block;margin-top:7px;color:#64748b;">Belum ada nilai yang diterbitkan.</small>
                @endif
            </div>


            {{-- TOTAL SKS --}}

            <div
                style="
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    background:#f8fafc;
                "
            >

                <div
                    style="
                        color:#64748b;
                        font-size:13px;
                    "
                >
                    Total SKS Diambil
                </div>

                <div
                    style="
                        font-size:26px;
                        font-weight:700;
                        margin-top:5px;
                    "
                >
                    {{ $totalSks }}

                    <span style="font-size:14px;font-weight:400;">
                        SKS
                    </span>
                </div>

            </div>


            {{-- MAKSIMAL SKS --}}

            <div
                style="
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    background:#f8fafc;
                "
            >

                <div
                    style="
                        color:#64748b;
                        font-size:13px;
                    "
                >
                    Maksimal SKS
                </div>

                <div
                    style="
                        font-size:26px;
                        font-weight:700;
                        margin-top:5px;
                    "
                >

                    {{ $batasSks }}

                    <span style="font-size:14px;font-weight:400;">
                        SKS
                    </span>

                </div>

            </div>


            {{-- SISA SKS --}}

            <div
                style="
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    background:#f8fafc;
                "
            >

                <div
                    style="
                        color:#64748b;
                        font-size:13px;
                    "
                >
                    Sisa SKS
                </div>

                <div
                    style="
                        font-size:26px;
                        font-weight:700;
                        margin-top:5px;
                    "
                >

                    {{ $sisaSks }}

                    <span style="font-size:14px;font-weight:400;">
                        SKS
                    </span>

                </div>

            </div>


            {{-- JUMLAH MATA KULIAH --}}

            <div
                style="
                    border:1px solid #e2e8f0;
                    border-radius:10px;
                    padding:18px;
                    background:#f8fafc;
                "
            >

                <div
                    style="
                        color:#64748b;
                        font-size:13px;
                    "
                >
                    Mata Kuliah Diambil
                </div>

                <div
                    style="
                        font-size:26px;
                        font-weight:700;
                        margin-top:5px;
                    "
                >

                    {{ $krs->count() }}

                    <span style="font-size:14px;font-weight:400;">
                        MK
                    </span>

                </div>

            </div>

        </div>


        {{-- =====================================================
             KRS YANG SUDAH DIAMBIL
        ====================================================== --}}

        <div class="page-card" style="margin-bottom:25px;">

            <div class="page-card-head">

                <h3 style="margin:0;">
                    📋 KRS Saya
                </h3>

                @if($periodeKartuKrs->isNotEmpty())
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @foreach($periodeKartuKrs as $periodeKartu)
                            <a href="{{ route('mahasiswa.krs.pdf', $periodeKartu) }}"
                               class="btn-outline"
                               style="display:inline-block;padding:7px 11px;font-size:12px;">
                                PDF {{ $periodeKartu['tahun_akademik'] }} {{ $periodeKartu['semester_akademik'] }}
                            </a>
                        @endforeach
                    </div>
                @endif

            </div>


            <div class="page-card-body">

                <div
                    class="table-wrap"
                    style="overflow-x:auto;"
                >

                    <table
                        style="
                            width:100%;
                            table-layout:auto;
                        "
                    >

                        <thead>

                            <tr>

                                <th style="width:50px;">
                                    No
                                </th>

                                <th>
                                    Kode MK
                                </th>

                                <th>
                                    Mata Kuliah
                                </th>

                                <th style="width:60px;">
                                    SKS
                                </th>

                                <th>
                                    Dosen
                                </th>

                                <th>
                                    Ruangan
                                </th>

                                <th>
                                    Jadwal
                                </th>

                                <th
                                    style="
                                        width:155px;
                                        min-width:155px;
                                        text-align:center;
                                        white-space:nowrap;
                                    "
                                >
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($krs as $item)

                                <tr>

                                    {{-- NO --}}

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>


                                    {{-- KODE MK --}}

                                    <td>
                                        {{ $item->jadwal->mataKuliah->kode_mk ?? '-' }}
                                    </td>


                                    {{-- MATA KULIAH --}}

                                    <td>

                                        <strong>
                                            {{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}
                                        </strong>

                                    </td>


                                    {{-- SKS --}}

                                    <td>
                                        {{ $item->jadwal->mataKuliah->sks ?? 0 }}
                                    </td>


                                    {{-- DOSEN --}}

                                    <td>
                                        {{ $item->jadwal->dosen->nama ?? '-' }}
                                    </td>


                                    {{-- RUANGAN --}}

                                    <td>
                                        {{ $item->jadwal->ruangan->nama_ruangan ?? '-' }}
                                    </td>


                                    {{-- JADWAL --}}

                                    <td>

                                        {{ $item->jadwal->hari }}

                                        <br>

                                        <small style="color:#64748b;">

                                            {{ $item->jadwal->jam_mulai }}
                                            -
                                            {{ $item->jadwal->jam_selesai }}

                                        </small>

                                    </td>


                                    {{-- STATUS --}}

                                    <td
                                        style="
                                            width:155px;
                                            min-width:155px;
                                            text-align:center;
                                            vertical-align:middle;
                                            white-space:normal;
                                        "
                                    >

                                        {{-- =====================
                                             DISETUJUI
                                        ====================== --}}

                                        @if($item->status === 'Disetujui')

                                            <span
                                                class="badge badge-green"
                                                style="
                                                    display:inline-flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    white-space:nowrap;
                                                    padding:6px 12px;
                                                "
                                            >
                                                ✅ Disetujui
                                            </span>


                                        {{-- =====================
                                             DITOLAK
                                        ====================== --}}

                                        @elseif($item->status === 'Ditolak')

                                            <div
                                                style="
                                                    display:flex;
                                                    flex-direction:column;
                                                    align-items:center;
                                                    gap:8px;
                                                "
                                            >

                                                <span
                                                    class="badge"
                                                    style="
                                                        display:inline-flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        white-space:nowrap;
                                                        background:#fee2e2;
                                                        color:#b91c1c;
                                                        padding:6px 12px;
                                                    "
                                                >
                                                    ❌ Ditolak
                                                </span>


                                                @if($item->alasan_penolakan)

                                                    <div
                                                        style="
                                                            width:100%;
                                                            max-width:170px;
                                                            padding:8px;
                                                            background:#fff1f2;
                                                            border:1px solid #fecdd3;
                                                            border-radius:6px;
                                                            color:#991b1b;
                                                            font-size:11px;
                                                            line-height:1.4;
                                                            text-align:left;
                                                            box-sizing:border-box;
                                                        "
                                                    >

                                                        <strong>
                                                            Alasan:
                                                        </strong>

                                                        <br>

                                                        {{ $item->alasan_penolakan }}

                                                    </div>

                                                @endif


                                                {{-- AJUKAN KEMBALI --}}

                                                @if($aksesKrsDibuka)
                                                <form
                                                    action="{{ route('mahasiswa.krs.ajukan-kembali', $item->id) }}"
                                                    method="POST"
                                                >

                                                    @csrf

                                                    <button
                                                        type="submit"
                                                        class="btn-primary"
                                                        style="
                                                            padding:6px 10px;
                                                            font-size:11px;
                                                            white-space:nowrap;
                                                        "
                                                        onclick="return confirm('Ajukan kembali KRS ini kepada Dosen Wali?')"
                                                    >
                                                        🔄 Ajukan Kembali
                                                    </button>

                                                </form>
                                                @endif

                                            </div>


                                        {{-- =====================
                                             MENUNGGU
                                        ====================== --}}

                                        @elseif($item->status === 'Menunggu')

                                            <span
                                                class="badge"
                                                style="
                                                    display:inline-flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    white-space:nowrap;
                                                    background:#fef3c7;
                                                    color:#92400e;
                                                    padding:6px 12px;
                                                "
                                            >
                                                ⏳ Menunggu
                                            </span>


                                        {{-- =====================
                                             DIAMBIL
                                        ====================== --}}

                                        @elseif($item->status === 'Diambil')

                                            <span
                                                class="badge"
                                                style="
                                                    display:inline-flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    white-space:nowrap;
                                                    background:#e0f2fe;
                                                    color:#0369a1;
                                                    padding:6px 12px;
                                                "
                                            >
                                                📋 Diambil
                                            </span>


                                        {{-- =====================
                                             STATUS LAIN
                                        ====================== --}}

                                        @else

                                            <span
                                                class="badge"
                                                style="
                                                    display:inline-flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    white-space:nowrap;
                                                    background:#f1f5f9;
                                                    color:#475569;
                                                    padding:6px 12px;
                                                "
                                            >
                                                {{ $item->status ?? 'Belum ada status' }}
                                            </span>

                                        @endif

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

                                        <div
                                            style="
                                                font-size:35px;
                                                margin-bottom:8px;
                                            "
                                        >
                                            📚
                                        </div>

                                        <strong>
                                            Belum ada mata kuliah
                                            dalam KRS.
                                        </strong>

                                        <div
                                            style="
                                                color:#64748b;
                                                margin-top:5px;
                                            "
                                        >
                                            Silakan pilih mata kuliah
                                            yang tersedia di bawah.
                                        </div>

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- =====================================================
             DAFTAR JADWAL TERSEDIA
        ====================================================== --}}

        <div class="page-card">

            <div class="page-card-head">

                <div>

                    <h3 style="margin:0;">
                        📚 Mata Kuliah Tersedia
                    </h3>

                    @if($periodeKrs)

                        <small
                            style="
                                color:#64748b;
                                display:block;
                                margin-top:5px;
                            "
                        >
                            Mata kuliah sesuai Program Studi dan periode
                            akademik Anda.
                        </small>

                    @endif

                </div>

            </div>


            <div class="page-card-body">

                @if(!$periodeKrs)

                    <div
                        style="
                            text-align:center;
                            padding:45px 20px;
                            color:#64748b;
                        "
                    >

                        <div
                            style="
                                font-size:45px;
                                margin-bottom:10px;
                            "
                        >
                            🔒
                        </div>

                        <strong
                            style="
                                display:block;
                                color:#334155;
                                font-size:16px;
                            "
                        >
                            Pengisian KRS Ditutup
                        </strong>

                        <p
                            style="
                                margin-top:8px;
                                font-size:13px;
                            "
                        >
                            Belum ada periode KRS yang sedang dibuka.
                        </p>

                    </div>


                @elseif(!$aksesKrsDibuka)

                    <div style="text-align:center;padding:45px 20px;color:#9a3412;">
                        <div style="font-size:40px;margin-bottom:10px;">🔒</div>
                        <strong style="display:block;font-size:16px;">Akses KRS Anda belum dibuka</strong>
                        <p style="margin-top:8px;font-size:13px;">{{ $pesanAksesKrs }}</p>
                    </div>

                @elseif($jadwalsBySemester->isEmpty())

                    <div
                        style="
                            text-align:center;
                            padding:45px 20px;
                            color:#64748b;
                        "
                    >

                        <div
                            style="
                                font-size:45px;
                                margin-bottom:10px;
                            "
                        >
                            📭
                        </div>

                        <strong
                            style="
                                display:block;
                                color:#334155;
                                font-size:16px;
                            "
                        >
                            Tidak Ada Mata Kuliah Tersedia
                        </strong>

                        <p
                            style="
                                margin-top:8px;
                                font-size:13px;
                            "
                        >
                            Belum ada mata kuliah tersedia untuk semester akademik periode ini. Hubungi admin akademik.
                        </p>

                    </div>


                @else

                    @foreach($jadwalsBySemester as $semesterAngka => $jadwalSemester)
                        <section style="margin-bottom:28px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                                <h4 style="margin:0;font-size:17px;color:#0f172a;">
                                    Semester {{ $semesterAngka }}
                                </h4>
                                <span class="badge badge-blue">
                                    {{ $jadwalSemester->count() }} mata kuliah
                                </span>
                            </div>

                            <div class="table-wrap" style="overflow-x:auto;">
                                <table style="width:100%;table-layout:auto;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode MK</th>
                                            <th>Mata Kuliah</th>
                                            <th>SKS</th>
                                            <th>Dosen</th>
                                            <th>Ruangan</th>
                                            <th>Jadwal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach($jadwalSemester as $jadwal)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $jadwal->mataKuliah->kode_mk ?? '-' }}</td>
                                                <td>
                                                    <strong>{{ $jadwal->mataKuliah->nama_mk ?? '-' }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-blue">
                                                        {{ $jadwal->mataKuliah->sks ?? 0 }} SKS
                                                    </span>
                                                </td>
                                                <td>{{ $jadwal->dosen->nama ?? '-' }}</td>
                                                <td>{{ $jadwal->ruangan->nama_ruangan ?? '-' }}</td>
                                                <td>
                                                    <strong>{{ $jadwal->hari }}</strong><br>
                                                    <small style="color:#64748b;">
                                                        {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <form action="{{ route('mahasiswa.krs.store') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="jadwal_id" value="{{ $jadwal->id }}">

                                                        @if(
                                                            $periodeKrs &&
                                                            $aksesKrsDibuka &&
                                                            ($totalSks + ($jadwal->mataKuliah->sks ?? 0)) <= $batasSks
                                                        )
                                                            <button type="submit" class="btn-primary" style="padding:6px 12px;font-size:11px;">
                                                                ➕ Ambil
                                                            </button>
                                                        @else
                                                            <button
                                                                type="button"
                                                                class="btn-outline"
                                                                style="padding:6px 12px;font-size:11px;color:#94a3b8;cursor:not-allowed;"
                                                                disabled
                                                            >
                                                                🔒 Batas SKS
                                                            </button>
                                                        @endif
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach

                @endif

            </div>

        </div>


        {{-- =====================================================
             INFORMASI BATAS SKS
        ====================================================== --}}

        @if($periodeKrs)

            <div
                style="
                    margin-top:20px;
                    padding:15px;
                    background:#f8fafc;
                    border:1px solid #e2e8f0;
                    border-radius:8px;
                    color:#475569;
                    font-size:13px;
                "
            >

                ℹ️

                Batas maksimal KRS Anda adalah
                <strong>
                    {{ $batasSks }} SKS
                </strong>.

                @if($jumlahKuesionerTertunda > 0)
                    IPK kumulatif disembunyikan sampai seluruh kuesioner dosen selesai diisi.
                @elseif($ipkTerlihat !== null)
                    Ketentuan ini dihitung berdasarkan IPK {{ number_format($ipkTerlihat, 2) }}.
                @else
                    Ketentuan ini menggunakan batas akademik awal karena belum ada nilai.
                @endif

                @if($periodeKrs->minimal_sks > 0)

                    Minimal pengambilan adalah
                    <strong>
                        {{ $periodeKrs->minimal_sks }} SKS
                    </strong>.

                @endif

            </div>

        @endif


    </div>

</div>

@endsection
