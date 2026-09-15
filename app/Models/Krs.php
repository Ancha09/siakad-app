<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    protected $table = 'krs';

    protected $fillable = [
        'mahasiswa_id',
        'jadwal_id',
        'mata_kuliah_id',
        'dosen_id',
        'prodi_id',
        'kelas_id',
        'angkatan',
        'semester',
        'status',
        'alasan_penolakan',
        'tahun_akademik',
        'semester_akademik',
        'is_manual',
        'manual_identity',
    ];

    protected function casts(): array
    {
        return ['is_manual' => 'boolean'];
    }

    // Relasi ke Mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    // Relasi ke Jadwal
    public function jadwal()
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function mataKuliahManual()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id');
    }

    public function dosenManual()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function prodiManual()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    public function kelasManual()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function getMataKuliahEfektifAttribute()
    {
        return $this->jadwal?->mataKuliah ?? $this->mataKuliahManual;
    }

    public function getDosenEfektifAttribute()
    {
        return $this->jadwal?->dosen ?? $this->dosenManual;
    }

    public function getKelasEfektifAttribute()
    {
        return $this->jadwal?->kelas ?? $this->kelasManual ?? $this->mahasiswa?->kelas;
    }

    public function getProdiEfektifAttribute()
    {
        return $this->prodiManual ?? $this->mata_kuliah_efektif?->prodi ?? $this->mahasiswa?->prodi;
    }

    // Relasi ke KHS (satu KRS memiliki satu nilai KHS)
    public function khs()
    {
        return $this->hasOne(Khs::class, 'krs_id');
    }

    public function kuesioner()
    {
        return $this->hasOne(Kuesioner::class, 'krs_id');
    }

    // Relasi Absen
    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }
}
