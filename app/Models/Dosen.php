<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    public function pengajuanSkripsi()
    {
        return $this->hasMany(PengajuanSkripsi::class);
    }

    public function scopePembimbingAktif($query)
    {
        return $query->where('skripsi_aktif', true)->whereNotNull('prodi_id')
            ->whereHas('user', fn ($user) => $user->where('role', 'dosen'));
    }

    protected $fillable = [
        'nidn',
        'nama',
        'email',
        'telepon',
        'jabatan',
        'golongan',
        'prodi_id',
        'user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    // Relasi ke Program Studi
    public function prodi()
    {
        return $this->belongsTo(Prodi::class);
    }

    // Relasi ke User / akun login
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke mahasiswa yang menjadi bimbingan/wali
    public function mahasiswaWali()
    {
        return $this->hasMany(
            Mahasiswa::class,
            'dosen_wali_id'
        );
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class, 'dosen_id');
    }

    public function penelitians()
    {
        return $this->hasMany(Penelitian::class, 'dosen_id');
    }
}
