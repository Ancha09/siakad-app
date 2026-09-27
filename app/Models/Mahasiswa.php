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
        'alamat',
        'angkatan',
        'semester',
        'prodi_id',
        'kelas_id',
        'dosen_wali_id',
        'user_id',
        'is_active',
        'ktm_photo_path',
        'ktm_photo_uploaded_at',
        'ktm_photo_locked',
        'ktm_photo_reset_at',
        'ktm_photo_reset_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'ktm_photo_uploaded_at' => 'datetime',
            'ktm_photo_locked' => 'boolean',
            'ktm_photo_reset_at' => 'datetime',
        ];
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

    public function ktmPhotoResetBy()
    {
        return $this->belongsTo(User::class, 'ktm_photo_reset_by');
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

    public function aksesPeriodeKrs()
    {
        return $this->hasMany(PeriodeKrsMahasiswa::class);
    }
}
