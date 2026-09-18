<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeKrs extends Model
{
    public const ACCESS_MODES = ['closed', 'all', 'selected', 'all_except'];

    protected $table = 'periode_krs';

    protected $fillable = [
        'tahun_akademik',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'minimal_sks',
        'maksimal_sks',
        'status',
        'access_mode',
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

    public function allowsMahasiswa(Mahasiswa $mahasiswa): bool
    {
        if (! $mahasiswa->is_active) {
            return false;
        }

        $akses = $mahasiswa->relationLoaded('aksesPeriodeKrs')
            ? $mahasiswa->aksesPeriodeKrs->firstWhere('periode_krs_id', $this->id)
            : $this->aksesMahasiswa()->where('mahasiswa_id', $mahasiswa->id)->first();

        return match ($this->access_mode ?? 'selected') {
            'all' => true,
            'all_except' => $akses === null || $akses->status_akses,
            'selected' => $akses?->status_akses === true,
            default => false,
        };
    }
}
