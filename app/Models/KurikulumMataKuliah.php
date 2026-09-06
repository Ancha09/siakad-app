<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KurikulumMataKuliah extends Model
{
    protected $table = 'kurikulum_mata_kuliah';

    protected $fillable = [
        'kurikulum_id',
        'mata_kuliah_id',
        'semester',
        'jenis',
        'silabus_path',
    ];

    public function kurikulum()
    {
        return $this->belongsTo(Kurikulum::class);
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }
}
