<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAccess extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
