<?php

namespace App\Support;

final class MiningCplCatalog
{
    public const HIDDEN_CPL = 'CPL 9';

    public const REDIRECT_TARGET_CPL = 'CPL 3';

    private const TEMPORARILY_EXCLUDED_MAPPINGS = [
        [
            'code' => 'KU302',
            'name' => 'matriksruangvektor',
        ],
        [
            'code' => 'TA601',
            'name' => 'mkpilihan2',
        ],
    ];

    /**
     * @return array<string, array{deskripsi:string,turunan_visi_misi:string,cpl_kkni:string}>
     */
    public static function all(): array
    {
        return [
            'CPL 1' => [
                'deskripsi' => 'Mampu menguasai dan menerapkan prinsip-prinsip ilmu rekayasa, sains, dan matematika secara profesional untuk merumuskan dan memecahkan permasalahan teknis kompleks dalam kegiatan eksplorasi dan eksploitasi pertambangan.',
                'turunan_visi_misi' => 'Misi 1: pendidikan secara profesional',
                'cpl_kkni' => 'KK1, P1',
            ],
            'CPL 2' => [
                'deskripsi' => 'Mampu merancang solusi rekayasa pertambangan yang berkelanjutan dan berwawasan lingkungan dengan mempertimbangkan aspek keselamatan kerja, ekonomi, sosial-budaya, serta perlindungan dan pelestarian lingkungan hidup.',
                'turunan_visi_misi' => 'Misi 3: perlindungan pelestarian lingkungan',
                'cpl_kkni' => 'KK2, P2',
            ],
            'CPL 3' => [
                'deskripsi' => 'Mampu menyampaikan gagasan, hasil kajian, dan laporan teknis pertambangan secara sistematis dan terstruktur baik secara lisan maupun tulisan kepada pemangku kepentingan di tingkat nasional.',
                'turunan_visi_misi' => 'Visi PS: tingkat nasional',
                'cpl_kkni' => 'KU3, KU4',
            ],
            'CPL 4' => [
                'deskripsi' => 'Mampu menginternalisasi dan menerapkan etika profesi keteknikan pertambangan dalam pengambilan keputusan yang bertanggung jawab terhadap dampak pekerjaan bagi masyarakat, ekonomi, dan kelestarian lingkungan.',
                'turunan_visi_misi' => 'Misi 3: perlindungan & pelestarian lingkungan',
                'cpl_kkni' => 'S7, S8, S10, KK5',
            ],
            'CPL 5' => [
                'deskripsi' => 'Mampu membangun dan mengembangkan jejaring kerja sama yang produktif dengan pemangku kepentingan industri pertambangan, bekerja secara efektif dalam tim multidisiplin, dan memimpin kelompok kerja menuju target yang terukur.',
                'turunan_visi_misi' => 'Misi 4: menjalin kerjasama',
                'cpl_kkni' => 'KU6, KU7, S9',
            ],
            'CPL 6' => [
                'deskripsi' => 'Mampu merancang dan melaksanakan penelitian inovatif di bidang pertambangan secara sistematis, menginterpretasi data dan hasil eksperimen, serta menyimpulkan solusi teknis yang aplikatif dan berdampak nyata.',
                'turunan_visi_misi' => 'Misi 2: penelitian inovatif',
                'cpl_kkni' => 'KU5, KK4, KU8',
            ],
            'CPL 7' => [
                'deskripsi' => 'Mampu mengembangkan diri secara mandiri dan berkelanjutan melalui penguasaan perkembangan ilmu teknologi pertambangan terkini sebagai landasan adaptasi terhadap dinamika industri dan kebutuhan pemangku kepentingan.',
                'turunan_visi_misi' => 'Visi PS: ilmu teknologi pertambangan',
                'cpl_kkni' => 'KU8, KU3, P4',
            ],
            'CPL 8' => [
                'deskripsi' => 'Mampu mengidentifikasi peluang dan mengembangkan usaha di bidang jasa dan industri pertambangan dengan menerapkan prinsip kewirausahaan yang profesional, inovatif, dan bertanggung jawab.',
                'turunan_visi_misi' => "Misi 1: profesional\nMisi 2: inovatif",
                'cpl_kkni' => 'S9, KU3',
            ],
            'CPL 9' => [
                'deskripsi' => 'Mampu mengimplementasikan nilai-nilai intelektualitas dan inovasi dalam seluruh aspek keprofesian sebagai ciri khas Sarjana Teknik Pertambangan STTMI yang unggul dan berdaya saing di bidang ilmu teknologi pertambangan pada tingkat nasional.',
                'turunan_visi_misi' => "Visi PS\nSK.004/STTMI/K/IV/2020: berbasis intelektualitas, inovasi dan yang unggul serta mampu bersaing secara global",
                'cpl_kkni' => 'S1, S2, S4, S6',
            ],
        ];
    }

    /**
     * @return array{deskripsi:string,turunan_visi_misi:string,cpl_kkni:string}|null
     */
    public static function forCode(string $code): ?array
    {
        return self::all()[strtoupper(trim($code))] ?? null;
    }

    /**
     * CPL yang aktif pada laporan Teknik Pertambangan. CPL 9 tetap disimpan
     * sebagai data historis, tetapi tidak ditampilkan sebagai CPL terpisah.
     *
     * @return array<int, string>
     */
    public static function activeCodes(): array
    {
        return array_values(array_filter(
            array_keys(self::all()),
            fn (string $code) => $code !== self::HIDDEN_CPL
        ));
    }

    public static function isActive(string $code): bool
    {
        return in_array(strtoupper(trim($code)), self::activeCodes(), true);
    }

    /**
     * Keputusan sementara prodi: mapping yang belum final tidak dihitung pada
     * laporan. Pengecualian memakai pasangan kode dan nama secara ketat agar
     * mata kuliah lain (termasuk MK Pilihan 3) tidak ikut dinonaktifkan.
     */
    public static function isTemporarilyExcludedMapping(string $courseCode, string $courseName, string $_cplCode): bool
    {
        $normalizedCode = strtoupper(trim($courseCode));
        $normalizedCode = preg_replace('/\(\s*(?:TP|TG)\s*\)$/i', '', $normalizedCode) ?? $normalizedCode;
        $normalizedCode = preg_replace('/[^A-Z0-9]+/', '', $normalizedCode) ?? $normalizedCode;
        $normalizedName = strtolower(trim($courseName));
        $normalizedName = preg_replace('/[^a-z0-9]+/', '', $normalizedName) ?? $normalizedName;

        return collect(self::TEMPORARILY_EXCLUDED_MAPPINGS)->contains(
            fn (array $mapping) => $normalizedCode === $mapping['code']
                && $normalizedName === $mapping['name']
        );
    }
}
