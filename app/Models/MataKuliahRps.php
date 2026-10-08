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
        'target_passing_grade',
        'porsi_cpl',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_passing_grade' => 'float',
            'porsi_cpl' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_rps) {
            return null;
        }

        return Storage::disk('public')->exists($this->file_rps)
            ? Storage::disk('public')->url($this->file_rps)
            : asset('storage/' . $this->file_rps);
    }
}
