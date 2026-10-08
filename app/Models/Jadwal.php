<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'dosen_id',
        'ruangan_id',
        'kelas_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'tahun_akademik',
        'semester_akademik',
        'is_lintas_prodi',
        'group_key',
    ];

    protected function casts(): array
    {
        return [
            'is_lintas_prodi' => 'boolean',
        ];
    }

    // Relasi ke Mata Kuliah
    public function mataKuliah()
    {
        return $this->belongsTo(
            MataKuliah::class,
            'mata_kuliah_id'
        );
    }

    // Relasi ke Dosen
    public function dosen()
    {
        return $this->belongsTo(
            Dosen::class,
            'dosen_id'
        );
    }

    // Relasi Multi-Dosen (Team Teaching) via tabel pivot jadwal_dosen
    public function dosens()
    {
        return $this->belongsToMany(Dosen::class, 'jadwal_dosen')
            ->withPivot('peran')
            ->withTimestamps();
    }

    public function dosenPendampings()
    {
        return $this->belongsToMany(Dosen::class, 'jadwal_dosen')
            ->wherePivot('peran', 'pendamping')
            ->withPivot('peran')
            ->withTimestamps();
    }

    // Scope untuk mencari jadwal yang diajar oleh dosen tertentu (baik dosen utama maupun pendamping)
    public function scopeUntukDosen($query, int|Dosen $dosen)
    {
        $dosenId = $dosen instanceof Dosen ? $dosen->id : (int) $dosen;

        return $query->where(function ($q) use ($dosenId) {
            $q->where('dosen_id', $dosenId)
              ->orWhereHas('dosens', fn ($sq) => $sq->where('dosens.id', $dosenId));
        });
    }

    // Helper untuk mengecek apakah dosen tertentu mengampu jadwal ini
    public function isDosenPengampu(int|Dosen $dosen): bool
    {
        $dosenId = $dosen instanceof Dosen ? $dosen->id : (int) $dosen;

        if ((int) $this->dosen_id === $dosenId) {
            return true;
        }

        if ($this->relationLoaded('dosens')) {
            return $this->dosens->contains('id', $dosenId);
        }

        return $this->dosens()->where('dosens.id', $dosenId)->exists();
    }

    // Accessor: Mengembalikan gabungan nama seluruh dosen pengampu
    public function getSemuaDosenNamaAttribute(): string
    {
        if ($this->relationLoaded('dosens') && $this->dosens->isNotEmpty()) {
            $sorted = $this->dosens->sortBy(fn ($d) => ($d->pivot?->peran === 'utama') ? 0 : 1);
            return $sorted->pluck('nama')->filter()->implode(' / ');
        }

        $dosenList = $this->dosens()
            ->orderByRaw("CASE WHEN peran = 'utama' THEN 0 ELSE 1 END")
            ->get();

        if ($dosenList->isNotEmpty()) {
            return $dosenList->pluck('nama')->filter()->implode(' / ');
        }

        return $this->dosen?->nama ?? '-';
    }

    // Accessor: Mengembalikan gabungan NIDN seluruh dosen pengampu
    public function getSemuaDosenNidnAttribute(): string
    {
        if ($this->relationLoaded('dosens') && $this->dosens->isNotEmpty()) {
            $sorted = $this->dosens->sortBy(fn ($d) => ($d->pivot?->peran === 'utama') ? 0 : 1);
            return $sorted->map(fn ($d) => $d->nidn ?: '-')->implode(' / ');
        }

        $dosenList = $this->dosens()
            ->orderByRaw("CASE WHEN peran = 'utama' THEN 0 ELSE 1 END")
            ->get();

        if ($dosenList->isNotEmpty()) {
            return $dosenList->map(fn ($d) => $d->nidn ?: '-')->implode(' / ');
        }

        return $this->dosen?->nidn ?? '-';
    }

    // Relasi ke Ruangan
    public function ruangan()
    {
        return $this->belongsTo(
            Ruangan::class,
            'ruangan_id'
        );
    }

    // Relasi ke Kelas
    public function kelas()
    {
        return $this->belongsTo(
            Kelas::class,
            'kelas_id'
        );
    }

    // Alias aman karena tabel jadwals lama juga memiliki kolom string `kelas`.
    // Mengakses $jadwal->kelas dapat membaca atribut lama tersebut, bukan relasi.
    public function kelasRelasi()
    {
        return $this->belongsTo(
            Kelas::class,
            'kelas_id'
        );
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'jadwal_id');
    }

    public function presensiPertemuans()
    {
        return $this->hasMany(PresensiPertemuan::class, 'jadwal_id');
    }

    public function skemaPenilaian()
    {
        return $this->hasOne(RpsPenilaianSkema::class, 'jadwal_id');
    }
}
