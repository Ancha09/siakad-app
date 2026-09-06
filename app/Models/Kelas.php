<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'prodi_id',
        'angkatan',
        'semester',
        'dosen_wali_id',
    ];

    // ===================== RELASI PROGRAM STUDI =====================

    public function prodi()
    {
        return $this->belongsTo(
            Prodi::class,
            'prodi_id'
        );
    }


    // ===================== RELASI DOSEN WALI =====================

    public function dosenWali()
    {
        return $this->belongsTo(
            Dosen::class,
            'dosen_wali_id'
        );
    }


    // ===================== RELASI MAHASISWA =====================

    public function mahasiswas()
    {
        return $this->hasMany(
            Mahasiswa::class,
            'kelas_id'
        );
    }


    // ===================== RELASI JADWAL =====================

    public function jadwals()
    {
        return $this->hasMany(
            Jadwal::class,
            'kelas_id'
        );
    }
}