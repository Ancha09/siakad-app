<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    protected $table = 'krs';

    protected $fillable = [
        'mahasiswa_id',
        'jadwal_id',
        'status',
        'alasan_penolakan',
        'tahun_akademik',
        'semester_akademik',
    ];

    // Relasi ke Mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    // Relasi ke Jadwal
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    // Relasi ke KHS (satu KRS memiliki satu nilai KHS)
    public function khs()
    {
        return $this->hasOne(Khs::class, 'krs_id');
    }

    public function kuesioner()
    {
        return $this->hasOne(Kuesioner::class, 'krs_id');
    }

    // Relasi Absen
    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }
}
