<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fakultas extends Model
{
    protected $fillable = [
        'kode_fakultas',
        'nama_fakultas',
    ];

    public function prodis()
    {
        return $this->hasMany(Prodi::class);
    }
}