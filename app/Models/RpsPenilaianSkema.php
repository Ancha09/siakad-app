<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpsPenilaianSkema extends Model
{
    protected $table = 'rps_penilaian_skema';

    protected $fillable = [
        'jadwal_id',
        'dosen_id',
        'is_finalized',
        'finalized_at',
        'matrix_alokasi',
        'komponen_standar',
    ];

    protected function casts(): array
    {
        return [
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
            'matrix_alokasi' => 'array',
            'komponen_standar' => 'array',
        ];
    }

    /**
     * Komponen dan bobot standar efektif (default: Kehadiran 10%, Tugas 20%, UTS 30%, UAS 40%)
     */
    public function getKomponenStandarEfektifAttribute(): array
    {
        if (! empty($this->komponen_standar) && is_array($this->komponen_standar)) {
            return $this->komponen_standar;
        }

        return [
            ['nama' => 'Kehadiran', 'bobot' => 10],
            ['nama' => 'Tugas', 'bobot' => 20],
            ['nama' => 'UTS', 'bobot' => 30],
            ['nama' => 'UAS', 'bobot' => 40],
        ];
    }

    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }

    public function komponens()
    {
        return $this->hasMany(RpsPenilaianKomponen::class, 'skema_id')->orderBy('urutan')->orderBy('id');
    }

    public function getTotalBobotAttribute(): float
    {
        return (float) $this->komponens->sum('bobot');
    }
}

