<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanMahasiswa extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['nominal_pokok' => 'integer', 'biaya_layanan' => 'integer', 'total_tagihan' => 'integer', 'total_dibayar' => 'integer', 'sisa_tagihan' => 'integer', 'jatuh_tempo' => 'date', 'boleh_cicil' => 'boolean', 'is_test' => 'boolean'];
    }

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(PembayaranMahasiswa::class);
    }

    public function audits()
    {
        return $this->hasMany(PaymentAudit::class);
    }

    public function getStatusTampilAttribute(): string
    {
        return $this->status === 'belum_dibayar' && $this->jatuh_tempo?->endOfDay()->isPast()
            ? 'expired' : $this->status;
    }

    public function getKelebihanPembayaranAttribute(): int
    {
        $actualFees = (int) $this->pembayaran()->where('status', 'paid')->sum('biaya_layanan');
        $expectedGross = (int) $this->nominal_pokok + $actualFees;

        return max(0, (int) $this->total_dibayar - $expectedGross);
    }
}
