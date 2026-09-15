<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensis';

    protected $fillable = [
        'krs_id',
        'tanggal',
        'pertemuan',
        'status',
        'keterangan',
        'foto',
        'materi',
        'is_manual',
        'manual_identity',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'is_manual' => 'boolean',
        ];
    }

    // ===================== RELASI KRS =====================

    public function krs()
    {
        return $this->belongsTo(
            Krs::class,
            'krs_id'
        );
    }
}
