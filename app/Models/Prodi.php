<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prodi extends Model
{
    protected $fillable = [
        'kode_prodi',
        'nama_prodi',
        'jenjang',
        'fakultas_id',
    ];

    public function fakultas()
    {
        return $this->belongsTo(
            Fakultas::class,
            'fakultas_id'
        );
    }

    public function dosens()
    {
        return $this->hasMany(
            Dosen::class,
            'prodi_id'
        );
    }

    public function mahasiswas()
    {
        return $this->hasMany(
            Mahasiswa::class,
            'prodi_id'
        );
    }

    public function mataKuliahs()
    {
        return $this->hasMany(
            MataKuliah::class,
            'prodi_id'
        );
    }

    public function kurikulums()
    {
        return $this->hasMany(Kurikulum::class);
    }

    public function kelas()
    {
        return $this->hasMany(
            Kelas::class,
            'prodi_id'
        );
    }
}
