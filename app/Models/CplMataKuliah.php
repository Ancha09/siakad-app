<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CplMataKuliah extends Model
{
    protected $table = 'cpl_mata_kuliah';

    protected $fillable = [
        'cpl_id',
        'mata_kuliah_id',
        'kode_sumber',
        'nama_sumber',
        'semester',
        'sks',
    ];

    protected function casts(): array
    {
        return ['semester' => 'integer', 'sks' => 'integer'];
    }

    public function cpl()
    {
        return $this->belongsTo(Cpl::class);
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class);
    }
}
