<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SIAKAD STTMI - Sistem Informasi Akademik</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/style.css') }}"
    >

    <link rel="stylesheet" href="{{ asset('assets/css/academic.css') }}?v={{ filemtime(public_path('assets/css/academic.css')) }}">
    @stack('styles')

</head>


<body>

<div class="app-wrapper">


    <!-- ===================================================== -->
    <!-- SIDEBAR -->
    <!-- ===================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <div class="sidebar-logo">

            <img
                src="{{ asset('images/logo_sttmi.jpeg') }}"
                alt="Logo STTM"
                class="logo-img"
            >

            <div class="logo-text">

                <h2>
                    SIAKAD STTMI
                </h2>

                <span>
                    Sistem Informasi Akademik
                </span>

            </div>

        </div>


        <!-- ================= USER SIDEBAR ================= -->

@php
    $sidebarMahasiswa = \App\Models\Mahasiswa::with([
        'prodi',
        'kelas'
    ])
    ->where('user_id', auth()->id())
    ->first();
@endphp

<div class="sidebar-user">

    <div class="user-avatar">
        {{ strtoupper(substr($sidebarMahasiswa->nama ?? 'M', 0, 1)) }}
    </div>

    <div class="user-info">

        <p>
            {{ $sidebarMahasiswa->nama ?? 'Mahasiswa' }}
        </p>

        <span>
            NIM: {{ $sidebarMahasiswa->nim ?? '-' }}
        </span>

        @if($sidebarMahasiswa?->prodi)
            <span>
                {{ $sidebarMahasiswa->prodi->nama_prodi }}
            </span>
        @endif

    </div>

</div>

        <!-- ================================================= -->
        <!-- NAVIGATION -->
        <!-- ================================================= -->

        <nav class="sidebar-nav" aria-label="Menu mahasiswa">
            <div class="nav-section-label">Menu Utama</div>
            <x-sidebar-item route="mahasiswa.dashboard" :active="request()->routeIs('mahasiswa.dashboard', 'mahasiswa.dashboard.*')" icon="dashboard">Dashboard</x-sidebar-item>

            <div class="nav-section-label">Akademik</div>
            <x-sidebar-item route="mahasiswa.kurikulum" :active="request()->routeIs('mahasiswa.kurikulum', 'mahasiswa.kurikulum.*')" icon="book">Kurikulum &amp; Silabus</x-sidebar-item>
            <x-sidebar-item route="mahasiswa.krs" :active="request()->routeIs('mahasiswa.krs', 'mahasiswa.krs.*')" icon="file-check">KRS</x-sidebar-item>
            <x-sidebar-item route="mahasiswa.jadwal" :active="request()->routeIs('mahasiswa.jadwal', 'mahasiswa.jadwal.*')" icon="calendar">Jadwal Kuliah</x-sidebar-item>
            <x-sidebar-item route="mahasiswa.khs" :active="request()->routeIs('mahasiswa.khs', 'mahasiswa.khs.*')" icon="file-chart">KHS &amp; Transkrip</x-sidebar-item>
            <x-sidebar-item route="mahasiswa.kuesioner" :active="request()->routeIs('mahasiswa.kuesioner', 'mahasiswa.kuesioner.*')" icon="clipboard">Kuesioner Dosen</x-sidebar-item>
            <x-sidebar-item route="mahasiswa.skripsi" :active="request()->routeIs('mahasiswa.skripsi', 'mahasiswa.skripsi.*')" icon="graduation">Pengajuan Skripsi</x-sidebar-item>

            <div class="nav-section-label">Layanan</div>
            <x-sidebar-item route="mahasiswa.keuangan" :active="request()->routeIs('mahasiswa.keuangan', 'mahasiswa.keuangan.*')" icon="wallet">Keuangan</x-sidebar-item>

            <div class="nav-section-label">Informasi Kampus</div>
            <x-sidebar-item route="mahasiswa.pemberitahuan" :active="request()->routeIs('mahasiswa.pemberitahuan*')" icon="bell">Pemberitahuan</x-sidebar-item>
            <div class="nav-section-label">Akun</div>
            <x-sidebar-item route="mahasiswa.profil" :active="request()->routeIs('mahasiswa.profil', 'mahasiswa.profil.*')" icon="user">Profil Saya</x-sidebar-item>

        </nav>


        <!-- ================================================= -->
        <!-- SIDEBAR FOOTER -->
        <!-- ================================================= -->

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


    <!-- ===================================================== -->
    <!-- SIDEBAR OVERLAY -->
    <!-- ===================================================== -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()"
    ></div>


    <!-- ===================================================== -->
    <!-- MAIN CONTENT -->
    <!-- ===================================================== -->

    <div class="main-content">


        <!-- ================================================= -->
        <!-- TOPBAR -->
        <!-- ================================================= -->

        <header class="topbar">


            <div class="topbar-left">


                <!-- HAMBURGER -->

                <button type="button" class="hamburger" onclick="toggleSidebar()" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
                    <x-layout-icon name="menu" />
                </button>


                <!-- PAGE TITLE -->

                <div class="page-title">


                    @if(request()->routeIs('mahasiswa.dashboard'))

                        <h1>
                            Dashboard
                        </h1>

                        <p>
                            Selamat datang di SIAKAD STTMI
                        </p>


                    @elseif(request()->routeIs('mahasiswa.krs*'))

                        <h1>
                            KRS
                        </h1>

                        <p>
                            Kartu Rencana Studi mahasiswa
                        </p>


                    @elseif(request()->routeIs('mahasiswa.jadwal'))

                        <h1>
                            Jadwal Kuliah
                        </h1>

                        <p>
                            Jadwal perkuliahan mahasiswa
                        </p>


                    @elseif(request()->routeIs('mahasiswa.khs*'))

                        <h1>
                            KHS & Transkrip
                        </h1>

                        <p>
                            Hasil studi dan nilai akademik mahasiswa
                        </p>


                    @elseif(request()->routeIs('mahasiswa.kuesioner*'))

                        <h1>
                            Kuesioner Dosen
                        </h1>

                        <p>
                            Evaluasi pengajaran sebelum melihat nilai
                        </p>


                    @elseif(request()->routeIs('mahasiswa.kurikulum'))

                        <h1>
                            Kurikulum & Silabus
                        </h1>

                        <p>
                            Kurikulum dan silabus perkuliahan
                        </p>


                    @elseif(request()->routeIs('mahasiswa.keuangan'))

                        <h1>
                            Keuangan
                        </h1>

                        <p>
                            Informasi pembayaran SPP dan UKT
                        </p>


                    @elseif(request()->routeIs('mahasiswa.presensi'))

                        <h1>
                            Presensi & Agenda
                        </h1>

                        <p>
                            Presensi dan agenda perkuliahan
                        </p>


                    @elseif(request()->routeIs('mahasiswa.pemberitahuan*'))
                        <h1>Pemberitahuan</h1><p>Pengumuman dari admin akademik</p>
                    @elseif(request()->routeIs('mahasiswa.profil'))

                        <h1>
                            Profil Saya
                        </h1>

                        <p>
                            Informasi profil mahasiswa
                        </p>


                    @else

                        <h1>
                            SIAKAD STTMI
                        </h1>

                        <p>
                            Sistem Informasi Akademik
                        </p>

                    @endif


                </div>


            </div>


            <!-- ================================================= -->
            <!-- TOPBAR RIGHT -->
            <!-- ================================================= -->

            <div class="topbar-right">


                <!-- NOTIFICATION -->

                <button type="button" class="topbar-btn" onclick="toggleNotif()" title="Notifikasi" aria-label="Notifikasi" aria-controls="notifPanel" aria-expanded="false">
                    <x-layout-icon name="bell" />
                    @if($unreadCount > 0)<span class="notif-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                </button>


                <!-- HELP -->

                <button type="button" class="topbar-btn" title="Bantuan" aria-label="Bantuan">
                    <x-layout-icon name="help" />
                </button>


             <!-- PROFILE -->

<a class="topbar-profile" href="{{ route('mahasiswa.profil') }}" aria-label="Buka profil mahasiswa">

    <div class="topbar-avatar">
        {{ strtoupper(substr($sidebarMahasiswa->nama ?? 'M', 0, 1)) }}
    </div>

    <div class="topbar-profile-info">

        <p>
            {{ $sidebarMahasiswa->nama ?? 'Mahasiswa' }}
        </p>

        <span>
            {{ $sidebarMahasiswa->prodi->nama_prodi ?? 'Program Studi' }}
        </span>

    </div>

</a>


            </div>


        </header>


        <!-- ================================================= -->
        <!-- PAGE CONTENT -->
        <!-- ================================================= -->

        <main class="page-content">

            @yield('content')

        </main>


    </div>


    <!-- ===================================================== -->
    <!-- NOTIFICATION PANEL -->
    <!-- ===================================================== -->

    <x-notification-panel :items="$notificationItems" :unread="$unreadCount" role="mahasiswa" />

</div>

<nav class="app-mobile-nav" aria-label="Navigasi utama mahasiswa">
    <a href="{{ route('mahasiswa.dashboard') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('mahasiswa.dashboard')]) @if(request()->routeIs('mahasiswa.dashboard')) aria-current="page" @endif>
        <x-layout-icon name="dashboard" />
        <span>Beranda</span>
    </a>
    <a href="{{ route('mahasiswa.krs') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('mahasiswa.krs', 'mahasiswa.krs.*')]) @if(request()->routeIs('mahasiswa.krs', 'mahasiswa.krs.*')) aria-current="page" @endif>
        <x-layout-icon name="file-check" />
        <span>KRS</span>
    </a>
    <a href="{{ route('mahasiswa.jadwal') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('mahasiswa.jadwal', 'mahasiswa.jadwal.*')]) @if(request()->routeIs('mahasiswa.jadwal', 'mahasiswa.jadwal.*')) aria-current="page" @endif>
        <x-layout-icon name="calendar" />
        <span>Jadwal</span>
    </a>
    <a href="{{ route('mahasiswa.profil') }}" @class(['app-mobile-nav-item', 'active' => request()->routeIs('mahasiswa.profil', 'mahasiswa.profil.*')]) @if(request()->routeIs('mahasiswa.profil', 'mahasiswa.profil.*')) aria-current="page" @endif>
        <x-layout-icon name="user" />
        <span>Profil</span>
    </a>
</nav>

<script src="{{ asset('assets/js/navigation.js') }}"></script>

</body>

</html>
