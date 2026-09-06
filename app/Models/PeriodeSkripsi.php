<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodeSkripsi extends Model
{
    protected $fillable = ['nama', 'mulai', 'berakhir'];

    protected function casts(): array
    {
        return ['mulai' => 'datetime', 'berakhir' => 'datetime'];
    }

    public function terbuka(): bool
    {
        return now()->betweenIncluded($this->mulai, $this->berakhir);
    }
}
