<?php

namespace App\Models;

use App\Models\Concerns\HasManualLecturer;
use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    use HasManualLecturer;

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
        'dosen_id',
        'dosen_override',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'is_manual' => 'boolean',
            'dosen_override' => 'boolean',
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
