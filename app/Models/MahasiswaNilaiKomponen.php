<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MahasiswaNilaiKomponen extends Model
{
    protected $table = 'mahasiswa_nilai_komponen';

    protected $fillable = [
        'krs_id',
        'komponen_id',
        'nilai_angka',
    ];

    protected function casts(): array
    {
        return [
            'nilai_angka' => 'float',
        ];
    }

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }

    public function komponen()
    {
        return $this->belongsTo(RpsPenilaianKomponen::class, 'komponen_id');
    }
}

