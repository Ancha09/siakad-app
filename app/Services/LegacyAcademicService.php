<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Krs;
use Illuminate\Database\Eloquent\Builder;
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

        if (! empty($data['dosen_id']) && $jadwal->dosen_id && (int) $jadwal->dosen_id !== (int) $data['dosen_id']) {
            throw ValidationException::withMessages([
                'dosen_id' => 'Dosen yang dipilih tidak sesuai dengan jadwal.',
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

    public function resolveKrs(array $data): Krs
    {
        $existing = $this->matchingKrs($data)
            ->where('status', 'Disetujui')
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $manual = $this->matchingKrs($data)
            ->where('is_manual', true)
            ->lockForUpdate()
            ->first();

        if ($manual) {
            $manual->update(['status' => 'Disetujui']);

            return $manual;
        }

        $jadwal = $this->validateSchedule($data);

        return Krs::create([
            'mahasiswa_id' => $data['mahasiswa_id'],
            'jadwal_id' => $data['jadwal_id'] ?? null,
            'mata_kuliah_id' => $data['mata_kuliah_id'],
            'dosen_id' => $data['dosen_id'] ?? $jadwal?->dosen_id,
            'prodi_id' => $data['prodi_id'] ?? null,
            'kelas_id' => $data['kelas_id'] ?? null,
            'angkatan' => $data['angkatan'] ?? null,
            'semester' => $data['semester'] ?? null,
            'status' => 'Disetujui',
            'tahun_akademik' => $data['tahun_akademik'],
            'semester_akademik' => $data['semester_akademik'],
            'is_manual' => true,
            'manual_identity' => $this->termIdentity($data),
        ]);
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
