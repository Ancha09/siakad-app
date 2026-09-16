-- DIAGNOSTIK EVALUASI DOSEN SIAKAD STTMI (READ-ONLY)
-- Seluruh statement di file ini hanya SELECT/SHOW. Tidak ada UPDATE/DELETE/INSERT.
-- Jalankan satu per satu dari phpMyAdmin setelah memilih database SIAKAD.

-- 1. Pastikan struktur yang tersedia di hosting sebelum menjalankan query lain.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('kuesioners', 'krs', 'khs', 'jadwals')
  AND COLUMN_NAME IN (
      'krs_id', 'dosen_id', 'mata_kuliah_id', 'kelas_id',
      'tahun_akademik', 'semester_akademik', 'is_manual', 'dosen_override'
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 2. Jejak Bahasa Inggris dari evaluasi -> KRS -> nilai/KHS -> jadwal.
--    Query ini aman dijalankan sebelum migration snapshot evaluasi dipasang.
SELECT
    q.id AS evaluasi_id,
    q.submitted_at,
    k.id AS krs_id,
    COALESCE(mk_krs.kode_mk, mk_jadwal.kode_mk) AS kode_mk,
    COALESCE(mk_krs.nama_mk, mk_jadwal.nama_mk) AS mata_kuliah,
    k.tahun_akademik,
    k.semester_akademik,
    k.kelas_id AS kelas_krs_id,
    m.kelas_id AS kelas_mahasiswa_id,
    kh.id AS khs_id,
    kh.is_manual AS khs_manual,
    kh.dosen_override,
    kh.dosen_id AS dosen_khs_id,
    d_khs.nama AS dosen_khs,
    k.dosen_id AS dosen_krs_id,
    d_krs.nama AS dosen_krs,
    j.id AS jadwal_id,
    j.dosen_id AS dosen_jadwal_id,
    d_jadwal.nama AS dosen_jadwal,
    j.kelas_id AS kelas_jadwal_id,
    j.tahun_akademik AS tahun_jadwal,
    j.semester_akademik AS semester_jadwal
FROM kuesioners q
JOIN krs k ON k.id = q.krs_id
JOIN mahasiswas m ON m.id = k.mahasiswa_id
LEFT JOIN khs kh ON kh.id = (
    SELECT MAX(kh2.id) FROM khs kh2 WHERE kh2.krs_id = k.id
)
LEFT JOIN dosens d_khs ON d_khs.id = kh.dosen_id
LEFT JOIN dosens d_krs ON d_krs.id = k.dosen_id
LEFT JOIN jadwals j ON j.id = k.jadwal_id
LEFT JOIN dosens d_jadwal ON d_jadwal.id = j.dosen_id
LEFT JOIN mata_kuliahs mk_krs ON mk_krs.id = k.mata_kuliah_id
LEFT JOIN mata_kuliahs mk_jadwal ON mk_jadwal.id = j.mata_kuliah_id
WHERE LOWER(COALESCE(mk_krs.nama_mk, mk_jadwal.nama_mk, '')) LIKE '%bahasa inggris%'
ORDER BY q.submitted_at DESC, q.id DESC;

-- 3. Daftar seluruh kandidat jadwal yang benar-benar cocok dengan konteks
--    Bahasa Inggris. Lebih dari satu dosen pada konteks yang sama berarti ambigu
--    dan aplikasi tidak boleh memilih "jadwal pertama".
SELECT
    k.id AS krs_id,
    COALESCE(mk_krs.nama_mk, mk_lama.nama_mk) AS mata_kuliah,
    k.tahun_akademik,
    k.semester_akademik,
    COALESCE(k.kelas_id, m.kelas_id) AS kelas_konteks_id,
    j_tepat.id AS jadwal_cocok_id,
    j_tepat.dosen_id,
    d_tepat.nama AS dosen_jadwal_cocok
FROM kuesioners q
JOIN krs k ON k.id = q.krs_id
JOIN mahasiswas m ON m.id = k.mahasiswa_id
LEFT JOIN jadwals j_lama ON j_lama.id = k.jadwal_id
LEFT JOIN mata_kuliahs mk_krs ON mk_krs.id = k.mata_kuliah_id
LEFT JOIN mata_kuliahs mk_lama ON mk_lama.id = j_lama.mata_kuliah_id
LEFT JOIN jadwals j_tepat
  ON j_tepat.mata_kuliah_id = COALESCE(k.mata_kuliah_id, j_lama.mata_kuliah_id)
 AND j_tepat.tahun_akademik = k.tahun_akademik
 AND j_tepat.semester_akademik = k.semester_akademik
 AND (COALESCE(k.kelas_id, m.kelas_id) IS NULL
      OR j_tepat.kelas_id = COALESCE(k.kelas_id, m.kelas_id))
LEFT JOIN dosens d_tepat ON d_tepat.id = j_tepat.dosen_id
WHERE LOWER(COALESCE(mk_krs.nama_mk, mk_lama.nama_mk, '')) LIKE '%bahasa inggris%'
ORDER BY k.id, j_tepat.id;

-- 4. Preview mapping lama tanpa mengubah data. Prioritas yang ditampilkan:
--    KHS manual override -> KRS -> jadwal tertaut yang konteksnya cocok.
SELECT
    x.*,
    d.nama AS dosen_hasil_mapping,
    p.nama_prodi AS prodi_dosen
FROM (
    SELECT
        q.id AS evaluasi_id,
        k.id AS krs_id,
        COALESCE(mk_krs.nama_mk, mk_jadwal.nama_mk) AS mata_kuliah,
        CASE
            WHEN kh.is_manual = 1 AND kh.dosen_override = 1 THEN kh.dosen_id
            WHEN k.dosen_id IS NOT NULL THEN k.dosen_id
            WHEN j.dosen_id IS NOT NULL
             AND (k.mata_kuliah_id IS NULL OR j.mata_kuliah_id = k.mata_kuliah_id)
             AND (COALESCE(k.kelas_id, m.kelas_id) IS NULL OR j.kelas_id = COALESCE(k.kelas_id, m.kelas_id))
             AND (k.tahun_akademik IS NULL OR j.tahun_akademik = k.tahun_akademik)
             AND (k.semester_akademik IS NULL OR j.semester_akademik = k.semester_akademik)
                THEN j.dosen_id
            ELSE NULL
        END AS dosen_hasil_id,
        CASE
            WHEN kh.is_manual = 1 AND kh.dosen_override = 1 THEN 'KHS manual override'
            WHEN k.dosen_id IS NOT NULL THEN 'KRS'
            WHEN j.dosen_id IS NOT NULL THEN 'Jadwal tertaut (perlu cek konteks)'
            ELSE 'Belum terpetakan'
        END AS sumber_mapping
    FROM kuesioners q
    JOIN krs k ON k.id = q.krs_id
    JOIN mahasiswas m ON m.id = k.mahasiswa_id
    LEFT JOIN khs kh ON kh.id = (
        SELECT MAX(kh2.id) FROM khs kh2 WHERE kh2.krs_id = k.id
    )
    LEFT JOIN jadwals j ON j.id = k.jadwal_id
    LEFT JOIN mata_kuliahs mk_krs ON mk_krs.id = k.mata_kuliah_id
    LEFT JOIN mata_kuliahs mk_jadwal ON mk_jadwal.id = j.mata_kuliah_id
) x
LEFT JOIN dosens d ON d.id = x.dosen_hasil_id
LEFT JOIN prodis p ON p.id = d.prodi_id
WHERE LOWER(COALESCE(x.mata_kuliah, '')) LIKE '%bahasa inggris%'
ORDER BY x.evaluasi_id DESC;

-- 5. Dosen prodi Informatika dan jumlah evaluasi yang dapat dipetakan ke mereka.
--    Tidak ada filter berdasarkan prodi mahasiswa pada query ini.
SELECT
    d.id AS dosen_id,
    d.nidn,
    d.nama,
    p.nama_prodi,
    COUNT(DISTINCT r.evaluasi_id) AS jumlah_responden_terpetakan,
    MAX(r.submitted_at) AS respon_terakhir
FROM dosens d
JOIN prodis p ON p.id = d.prodi_id
LEFT JOIN (
    SELECT
        q.id AS evaluasi_id,
        q.submitted_at,
        CASE
            WHEN kh.is_manual = 1 AND kh.dosen_override = 1 THEN kh.dosen_id
            WHEN k.dosen_id IS NOT NULL THEN k.dosen_id
            WHEN j.dosen_id IS NOT NULL
             AND (k.mata_kuliah_id IS NULL OR j.mata_kuliah_id = k.mata_kuliah_id)
             AND (COALESCE(k.kelas_id, m.kelas_id) IS NULL OR j.kelas_id = COALESCE(k.kelas_id, m.kelas_id))
             AND j.tahun_akademik = k.tahun_akademik
             AND j.semester_akademik = k.semester_akademik
                THEN j.dosen_id
            ELSE NULL
        END AS dosen_hasil_id
    FROM kuesioners q
    JOIN krs k ON k.id = q.krs_id
    JOIN mahasiswas m ON m.id = k.mahasiswa_id
    LEFT JOIN khs kh ON kh.id = (
        SELECT MAX(kh2.id) FROM khs kh2 WHERE kh2.krs_id = k.id
    )
    LEFT JOIN jadwals j ON j.id = k.jadwal_id
) r ON r.dosen_hasil_id = d.id
WHERE LOWER(p.nama_prodi) LIKE '%informatika%'
GROUP BY d.id, d.nidn, d.nama, p.nama_prodi
ORDER BY d.nama;

-- 6. Hitung ulang langsung dari semua jawaban. Ini memeriksa kasus jumlah
--    responden bertambah tetapi rata-rata tampak tidak berubah.
SELECT
    d.id AS dosen_id,
    d.nama AS dosen,
    COUNT(DISTINCT r.evaluasi_id) AS jumlah_responden,
    ROUND(AVG(r.rata_responden), 2) AS rata_keseluruhan,
    ROUND(AVG(r.penguasaan_materi), 2) AS avg_penguasaan_materi,
    ROUND(AVG(r.kejelasan_penyampaian), 2) AS avg_kejelasan_penyampaian,
    ROUND(AVG(r.kesesuaian_rps), 2) AS avg_kesesuaian_rps,
    ROUND(AVG(r.ketepatan_waktu), 2) AS avg_ketepatan_waktu,
    ROUND(AVG(r.kesempatan_bertanya), 2) AS avg_kesempatan_bertanya,
    ROUND(AVG(r.objektivitas_penilaian), 2) AS avg_objektivitas_penilaian,
    ROUND(AVG(r.penggunaan_media), 2) AS avg_penggunaan_media,
    ROUND(AVG(r.motivasi_belajar), 2) AS avg_motivasi_belajar,
    MIN(r.submitted_at) AS respon_pertama,
    MAX(r.submitted_at) AS respon_terakhir
FROM (
    SELECT
        q.id AS evaluasi_id,
        q.submitted_at,
        q.penguasaan_materi,
        q.kejelasan_penyampaian,
        q.kesesuaian_rps,
        q.ketepatan_waktu,
        q.kesempatan_bertanya,
        q.objektivitas_penilaian,
        q.penggunaan_media,
        q.motivasi_belajar,
        (
            q.penguasaan_materi + q.kejelasan_penyampaian + q.kesesuaian_rps
            + q.ketepatan_waktu + q.kesempatan_bertanya + q.objektivitas_penilaian
            + q.penggunaan_media + q.motivasi_belajar
        ) / 8.0 AS rata_responden,
        CASE
            WHEN kh.is_manual = 1 AND kh.dosen_override = 1 THEN kh.dosen_id
            WHEN k.dosen_id IS NOT NULL THEN k.dosen_id
            WHEN j.dosen_id IS NOT NULL
             AND (k.mata_kuliah_id IS NULL OR j.mata_kuliah_id = k.mata_kuliah_id)
             AND (COALESCE(k.kelas_id, m.kelas_id) IS NULL OR j.kelas_id = COALESCE(k.kelas_id, m.kelas_id))
             AND j.tahun_akademik = k.tahun_akademik
             AND j.semester_akademik = k.semester_akademik
                THEN j.dosen_id
            ELSE NULL
        END AS dosen_hasil_id
    FROM kuesioners q
    JOIN krs k ON k.id = q.krs_id
    JOIN mahasiswas m ON m.id = k.mahasiswa_id
    LEFT JOIN khs kh ON kh.id = (
        SELECT MAX(kh2.id) FROM khs kh2 WHERE kh2.krs_id = k.id
    )
    LEFT JOIN jadwals j ON j.id = k.jadwal_id
) r
LEFT JOIN dosens d ON d.id = r.dosen_hasil_id
GROUP BY d.id, d.nama
ORDER BY d.nama;

-- 7. Cari sumber ambiguitas/duplikasi tanpa memperbaiki apa pun.
SELECT krs_id, COUNT(*) AS jumlah_khs, GROUP_CONCAT(id ORDER BY id) AS khs_ids
FROM khs
GROUP BY krs_id
HAVING COUNT(*) > 1
ORDER BY jumlah_khs DESC, krs_id;

SELECT
    COUNT(*) AS total_evaluasi,
    COUNT(DISTINCT krs_id) AS krs_unik,
    MIN(submitted_at) AS evaluasi_pertama,
    MAX(submitted_at) AS evaluasi_terakhir
FROM kuesioners;

-- 8. JALANKAN HANYA SETELAH migration 2026_09_16_000000 berstatus Ran.
--    Membandingkan snapshot baru dengan KHS/KRS/jadwal untuk menemukan konflik.
SELECT
    q.id AS evaluasi_id,
    q.submitted_at,
    q.dosen_id AS dosen_snapshot_id,
    d_snapshot.nama AS dosen_snapshot,
    q.mata_kuliah_id AS mk_snapshot_id,
    mk_snapshot.nama_mk AS mata_kuliah_snapshot,
    q.kelas_id AS kelas_snapshot_id,
    q.tahun_akademik AS tahun_snapshot,
    q.semester_akademik AS semester_snapshot,
    kh.dosen_id AS dosen_khs_id,
    d_khs.nama AS dosen_khs,
    k.dosen_id AS dosen_krs_id,
    d_krs.nama AS dosen_krs,
    j.dosen_id AS dosen_jadwal_id,
    d_jadwal.nama AS dosen_jadwal
FROM kuesioners q
JOIN krs k ON k.id = q.krs_id
LEFT JOIN khs kh ON kh.id = (
    SELECT MAX(kh2.id) FROM khs kh2 WHERE kh2.krs_id = k.id
)
LEFT JOIN jadwals j ON j.id = k.jadwal_id
LEFT JOIN dosens d_snapshot ON d_snapshot.id = q.dosen_id
LEFT JOIN dosens d_khs ON d_khs.id = kh.dosen_id
LEFT JOIN dosens d_krs ON d_krs.id = k.dosen_id
LEFT JOIN dosens d_jadwal ON d_jadwal.id = j.dosen_id
LEFT JOIN mata_kuliahs mk_snapshot ON mk_snapshot.id = q.mata_kuliah_id
WHERE q.dosen_id IS NULL
   OR (kh.is_manual = 1 AND kh.dosen_override = 1 AND NOT (q.dosen_id <=> kh.dosen_id))
ORDER BY q.submitted_at DESC, q.id DESC;
