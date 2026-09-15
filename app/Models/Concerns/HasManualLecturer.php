<?php

namespace App\Models\Concerns;

use App\Models\Dosen;
use Illuminate\Database\Eloquent\Builder;

trait HasManualLecturer
{
    public function dosenManual()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id');
    }

    public function getDosenEfektifAttribute()
    {
        return $this->is_manual && $this->dosen_override
            ? $this->dosenManual
            : $this->krs?->dosen_efektif;
    }

    public function scopeForDosen(Builder $query, int $dosenId): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $manual) => $manual->where('is_manual', true)->where('dosen_override', true)->where('dosen_id', $dosenId))
            ->orWhere(fn (Builder $inherited) => $inherited
                ->where(fn (Builder $fallback) => $fallback->where('is_manual', false)->orWhere('dosen_override', false))
                ->whereHas('krs', fn (Builder $krs) => $krs->forDosen($dosenId))));
    }
}
