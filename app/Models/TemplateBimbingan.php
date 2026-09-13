<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateBimbingan extends Model
{
    protected $fillable = ['nama_asli', 'path', 'mime_type', 'ukuran', 'diunggah_oleh'];

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public static function aktif(): ?self
    {
        return static::latest('id')->first();
    }
}
