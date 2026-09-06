<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanSkripsi extends Model
{
    public const STATUS = ['Menunggu', 'Diterima', 'Ditolak', 'Dialihkan'];

    protected $fillable = ['periode_skripsi_id', 'mahasiswa_id', 'dosen_id', 'judul', 'status', 'alasan_keputusan', 'diputuskan_pada', 'dibuat_oleh', 'jenis_pembuat', 'pengajuan_asal_id'];

    protected function casts(): array
    {
        return ['diputuskan_pada' => 'datetime'];
    }

    public function periode()
    {
        return $this->belongsTo(PeriodeSkripsi::class, 'periode_skripsi_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatSkripsi::class);
    }

    // Pengajuan diterima merupakan sumber resmi pembimbing per periode.
    public function scopeDiterima($query)
    {
        return $query->where('status', 'Diterima');
    }
}
