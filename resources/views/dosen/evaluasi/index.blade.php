@extends('layouts.dosen')

@section('title', 'Evaluasi Saya')
@section('page-title', 'Evaluasi Saya')
@section('page-subtitle', 'Ringkasan penilaian dan komentar mahasiswa secara anonim')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/admin-evaluasi.css') }}?v={{ filemtime(public_path('assets/css/admin-evaluasi.css')) }}">
@endpush

@section('content')
<div class="evaluation-page">
    <section class="evaluation-filter" aria-labelledby="filter-evaluasi-saya">
        <div class="evaluation-section-heading">
            <div>
                <span class="evaluation-eyebrow">HASIL EVALUASI ANONIM</span>
                <h2 id="filter-evaluasi-saya">Evaluasi Saya</h2>
                <p>Identitas mahasiswa, NIM, user ID, dan mahasiswa ID tidak ditampilkan pada halaman maupun dokumen PDF.</p>
            </div>
            <a href="{{ route('dosen.evaluasi.pdf', request()->except('page')) }}" class="evaluation-button primary">Download PDF</a>
        </div>

        <form method="GET" action="{{ route('dosen.evaluasi') }}" class="evaluation-filter-form lecturer-filter-form">
            <label>
                <span>Mata Kuliah</span>
                <select name="mata_kuliah_id">
                    <option value="">Semua mata kuliah</option>
                    @foreach($filterMataKuliahs as $mataKuliah)
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
                    @foreach($filterTahunAkademik as $tahun)
                        <option value="{{ $tahun }}" @selected(request('tahun_akademik') === $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Kelas</span>
                <select name="kelas_id">
                    <option value="">Semua kelas</option>
                    @foreach($filterKelases as $kelas)
                        <option value="{{ $kelas->id }}" @selected((string) request('kelas_id') === (string) $kelas->id)>{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </label>

            <div class="evaluation-filter-actions">
                <button type="submit" class="evaluation-button primary">Terapkan Filter</button>
                <a href="{{ route('dosen.evaluasi') }}" class="evaluation-button secondary">Reset</a>
            </div>
        </form>
    </section>

    <section class="evaluation-profile-card lecturer-summary-card">
        <div class="evaluation-profile-main">
            <span class="evaluation-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($dosen->nama, 0, 1)) }}</span>
            <div>
                <span class="evaluation-eyebrow">DOSEN LOGIN</span>
                <h2>{{ $dosen->nama }}</h2>
                <p>NIDN/NIP/Kode: {{ $dosen->nidn ?: '-' }} &middot; {{ $dosen->prodi?->nama_prodi ?? 'Program studi belum diisi' }}</p>
            </div>
        </div>
        <div class="lecturer-evaluation-numbers">
            <div>
                <span>Jumlah responden</span>
                <strong>{{ number_format($jumlahResponden) }}</strong>
            </div>
            <div>
                <span>Rata-rata keseluruhan</span>
                <strong>{{ $rataRata !== null ? number_format($rataRata, 2) : '-' }}</strong>
                <small>/ 5</small>
            </div>
        </div>
    </section>

    @if($jumlahResponden === 0)
        <section class="evaluation-empty-detail">
            <strong>Belum ada evaluasi pada filter ini.</strong>
            <p>Belum ada jawaban mahasiswa untuk mata kuliah, semester, tahun akademik, atau kelas yang dipilih.</p>
        </section>
    @else
        <section class="evaluation-detail-grid">
            <article class="evaluation-detail-card indicator-card">
                <div class="evaluation-section-heading">
                    <div>
                        <span class="evaluation-eyebrow">RINGKASAN SKOR</span>
                        <h2>Rata-rata per Pertanyaan</h2>
                        <p>Nilai merupakan rata-rata anonim pada skala 1 sampai 5.</p>
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
                    @foreach($mataKuliahs as $mataKuliah)
                        <span class="evaluation-chip">{{ $mataKuliah->kode_mk }} - {{ $mataKuliah->nama_mk }}</span>
                    @endforeach
                </div>

                <div class="evaluation-context-group">
                    <h3>Kelas</h3>
                    @forelse($kelases as $kelas)
                        <span class="evaluation-chip neutral">{{ $kelas->nama_kelas }}</span>
                    @empty
                        <span class="evaluation-muted">Tidak ada data kelas.</span>
                    @endforelse
                </div>

                <div class="evaluation-context-group">
                    <h3>Semester / Tahun Akademik</h3>
                    @foreach($periode as $item)
                        <span class="evaluation-period">{{ $item }}</span>
                    @endforeach
                </div>
            </aside>
        </section>

        <section class="evaluation-comments-card" aria-labelledby="komentar-anonim-title">
            <div class="evaluation-section-heading table-heading">
                <div>
                    <span class="evaluation-eyebrow">UMPAN BALIK ANONIM</span>
                    <h2 id="komentar-anonim-title">Komentar Mahasiswa</h2>
                    <p>Komentar ditampilkan tanpa kode responden maupun identitas mahasiswa.</p>
                </div>
                <span class="evaluation-result-count">{{ number_format($komentar->total()) }} komentar</span>
            </div>

            <div class="evaluation-comments">
                @forelse($komentar as $item)
                    <article class="evaluation-comment">
                        <div class="evaluation-comment-head">
                            <div>
                                <strong>Responden anonim</strong>
                                <span>{{ $item->krs?->mata_kuliah_efektif?->nama_mk ?? 'Mata kuliah tidak tersedia' }}</span>
                            </div>
                            <time datetime="{{ $item->submitted_at?->toIso8601String() }}">{{ $item->submitted_at?->format('d M Y') }}</time>
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
                        <span>Skor evaluasi tersedia, tetapi mahasiswa tidak menuliskan komentar.</span>
                    </div>
                @endforelse
            </div>

            {{ $komentar->appends(request()->query())->onEachSide(1)->links() }}
        </section>
    @endif
</div>
@endsection
