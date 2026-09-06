<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'dosen_id',
        'ruangan_id',
        'kelas_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'tahun_akademik',
        'semester_akademik',
    ];

    // Relasi ke Mata Kuliah
    public function mataKuliah()
    {
        return $this->belongsTo(
            MataKuliah::class,
            'mata_kuliah_id'
        );
    }

    // Relasi ke Dosen
    public function dosen()
    {
        return $this->belongsTo(
            Dosen::class,
            'dosen_id'
        );
    }

    // Relasi ke Ruangan
    public function ruangan()
    {
        return $this->belongsTo(
            Ruangan::class,
            'ruangan_id'
        );
    }

    // Relasi ke Kelas
    public function kelas()
    {
        return $this->belongsTo(
            Kelas::class,
            'kelas_id'
        );
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'jadwal_id');
    }

    public function presensiPertemuans()
    {
        return $this->hasMany(PresensiPertemuan::class, 'jadwal_id');
    }
}
