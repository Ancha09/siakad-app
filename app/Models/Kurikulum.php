<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    protected $fillable = [
        'prodi_id',
        'nama_kurikulum',
        'tahun_mulai',
        'tahun_selesai',
        'status',
    ];

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    public function mataKuliahKurikulum()
    {
        return $this->hasMany(KurikulumMataKuliah::class)
            ->orderBy('semester')
            ->orderBy('id');
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(MataKuliah::class, 'kurikulum_mata_kuliah')
            ->withPivot(['id', 'semester', 'jenis', 'silabus_path'])
            ->withTimestamps();
    }
}
