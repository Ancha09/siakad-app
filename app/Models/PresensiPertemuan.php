<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PresensiPertemuan extends Model
{
    protected $table = 'presensi_pertemuans';

    protected $fillable = [
        'jadwal_id',
        'pertemuan',
        'tanggal',
        'foto',
        'materi',
    ];


    // ===================== RELASI JADWAL =====================

    public function jadwal()
    {
        return $this->belongsTo(
            Jadwal::class,
            'jadwal_id'
        );
    }
}