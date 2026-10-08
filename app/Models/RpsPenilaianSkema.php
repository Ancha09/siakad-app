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
    ];

    protected function casts(): array
    {
        return [
            'is_finalized' => 'boolean',
            'finalized_at' => 'datetime',
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

