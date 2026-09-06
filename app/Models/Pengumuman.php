<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';

    protected $fillable = ['judul', 'isi', 'penerima', 'tautan', 'penting', 'status', 'terbit_pada', 'berakhir_pada'];

    protected function casts(): array
    {
        return ['penting' => 'boolean', 'terbit_pada' => 'datetime', 'berakhir_pada' => 'datetime'];
    }

    public function penulis()
    {
        return $this->belongsTo(User::class, 'penulis_id');
    }

    public function pembaca()
    {
        return $this->belongsToMany(User::class, 'pengumuman_reads')->withPivot('read_at');
    }

    public function scopeTerlihat(Builder $query, User $user): Builder
    {
        return $query->where('status', 'terbit')
            ->where('terbit_pada', '<=', now())
            ->where(fn ($q) => $q->whereNull('berakhir_pada')->orWhere('berakhir_pada', '>', now()))
            ->when($user->role !== 'admin', fn ($q) => $q->where('penerima', $user->role));
    }

    public function scopeDenganStatusBaca(Builder $query, User $user): Builder
    {
        return $query->withExists(['pembaca as sudah_dibaca' => fn ($q) => $q->where('users.id', $user->id)]);
    }

    public function getLabelStatusAttribute(): string
    {
        if ($this->status === 'draft') return 'Draft';
        if ($this->berakhir_pada?->isPast()) return 'Berakhir';
        if ($this->terbit_pada?->isFuture()) return 'Terjadwal';
        return 'Terbit';
    }
}
