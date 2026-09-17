<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeKrsMahasiswa extends Model
{
    protected $table = 'periode_krs_mahasiswas';

    protected $fillable = [
        'periode_krs_id',
        'mahasiswa_id',
        'status_akses',
        'tanggal_dibuka',
        'tanggal_ditutup',
        'dibuka_oleh',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'status_akses' => 'boolean',
            'tanggal_dibuka' => 'datetime',
            'tanggal_ditutup' => 'datetime',
        ];
    }

    public function periodeKrs()
    {
        return $this->belongsTo(PeriodeKrs::class);
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function pembuka()
    {
        return $this->belongsTo(User::class, 'dibuka_oleh');
    }

    public function isDibuka(): bool
    {
        return $this->status_akses;
    }
}
