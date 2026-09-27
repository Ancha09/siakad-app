<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KrsApprovalReset extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_id', 'mahasiswa_id', 'periode_krs_id', 'aksi', 'alasan', 'krs_sebelum', 'created_at'];

    protected function casts(): array
    {
        return ['krs_sebelum' => 'array', 'created_at' => 'datetime'];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function periodeKrs()
    {
        return $this->belongsTo(PeriodeKrs::class);
    }
}
