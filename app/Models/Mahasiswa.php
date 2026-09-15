<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    public const MIN_SEMESTER_SKRIPSI = 7;

    public function memenuhiSyaratSemesterSkripsi(): bool
    {
        return $this->semester !== null && (int) $this->semester >= self::MIN_SEMESTER_SKRIPSI;
    }

    public function pengajuanSkripsi()
    {
        return $this->hasMany(PengajuanSkripsi::class);
    }

    public function pembimbingSkripsi()
    {
        return $this->pengajuanSkripsi()->diterima();
    }

    protected $fillable = [
        'nim',
        'nama',
        'email',
        'telepon',
        'angkatan',
        'semester',
        'prodi_id',
        'kelas_id',
        'dosen_wali_id',
        'user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // ===================== RELASI PROGRAM STUDI =====================

    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    // ===================== RELASI KELAS =====================

    public function kelas()
    {
        return $this->belongsTo(
            Kelas::class,
            'kelas_id'
        );
    }

    // ===================== RELASI USER =====================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===================== RELASI DOSEN WALI =====================
    // Dipertahankan dulu karena fitur lama masih bisa menggunakannya.

    public function dosenWali()
    {
        return $this->belongsTo(
            Dosen::class,
            'dosen_wali_id'
        );
    }

    // ===================== RELASI KRS =====================

    public function krs()
    {
        return $this->hasMany(
            Krs::class,
            'mahasiswa_id'
        );
    }

    public function kuesioners()
    {
        return $this->hasManyThrough(
            Kuesioner::class,
            Krs::class,
            'mahasiswa_id',
            'krs_id'
        );
    }
}
