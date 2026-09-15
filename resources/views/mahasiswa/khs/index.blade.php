@extends('layouts.mahasiswa')

@section('title', 'KHS Mahasiswa')

@section('content')

<div class="page-card">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-card-head">

        <h2>📑 Kartu Hasil Studi</h2>

    </div>


    {{-- =====================================================
         IDENTITAS MAHASISWA
    ====================================================== --}}

    <div class="page-card-body">

        <div style="display:flex;justify-content:flex-end;margin-bottom:18px;">
            <a href="{{ route('mahasiswa.transkrip.pdf') }}" class="btn-primary">
                Download Transkrip PDF
            </a>
        </div>

        @if(session('success'))
            <div style="background:#dcfce7;color:#166534;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
                {{ session('success') }}
            </div>
        @endif

        <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
            🔒 Nilai mata kuliah terbuka setelah kuesionernya diisi. IPS dan IPK kumulatif tetap terkunci sampai seluruh kuesioner yang wajib selesai.
            @if($jumlahKuesionerTertunda > 0)
                <a href="{{ route('mahasiswa.kuesioner') }}" style="display:inline-block;margin-left:8px;color:#9a3412;font-weight:700;">Isi {{ $jumlahKuesionerTertunda }} kuesioner tertunda →</a>
            @endif
        </div>

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:15px;
            margin-bottom:25px;
        ">

            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    NIM
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->nim ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Nama Mahasiswa
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->nama ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Program Studi
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->prodi->nama_prodi ?? '-' }}
                </div>

            </div>


            <div style="
                background:#f8fafc;
                padding:18px;
                border-radius:10px;
            ">

                <small style="color:#64748b;">
                    Kelas
                </small>

                <div style="
                    font-weight:600;
                    margin-top:5px;
                ">
                    {{ $mahasiswa->kelas->nama_kelas ?? '-' }}
                </div>

            </div>

        </div>


        {{-- =====================================================
             RINGKASAN AKADEMIK
        ====================================================== --}}

        <div style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
            gap:15px;
            margin-bottom:30px;
        ">

            {{-- TOTAL SKS --}}

            <div style="
                background:#eff6ff;
                border-radius:12px;
                padding:20px;
                border:1px solid #dbeafe;
            ">

                <div style="
                    font-size:14px;
                    color:#64748b;
                ">
                    Total SKS
                </div>

                <div style="
                    font-size:28px;
                    font-weight:700;
                    margin-top:5px;
                ">
                    {{ $totalSks }}
                </div>

                <small>
                    SKS ditempuh
                </small>

            </div>


            {{-- IPK --}}

            <div style="
                background:#f0fdf4;
                border-radius:12px;
                padding:20px;
                border:1px solid #dcfce7;
            ">

                <div style="
                    font-size:14px;
                    color:#64748b;
                ">
                    IPK
                </div>

                <div style="
                    font-size:28px;
                    font-weight:700;
                    margin-top:5px;
                ">
                    @if($ipk !== null)
                        {{ number_format($ipk, 2) }}
                    @elseif($jumlahKuesionerTertunda > 0)
                        🔒
                    @else
                        -
                    @endif
                </div>

                <small>
                    Indeks Prestasi Kumulatif
                </small>

            </div>

        </div>


        {{-- =====================================================
             DATA KHS PER SEMESTER
        ====================================================== --}}

        @forelse($khsPerSemester as $semester => $data)

            <div style="margin-bottom:35px;">

                {{-- HEADER SEMESTER --}}

                <div style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:15px;
                    margin-bottom:15px;
                    flex-wrap:wrap;
                ">

                    <div>

                        <h3 style="margin:0;">
                            📚 {{ $semester }}
                        </h3>

                    </div>


                    {{-- IPS --}}

                    <div style="
                        background:#f8fafc;
                        padding:10px 18px;
                        border-radius:8px;
                    ">

                        <strong>
                            IPS:
                        </strong>

                        <span style="
                            font-size:18px;
                            font-weight:700;
                            margin-left:5px;
                        ">

                            @if(($ipsPerSemester[$semester] ?? null) !== null)
                                {{ number_format($ipsPerSemester[$semester], 2) }}
                            @else
                                🔒
                            @endif

                        </span>

                    </div>

                </div>


                {{-- TABEL KHS --}}

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>No</th>

                                <th>Kode</th>

                                <th>Mata Kuliah</th>

                                <th>Dosen</th>

                                <th>SKS</th>

                                <th>Nilai</th>

                                <th>Huruf</th>

                                <th>Bobot</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>

                            @foreach($data as $index => $item)

                                <tr>

                                    {{-- NO --}}

                                    <td>
                                        {{ $index + 1 }}
                                    </td>


                                    {{-- KODE --}}

                                    <td>
                                        {{ $item->krs?->mata_kuliah_efektif?->kode_mk ?? '-' }}
                                    </td>


                                    {{-- MATA KULIAH --}}

                                    <td>

                                        <strong>
                                            {{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? '-' }}
                                        </strong>

                                    </td>


                                    {{-- DOSEN --}}

                                    <td>
                                        {{ $item->dosen_efektif?->nama ?? '-' }}
                                    </td>


                                    {{-- SKS --}}

                                    <td>

                                        {{ $item->sks_efektif }}

                                    </td>


                                    {{-- NILAI ANGKA --}}

                                    <td>

                                        {{ $item->krs->kuesioner ? ($item->nilai_angka ?? '-') : '🔒' }}

                                    </td>


                                    {{-- NILAI HURUF --}}

                                    <td>

                                        <strong>
                                            {{ ($item->is_manual || $item->krs?->kuesioner) ? ($item->nilai_huruf ?? '-') : '🔒' }}
                                        </strong>

                                    </td>


                                    {{-- BOBOT --}}

                                    <td>

                                        {{ ($item->is_manual || $item->krs?->kuesioner) ? ($item->bobot ?? '-') : '🔒' }}

                                    </td>


                                    {{-- STATUS KUESIONER --}}

                                    <td>
                                        @if($item->krs->kuesioner)
                                            <span class="badge-success">Nilai terbuka</span>
                                        @else
                                            <a href="{{ route('mahasiswa.kuesioner.create', $item->krs_id) }}"
                                               class="btn-primary"
                                               style="display:inline-block;padding:8px 12px;white-space:nowrap;">
                                                Isi Kuesioner
                                            </a>
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        @empty

            {{-- =================================================
                 BELUM ADA KHS
            ================================================== --}}

            <div style="
                text-align:center;
                padding:50px 20px;
                background:#f8fafc;
                border-radius:12px;
            ">

                <div style="
                    font-size:45px;
                    margin-bottom:10px;
                ">
                    📑
                </div>

                <h3>
                    Belum Ada Data KHS
                </h3>

                <p style="
                    color:#64748b;
                    margin:0;
                ">
                    Data hasil studi Anda belum tersedia.
                </p>

            </div>

        @endforelse

    </div>

</div>

@endsection
