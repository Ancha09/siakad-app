<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';

    protected $fillable = ['penulis_id', 'judul', 'isi', 'penerima', 'tautan', 'penting', 'status', 'terbit_pada', 'berakhir_pada', 'target_type', 'target_user_id', 'target_periode_krs_id', 'target_prodi_id', 'target_angkatan'];

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
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $student = $user->role === 'mahasiswa' ? $user->mahasiswa : null;
                $periodIds = $student ? PeriodeKrs::query()
                    ->where(function ($periods) use ($student) {
                        $periods->whereHas('aksesMahasiswa', fn ($access) => $access
                            ->where('mahasiswa_id', $student->id)
                            ->where('status_akses', true))
                            ->orWhereExists(fn ($krs) => $krs->selectRaw('1')->from('krs')
                                ->whereColumn('krs.tahun_akademik', 'periode_krs.tahun_akademik')
                                ->whereColumn('krs.semester_akademik', 'periode_krs.semester')
                                ->where('krs.mahasiswa_id', $student->id)
                                ->where('krs.is_manual', false));
                    })->pluck('id') : collect();

                $q->where('penerima', $user->role)
                    ->where(function ($target) use ($user, $student, $periodIds) {
                        $target->where('target_type', 'all')
                            ->orWhere(fn ($specific) => $specific->where('target_type', 'student')->where('target_user_id', $user->id));

                        if ($student) {
                            $target->orWhere(fn ($program) => $program->where('target_type', 'prodi')->where('target_prodi_id', $student->prodi_id))
                                ->orWhere(fn ($cohort) => $cohort->where('target_type', 'angkatan')->where('target_angkatan', $student->angkatan));
                            if ($periodIds->isNotEmpty()) {
                                $target->orWhere(fn ($period) => $period->where('target_type', 'period')->whereIn('target_periode_krs_id', $periodIds));
                            }
                        }
                    });
            });
    }

    public function scopeDenganStatusBaca(Builder $query, User $user): Builder
    {
        return $query->withExists(['pembaca as sudah_dibaca' => fn ($q) => $q->where('users.id', $user->id)]);
    }

    public function getLabelStatusAttribute(): string
    {
        if ($this->status === 'draft') {
            return 'Draft';
        }
        if ($this->berakhir_pada?->isPast()) {
            return 'Berakhir';
        }
        if ($this->terbit_pada?->isFuture()) {
            return 'Terjadwal';
        }

        return 'Terbit';
    }
}
