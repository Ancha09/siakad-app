<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'SIAKAD STTMI')
    </title>


    {{-- GOOGLE FONT --}}

    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700&display=swap"
        rel="stylesheet"
    >


    {{-- CSS UTAMA --}}

    <link
        rel="stylesheet"
        href="{{ asset('assets/css/style.css') }}"
    >


    <link rel="stylesheet" href="{{ asset('assets/css/academic.css') }}">
    @stack('styles')

</head>


<body>

    @php
        $adminUser = auth()->user();
        $adminName = $adminUser?->name ?: 'Admin Akademik';
        $adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1));
        $adminSubtitle = match (true) {
            request()->routeIs('admin.kurikulum.*', 'admin.jadwal*', 'admin.periode-krs*', 'admin.krs*', 'admin.khs*', 'admin.skripsi*') => 'Kelola proses dan data akademik STTMI',
            request()->routeIs('admin.presensi*', 'admin.kuesioner*', 'admin.laporan.*') => 'Monitoring dan laporan akademik STTMI',
            request()->routeIs('admin.pengumuman.*', 'admin.pemberitahuan*') => 'Informasi dan komunikasi kampus',
            request()->routeIs('profile.*') => 'Kelola identitas dan keamanan akun administrator',
            default => 'Ringkasan akademik seluruh program studi STTMI',
        };
    @endphp


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        {{-- ===================== LOGO ===================== --}}

        <div class="sidebar-logo">

            <img
                src="{{ asset('images/logo_sttmi.jpeg') }}"
                alt="Logo STTMI"
                class="logo-img"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
            >


            <div
                class="logo-badge"
                style="display:none"
            >

                <span class="lb-star">
                    ⭐
                </span>

                <span class="lb-text">
                    STTM
                </span>

                <span class="lb-est">
                    EST.
                </span>

            </div>


            <div class="logo-text">

                <h2>
                    SIAKAD STTM
                </h2>

                <span>
                    Panel Administrator
                </span>

            </div>

        </div>



        {{-- ===================== USER ===================== --}}

        <div class="sidebar-user">

            <div class="user-avatar">
                {{ $adminInitial }}
            </div>


            <div class="user-info">

                <p>
                    {{ $adminName }}
                </p>

                <span>
                    Super Admin
                </span>

            </div>

        </div>



        <!-- =====================================================
             NAVIGATION
        ====================================================== -->

        <nav class="sidebar-nav" aria-label="Menu administrator">
            <div class="nav-section-label">Menu Utama</div>
            <x-sidebar-item route="admin.dashboard" :active="request()->routeIs('admin.dashboard', 'admin.dashboard.*')" icon="dashboard">Dashboard</x-sidebar-item>

            <div class="nav-section-label">Data Master</div>
            <x-sidebar-item route="admin.fakultas" :active="request()->routeIs('admin.fakultas', 'admin.fakultas.*')" icon="building">Fakultas</x-sidebar-item>
            <x-sidebar-item route="admin.prodi" :active="request()->routeIs('admin.prodi', 'admin.prodi.*')" icon="school">Program Studi</x-sidebar-item>
            <x-sidebar-item route="admin.dosen" :active="request()->routeIs('admin.dosen', 'admin.dosen.*')" icon="graduation">Data Dosen</x-sidebar-item>
            <x-sidebar-item route="admin.mahasiswa" :active="request()->routeIs('admin.mahasiswa', 'admin.mahasiswa.*')" icon="users">Data Mahasiswa</x-sidebar-item>
            <x-sidebar-item route="admin.kelas" :active="request()->routeIs('admin.kelas', 'admin.kelas.*')" icon="layers">Data Kelas</x-sidebar-item>
            <x-sidebar-item route="admin.matakuliah" :active="request()->routeIs('admin.matakuliah', 'admin.matakuliah.*')" icon="book">Mata Kuliah</x-sidebar-item>
            <x-sidebar-item route="admin.ruangan" :active="request()->routeIs('admin.ruangan', 'admin.ruangan.*')" icon="door">Ruangan</x-sidebar-item>

            <div class="nav-section-label">Akademik</div>
            <x-sidebar-item route="admin.kurikulum.index" :active="request()->routeIs('admin.kurikulum.*')" icon="book">Kurikulum &amp; Silabus</x-sidebar-item>
            <x-sidebar-item route="admin.jadwal" :active="request()->routeIs('admin.jadwal', 'admin.jadwal.*')" icon="calendar">Jadwal Kuliah</x-sidebar-item>
            <x-sidebar-item route="admin.periode-krs" :active="request()->routeIs('admin.periode-krs', 'admin.periode-krs.*')" icon="period">Periode KRS</x-sidebar-item>
            <x-sidebar-item route="admin.krs" :active="request()->routeIs('admin.krs', 'admin.krs.*')" icon="file-check">KRS</x-sidebar-item>
            <x-sidebar-item route="admin.khs" :active="request()->routeIs('admin.khs', 'admin.khs.*')" icon="file-chart">KHS</x-sidebar-item>
            <x-sidebar-item route="admin.skripsi" :active="request()->routeIs('admin.skripsi', 'admin.skripsi.*')" icon="graduation">Pembimbing Skripsi</x-sidebar-item>

            <div class="nav-section-label">Monitoring &amp; Laporan</div>
            <x-sidebar-item route="admin.presensi" :active="request()->routeIs('admin.presensi', 'admin.presensi.*')" icon="attendance" badge="Baru">Monitoring Absensi</x-sidebar-item>
            <x-sidebar-item route="admin.kuesioner" :active="request()->routeIs('admin.kuesioner', 'admin.kuesioner.*')" icon="clipboard">Evaluasi Dosen</x-sidebar-item>
            <x-sidebar-item route="admin.laporan.index" :active="request()->routeIs('admin.laporan.*')" icon="file">Laporan Akademik</x-sidebar-item>

            <div class="nav-section-label">Informasi</div>
            <x-sidebar-item route="admin.pengumuman.index" :active="request()->routeIs('admin.pengumuman.*')" icon="bell">Kelola Pengumuman</x-sidebar-item>
            <x-sidebar-item route="admin.pemberitahuan" :active="request()->routeIs('admin.pemberitahuan*')" icon="clipboard">Pemberitahuan</x-sidebar-item>
            <div class="nav-section-label">Akun</div>
            <x-sidebar-item route="profile.edit" :active="request()->routeIs('profile.*')" icon="user">Profil Admin</x-sidebar-item>

        </nav>



        <!-- =====================================================
             LOGOUT
        ====================================================== -->

        <div class="sidebar-footer">

            <form
                action="{{ route('logout') }}"
                method="POST"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-btn"
                >
                    <x-layout-icon name="logout" />
                    <span>Keluar</span>
                </button>

            </form>

        </div>


    </aside>



    <!-- =========================================================
         SIDEBAR OVERLAY
    ========================================================== -->

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()"
    ></div>



    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->

    <div class="main-content">



        <!-- =====================================================
             TOPBAR
        ====================================================== -->

        <header class="topbar">


            <div class="topbar-left">


                {{-- HAMBURGER --}}

                <button type="button" class="hamburger" onclick="toggleSidebar()" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
                    <x-layout-icon name="menu" />
                </button>



                {{-- PAGE TITLE --}}

                <div class="page-title">

                    <h1>
                        @hasSection('page-title')
                            @yield('page-title')
                        @else
                            @yield('title', 'Dashboard')
                        @endif
                    </h1>

                    <p>
                        @yield('page-subtitle', $adminSubtitle)
                    </p>

                </div>


            </div>



            <!-- =================================================
                 TOPBAR RIGHT
            ================================================== -->

            <div class="topbar-right">


                {{-- NOTIFIKASI --}}

                <button type="button" class="topbar-btn" onclick="toggleNotif()" title="Notifikasi" aria-label="Notifikasi" aria-controls="notifPanel" aria-expanded="false">
                    <x-layout-icon name="bell" />
                    @if($unreadCount > 0)<span class="notif-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                </button>



                {{-- BANTUAN --}}

                <button type="button" class="topbar-btn" title="Bantuan" aria-label="Bantuan">
                    <x-layout-icon name="help" />
                </button>



                {{-- PROFILE --}}

                <a class="topbar-profile" href="{{ route('profile.edit') }}" aria-label="Buka profil admin">


                    <div class="topbar-avatar">
                        {{ $adminInitial }}
                    </div>


                    <div class="topbar-profile-info">

                        <p>
                            {{ $adminName }}
                        </p>

                        <span>
                            Super Admin
                        </span>

                    </div>


                </a>


            </div>


        </header>



        <!-- =====================================================
             PAGE CONTENT
        ====================================================== -->

        <main class="page-content">

            @yield('content')

        </main>


    </div>



    <!-- =========================================================
         NOTIFICATION PANEL
    ========================================================== -->

    <x-notification-panel :items="$notificationItems" :unread="$unreadCount" role="admin" />

    <!-- =========================================================
         JAVASCRIPT
    ========================================================== -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>

    <script src="https://unpkg.com/lucide@latest"></script>


    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                if (
                    typeof lucide !== 'undefined'
                ) {

                    lucide.createIcons();

                }

            }
        );

    </script>


    @stack('scripts')


<script src="{{ asset('assets/js/navigation.js') }}"></script>

</body>

</html>
