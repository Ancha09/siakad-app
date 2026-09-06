<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penelitian extends Model
{
    protected $table = 'penelitian_p3m';

    protected $fillable = [
        'dosen_id',
        'judul',
        'jenis',
        'tahun',
        'sumber_dana',
        'status',
        'ringkasan',
        'link_artikel',
        'hasil_path',
        'artikel_path',
    ];

    public function dosen()
    {
        return $this->belongsTo(Dosen::class);
    }
}
