<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'semester',
        'prodi_id',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function kurikulums()
    {
        return $this->belongsToMany(Kurikulum::class, 'kurikulum_mata_kuliah')
            ->withPivot(['id', 'semester', 'jenis', 'silabus_path'])
            ->withTimestamps();
    }

    public function cplMappings()
    {
        return $this->hasMany(CplMataKuliah::class);
    }

    public function cpls()
    {
        return $this->belongsToMany(Cpl::class, 'cpl_mata_kuliah')
            ->withPivot(['id', 'kode_sumber', 'nama_sumber', 'semester', 'sks'])
            ->withTimestamps();
    }
}
