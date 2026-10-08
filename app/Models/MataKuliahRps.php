<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MataKuliahRps extends Model
{
    protected $table = 'mata_kuliah_rps';

    protected $fillable = [
        'mata_kuliah_id',
        'tahun_akademik',
        'file_rps',
        'file_rps_path',
        'target_passing_grade',
        'porsi_cpl',
        'komponen_bobot_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_passing_grade' => 'float',
            'porsi_cpl' => 'array',
            'komponen_bobot_default' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_rps_path ?: $this->file_rps;
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : asset('storage/' . $path);
    }

    public function getEffectiveFilePathAttribute(): ?string
    {
        return $this->file_rps_path ?: $this->file_rps;
    }
}

