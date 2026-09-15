<?php

namespace App\Models;

use App\Models\Concerns\HasManualLecturer;
use Illuminate\Database\Eloquent\Model;

class Khs extends Model
{
    use HasManualLecturer;

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
        'dosen_id',
        'dosen_override',
    ];

    protected function casts(): array
    {
        return ['is_manual' => 'boolean', 'dosen_override' => 'boolean'];
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
