<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeKrs extends Model
{
    protected $table = 'periode_krs';

    protected $fillable = [
        'tahun_akademik',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'minimal_sks',
        'maksimal_sks',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
    ];

    public function aksesMahasiswa()
    {
        return $this->hasMany(PeriodeKrsMahasiswa::class);
    }
}
