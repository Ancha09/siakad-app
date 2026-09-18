@extends('layouts.admin')

@section('title', 'Periode KRS')

@section('content')

{{-- ===================== SUCCESS ===================== --}}

@if(session('success'))

    <div class="alert-success">
        {{ session('success') }}
    </div>

@endif


{{-- ===================== ERROR ===================== --}}

@if(session('error'))

    <div style="
        background:#fee2e2;
        color:#991b1b;
        padding:15px;
        border-radius:8px;
        margin-bottom:20px;
    ">
        ❌ {{ session('error') }}
    </div>

@endif


{{-- ===================== TOOLBAR ===================== --}}

<div class="toolbar">

    <div>
        <h2 style="margin:0;">
            📅 Periode KRS
        </h2>

        <p style="
            margin:5px 0 0;
            color:#64748b;
            font-size:13px;
        ">
            Atur jadwal pembukaan dan penutupan pengisian KRS mahasiswa.
        </p>
    </div>


    <a
        href="{{ route('admin.periode-krs.create', ['return_url' => request()->fullUrl()]) }}"
        class="btn-primary"
    >
        ➕ Tambah Periode KRS
    </a>

</div>


{{-- ===================== FILTER ===================== --}}

<div class="page-card" style="margin-bottom:20px;">
    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.periode-krs') }}">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;align-items:end;">
                <div>
                    <label for="tahun_akademik">Tahun Akademik</label>
                    <select id="tahun_akademik" name="tahun_akademik" class="form-control">
                        <option value="">Semua tahun</option>
                        @foreach($tahunAkademiks as $tahun)
                            <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="semester">Semester Akademik</label>
                    <select id="semester" name="semester" class="form-control">
                        <option value="">Semua semester</option>
                        <option value="Ganjil" @selected(request('semester') === 'Ganjil')>Ganjil</option>
                        <option value="Genap" @selected(request('semester') === 'Genap')>Genap</option>
                    </select>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <a href="{{ route('admin.periode-krs') }}" class="btn-outline">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>


{{-- ===================== DATA ===================== --}}

<div class="page-card">

    <div class="page-card-head">

        <h2>
            📋 Daftar Periode KRS
        </h2>

    </div>


    <div class="page-card-body">

        <div class="table-wrap">

            <table>

                <thead>

                <tr>

                    <th>No</th>

                    <th>Tahun Akademik</th>

                    <th>Semester</th>

                    <th>Mulai</th>

                    <th>Selesai</th>

                    <th>Minimal SKS</th>

                    <th>Maksimal SKS</th>

                    <th>Status</th>

                    <th>Mode Akses</th>

                    <th>Keterangan</th>

                    <th width="310">Aksi</th>

                </tr>

                </thead>


                <tbody>

                @forelse($periodeKrs as $item)

                    <tr>

                        {{-- NO --}}

                        <td>
                            {{ $periodeKrs->firstItem() + $loop->index }}
                        </td>


                        {{-- TAHUN AKADEMIK --}}

                        <td>

                            <strong>
                                {{ $item->tahun_akademik }}
                            </strong>

                        </td>


                        {{-- SEMESTER --}}

                        <td>

                            @if($item->semester === 'Ganjil')

                                <span class="badge badge-blue">
                                    Ganjil
                                </span>

                            @else

                                <span class="badge badge-green">
                                    Genap
                                </span>

                            @endif

                        </td>


                        {{-- TANGGAL MULAI --}}

                        <td>

                            {{ $item->tanggal_mulai
                                ? $item->tanggal_mulai->format('d/m/Y H:i')
                                : '-' }}

                        </td>


                        {{-- TANGGAL SELESAI --}}

                        <td>

                            {{ $item->tanggal_selesai
                                ? $item->tanggal_selesai->format('d/m/Y H:i')
                                : '-' }}

                        </td>


                        {{-- MINIMAL SKS --}}

                        <td>
                            {{ $item->minimal_sks }} SKS
                        </td>


                        {{-- MAKSIMAL SKS --}}

                        <td>
                            {{ $item->maksimal_sks }} SKS
                        </td>


                        {{-- STATUS --}}

                        <td>

                            @if($item->status === 'Dibuka')

                                <span class="badge badge-green">
                                    🔓 Dibuka
                                </span>

                            @else

                                <span class="badge badge-gray">
                                    🔒 Ditutup
                                </span>

                            @endif

                        </td>

                        <td>
                            {{ [
                                'closed' => 'Semua ditutup',
                                'all' => 'Semua dibuka',
                                'selected' => 'Mahasiswa tertentu',
                                'all_except' => 'Semua kecuali pilihan',
                            ][$item->access_mode ?? 'selected'] }}
                        </td>


                        {{-- KETERANGAN --}}

                        <td>

                            {{ $item->keterangan ?? '-' }}

                        </td>


                        {{-- AKSI --}}

                        <td>

                            <div
                                class="action-buttons"
                                style="display:flex;gap:6px;flex-wrap:wrap;"
                            >

                                <a
                                    href="{{ route('admin.periode-krs.students', $item) }}"
                                    class="btn-primary"
                                    style="white-space:nowrap;"
                                >
                                    Akses Mahasiswa
                                </a>

                                {{-- TOGGLE STATUS --}}

                                <form
                                    action="{{ route(
                                        'admin.periode-krs.toggle',
                                        $item->id
                                    ) }}"
                                    method="POST"
                                >

                                    @csrf
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                                    @method('PATCH')

                                    @if($item->status === 'Dibuka')

                                        <button
                                            type="submit"
                                            class="btn-delete"
                                            onclick="return confirm(
                                                'Yakin ingin menutup periode KRS ini?'
                                            )"
                                        >
                                            🔒 Tutup
                                        </button>

                                    @else

                                        <button
                                            type="submit"
                                            class="btn-primary"
                                            onclick="return confirm(
                                                'Yakin ingin membuka periode KRS ini?'
                                            )"
                                        >
                                            🔓 Buka
                                        </button>

                                    @endif

                                </form>


                                {{-- EDIT --}}

                                <a
                                    href="{{ route('admin.periode-krs.edit', ['periodeKrs' => $item->id, 'return_url' => request()->fullUrl()]) }}"
                                    class="btn-edit"
                                >
                                    ✏ Edit
                                </a>


                                {{-- DELETE --}}

                                <form
                                    action="{{ route(
                                        'admin.periode-krs.destroy',
                                        $item->id
                                    ) }}"
                                    method="POST"
                                >

                                    @csrf
                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus periode KRS ini?'
                                        )"
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
                            colspan="11"
                            style="
                                text-align:center;
                                padding:45px;
                            "
                        >

                            <div style="font-size:40px;">
                                📅
                            </div>

                            <strong>
                                Belum ada periode KRS
                            </strong>

                            <p style="
                                color:#64748b;
                                margin-top:6px;
                            ">
                                Silakan tambahkan periode KRS terlebih dahulu.
                            </p>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>


        {{-- ===================== PAGINATION ===================== --}}

        <div style="margin-top:20px;">

            {{ $periodeKrs->appends(request()->query())->onEachSide(1)->links() }}

        </div>

    </div>

</div>

@endsection
