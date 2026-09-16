<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranKrs extends Model
{
    protected $table = 'pembayaran_krs';

    protected $fillable = [
        'mahasiswa_id',
        'semester',
        'tahun_akademik',
        'semester_akademik',
        'status_bayar',
        'tanggal_bayar',
        'catatan',
        'diverifikasi_oleh',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'tanggal_bayar' => 'date',
        ];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_bayar === 'lunas' ? 'Sudah Bayar' : 'Belum Bayar';
    }

    public static function periodKey(int $mahasiswaId, string $tahunAkademik, string $semesterAkademik): string
    {
        return implode('|', [$mahasiswaId, $tahunAkademik, $semesterAkademik]);
    }
}
