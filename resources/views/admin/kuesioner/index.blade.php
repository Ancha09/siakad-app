@extends('layouts.admin')

@section('title', 'Evaluasi Dosen')
@section('page-title', 'Evaluasi Dosen')
@section('page-subtitle', 'Ringkasan hasil evaluasi dan dosen yang belum memiliki penilaian')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-evaluasi.css') }}?v={{ filemtime(public_path('assets/css/admin-evaluasi.css')) }}">
@endpush

@section('content')
<div class="evaluation-page">
    <section class="evaluation-filter" aria-labelledby="filter-evaluasi-title">
        <div class="evaluation-section-heading">
            <div>
                <span class="evaluation-eyebrow">PENCARIAN DATA</span>
                <h2 id="filter-evaluasi-title">Filter Evaluasi Dosen</h2>
                <p>Filter periode hanya memengaruhi jawaban evaluasi. Dosen tanpa jawaban pada periode tersebut tetap dapat ditampilkan.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.kuesioner') }}" class="evaluation-filter-form">
            <label>
                <span>Nama / NIDN</span>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau NIDN dosen">
            </label>

            <label>
                <span>Dosen</span>
                <select name="dosen_id">
                    <option value="">Semua dosen</option>
                    @foreach($dosens as $dosen)
                        <option value="{{ $dosen->id }}" @selected((string) request('dosen_id') === (string) $dosen->id)>
                            {{ $dosen->nama }}{{ $dosen->nidn ? ' - '.$dosen->nidn : '' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Mata Kuliah</span>
                <select name="mata_kuliah_id">
                    <option value="">Semua mata kuliah</option>
                    @foreach($mataKuliahs as $mataKuliah)
                        <option value="{{ $mataKuliah->id }}" @selected((string) request('mata_kuliah_id') === (string) $mataKuliah->id)>
                            {{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Semester</span>
                <select name="semester_akademik">
                    <option value="">Semua semester</option>
                    <option value="Ganjil" @selected(request('semester_akademik') === 'Ganjil')>Ganjil</option>
                    <option value="Genap" @selected(request('semester_akademik') === 'Genap')>Genap</option>
                </select>
            </label>

            <label>
                <span>Tahun Akademik</span>
                <select name="tahun_akademik">
                    <option value="">Semua tahun</option>
                    @foreach($tahunAkademik as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Status Evaluasi</span>
                <select name="status_evaluasi">
                    <option value="">Semua status</option>
                    <option value="sudah" @selected(request('status_evaluasi') === 'sudah')>Sudah dinilai</option>
                    <option value="belum" @selected(request('status_evaluasi') === 'belum')>Belum dinilai</option>
                </select>
            </label>

            <div class="evaluation-filter-actions">
                <button type="submit" class="evaluation-button primary">Terapkan Filter</button>
                <a href="{{ route('admin.kuesioner') }}" class="evaluation-button secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="evaluation-stats" aria-label="Ringkasan evaluasi dosen">
        <article class="evaluation-stat blue">
            <span>Total Dosen</span>
            <strong>{{ number_format($totalDosen) }}</strong>
            <small>Sesuai pencarian dosen</small>
        </article>
        <article class="evaluation-stat green">
            <span>Sudah Dinilai</span>
            <strong>{{ number_format($totalDinilai) }}</strong>
            <small>Memiliki evaluasi pada filter aktif</small>
        </article>
        <article class="evaluation-stat amber">
            <span>Belum Dinilai</span>
            <strong>{{ number_format($totalBelumDinilai) }}</strong>
            <small>Belum memiliki evaluasi pada filter aktif</small>
        </article>
        <article class="evaluation-stat violet">
            <span>Total Responden</span>
            <strong>{{ number_format($totalResponden) }}</strong>
            <small>Jawaban anonim yang masuk</small>
        </article>
    </section>

    <section class="evaluation-table-card" aria-labelledby="daftar-evaluasi-title">
        <div class="evaluation-section-heading table-heading">
            <div>
                <span class="evaluation-eyebrow">REKAP PER DOSEN</span>
                <h2 id="daftar-evaluasi-title">Daftar Evaluasi Dosen</h2>
                <p>Dosen yang belum pernah dinilai tetap tercantum agar mudah dipantau.</p>
            </div>
            <span class="evaluation-result-count">{{ number_format($evaluasiDosen->total()) }} dosen</span>
        </div>

        <div class="evaluation-table-wrap">
            <table class="evaluation-table">
                <thead>
                    <tr>
                        <th class="column-number">No</th>
                        <th>Dosen</th>
                        <th>Program Studi</th>
                        <th>Mata Kuliah Terkait</th>
                        <th>Periode</th>
                        <th class="column-center">Responden</th>
                        <th class="column-center">Rata-rata</th>
                        <th class="column-center">Status</th>
                        <th class="column-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluasiDosen as $rekap)
                        <tr>
                            <td class="column-number">{{ ($evaluasiDosen->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                <strong class="evaluation-lecturer-name">{{ $rekap->dosen->nama }}</strong>
                                <small class="evaluation-muted">NIDN/NIP/Kode: {{ $rekap->dosen->nidn ?: '-' }}</small>
                            </td>
                            <td>{{ $rekap->dosen->prodi?->nama_prodi ?? '-' }}</td>
                            <td>
                                @forelse($rekap->mata_kuliahs->take(2) as $mataKuliah)
                                    <span class="evaluation-chip">{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</span>
                                @empty
                                    <span class="evaluation-muted">Belum ada data</span>
                                @endforelse
                                @if($rekap->mata_kuliahs->count() > 2)
                                    <small class="evaluation-more">+{{ $rekap->mata_kuliahs->count() - 2 }} mata kuliah lain</small>
                                @endif
                            </td>
                            <td>
                                @forelse($rekap->periode->take(2) as $periode)
                                    <span class="evaluation-period">{{ $periode }}</span>
                                @empty
                                    <span class="evaluation-muted">-</span>
                                @endforelse
                            </td>
                            <td class="column-center"><strong>{{ $rekap->jumlah_responden }}</strong></td>
                            <td class="column-center">
                                @if($rekap->rata_rata !== null)
                                    <span class="evaluation-score">{{ number_format($rekap->rata_rata, 2) }}</span>
                                    <small class="evaluation-score-scale">/ 5</small>
                                @else
                                    <span class="evaluation-muted">-</span>
                                @endif
                            </td>
                            <td class="column-center">
                                @if($rekap->jumlah_responden > 0)
                                    <span class="evaluation-status evaluated">Sudah dinilai</span>
                                @else
                                    <span class="evaluation-status empty">Belum dinilai</span>
                                @endif
                            </td>
                            <td class="column-action">
                                @php
                                    $detailQuery = array_merge(request()->except('page'), [
                                        'dosen' => $rekap->dosen->id,
                                        'return_url' => request()->fullUrl(),
                                    ]);
                                @endphp
                                <a href="{{ route('admin.kuesioner.dosen', $detailQuery) }}" class="evaluation-detail-button">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="evaluation-empty">
                                    <strong>Data dosen tidak ditemukan.</strong>
                                    <span>Coba ubah atau reset filter yang sedang digunakan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $evaluasiDosen->appends(request()->query())->onEachSide(1)->links() }}
    </section>
</div>
@endsection
