<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Khs extends Model
{
    protected $table = 'khs';

    protected $fillable = [
        'krs_id',
        'nilai_angka',
        'nilai_huruf',
        'bobot',
        'sks',
        'tahun_akademik',
        'semester_akademik',
        'is_manual',
    ];

    protected function casts(): array
    {
        return ['is_manual' => 'boolean'];
    }

    public function getSksEfektifAttribute(): int
    {
        return (int) ($this->sks ?? $this->krs?->mata_kuliah_efektif?->sks ?? 0);
    }

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }
}
