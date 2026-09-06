<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Khs extends Model
{
    protected $table = 'khs';

    protected $fillable = [
        'krs_id',
        'nilai_angka',
        'nilai_huruf',
        'bobot',
        'tahun_akademik',
        'semester_akademik',
    ];

    public function krs()
    {
        return $this->belongsTo(Krs::class);
    }
}