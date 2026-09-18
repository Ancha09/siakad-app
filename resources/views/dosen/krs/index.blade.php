@extends('layouts.dosen')

@section('title', 'Persetujuan KRS')

@section('content')

<div class="inner-page">

    {{-- ===================== RINGKASAN ===================== --}}

    <div class="khs-summary">

        <div class="khs-stat">
            <div class="khs-stat-val" style="color:var(--gold)">
                {{ $menunggu }}
            </div>

            <div class="khs-stat-label">
                Menunggu Persetujuan
            </div>
        </div>


        <div class="khs-stat">
            <div class="khs-stat-val" style="color:var(--green)">
                {{ $disetujui }}
            </div>

            <div class="khs-stat-label">
                KRS Disetujui
            </div>
        </div>


        <div class="khs-stat">
            <div class="khs-stat-val" style="color:#dc2626">
                {{ $ditolak }}
            </div>

            <div class="khs-stat-label">
                KRS Ditolak
            </div>
        </div>

    </div>


    {{-- ===================== PESAN SUCCESS ===================== --}}

    @if(session('success'))

        <div class="info-alert" style="margin-bottom:24px;">

            <span>✅</span>

            <div>
                {{ session('success') }}
            </div>

        </div>

    @endif


    {{-- ===================== PESAN ERROR ===================== --}}

    @if(session('error'))

        <div
            class="info-alert"
            style="margin-bottom:24px;"
        >

            <span>⚠️</span>

            <div>
                {{ session('error') }}
            </div>

        </div>

    @endif


    {{-- ===================== DAFTAR PENGAJUAN ===================== --}}

    <div class="page-card">

        <div class="page-card-head">

            <h2>📋 Persetujuan KRS Mahasiswa</h2>

            <span class="badge badge-blue">
                {{ $krs->count() }} Pengajuan
            </span>

        </div>


        <div class="page-card-body">

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>No</th>

                            <th>NIM</th>

                            <th>Nama Mahasiswa</th>

                            <th>Mata Kuliah</th>

                            <th>SKS</th>

                            <th>Semester</th>

                            <th>Status</th>

                            <th>Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($krs as $item)

                            <tr>

                                {{-- ===================== NO ===================== --}}

                                <td>
                                    {{ $loop->iteration }}
                                </td>


                                {{-- ===================== NIM ===================== --}}

                                <td>
                                    {{ $item->mahasiswa->nim ?? '-' }}
                                </td>


                                {{-- ===================== NAMA ===================== --}}

                                <td>
                                    {{ $item->mahasiswa->nama ?? '-' }}
                                </td>


                                {{-- ===================== MATA KULIAH ===================== --}}

                                <td>
                                    {{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}
                                </td>


                                {{-- ===================== SKS ===================== --}}

                                <td>
                                    {{ $item->jadwal->mataKuliah->sks ?? 0 }}
                                </td>


                                {{-- ===================== SEMESTER ===================== --}}

                                <td>
                                    @if($item->mahasiswa && $item->mahasiswa->semester)
                                        Semester {{ $item->mahasiswa->semester }}
                                    @else
                                        -
                                    @endif
                                </td>


                                {{-- ===================== STATUS ===================== --}}

                                <td>

                                    @if($item->status === 'Menunggu')

                                        <span class="badge badge-gold">
                                            🕐 Menunggu
                                        </span>

                                    @elseif($item->status === 'Disetujui')

                                        <span class="badge badge-green">
                                            ✅ Disetujui
                                        </span>

                                    @elseif($item->status === 'Ditolak')

                                        <span
                                            class="badge"
                                            style="
                                                background:#fee2e2;
                                                color:#dc2626;
                                            "
                                        >
                                            ❌ Ditolak
                                        </span>

                                    @else

                                        <span class="badge badge-gray">
                                            {{ $item->status ?? '-' }}
                                        </span>

                                    @endif

                                </td>


                                {{-- ===================== AKSI ===================== --}}

                                <td>

                                    {{-- ===================== MENUNGGU ===================== --}}

                                    @if($item->status === 'Menunggu')

                                        <div
                                            style="
                                                display:flex;
                                                gap:6px;
                                                align-items:center;
                                            "
                                        >

                                            {{-- ===================== SETUJUI ===================== --}}

                                            <form
                                                action="{{ route('dosen.krs.setujui', $item->id) }}"
                                                method="POST"
                                            >

                                                @csrf

                                                @method('PUT')

                                                <button
                                                    type="submit"
                                                    class="btn-primary"
                                                    style="
                                                        padding:6px 10px;
                                                        font-size:11px;
                                                    "
                                                >
                                                    ✓ Setujui
                                                </button>

                                            </form>


                                            {{-- ===================== TOLAK ===================== --}}

                                            <button
                                                type="button"
                                                class="btn-outline"
                                                style="
                                                    padding:6px 10px;
                                                    font-size:11px;
                                                "
                                                data-id="{{ $item->id }}" onclick="openTolakModal(this.dataset.id)"
                                            >
                                                ✕ Tolak
                                            </button>

                                            <div
                                                id="tolakModal{{ $item->id }}"
                                                style="
                                                    display:none;
                                                    position:fixed;
                                                    inset:0;
                                                    background:rgba(15,23,42,.55);
                                                    z-index:9999;
                                                    align-items:center;
                                                    justify-content:center;
                                                    padding:20px;
                                                "
                                            >
                                                <div
                                                    style="
                                                        background:#fff;
                                                        width:100%;
                                                        max-width:500px;
                                                        border-radius:14px;
                                                        padding:24px;
                                                        box-shadow:0 20px 50px rgba(0,0,0,.2);
                                                    "
                                                >

                                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
                                                        <h3 style="margin:0;font-size:18px;">
                                                            ❌ Tolak Pengajuan KRS
                                                        </h3>

                                                        <button
                                                            type="button"
                                                            data-id="{{ $item->id }}" onclick="closeTolakModal(this.dataset.id)"
                                                            style="border:0;background:none;font-size:20px;cursor:pointer;color:#64748b;"
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>

                                                    <p style="margin-bottom:8px;color:#475569;">
                                                        Mahasiswa:
                                                        <strong>{{ $item->mahasiswa->nama ?? '-' }}</strong>
                                                    </p>

                                                    <p style="margin-bottom:18px;color:#475569;">
                                                        Mata Kuliah:
                                                        <strong>{{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}</strong>
                                                    </p>

                                                    <form
                                                        action="{{ route('dosen.krs.tolak', $item->id) }}"
                                                        method="POST"
                                                    >
                                                        @csrf
                                                        @method('PUT')

                                                        <div class="form-group">
                                                            <label>Alasan Penolakan</label>

                                                            <textarea
                                                                name="alasan_penolakan"
                                                                class="form-control"
                                                                rows="5"
                                                                required
                                                                minlength="5"
                                                                maxlength="1000"
                                                                placeholder="Contoh: Jadwal bentrok dengan mata kuliah lain."
                                                            ></textarea>

                                                            <small style="color:#64748b;">
                                                                Jelaskan alasan penolakan agar mahasiswa mengetahui
                                                                apa yang perlu diperbaiki.
                                                            </small>
                                                        </div>

                                                        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
                                                            <button
                                                                type="button"
                                                                class="btn-outline"
                                                                data-id="{{ $item->id }}" onclick="closeTolakModal(this.dataset.id)"
                                                            >
                                                                Batal
                                                            </button>

                                                            <button
                                                                type="submit"
                                                                class="btn-delete"
                                                            >
                                                                ❌ Tolak KRS
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                        </div>


                                    {{-- ===================== DISETUJUI ===================== --}}

                                    @elseif($item->status === 'Disetujui')

                                        <span
                                            style="
                                                color:var(--green);
                                                font-weight:600;
                                            "
                                        >
                                            Sudah disetujui
                                        </span>


                                    {{-- ===================== DITOLAK ===================== --}}

                                    @elseif($item->status === 'Ditolak')

                                        <span
                                            style="
                                                color:#dc2626;
                                                font-weight:600;
                                            "
                                        >
                                            Ditolak
                                        </span>


                                    {{-- ===================== STATUS LAIN ===================== --}}

                                    @else

                                        <span style="color:#777;">
                                            -
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
                                            font-size:32px;
                                            margin-bottom:10px;
                                        "
                                    >
                                        📋
                                    </div>

                                    <strong>
                                        Belum ada pengajuan KRS
                                    </strong>

                                    <br>

                                    <span style="color:#777;">
                                        Pengajuan KRS mahasiswa akan muncul di sini.
                                    </span>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script>
    function openTolakModal(id) {
        const modal = document.getElementById('tolakModal' + id);

        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeTolakModal(id) {
        const modal = document.getElementById('tolakModal' + id);

        if (modal) {
            modal.style.display = 'none';
        }
    }

    document.addEventListener('click', function(event) {
        if (event.target.matches('[id^="tolakModal"]')) {
            event.target.style.display = 'none';
        }
    });
</script>

@endsection
