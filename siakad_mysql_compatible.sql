-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 06, 2026 at 05:45 PM
-- Server version: 8.0.30
-- PHP Version: 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siakad_sttmi`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dosens`
--

CREATE TABLE `dosens` (
  `id` bigint UNSIGNED NOT NULL,
  `nidn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jabatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `golongan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `skripsi_aktif` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dosens`
--

INSERT INTO `dosens` (`id`, `nidn`, `nama`, `email`, `telepon`, `jabatan`, `golongan`, `prodi_id`, `user_id`, `created_at`, `updated_at`, `skripsi_aktif`) VALUES
(2, '0412038801', 'Dr. Hendra Saputra', 'hendra@sttmi.ac.id', '081234567890', 'Lektor', 'III/c', 1, 2, '2026-08-07 07:11:01', '2026-09-05 01:24:34', 1),
(6, '11223344', 'Chandra', NULL, '0581985981', 'Dosen', NULL, 2, 11, '2026-08-07 20:19:54', '2026-09-05 01:36:58', 1),
(7, '1234', 'Dello', NULL, '07885', 'Kapordi', NULL, 4, 12, '2026-08-07 20:21:32', '2026-09-05 01:24:32', 1),
(9, '11111', 'test 1', NULL, '82893568923', 'Lektor', NULL, 4, 15, '2026-08-09 08:32:50', '2026-09-05 01:24:37', 1);

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fakultas`
--

CREATE TABLE `fakultas` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_fakultas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_fakultas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fakultas`
--

INSERT INTO `fakultas` (`id`, `kode_fakultas`, `nama_fakultas`, `created_at`, `updated_at`) VALUES
(1, 'ILK-01', 'Ilmu Komputer', '2026-08-07 20:35:50', '2026-08-22 01:26:22'),
(3, 'FEB-01', 'Ekonomi dan Bisnis Internasional', '2026-08-07 21:13:35', '2026-08-07 21:13:35'),
(5, 'TK-01', 'Teknik', '2026-08-22 11:20:01', '2026-08-22 11:20:01');

-- --------------------------------------------------------

--
-- Table structure for table `jadwals`
--

CREATE TABLE `jadwals` (
  `id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `mata_kuliah_id` bigint UNSIGNED DEFAULT NULL,
  `dosen_id` bigint UNSIGNED DEFAULT NULL,
  `ruangan_id` bigint UNSIGNED DEFAULT NULL,
  `kelas_id` bigint UNSIGNED DEFAULT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kelas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_akademik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester_akademik` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwals`
--

INSERT INTO `jadwals` (`id`, `created_at`, `updated_at`, `mata_kuliah_id`, `dosen_id`, `ruangan_id`, `kelas_id`, `hari`, `jam_mulai`, `jam_selesai`, `kelas`, `tahun_akademik`, `semester_akademik`) VALUES
(1, '2026-08-07 20:19:10', '2026-08-07 20:19:10', 1, 2, 1, NULL, 'Senin', '08:20:00', '09:50:00', 'Sistem informasi 5', '2026/2027', 'Ganjil'),
(4, '2026-08-07 21:26:12', '2026-08-07 21:26:12', 3, 6, 1, NULL, 'Senin', '14:50:00', '16:20:00', 'Ilmu komputer 1', '2026/2027', 'Ganjil'),
(5, '2026-08-07 21:28:32', '2026-08-07 21:28:39', 4, 7, 3, NULL, 'Kamis', '10:20:00', '12:00:00', 'Kelas A', '2026/2027', 'Ganjil'),
(6, '2026-08-22 02:05:13', '2026-08-22 02:05:25', 5, 7, 3, NULL, 'Rabu', '10:20:00', '12:20:00', NULL, '2026/2027', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` bigint UNSIGNED NOT NULL,
  `nama_kelas` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prodi_id` bigint UNSIGNED NOT NULL,
  `angkatan` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` tinyint UNSIGNED DEFAULT NULL,
  `dosen_wali_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `prodi_id`, `angkatan`, `semester`, `dosen_wali_id`, `created_at`, `updated_at`) VALUES
(1, 'TM-1A', 4, '2026', 1, 7, '2026-08-19 02:28:59', '2026-08-19 02:28:59'),
(2, 'SI-1', 1, '2026', 3, 6, '2026-08-19 04:34:08', '2026-08-19 04:34:08'),
(3, 'IT-1', 2, '2026', 2, 2, '2026-08-19 04:36:53', '2026-08-19 04:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `khs`
--

CREATE TABLE `khs` (
  `id` bigint UNSIGNED NOT NULL,
  `krs_id` bigint UNSIGNED NOT NULL,
  `nilai_angka` decimal(5,2) DEFAULT NULL,
  `nilai_huruf` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bobot` decimal(3,2) DEFAULT NULL,
  `tahun_akademik` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_akademik` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khs`
--

INSERT INTO `khs` (`id`, `krs_id`, `nilai_angka`, `nilai_huruf`, `bobot`, `tahun_akademik`, `semester_akademik`, `created_at`, `updated_at`) VALUES
(1, 3, 75.89, 'B+', 3.50, '2026/2027', 'Ganjil', '2026-08-07 21:50:21', '2026-09-05 10:15:13'),
(4, 9, 80.00, 'A-', 3.75, '2026/2027', 'Ganjil', '2026-09-05 10:15:13', '2026-09-05 10:15:13'),
(5, 10, 85.00, 'A', 4.00, '2026/2027', 'Ganjil', '2026-09-05 10:15:13', '2026-09-05 10:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `krs`
--

CREATE TABLE `krs` (
  `id` bigint UNSIGNED NOT NULL,
  `mahasiswa_id` bigint UNSIGNED NOT NULL,
  `jadwal_id` bigint UNSIGNED NOT NULL,
  `status` enum('Menunggu','Diambil','Disetujui','Ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `tahun_akademik` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_akademik` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `krs`
--

INSERT INTO `krs` (`id`, `mahasiswa_id`, `jadwal_id`, `status`, `alasan_penolakan`, `tahun_akademik`, `semester_akademik`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'Diambil', NULL, '2026/2027', 'Ganjil', '2026-08-07 21:25:33', '2026-08-07 21:25:33'),
(3, 2, 5, 'Disetujui', NULL, '2026/2027', 'Ganjil', '2026-08-07 21:29:00', '2026-08-22 20:51:34'),
(9, 7, 5, 'Disetujui', NULL, '2026/2027', 'Ganjil', '2026-08-22 22:17:16', '2026-09-05 10:13:00'),
(10, 6, 5, 'Disetujui', NULL, '2026/2027', 'Ganjil', '2026-09-05 10:11:12', '2026-09-05 10:12:57');

-- --------------------------------------------------------

--
-- Table structure for table `kuesioners`
--

CREATE TABLE `kuesioners` (
  `id` bigint UNSIGNED NOT NULL,
  `krs_id` bigint UNSIGNED NOT NULL,
  `penguasaan_materi` tinyint UNSIGNED NOT NULL,
  `kejelasan_penyampaian` tinyint UNSIGNED NOT NULL,
  `kesesuaian_rps` tinyint UNSIGNED NOT NULL,
  `ketepatan_waktu` tinyint UNSIGNED NOT NULL,
  `kesempatan_bertanya` tinyint UNSIGNED NOT NULL,
  `objektivitas_penilaian` tinyint UNSIGNED NOT NULL,
  `penggunaan_media` tinyint UNSIGNED NOT NULL,
  `motivasi_belajar` tinyint UNSIGNED NOT NULL,
  `komentar` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kuesioners`
--

INSERT INTO `kuesioners` (`id`, `krs_id`, `penguasaan_materi`, `kejelasan_penyampaian`, `kesesuaian_rps`, `ketepatan_waktu`, `kesempatan_bertanya`, `objektivitas_penilaian`, `penggunaan_media`, `motivasi_belajar`, `komentar`, `submitted_at`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 5, 5, 5, 5, 5, 5, 5, 'sangat mantap', '2026-09-02 09:06:05', '2026-09-02 09:06:05', '2026-09-02 09:06:05'),
(2, 10, 5, 5, 5, 5, 5, 5, 5, 5, 'banyakin jokesnya bapaaa hehehe', '2026-09-05 10:16:35', '2026-09-05 10:16:35', '2026-09-05 10:16:35');

-- --------------------------------------------------------

--
-- Table structure for table `kurikulums`
--

CREATE TABLE `kurikulums` (
  `id` bigint UNSIGNED NOT NULL,
  `prodi_id` bigint UNSIGNED NOT NULL,
  `nama_kurikulum` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun_mulai` year NOT NULL,
  `tahun_selesai` year DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kurikulums`
--

INSERT INTO `kurikulums` (`id`, `prodi_id`, `nama_kurikulum`, `tahun_mulai`, `tahun_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 'Kurikulum', '2026', '2033', 'Aktif', '2026-09-05 09:57:57', '2026-09-05 09:57:57');

-- --------------------------------------------------------

--
-- Table structure for table `kurikulum_mata_kuliah`
--

CREATE TABLE `kurikulum_mata_kuliah` (
  `id` bigint UNSIGNED NOT NULL,
  `kurikulum_id` bigint UNSIGNED NOT NULL,
  `mata_kuliah_id` bigint UNSIGNED NOT NULL,
  `semester` tinyint UNSIGNED NOT NULL,
  `jenis` enum('Wajib','Pilihan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Wajib',
  `silabus_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kurikulum_mata_kuliah`
--

INSERT INTO `kurikulum_mata_kuliah` (`id`, `kurikulum_id`, `mata_kuliah_id`, `semester`, `jenis`, `silabus_path`, `created_at`, `updated_at`) VALUES
(2, 1, 4, 1, 'Wajib', NULL, '2026-09-05 09:59:26', '2026-09-05 09:59:26');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswas`
--

CREATE TABLE `mahasiswas` (
  `id` bigint UNSIGNED NOT NULL,
  `nim` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `angkatan` year DEFAULT NULL,
  `semester` tinyint UNSIGNED DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL,
  `kelas_id` bigint UNSIGNED DEFAULT NULL,
  `dosen_wali_id` bigint UNSIGNED DEFAULT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mahasiswas`
--

INSERT INTO `mahasiswas` (`id`, `nim`, `created_at`, `updated_at`, `nama`, `email`, `telepon`, `angkatan`, `semester`, `prodi_id`, `kelas_id`, `dosen_wali_id`, `user_id`) VALUES
(2, '123124', '2026-08-07 10:37:54', '2026-09-05 01:36:58', 'owowi', NULL, '3205932', '2024', 4, 1, 2, 6, 9),
(4, '10522191', '2026-08-07 21:22:59', '2026-09-05 01:36:58', 'Test', NULL, '08897656', '2026', 1, 1, 2, 6, 13),
(5, '222222', '2026-08-09 08:34:42', '2026-09-05 01:36:58', 'Test2', NULL, '0283758932', '2022', 8, 5, 3, 6, 16),
(6, '12345', '2026-08-18 02:35:34', '2026-09-05 01:36:58', 'Tester', NULL, '52352', '2026', 7, 4, 1, 6, 19),
(7, '0001', '2026-08-22 01:28:57', '2026-09-05 01:36:58', 'Testing', NULL, '0932705', '2026', 5, 4, NULL, 6, 20);

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliahs`
--

CREATE TABLE `mata_kuliahs` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_mk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_mk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sks` tinyint UNSIGNED NOT NULL,
  `semester` tinyint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `prodi_id` bigint UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mata_kuliahs`
--

INSERT INTO `mata_kuliahs` (`id`, `kode_mk`, `nama_mk`, `sks`, `semester`, `created_at`, `updated_at`, `prodi_id`) VALUES
(1, '001', 'Sistem Basis Data', 3, 1, '2026-08-07 19:45:02', '2026-08-07 19:45:02', 1),
(3, '003', 'Pemograman Web', 3, 1, '2026-08-07 20:20:40', '2026-08-07 20:20:40', 2),
(4, 'TB-01', 'Rekaysa Pertambangan', 4, 3, '2026-08-07 20:22:26', '2026-08-22 01:44:26', 4),
(5, 'KS1', 'Kimia', 4, 1, '2026-08-22 01:43:35', '2026-08-22 01:43:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(12, '0001_01_01_000000_create_users_table', 1),
(13, '0001_01_01_000001_create_cache_table', 1),
(14, '0001_01_01_000002_create_jobs_table', 1),
(15, '2026_07_05_030849_create_fakultas_table', 1),
(16, '2026_07_05_030858_create_prodis_table', 1),
(17, '2026_07_05_030905_create_dosens_table', 1),
(18, '2026_07_05_030913_create_mahasiswas_table', 1),
(19, '2026_07_05_030919_create_mata_kuliahs_table', 1),
(20, '2026_07_05_030925_create_ruangans_table', 1),
(21, '2026_07_05_030930_create_jadwals_table', 1),
(22, '2026_07_05_041846_add_role_to_users_table', 1),
(23, '2026_08_07_170017_add_fields_to_mahasiswas_table', 2),
(24, '2026_08_08_024132_add_fields_to_mata_kuliahs_table', 3),
(25, '2026_08_08_025746_add_fields_to_ruangans_table', 4),
(26, '2026_08_08_031351_add_fields_to_jadwals_table', 5),
(27, '2026_08_08_033308_add_fields_to_fakultas_table', 6),
(28, '2026_08_08_033954_create_krs_table', 7),
(29, '2026_08_08_043148_create_khs_table', 8),
(30, '2026_08_09_044453_create_presensis_table', 9),
(31, '2026_08_18_145324_add_dosen_wali_id_to_mahasiswa_table', 10),
(32, '2026_08_19_091444_create_kelas_table', 11),
(33, '2026_08_19_091740_add_kelas_id_to_mahasiswas_table', 12),
(34, '2026_08_19_115128_add_fakultas_id_to_prodis_table', 13),
(35, '2026_08_22_084719_add_kelas_id_to_jadwals_table', 14),
(36, '2026_08_22_094833_create_periode_krs_table', 15),
(37, '2026_08_23_034249_update_status_enum_on_krs_table', 16),
(38, '2026_08_23_040533_add_alasan_penolakan_to_krs_table', 17),
(39, '2026_08_23_131422_create_kurikulums_table', 18),
(40, '2026_08_23_131445_create_kurikulum_mata_kuliah_table', 19),
(41, '2026_08_23_140232_add_foto_materi_to_presensis_table', 20),
(42, '2026_08_23_141050_create_presensi_pertemuans_table', 21),
(43, '2026_09_02_000000_create_kuesioners_table', 22),
(44, '2026_09_05_000000_create_skripsi_tables', 23),
(45, '2026_09_05_120000_add_silabus_path_to_kurikulum_mata_kuliah_table', 24),
(46, '2026_09_06_000000_create_penelitian_p3m_table', 25),
(47, '2026_09_06_120000_create_pengumuman_tables', 26),
(48, '2026_09_06_130000_fix_krs_status_for_postgresql', 27);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penelitian_p3m`
--

CREATE TABLE `penelitian_p3m` (
  `id` bigint UNSIGNED NOT NULL,
  `dosen_id` bigint UNSIGNED NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('Penelitian','Pengabdian') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tahun` year NOT NULL,
  `sumber_dana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Draft','Berjalan','Selesai','Terbit') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `ringkasan` text COLLATE utf8mb4_unicode_ci,
  `link_artikel` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hasil_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artikel_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_skripsis`
--

CREATE TABLE `pengajuan_skripsis` (
  `id` bigint UNSIGNED NOT NULL,
  `periode_skripsi_id` bigint UNSIGNED NOT NULL,
  `mahasiswa_id` bigint UNSIGNED NOT NULL,
  `dosen_id` bigint UNSIGNED NOT NULL,
  `judul` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Menunggu','Diterima','Ditolak','Dialihkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Menunggu',
  `alasan_keputusan` text COLLATE utf8mb4_unicode_ci,
  `diputuskan_pada` datetime DEFAULT NULL,
  `dibuat_oleh` bigint UNSIGNED NOT NULL,
  `jenis_pembuat` enum('mahasiswa','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `pengajuan_asal_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengajuan_skripsis`
--

INSERT INTO `pengajuan_skripsis` (`id`, `periode_skripsi_id`, `mahasiswa_id`, `dosen_id`, `judul`, `status`, `alasan_keputusan`, `diputuskan_pada`, `dibuat_oleh`, `jenis_pembuat`, `pengajuan_asal_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 6, 'Rekaya Janda Pirang', 'Diterima', NULL, '2026-09-05 16:27:16', 19, 'mahasiswa', NULL, '2026-09-05 01:35:54', '2026-09-05 09:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `pengumumans`
--

CREATE TABLE `pengumumans` (
  `id` bigint UNSIGNED NOT NULL,
  `penulis_id` bigint UNSIGNED DEFAULT NULL,
  `penerima` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tautan` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penting` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `terbit_pada` datetime DEFAULT NULL,
  `berakhir_pada` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumumans`
--

INSERT INTO `pengumumans` (`id`, `penulis_id`, `penerima`, `judul`, `isi`, `tautan`, `penting`, `status`, `terbit_pada`, `berakhir_pada`, `created_at`, `updated_at`) VALUES
(1, 1, 'mahasiswa', 'Testt pengumuman', 'Hanya testing saja', NULL, 1, 'terbit', '2026-09-05 09:53:00', '2026-09-08 09:53:00', '2026-09-06 02:53:59', '2026-09-06 02:57:29'),
(2, 1, 'dosen', 'Testt pengumuman', 'untuk dosen', NULL, 1, 'terbit', '2026-09-05 09:57:00', '2026-09-08 09:57:00', '2026-09-06 02:57:17', '2026-09-06 02:57:36');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman_reads`
--

CREATE TABLE `pengumuman_reads` (
  `id` bigint UNSIGNED NOT NULL,
  `pengumuman_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `read_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumuman_reads`
--

INSERT INTO `pengumuman_reads` (`id`, `pengumuman_id`, `user_id`, `read_at`) VALUES
(1, 2, 12, '2026-09-06 09:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `periode_krs`
--

CREATE TABLE `periode_krs` (
  `id` bigint UNSIGNED NOT NULL,
  `tahun_akademik` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('Ganjil','Genap') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `minimal_sks` int UNSIGNED NOT NULL DEFAULT '0',
  `maksimal_sks` int UNSIGNED NOT NULL DEFAULT '24',
  `status` enum('Dibuka','Ditutup') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ditutup',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `periode_krs`
--

INSERT INTO `periode_krs` (`id`, `tahun_akademik`, `semester`, `tanggal_mulai`, `tanggal_selesai`, `minimal_sks`, `maksimal_sks`, `status`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, '2026/2027', 'Ganjil', '2026-08-22 00:00:00', '2026-09-15 23:59:00', 0, 24, 'Dibuka', NULL, '2026-08-22 20:40:11', '2026-08-22 20:40:11');

-- --------------------------------------------------------

--
-- Table structure for table `periode_skripsis`
--

CREATE TABLE `periode_skripsis` (
  `id` bigint UNSIGNED NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mulai` datetime NOT NULL,
  `berakhir` datetime NOT NULL,
  `lock_version` bigint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `periode_skripsis`
--

INSERT INTO `periode_skripsis` (`id`, `nama`, `mulai`, `berakhir`, `lock_version`, `created_at`, `updated_at`) VALUES
(1, 'Semester Ganjil 2026/2027', '2026-09-04 15:33:00', '2027-02-05 15:33:00', 3, '2026-09-05 01:33:36', '2026-09-05 09:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `presensis`
--

CREATE TABLE `presensis` (
  `id` bigint UNSIGNED NOT NULL,
  `krs_id` bigint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `pertemuan` tinyint UNSIGNED NOT NULL,
  `status` enum('Hadir','Izin','Sakit','Alpha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Hadir',
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `materi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `presensis`
--

INSERT INTO `presensis` (`id`, `krs_id`, `tanggal`, `pertemuan`, `status`, `keterangan`, `foto`, `materi`, `created_at`, `updated_at`) VALUES
(1, 3, '2026-08-23', 1, 'Hadir', NULL, NULL, NULL, '2026-08-08 21:58:21', '2026-08-23 07:22:15'),
(2, 3, '2026-08-24', 2, 'Izin', NULL, NULL, NULL, '2026-08-09 08:44:56', '2026-08-23 07:25:45'),
(3, 3, '2026-08-24', 3, 'Hadir', NULL, NULL, NULL, '2026-08-09 08:45:45', '2026-08-24 06:48:18'),
(4, 9, '2026-08-23', 1, 'Hadir', NULL, NULL, NULL, '2026-08-23 07:22:15', '2026-08-23 07:22:15'),
(5, 9, '2026-08-24', 2, 'Hadir', NULL, NULL, NULL, '2026-08-23 07:25:45', '2026-08-23 07:25:45'),
(6, 9, '2026-08-24', 3, 'Hadir', NULL, NULL, NULL, '2026-08-24 06:48:18', '2026-08-24 06:48:18'),
(7, 10, '2026-08-23', 1, 'Hadir', NULL, NULL, NULL, '2026-09-05 10:18:19', '2026-09-05 10:18:19'),
(8, 10, '2026-08-24', 2, 'Hadir', NULL, NULL, NULL, '2026-09-05 10:18:26', '2026-09-05 10:18:26'),
(9, 10, '2026-08-24', 3, 'Hadir', NULL, NULL, NULL, '2026-09-05 10:18:34', '2026-09-05 10:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `presensi_pertemuans`
--

CREATE TABLE `presensi_pertemuans` (
  `id` bigint UNSIGNED NOT NULL,
  `jadwal_id` bigint UNSIGNED NOT NULL,
  `pertemuan` tinyint UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `materi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `presensi_pertemuans`
--

INSERT INTO `presensi_pertemuans` (`id`, `jadwal_id`, `pertemuan`, `tanggal`, `foto`, `materi`, `created_at`, `updated_at`) VALUES
(1, 5, 1, '2026-08-23', 'presensi/foto/mwMvoUkSNOn5cGUunkZFwrj6dxJo49GoEaVybZ05.jpg', NULL, '2026-08-23 07:22:15', '2026-08-23 07:22:15'),
(2, 5, 2, '2026-08-24', 'presensi/foto/5r9MzS1DGfH3cgtq7mn4h7AmQEUWdkKhJPaJKll9.jpg', NULL, '2026-08-23 07:25:45', '2026-08-23 07:25:45'),
(3, 5, 3, '2026-08-24', 'presensi/foto/v4Vrmivt8K01FbLWSQx0aVVs7abza7wJXvyrpbJQ.jpg', NULL, '2026-08-24 06:48:18', '2026-08-24 06:48:18');

-- --------------------------------------------------------

--
-- Table structure for table `prodis`
--

CREATE TABLE `prodis` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_prodi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_prodi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenjang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'S1',
  `fakultas_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prodis`
--

INSERT INTO `prodis` (`id`, `kode_prodi`, `nama_prodi`, `jenjang`, `fakultas_id`, `created_at`, `updated_at`) VALUES
(1, 'SI', 'Sistem Informasi', 'S1', 3, '2026-08-07 07:05:57', '2026-08-22 11:20:43'),
(2, 'TI', 'Teknik Informatika', 'S1', 1, '2026-08-07 07:06:05', '2026-08-22 11:20:35'),
(4, 'TB', 'Teknik Pertambangan', 'S1', 5, '2026-08-07 19:54:47', '2026-08-22 11:20:22'),
(5, 'SI-1', 'Ilmu Komputer', 'S1', 1, '2026-08-07 21:16:03', '2026-08-19 05:22:52');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_skripsis`
--

CREATE TABLE `riwayat_skripsis` (
  `id` bigint UNSIGNED NOT NULL,
  `periode_skripsi_id` bigint UNSIGNED DEFAULT NULL,
  `pengajuan_skripsi_id` bigint UNSIGNED DEFAULT NULL,
  `pelaku_id` bigint UNSIGNED NOT NULL,
  `pelaku_nama` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pelaku_role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tindakan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perubahan` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_skripsis`
--

INSERT INTO `riwayat_skripsis` (`id`, `periode_skripsi_id`, `pengajuan_skripsi_id`, `pelaku_id`, `pelaku_nama`, `pelaku_role`, `tindakan`, `perubahan`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 1, 'Administrator', 'admin', 'Kelayakan dosen', '{\"nama\": \"Chandra\", \"sebelum\": false, \"sesudah\": true, \"dosen_id\": 6}', '2026-09-05 01:24:25', '2026-09-05 01:24:25'),
(2, NULL, NULL, 1, 'Administrator', 'admin', 'Kelayakan dosen', '{\"nama\": \"Dello\", \"sebelum\": false, \"sesudah\": true, \"dosen_id\": 7}', '2026-09-05 01:24:32', '2026-09-05 01:24:32'),
(3, NULL, NULL, 1, 'Administrator', 'admin', 'Kelayakan dosen', '{\"nama\": \"Dr. Hendra Saputra\", \"sebelum\": false, \"sesudah\": true, \"dosen_id\": 2}', '2026-09-05 01:24:34', '2026-09-05 01:24:34'),
(4, NULL, NULL, 1, 'Administrator', 'admin', 'Kelayakan dosen', '{\"nama\": \"test 1\", \"sebelum\": false, \"sesudah\": true, \"dosen_id\": 9}', '2026-09-05 01:24:37', '2026-09-05 01:24:37'),
(5, 1, NULL, 1, 'Administrator', 'admin', 'Pengaturan periode', '{\"sebelum\": {\"nama\": null, \"mulai\": null, \"berakhir\": null}, \"sesudah\": {\"nama\": \"Semester Ganjil 2026/2027\", \"mulai\": \"2026-09-05T15:33:00.000000Z\", \"berakhir\": \"2027-02-05T15:33:00.000000Z\"}, \"timezone\": \"UTC\"}', '2026-09-05 01:33:36', '2026-09-05 01:33:36'),
(6, 1, NULL, 1, 'Administrator', 'admin', 'Pengaturan periode', '{\"sebelum\": {\"nama\": \"Semester Ganjil 2026/2027\", \"mulai\": \"2026-09-05T15:33:00.000000Z\", \"berakhir\": \"2027-02-05T15:33:00.000000Z\"}, \"sesudah\": {\"nama\": \"Semester Ganjil 2026/2027\", \"mulai\": \"2026-09-04T15:33:00.000000Z\", \"berakhir\": \"2027-02-05T15:33:00.000000Z\"}, \"timezone\": \"UTC\"}', '2026-09-05 01:35:09', '2026-09-05 01:35:09'),
(7, 1, 1, 19, 'Tester', 'mahasiswa', 'Pengajuan', '{\"status\": \"Menunggu\", \"judul_baru\": \"Rekaya Janda Pirang\", \"judul_lama\": null, \"dosen_asal_id\": null, \"dosen_tujuan_id\": 6}', '2026-09-05 01:35:54', '2026-09-05 01:35:54'),
(8, 1, 1, 11, 'Chandra', 'dosen', 'Diterima', '{\"judul\": \"Rekaya Janda Pirang\", \"alasan\": null, \"dosen_id\": 6, \"status_baru\": \"Diterima\", \"status_lama\": \"Menunggu\"}', '2026-09-05 09:27:16', '2026-09-05 09:27:16');

-- --------------------------------------------------------

--
-- Table structure for table `ruangans`
--

CREATE TABLE `ruangans` (
  `id` bigint UNSIGNED NOT NULL,
  `kode_ruangan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_ruangan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gedung` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kapasitas` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ruangans`
--

INSERT INTO `ruangans` (`id`, `kode_ruangan`, `nama_ruangan`, `gedung`, `kapasitas`, `created_at`, `updated_at`) VALUES
(1, '001', 'Lab Komputer', 'A lantai 2', 40, '2026-08-07 20:01:56', '2026-08-07 20:01:56'),
(3, '003', 'Ruangan uji lab', 'B lantai 3', 40, '2026-08-07 20:03:14', '2026-08-07 20:03:14');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('rUALKowGYu0n7bYITLcZ2sKVP7UfoxlN0BxS7sT1', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJJRDZzblNqc0RJT2xKcE1zZVNGNFpoRjVoTlBZbm9MaUh4c1dIeWZmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9hZG1pblwvZGFzaGJvYXJkIiwicm91dGUiOiJhZG1pbi5kYXNoYm9hcmQifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1788712269),
('T9ojT0xXM60YCVcsVYMl1fm3JutX1CuS5od0pbg9', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJhQWJoMmJOeWVpa0QyZ21paWs0M1FCU1FCYzZrMzNhSkFrclVPM3luIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvbWFoYXNpc3dhXC9za3JpcHNpIn0sIl9wcmV2aW91cyI6eyJ1cmwiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODAwMFwvbG9naW4iLCJyb3V0ZSI6ImxvZ2luIn19', 1788689506);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','dosen','mahasiswa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mahasiswa',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `login`, `role`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', 'admin', 'admin@sttmi.local', NULL, '$2y$12$sbSWHrkOAQprXKu4i.oHoejaTbDN0GkF/Cv6BeTip2nMrP3lfC2me', NULL, '2026-08-07 06:56:22', '2026-09-06 17:44:37'),
(2, 'Dr. Hendra Saputra', '0412038801', 'dosen', NULL, NULL, '$2y$12$Ik81NJkXjm/sfEkFSwvufetVa0UQAmttkrf21Nd9Xr4Rvnd5Wl9Fi', NULL, '2026-08-07 06:56:33', '2026-08-07 06:56:33'),
(7, 'Budi Santoso', '2310114001', 'mahasiswa', NULL, NULL, '$2y$12$YuatV7QYBFZ5RZFNtHx32uVCcu0KChP299javK4feNe7ZU5CCVr2C', NULL, '2026-08-07 08:42:04', '2026-08-07 08:42:04'),
(9, 'owowi', '123124', 'mahasiswa', NULL, NULL, '$2y$12$SuhFPAi7pM1ybzOFiFY7wuWigps3HJ9cFuso8PLqsTGFMpI7br6ou', NULL, '2026-08-07 10:37:54', '2026-09-02 09:02:47'),
(11, 'Chandra', '11223344', 'dosen', NULL, NULL, '$2y$12$eP.BiDU/u3t8vu2wb9e4xuA8bipxVCfLefOUq0J1xduw1z4afr2Li', NULL, '2026-08-07 20:19:54', '2026-09-05 01:36:58'),
(12, 'Dello', '1234', 'dosen', NULL, NULL, '$2y$12$jGDFHHIDWDt2FjOS6hX9WuGYS.w5luGgVelhVXKkOkoDf0rkieeam', NULL, '2026-08-07 20:21:32', '2026-08-17 09:13:18'),
(13, 'Test', '10522191', 'mahasiswa', NULL, NULL, '$2y$12$G1Qo61o/ZNRPoqMgsmF96ubP3sR0NKW025wFfVubJCbkSKCmwNDku', NULL, '2026-08-07 21:22:59', '2026-08-07 21:22:59'),
(15, 'test 1', '11111', 'dosen', NULL, NULL, '$2y$12$s./mpMX0lF2JCO7jzNUyweZd/H/HAhB4.5hrtKZrI3tEgxCORLLJu', NULL, '2026-08-09 08:32:50', '2026-08-09 08:32:50'),
(16, 'Test2', '222222', 'mahasiswa', NULL, NULL, '$2y$12$/Z.TUcBMqrmpqF3kaAxIruDOLPhzVj42IRBzqLUA22lnlQwFcEppi', NULL, '2026-08-09 08:34:42', '2026-08-09 08:34:42'),
(19, 'Tester', '12345', 'mahasiswa', NULL, NULL, '$2y$12$3by6TvUmGZ.xh22B0SHVoeC50U/QSY1Il6R.KJhuyXRRm0ecePvYK', NULL, '2026-08-18 02:35:34', '2026-09-05 01:31:32'),
(20, 'Testing', '0001', 'mahasiswa', NULL, NULL, '$2y$12$59gdnW1kRi4hGZi9oBR5iuTyYyTjgkCixnxk7fpX7wp2Ul9/Ny1Pu', NULL, '2026-08-22 01:28:57', '2026-08-22 01:28:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `dosens`
--
ALTER TABLE `dosens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dosens_nidn_unique` (`nidn`),
  ADD KEY `dosens_prodi_id_foreign` (`prodi_id`),
  ADD KEY `dosens_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fakultas_kode_fakultas_unique` (`kode_fakultas`);

--
-- Indexes for table `jadwals`
--
ALTER TABLE `jadwals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwals_mata_kuliah_id_foreign` (`mata_kuliah_id`),
  ADD KEY `jadwals_dosen_id_foreign` (`dosen_id`),
  ADD KEY `jadwals_ruangan_id_foreign` (`ruangan_id`),
  ADD KEY `jadwals_kelas_id_foreign` (`kelas_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kelas_prodi_id_foreign` (`prodi_id`),
  ADD KEY `kelas_dosen_wali_id_foreign` (`dosen_wali_id`);

--
-- Indexes for table `khs`
--
ALTER TABLE `khs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `khs_krs_id_foreign` (`krs_id`);

--
-- Indexes for table `krs`
--
ALTER TABLE `krs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `krs_mahasiswa_id_foreign` (`mahasiswa_id`),
  ADD KEY `krs_jadwal_id_foreign` (`jadwal_id`);

--
-- Indexes for table `kuesioners`
--
ALTER TABLE `kuesioners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kuesioners_krs_id_unique` (`krs_id`);

--
-- Indexes for table `kurikulums`
--
ALTER TABLE `kurikulums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kurikulums_prodi_id_foreign` (`prodi_id`);

--
-- Indexes for table `kurikulum_mata_kuliah`
--
ALTER TABLE `kurikulum_mata_kuliah`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kmk_unique` (`kurikulum_id`,`mata_kuliah_id`,`semester`),
  ADD KEY `kurikulum_mata_kuliah_mata_kuliah_id_foreign` (`mata_kuliah_id`);

--
-- Indexes for table `mahasiswas`
--
ALTER TABLE `mahasiswas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mahasiswas_nim_unique` (`nim`),
  ADD KEY `mahasiswas_prodi_id_foreign` (`prodi_id`),
  ADD KEY `mahasiswas_user_id_foreign` (`user_id`),
  ADD KEY `mahasiswas_dosen_wali_id_foreign` (`dosen_wali_id`),
  ADD KEY `mahasiswas_kelas_id_foreign` (`kelas_id`);

--
-- Indexes for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mata_kuliahs_kode_mk_unique` (`kode_mk`),
  ADD KEY `mata_kuliahs_prodi_id_foreign` (`prodi_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `penelitian_p3m`
--
ALTER TABLE `penelitian_p3m`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penelitian_p3m_dosen_id_tahun_index` (`dosen_id`,`tahun`),
  ADD KEY `penelitian_p3m_dosen_id_jenis_index` (`dosen_id`,`jenis`);

--
-- Indexes for table `pengajuan_skripsis`
--
ALTER TABLE `pengajuan_skripsis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengajuan_skripsis_mahasiswa_id_foreign` (`mahasiswa_id`),
  ADD KEY `pengajuan_skripsis_dosen_id_foreign` (`dosen_id`),
  ADD KEY `pengajuan_skripsis_dibuat_oleh_foreign` (`dibuat_oleh`),
  ADD KEY `pengajuan_skripsis_pengajuan_asal_id_foreign` (`pengajuan_asal_id`),
  ADD KEY `skripsi_riwayat_mahasiswa` (`periode_skripsi_id`,`mahasiswa_id`,`id`),
  ADD KEY `skripsi_beban_dosen` (`periode_skripsi_id`,`dosen_id`,`status`);

--
-- Indexes for table `pengumumans`
--
ALTER TABLE `pengumumans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengumumans_penulis_id_foreign` (`penulis_id`),
  ADD KEY `pengumumans_penerima_status_terbit_pada_index` (`penerima`,`status`,`terbit_pada`);

--
-- Indexes for table `pengumuman_reads`
--
ALTER TABLE `pengumuman_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pengumuman_reads_pengumuman_id_user_id_unique` (`pengumuman_id`,`user_id`),
  ADD KEY `pengumuman_reads_user_id_foreign` (`user_id`);

--
-- Indexes for table `periode_krs`
--
ALTER TABLE `periode_krs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `periode_skripsis`
--
ALTER TABLE `periode_skripsis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `periode_skripsis_mulai_berakhir_index` (`mulai`,`berakhir`);

--
-- Indexes for table `presensis`
--
ALTER TABLE `presensis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `presensis_krs_id_pertemuan_unique` (`krs_id`,`pertemuan`);

--
-- Indexes for table `presensi_pertemuans`
--
ALTER TABLE `presensi_pertemuans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jadwal_pertemuan_unique` (`jadwal_id`,`pertemuan`);

--
-- Indexes for table `prodis`
--
ALTER TABLE `prodis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prodis_kode_prodi_unique` (`kode_prodi`),
  ADD KEY `prodis_fakultas_id_foreign` (`fakultas_id`);

--
-- Indexes for table `riwayat_skripsis`
--
ALTER TABLE `riwayat_skripsis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `riwayat_skripsis_pengajuan_skripsi_id_foreign` (`pengajuan_skripsi_id`),
  ADD KEY `riwayat_skripsis_pelaku_id_foreign` (`pelaku_id`),
  ADD KEY `riwayat_skripsis_periode_skripsi_id_created_at_index` (`periode_skripsi_id`,`created_at`);

--
-- Indexes for table `ruangans`
--
ALTER TABLE `ruangans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruangans_kode_ruangan_unique` (`kode_ruangan`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_login_unique` (`login`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dosens`
--
ALTER TABLE `dosens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fakultas`
--
ALTER TABLE `fakultas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwals`
--
ALTER TABLE `jadwals`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `khs`
--
ALTER TABLE `khs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `krs`
--
ALTER TABLE `krs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kuesioners`
--
ALTER TABLE `kuesioners`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kurikulums`
--
ALTER TABLE `kurikulums`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kurikulum_mata_kuliah`
--
ALTER TABLE `kurikulum_mata_kuliah`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mahasiswas`
--
ALTER TABLE `mahasiswas`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `penelitian_p3m`
--
ALTER TABLE `penelitian_p3m`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengajuan_skripsis`
--
ALTER TABLE `pengajuan_skripsis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pengumumans`
--
ALTER TABLE `pengumumans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengumuman_reads`
--
ALTER TABLE `pengumuman_reads`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periode_krs`
--
ALTER TABLE `periode_krs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `periode_skripsis`
--
ALTER TABLE `periode_skripsis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `presensis`
--
ALTER TABLE `presensis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `presensi_pertemuans`
--
ALTER TABLE `presensi_pertemuans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prodis`
--
ALTER TABLE `prodis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `riwayat_skripsis`
--
ALTER TABLE `riwayat_skripsis`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ruangans`
--
ALTER TABLE `ruangans`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosens`
--
ALTER TABLE `dosens`
  ADD CONSTRAINT `dosens_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `dosens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jadwals`
--
ALTER TABLE `jadwals`
  ADD CONSTRAINT `jadwals_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `dosens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jadwals_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jadwals_mata_kuliah_id_foreign` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliahs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jadwals_ruangan_id_foreign` FOREIGN KEY (`ruangan_id`) REFERENCES `ruangans` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_dosen_wali_id_foreign` FOREIGN KEY (`dosen_wali_id`) REFERENCES `dosens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kelas_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `khs`
--
ALTER TABLE `khs`
  ADD CONSTRAINT `khs_krs_id_foreign` FOREIGN KEY (`krs_id`) REFERENCES `krs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `krs`
--
ALTER TABLE `krs`
  ADD CONSTRAINT `krs_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `krs_mahasiswa_id_foreign` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kuesioners`
--
ALTER TABLE `kuesioners`
  ADD CONSTRAINT `kuesioners_krs_id_foreign` FOREIGN KEY (`krs_id`) REFERENCES `krs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kurikulums`
--
ALTER TABLE `kurikulums`
  ADD CONSTRAINT `kurikulums_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `kurikulum_mata_kuliah`
--
ALTER TABLE `kurikulum_mata_kuliah`
  ADD CONSTRAINT `kurikulum_mata_kuliah_kurikulum_id_foreign` FOREIGN KEY (`kurikulum_id`) REFERENCES `kurikulums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kurikulum_mata_kuliah_mata_kuliah_id_foreign` FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliahs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `mahasiswas`
--
ALTER TABLE `mahasiswas`
  ADD CONSTRAINT `mahasiswas_dosen_wali_id_foreign` FOREIGN KEY (`dosen_wali_id`) REFERENCES `dosens` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mahasiswas_kelas_id_foreign` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mahasiswas_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mahasiswas_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mata_kuliahs`
--
ALTER TABLE `mata_kuliahs`
  ADD CONSTRAINT `mata_kuliahs_prodi_id_foreign` FOREIGN KEY (`prodi_id`) REFERENCES `prodis` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `penelitian_p3m`
--
ALTER TABLE `penelitian_p3m`
  ADD CONSTRAINT `penelitian_p3m_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `dosens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pengajuan_skripsis`
--
ALTER TABLE `pengajuan_skripsis`
  ADD CONSTRAINT `pengajuan_skripsis_dibuat_oleh_foreign` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `pengajuan_skripsis_dosen_id_foreign` FOREIGN KEY (`dosen_id`) REFERENCES `dosens` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `pengajuan_skripsis_mahasiswa_id_foreign` FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswas` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `pengajuan_skripsis_pengajuan_asal_id_foreign` FOREIGN KEY (`pengajuan_asal_id`) REFERENCES `pengajuan_skripsis` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `pengajuan_skripsis_periode_skripsi_id_foreign` FOREIGN KEY (`periode_skripsi_id`) REFERENCES `periode_skripsis` (`id`) ON DELETE RESTRICT;

--
-- Constraints for table `pengumumans`
--
ALTER TABLE `pengumumans`
  ADD CONSTRAINT `pengumumans_penulis_id_foreign` FOREIGN KEY (`penulis_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pengumuman_reads`
--
ALTER TABLE `pengumuman_reads`
  ADD CONSTRAINT `pengumuman_reads_pengumuman_id_foreign` FOREIGN KEY (`pengumuman_id`) REFERENCES `pengumumans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumuman_reads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presensis`
--
ALTER TABLE `presensis`
  ADD CONSTRAINT `presensis_krs_id_foreign` FOREIGN KEY (`krs_id`) REFERENCES `krs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presensi_pertemuans`
--
ALTER TABLE `presensi_pertemuans`
  ADD CONSTRAINT `presensi_pertemuans_jadwal_id_foreign` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwals` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prodis`
--
ALTER TABLE `prodis`
  ADD CONSTRAINT `prodis_fakultas_id_foreign` FOREIGN KEY (`fakultas_id`) REFERENCES `fakultas` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `riwayat_skripsis`
--
ALTER TABLE `riwayat_skripsis`
  ADD CONSTRAINT `riwayat_skripsis_pelaku_id_foreign` FOREIGN KEY (`pelaku_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `riwayat_skripsis_pengajuan_skripsi_id_foreign` FOREIGN KEY (`pengajuan_skripsi_id`) REFERENCES `pengajuan_skripsis` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `riwayat_skripsis_periode_skripsi_id_foreign` FOREIGN KEY (`periode_skripsi_id`) REFERENCES `periode_skripsis` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
