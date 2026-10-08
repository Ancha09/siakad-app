<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cpmk extends Model
{
    protected $fillable = [
        'mata_kuliah_id',
        'kode_cpmk',
        'deskripsi',
    ];

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function subCpmks()
    {
        return $this->hasMany(SubCpmk::class);
    }
}
