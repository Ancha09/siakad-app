@extends('layouts.admin')

@section('title', 'Detail Evaluasi Dosen')
@section('page-title', 'Detail Evaluasi Dosen')
@section('page-subtitle', 'Rata-rata indikator dan komentar anonim mahasiswa')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-evaluasi.css') }}?v={{ filemtime(public_path('assets/css/admin-evaluasi.css')) }}">
@endpush

@section('content')
<div class="evaluation-page">
    <div class="evaluation-back-row">
        <a href="{{ $returnUrl }}" class="evaluation-button secondary icon-button"><x-layout-icon name="arrow-left" /> Kembali ke Daftar</a>
        <a href="{{ route('admin.kuesioner.dosen.pdf', array_merge($activeFilters, request()->except(['page', 'return_url']), ['dosen' => $dosen->id])) }}" class="evaluation-button primary icon-button"><x-layout-icon name="download" /> Download PDF</a>
    </div>

    <section class="evaluation-profile-card">
        <div class="evaluation-profile-main">
            <span class="evaluation-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($dosen->nama, 0, 1)) }}</span>
            <div>
                <span class="evaluation-eyebrow">DOSEN</span>
                <h2>{{ $dosen->nama }}</h2>
                <p>NIDN/NIP/Kode: {{ $dosen->nidn ?: '-' }} &middot; {{ $dosen->prodi?->nama_prodi ?? 'Program studi belum diisi' }}</p>
            </div>
        </div>
        <div class="evaluation-profile-score">
            <span>Rata-rata keseluruhan</span>
            <strong>{{ $rataRata !== null ? number_format($rataRata, 2) : '-' }}</strong>
            <small>{{ $jumlahResponden }} responden</small>
        </div>
    </section>

    @if($jumlahResponden === 0)
        <section class="evaluation-empty-detail">
            <strong>Belum ada evaluasi untuk dosen ini.</strong>
            <p>Dosen tetap tercatat di rekap, tetapi belum mempunyai jawaban pada semester, tahun akademik, atau mata kuliah yang sedang difilter.</p>
        </section>
    @else
        <section class="evaluation-detail-grid">
            <article class="evaluation-detail-card indicator-card">
                <div class="evaluation-section-heading">
                    <div>
                        <span class="evaluation-eyebrow">RINGKASAN SKOR</span>
                        <h2>Rata-rata per Pertanyaan</h2>
                        <p>Skala penilaian 1 sampai 5 dari seluruh jawaban pada filter aktif.</p>
                    </div>
                </div>

                <div class="evaluation-indicators">
                    @foreach($pertanyaan as $kolom => $label)
                        <div class="evaluation-indicator">
                            <div>
                                <span>{{ $label }}</span>
                                <strong>{{ $rataPertanyaan[$kolom] !== null ? number_format($rataPertanyaan[$kolom], 2) : '-' }}</strong>
                            </div>
                            <meter min="0" max="5" value="{{ $rataPertanyaan[$kolom] ?? 0 }}">{{ $rataPertanyaan[$kolom] ?? 0 }} dari 5</meter>
                        </div>
                    @endforeach
                </div>
            </article>

            <aside class="evaluation-detail-card context-card">
                <div class="evaluation-section-heading">
                    <div>
                        <span class="evaluation-eyebrow">KONTEKS EVALUASI</span>
                        <h2>Data Terkait</h2>
                    </div>
                </div>

                <div class="evaluation-context-group">
                    <h3>Mata Kuliah</h3>
                    @forelse($mataKuliahs as $mataKuliah)
                        <span class="evaluation-chip">{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</span>
                    @empty
                        <span class="evaluation-muted">Tidak ada data.</span>
                    @endforelse
                </div>

                <div class="evaluation-context-group">
                    <h3>Kelas</h3>
                    @forelse($kelases as $kelas)
                        <span class="evaluation-chip neutral">{{ $kelas->nama_kelas }}</span>
                    @empty
                        <span class="evaluation-muted">Tidak ada data.</span>
                    @endforelse
                </div>

                <div class="evaluation-context-group">
                    <h3>Semester / Tahun Akademik</h3>
                    @forelse($periode as $item)
                        <span class="evaluation-period">{{ $item }}</span>
                    @empty
                        <span class="evaluation-muted">Tidak ada data.</span>
                    @endforelse
                </div>
            </aside>
        </section>

        <section class="evaluation-comments-card" aria-labelledby="komentar-title">
            <div class="evaluation-section-heading table-heading">
                <div>
                    <span class="evaluation-eyebrow">UMPAN BALIK ANONIM</span>
                    <h2 id="komentar-title">Komentar Mahasiswa</h2>
                    <p>Nama dan NIM mahasiswa tidak ditampilkan untuk menjaga kerahasiaan responden.</p>
                </div>
                <span class="evaluation-result-count">{{ number_format($komentar->total()) }} komentar</span>
            </div>

            <div class="evaluation-comments">
                @forelse($komentar as $item)
                    <article class="evaluation-comment">
                        <div class="evaluation-comment-head">
                            <div>
                                <strong>{{ $item->kode_responden }}</strong>
                                <span>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? 'Mata kuliah tidak tersedia' }}</span>
                            </div>
                        </div>
                        <p>{{ $item->komentar }}</p>
                        <small>
                            {{ $item->krs?->kelas_efektif?->nama_kelas ?? 'Kelas tidak tersedia' }}
                            &middot; {{ $item->krs?->semester_akademik ?? '-' }} {{ $item->krs?->tahun_akademik ?? '' }}
                            &middot; Skor {{ number_format($item->rata_rata, 2) }}/5
                        </small>
                    </article>
                @empty
                    <div class="evaluation-empty compact">
                        <strong>Belum ada komentar tertulis.</strong>
                        <span>Nilai evaluasi tersedia, tetapi responden tidak menulis komentar.</span>
                    </div>
                @endforelse
            </div>

            {{ $komentar->appends(request()->query())->onEachSide(1)->links() }}
        </section>
    @endif
</div>
@endsection
