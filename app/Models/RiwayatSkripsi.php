<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatSkripsi extends Model
{
    protected $fillable = ['periode_skripsi_id', 'pengajuan_skripsi_id', 'pelaku_id', 'pelaku_nama', 'pelaku_role', 'tindakan', 'perubahan'];

    protected function casts(): array
    {
        return ['perubahan' => 'array'];
    }

    public function pelaku()
    {
        return $this->belongsTo(User::class, 'pelaku_id');
    }

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanSkripsi::class, 'pengajuan_skripsi_id');
    }
}
