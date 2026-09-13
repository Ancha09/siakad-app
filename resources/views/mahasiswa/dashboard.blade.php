@extends('layouts.mahasiswa')

@section('title', 'Dashboard Mahasiswa')

@section('content')

<!-- PAGE CONTENT -->
    <main class="page-content">
      <div class="view active" id="view-dashboard">

        <!-- ── HERO: Background gedung + overlay + konten ── -->
        <div class="dashboard-hero">

          <!-- Header: Logo + Judul -->
          <div class="hero-header">

  

          <img src="{{ asset('images/logo_sttmi.jpeg') }}" alt="Logo STTMI" class="hero-logo-img"
     onerror="this.style.display='none';document.getElementById('logoFallback').style.display='flex'">

            <div class="hero-title-block">
              <h1>SIAKAD STTMI</h1>
              <p>Sistem Informasi Akademik</p>
            </div>
          </div>

          <!-- Grid menu kartu — baris 1: 4 kartu -->
          <div class="hero-menu-section">
            <div class="hero-menu-row cols-4">

              <!-- KRS -->
              <a href="{{ route('mahasiswa.krs') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <rect x="4" y="3" width="16" height="18" rx="2"/>
                    <path d="M9 7h6M9 11h6M9 15h4"/>
                    <circle cx="7" cy="15" r="1" fill="currentColor" stroke="none"/>
                    <path d="M16 15l1.5 1.5L20 13"/>
                  </svg>
                </div>
                <div class="hero-card-title">KRS</div>
                <div class="hero-card-desc">Kartu Rencana Studi</div>
              </a>

              <!-- Jadwal Kuliah -->
              <a href="{{ route('mahasiswa.jadwal') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                    <circle cx="12" cy="15" r="2"/>
                    <path d="M12 13v-1.5"/>
                  </svg>
                </div>
                <div class="hero-card-title">Jadwal Kuliah</div>
                <div class="hero-card-desc">Lihat jadwal semester</div>
              </a>

              <!-- KHS & Transkrip -->
              <a href="{{ route('mahasiswa.khs') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/>
                    <path d="M6 12v5c3.33 1.67 8.67 1.67 12 0v-5"/>
                    <circle cx="19" cy="16" r="2"/>
                    <path d="M19 18v3"/>
                  </svg>
                </div>
                <div class="hero-card-title">KHS &amp; Transkrip</div>
                <div class="hero-card-desc">Nilai &amp; transkrip akademik</div>
              </a>

              <!-- Kurikulum -->
              <a href="{{ route('mahasiswa.kurikulum') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    <path d="M9 7h7M9 11h7M9 15h4"/>
                  </svg>
                </div>
                <div class="hero-card-title">Kurikulum &amp; Silabus</div>
                <div class="hero-card-desc">Struktur kurikulum prodi</div>
              </a>

            </div><!-- / row 1 -->

            <!-- Baris 2: 3 kartu (sesuai gambar referensi) -->
            <div class="hero-menu-row cols-2">

              <!-- Keuangan -->
              <a href="{{ route('mahasiswa.keuangan') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 3H5a2 2 0 00-2 2v2"/>
                    <circle cx="12" cy="14" r="2"/>
                    <path d="M6 14h.01M18 14h.01"/>
                  </svg>
                </div>
                <div class="hero-card-title">Keuangan (SPP/UKT)</div>
                <div class="hero-card-desc">Informasi &amp; riwayat bayar</div>
              </a>

              <!-- Presensi -->
              <a href="{{ route('mahasiswa.presensi') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    <circle cx="18" cy="18" r="3"/>
                    <path d="M18 16v2l1 1"/>
                  </svg>
                </div>
                <div class="hero-card-title">Presensi &amp; Agenda</div>
                <div class="hero-card-desc">Rekap kehadiran kuliah</div>
              </a>

              @if($templateBimbingan)
              <a href="{{ route('mahasiswa.skripsi.template.download') }}" class="hero-menu-card">
                <div class="hero-card-icon">
                  <svg viewBox="0 0 24 24"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M5 21h14a2 2 0 002-2v-2M3 17v2a2 2 0 002 2"/></svg>
                </div>
                <div class="hero-card-title">Kartu Bimbingan</div>
                <div class="hero-card-desc">Unduh template terbaru</div>
              </a>
              @endif

            </div><!-- / row 2 -->
          </div><!-- / hero-menu-section -->

        </div><!-- / dashboard-hero -->

        <!-- ── BERITA TERKINI ─────────────────── -->
        <x-pengumuman-feed />

      </div><!-- / view-dashboard -->
@endsection
