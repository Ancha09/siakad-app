<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kuesioner extends Model
{
    public const PERTANYAAN = [
        'penguasaan_materi' => 'Dosen menguasai materi perkuliahan.',
        'kejelasan_penyampaian' => 'Dosen menyampaikan materi dengan jelas dan mudah dipahami.',
        'kesesuaian_rps' => 'Materi perkuliahan sesuai dengan RPS/silabus.',
        'ketepatan_waktu' => 'Dosen hadir dan mengakhiri perkuliahan tepat waktu.',
        'kesempatan_bertanya' => 'Dosen memberikan kesempatan untuk bertanya dan berdiskusi.',
        'objektivitas_penilaian' => 'Dosen memberikan penilaian secara objektif dan transparan.',
        'penggunaan_media' => 'Dosen menggunakan media pembelajaran secara efektif.',
        'motivasi_belajar' => 'Dosen mampu meningkatkan motivasi belajar mahasiswa.',
    ];

    protected $fillable = [
        'krs_id',
        'penguasaan_materi',
        'kejelasan_penyampaian',
        'kesesuaian_rps',
        'ketepatan_waktu',
        'kesempatan_bertanya',
        'objektivitas_penilaian',
        'penggunaan_media',
        'motivasi_belajar',
        'komentar',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }

    public function getRataRataAttribute(): float
    {
        $total = collect(array_keys(self::PERTANYAAN))->sum(
            fn (string $kolom) => (int) $this->{$kolom}
        );

        return round($total / count(self::PERTANYAAN), 2);
    }

    public function getKodeRespondenAttribute(): string
    {
        return 'RESP-'.strtoupper(substr(
            hash_hmac('sha256', (string) $this->krs_id, (string) config('app.key')),
            0,
            8
        ));
    }
}
