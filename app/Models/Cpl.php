<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cpl extends Model
{
    protected $fillable = [
        'program_studi_id',
        'kode_cpl',
        'nama_cpl',
        'deskripsi',
        'turunan_visi_misi',
        'cpl_kkni',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function programStudi()
    {
        return $this->belongsTo(Prodi::class, 'program_studi_id');
    }

    public function mappings()
    {
        return $this->hasMany(CplMataKuliah::class)->orderBy('semester')->orderBy('kode_sumber');
    }

    public function mataKuliahs()
    {
        return $this->belongsToMany(MataKuliah::class, 'cpl_mata_kuliah')
            ->withPivot(['id', 'kode_sumber', 'nama_sumber', 'semester', 'sks'])
            ->withTimestamps();
    }
}
