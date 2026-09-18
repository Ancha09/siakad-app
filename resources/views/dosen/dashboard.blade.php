@extends('layouts.dosen')

@section('content')

<div class="view active" id="view-dashboard">

     <!-- ═══ DASHBOARD ════════════════════════════════════════ -->

        <!-- ── HERO ── -->
        <div class="dashboard-hero">

          <div class="hero-header">
            <img src="{{ asset('images/logo_sttmi.jpeg') }}" alt="Logo STTMI" class="hero-logo-img"
               onerror="this.style.display='none';document.getElementById('logoFallback').style.display='flex'">
            <div class="hero-title-block">
              <h1>SIAKAD STTMI</h1>
              <p>Sistem Informasi Akademik</p>
            </div>
          </div>

          <!-- Baris 1: 4 kartu -->
          <div class="hero-menu-section">
            <div class="hero-menu-row cols-4">

              <!-- Jadwal Mengajar -->
           <a href="{{ route('dosen.jadwal') }}" class="hero-menu-card">

    <div class="hero-card-icon">
        <svg viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2"/>
            <path d="M16 2v4M8 2v4M3 10h18"/>
            <circle cx="12" cy="15" r="2"/>
            <path d="M12 13v-1.5"/>
        </svg>
    </div>

    <div class="hero-card-title">
        Jadwal Mengajar
    </div>

    <div class="hero-card-desc">
        Lihat jadwal mengajar
    </div>

</a>

              <!-- Mata Kuliah Ampu -->
            <a href="{{ route('dosen.matakuliah') }}" class="hero-menu-card">

    <div class="hero-card-icon">
        <svg viewBox="0 0 24 24">
            <rect x="4" y="3" width="16" height="18" rx="2"/>
            <path d="M9 7h6M9 11h6M9 15h4"/>
            <circle cx="7" cy="15" r="1" fill="currentColor" stroke="none"/>
            <path d="M16 15l1.5 1.5L20 13"/>
        </svg>
    </div>

    <div class="hero-card-title">
        Mata Kuliah Ampu
    </div>

    <div class="hero-card-desc">
        Daftar MK yang diampu
    </div>

</a>

              <!-- Input Nilai -->
             <a href="{{ route('dosen.nilai') }}" class="hero-menu-card">

    <div class="hero-card-icon">
        <svg viewBox="0 0 24 24">
            <path d="M22 10v6M2 10l10-5 10 5-10 5-10-5z"/>
            <path d="M6 12v5c3.33 1.67 8.67 1.67 12 0v-5"/>
            <circle cx="19" cy="16" r="2"/>
            <path d="M19 18v3"/>
        </svg>
    </div>

    <div class="hero-card-title">
        Input Nilai
    </div>

    <div class="hero-card-desc">
        Entry nilai mahasiswa
    </div>

</a>

            </div><!-- / row 1 -->

            <!-- Baris 2 -->
            <div class="hero-menu-row cols-1">

              <!-- Penelitian -->
            <a href="{{ route('dosen.penelitian') }}" class="hero-menu-card">

    <div class="hero-card-icon">
        <svg viewBox="0 0 24 24">
            <path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
        </svg>
    </div>

    <div class="hero-card-title">
        Penelitian &amp; P3M
    </div>

    <div class="hero-card-desc">
        Rekam penelitian &amp; pengabdian
    </div>

</a>

            </div><!-- / row 2 -->
          </div><!-- / hero-menu-section -->

        </div><!-- / dashboard-hero -->

        <!-- ── BERITA TERKINI ── -->
        <x-pengumuman-feed />

      </div><!-- / view-dashboard -->
@endsection
