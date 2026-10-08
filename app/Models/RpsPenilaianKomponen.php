<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RpsPenilaianKomponen extends Model
{
    protected $table = 'rps_penilaian_komponen';

    protected $fillable = [
        'skema_id',
        'nama_instrumen',
        'sub_cpmk_id',
        'bobot',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'bobot' => 'float',
            'urutan' => 'integer',
        ];
    }

    public function skema()
    {
        return $this->belongsTo(RpsPenilaianSkema::class, 'skema_id');
    }

    public function subCpmk()
    {
        return $this->belongsTo(SubCpmk::class);
    }

    public function nilaiMahasiswas()
    {
        return $this->hasMany(MahasiswaNilaiKomponen::class, 'komponen_id');
    }
}
