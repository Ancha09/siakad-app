<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranMahasiswa extends Model
{
    public const OPEN_STATUSES = ['creating', 'pending', 'unknown'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'nominal_pokok' => 'integer', 'biaya_layanan' => 'integer', 'is_test' => 'boolean', 'paid_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function tagihan()
    {
        return $this->belongsTo(TagihanMahasiswa::class, 'tagihan_mahasiswa_id');
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMetodeLabelAttribute(): string
    {
        if ($this->metode_pembayaran === 'cash' || in_array($this->source, ['cash', 'manual'], true)) {
            return 'Cash / Manual';
        }

        $banks = config('payments.va_methods', []);

        return 'VA Midtrans'.(isset($banks[$this->payment_method]) ? ' - '.$banks[$this->payment_method] : '');
    }
}
