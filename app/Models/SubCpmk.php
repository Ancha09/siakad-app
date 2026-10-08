<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCpmk extends Model
{
    protected $table = 'sub_cpmks';

    protected $fillable = [
        'cpmk_id',
        'cpl_id',
        'kode_sub_cpmk',
        'deskripsi',
        'bobot_default',
    ];

    protected function casts(): array
    {
        return [
            'bobot_default' => 'float',
        ];
    }

    public function cpmk()
    {
        return $this->belongsTo(Cpmk::class);
    }

    public function cpl()
    {
        return $this->belongsTo(Cpl::class);
    }

    public function komponens()
    {
        return $this->hasMany(RpsPenilaianKomponen::class, 'sub_cpmk_id');
    }
}
