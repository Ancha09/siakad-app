<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\MataKuliah;
use App\Models\Prodi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LegacyAcademicService
{
    public function matchingKrs(array $data): Builder
    {
        return Krs::query()
            ->where('mahasiswa_id', $data['mahasiswa_id'])
            ->where('tahun_akademik', $data['tahun_akademik'])
            ->where('semester_akademik', $data['semester_akademik'])
            ->where(function (Builder $query) use ($data) {
                $query->where('mata_kuliah_id', $data['mata_kuliah_id'])
                    ->orWhereHas('jadwal', fn (Builder $jadwal) => $jadwal
                        ->where('mata_kuliah_id', $data['mata_kuliah_id']));
            });
    }

    public function validateSchedule(array $data): ?Jadwal
    {
        if (empty($data['jadwal_id'])) {
            return null;
        }

        $jadwal = Jadwal::findOrFail($data['jadwal_id']);

        if ((int) $jadwal->mata_kuliah_id !== (int) $data['mata_kuliah_id']) {
            throw ValidationException::withMessages([
                'jadwal_id' => 'Jadwal yang dipilih tidak sesuai dengan mata kuliah.',
            ]);
        }

        if ($jadwal->tahun_akademik && $jadwal->tahun_akademik !== $data['tahun_akademik']) {
            throw ValidationException::withMessages([
                'tahun_akademik' => 'Tahun ajaran tidak sesuai dengan jadwal yang dipilih.',
            ]);
        }

        if ($jadwal->semester_akademik && $jadwal->semester_akademik !== $data['semester_akademik']) {
            throw ValidationException::withMessages([
                'semester_akademik' => 'Semester akademik tidak sesuai dengan jadwal yang dipilih.',
            ]);
        }

        if (! empty($data['kelas_id']) && $jadwal->kelas_id && (int) $jadwal->kelas_id !== (int) $data['kelas_id']) {
            throw ValidationException::withMessages([
                'kelas_id' => 'Kelas yang dipilih tidak sesuai dengan jadwal.',
            ]);
        }

        return $jadwal;
    }

    public function resolveKrs(array $data, bool $overwriteMetadata = false, ?int $currentKrsId = null): Krs
    {
        $current = $currentKrsId ? $this->matchingKrs($data)->whereKey($currentKrsId)->lockForUpdate()->first() : null;
        if ($current && ! $current->is_manual) {
            return $current;
        }
        // Historical lecturer corrections are stored on the record, not on an active KRS.
        $active = $current ? null : $this->matchingKrs($data)->where('is_manual', false)->where('status', 'Disetujui')->lockForUpdate()->first();
        if ($active) {
            return $active;
        }
        $metadata = [
            'jadwal_id' => $data['jadwal_id'] ?? null,
            'mata_kuliah_id' => $data['mata_kuliah_id'],
            'prodi_id' => $data['prodi_id'] ?? null,
            'kelas_id' => $data['kelas_id'] ?? null,
            'angkatan' => $data['angkatan'] ?? null,
            'semester' => $data['semester'] ?? null,
            'status' => 'Disetujui',
        ];
        $manual = $current ?? $this->matchingKrs($data)->where('is_manual', true)->lockForUpdate()->first();
        if ($manual) {
            // Incomplete new entries must not erase metadata from earlier historical entries.
            $manual->update($overwriteMetadata ? $metadata : array_filter($metadata, fn ($value) => $value !== null));

            return $manual;
        }

        // Keep manual metadata separate; never modify an active KRS or its schedule.
        return Krs::create($metadata + [
            'dosen_id' => $data['dosen_id'] ?? null,
            'mahasiswa_id' => $data['mahasiswa_id'],
            'tahun_akademik' => $data['tahun_akademik'],
            'semester_akademik' => $data['semester_akademik'],
            'is_manual' => true,
            'manual_identity' => $this->termIdentity($data),
        ]);
    }

    public function filterRecords(Builder $query, Request $request, bool $attendance = false): Builder
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'angkatan' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'semester' => ['nullable', 'integer', 'between:1,14'],
            'tahun_akademik' => ['nullable', 'string', 'max:20'],
            'semester_akademik' => ['nullable', Rule::in(['Ganjil', 'Genap'])],
            'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'mata_kuliah_id' => ['nullable', 'integer', 'exists:mata_kuliahs,id'],
            'dosen_id' => ['nullable', 'integer', 'exists:dosens,id'],
            'status' => ['nullable', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alpha'])],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:'.($request->input('tanggal_mulai') ?: '1900-01-01')],
        ]);
        $query->whereHas('krs', function (Builder $krs) use ($filters) {
            foreach (['tahun_akademik', 'semester_akademik', 'semester'] as $field) {
                if (! empty($filters[$field])) {
                    $krs->where($field, $filters[$field]);
                }
            }
            if (! empty($filters['search'])) {
                $search = '%'.trim($filters['search']).'%';
                $krs->whereHas('mahasiswa', fn (Builder $m) => $m->where('nim', 'like', $search)->orWhere('nama', 'like', $search));
            }
            foreach (['prodi_id', 'angkatan'] as $field) {
                if (! empty($filters[$field])) {
                    $krs->where(fn (Builder $q) => $q->where($field, $filters[$field])
                        ->orWhere(fn (Builder $fallback) => $fallback->whereNull($field)->whereHas('mahasiswa', fn (Builder $m) => $m->where($field, $filters[$field]))));
                }
            }
            if (! empty($filters['mata_kuliah_id'])) {
                $krs->where(fn (Builder $q) => $q->where('mata_kuliah_id', $filters['mata_kuliah_id'])
                    ->orWhereHas('jadwal', fn (Builder $j) => $j->where('mata_kuliah_id', $filters['mata_kuliah_id'])));
            }
            if (! empty($filters['kelas_id'])) {
                // Match the same precedence as kelas_efektif: schedule, manual KRS, student.
                $kelasId = $filters['kelas_id'];
                $krs->where(fn (Builder $q) => $q
                    ->whereHas('jadwal', fn (Builder $j) => $j->where('kelas_id', $kelasId))
                    ->orWhere(fn (Builder $fallback) => $fallback
                        ->whereDoesntHave('jadwal', fn (Builder $j) => $j->whereNotNull('kelas_id'))
                        ->where(fn (Builder $manual) => $manual->where('kelas_id', $kelasId)
                            ->orWhere(fn (Builder $student) => $student->whereNull('kelas_id')
                                ->whereHas('mahasiswa', fn (Builder $m) => $m->where('kelas_id', $kelasId))))));
            }
        });
        if (! empty($filters['dosen_id'])) {
            $query->forDosen((int) $filters['dosen_id']);
        }
        if ($attendance) {
            foreach (['status' => '=', 'tanggal_mulai' => '>=', 'tanggal_selesai' => '<='] as $field => $operator) {
                if (! empty($filters[$field])) {
                    $query->where($field === 'status' ? 'status' : 'tanggal', $operator, $filters[$field]);
                }
            }
        }

        return $query;
    }

    public function filterOptions(): array
    {
        return [
            'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'kelases' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahs' => MataKuliah::orderBy('kode_mk')->get(),
            'dosens' => Dosen::orderBy('nama')->get(),
        ];
    }

    public function termIdentity(array $data): string
    {
        return hash('sha256', implode('|', [
            $data['mahasiswa_id'],
            $data['mata_kuliah_id'],
            strtolower($data['semester_akademik']),
            strtolower(trim($data['tahun_akademik'])),
        ]));
    }

    public function attendanceIdentity(array $data): string
    {
        return hash('sha256', $this->termIdentity($data).'|'.($data['tanggal'] ?? '').'|'.($data['pertemuan'] ?? 'tanpa-pertemuan'));
    }
}
