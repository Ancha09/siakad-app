<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIAKAD STTM - Sistem Informasi Akademik (Dosen)</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">

    {{-- CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/academic.css') }}?v={{ filemtime(public_path('assets/css/academic.css')) }}">
    @stack('styles')

</head>


<body>

@php
    $dosenLogin = \App\Models\Dosen::with('prodi')
        ->where('user_id', auth()->id())
        ->first();
@endphp


<div class="app-wrapper">


    <!-- ─── SIDEBAR ──────────────────────────── -->

    <aside class="sidebar" id="sidebar">


        <div class="sidebar-logo">

            <img
                src="{{ asset('images/logo_sttmi.jpeg') }}"
                alt="Logo STTM"
                class="logo-img"
            >

            <div class="logo-text">

                <h2>SIAKAD STTM</h2>

                <span>Sistem Informasi Akademik</span>

            </div>

        </div>


        <div class="sidebar-user">

            <div class="user-avatar">
                {{ strtoupper(substr($dosenLogin->nama ?? 'D', 0, 1)) }}
            </div>

            <div class="user-info">

                <p>
                    {{ $dosenLogin->nama ?? auth()->user()->name }}
                </p>

                <span>
                    NIDN: {{ $dosenLogin->nidn ?? '-' }}
                </span>

            </div>

        </div>


        <nav class="sidebar-nav" aria-label="Menu dosen">
            <div class="nav-section-label">Menu Utama</div>
            <x-sidebar-item route="dosen.dashboard" :active="request()->routeIs('dosen.dashboard', 'dosen.dashboard.*')" icon="dashboard">Dashboard</x-sidebar-item>

            <div class="nav-section-label">Perkuliahan</div>
            <x-sidebar-item route="dosen.matakuliah" :active="request()->routeIs('dosen.matakuliah', 'dosen.matakuliah.*')" icon="book" badge="Baru">Mata Kuliah Ampu</x-sidebar-item>
            <x-sidebar-item route="dosen.jadwal" :active="request()->routeIs('dosen.jadwal', 'dosen.jadwal.*')" icon="calendar">Jadwal Mengajar</x-sidebar-item>
            <x-sidebar-item route="dosen.nilai" :active="request()->routeIs('dosen.nilai', 'dosen.nilai.*') && ! request()->routeIs('dosen.nilai.rekap')" icon="file-check">Input Nilai</x-sidebar-item>
            <x-sidebar-item route="dosen.nilai.rekap" :active="request()->routeIs('dosen.nilai.rekap', 'dosen.nilai.rekap.*')" icon="chart">Rekap Nilai</x-sidebar-item>
            <x-sidebar-item route="dosen.evaluasi" :active="request()->routeIs('dosen.evaluasi', 'dosen.evaluasi.*')" icon="clipboard">Evaluasi Saya</x-sidebar-item>

            <div class="nav-section-label">Bimbingan &amp; Akademik</div>
            <x-sidebar-item route="dosen.kurikulum" :active="request()->routeIs('dosen.kurikulum', 'dosen.kurikulum.*')" icon="book">Kurikulum &amp; Silabus</x-sidebar-item>
            <x-sidebar-item route="dosen.krs" :active="request()->routeIs('dosen.krs', 'dosen.krs.*')" icon="file-check">Persetujuan KRS</x-sidebar-item>
            <x-sidebar-item route="dosen.skripsi" :active="request()->routeIs('dosen.skripsi', 'dosen.skripsi.*')" icon="graduation">Pengajuan Bimbingan</x-sidebar-item>
            <x-sidebar-item route="dosen.penelitian" :active="request()->routeIs('dosen.penelitian', 'dosen.penelitian.*')" icon="research">Penelitian &amp; P3M</x-sidebar-item>

            <div class="nav-section-label">Informasi Kampus</div>
            <x-sidebar-item route="dosen.pemberitahuan" :active="request()->routeIs('dosen.pemberitahuan*')" icon="bell">Pemberitahuan</x-sidebar-item>
            <div class="nav-section-label">Akun</div>
            <x-sidebar-item route="dosen.profil" :active="request()->routeIs('dosen.profil', 'dosen.profil.*')" icon="user">Profil Saya</x-sidebar-item>

        </nav>


        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-btn">
                    <x-layout-icon name="logout" />
                    <span>Keluar</span>
                </button>
            </form>
        </div>


    </aside>


    <!-- Overlay -->

    <div class="sidebar-overlay"
         id="sidebarOverlay"
         onclick="closeSidebar()">
    </div>



    <!-- ─── MAIN CONTENT ─────────────────────── -->

    <div class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-left">


                <button type="button" class="hamburger" onclick="toggleSidebar()" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
                    <x-layout-icon name="menu" />
                </button>


                <div class="page-title">

                    <h1 id="pageTitle">
                        @yield('page-title', 'Dashboard')
                    </h1>

                    <p id="pageSubtitle">
                        @yield('page-subtitle', 'Selamat datang di SIAKAD STTM — Portal Dosen')
                    </p>

                </div>


            </div>



            <div class="topbar-right">


                <button type="button" class="topbar-btn" onclick="toggleNotif()" title="Notifikasi" aria-label="Notifikasi" aria-controls="notifPanel" aria-expanded="false">
                    <x-layout-icon name="bell" />
                    @if($unreadCount > 0)<span class="notif-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                </button>


                <button type="button" class="topbar-btn" title="Bantuan" aria-label="Bantuan">
                    <x-layout-icon name="help" />
                </button>


                <a class="topbar-profile" href="{{ route('dosen.profil') }}" aria-label="Buka profil dosen">


                    <div class="topbar-avatar">

                        {{ strtoupper(substr($dosenLogin->nama ?? 'D', 0, 1)) }}

                    </div>


                    <div class="topbar-profile-info">

                        <p>
                            {{ $dosenLogin->nama ?? auth()->user()->name }}
                        </p>

                        <span>

                            {{ $dosenLogin->jabatan ?? 'Dosen' }}

                            –

                            {{ $dosenLogin->prodi->nama_prodi ?? '-' }}

                        </span>

                    </div>


                </a>


            </div>


        </header>



        <!-- PAGE CONTENT -->

        <main class="page-content">

            @yield('content')

        </main>


    </div>



    <!-- ─── NOTIFICATION PANEL ─────────────────────── -->

    <x-notification-panel :items="$notificationItems" :unread="$unreadCount" role="dosen" />

</div>

<nav class="app-mobile-nav" aria-label="Navigasi utama dosen">
    <a href="{{ route('dosen.dashboard') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('dosen.dashboard')]) @if(request()->routeIs('dosen.dashboard')) aria-current="page" @endif>
        <x-layout-icon name="dashboard" />
        <span>Beranda</span>
    </a>
    <a href="{{ route('dosen.jadwal') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('dosen.jadwal', 'dosen.jadwal.*')]) @if(request()->routeIs('dosen.jadwal', 'dosen.jadwal.*')) aria-current="page" @endif>
        <x-layout-icon name="calendar" />
        <span>Jadwal</span>
    </a>
    <a href="{{ route('dosen.nilai') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('dosen.nilai', 'dosen.nilai.*')]) @if(request()->routeIs('dosen.nilai', 'dosen.nilai.*')) aria-current="page" @endif>
        <x-layout-icon name="file-check" />
        <span>Nilai</span>
    </a>
    <a href="{{ route('dosen.profil') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('dosen.profil', 'dosen.profil.*')]) @if(request()->routeIs('dosen.profil', 'dosen.profil.*')) aria-current="page" @endif>
        <x-layout-icon name="user" />
        <span>Profil</span>
    </a>
</nav>

<script src="{{ asset('assets/js/navigation.js') }}"></script>

</body>

</html>
