<?php

namespace App\Services;

use App\Models\Krs;
use App\Models\KrsApprovalReset;
use App\Models\Mahasiswa;
use App\Models\Pengumuman;
use App\Models\PeriodeKrs;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class KrsApprovalResetService
{
    public function eligibleStudents(PeriodeKrs $periode): Builder
    {
        return Mahasiswa::query()
            ->whereHas('krs', fn (Builder $query) => $this->periodRecords($query, $periode)
                ->where('status', '!=', 'Draft')
                ->where('admin_revision_open', false))
            ->whereDoesntHave('krs', fn (Builder $query) => $this->periodRecords($query, $periode)->whereHas('khs'));
    }

    public function resetStudent(PeriodeKrs $periode, int $mahasiswaId, User $admin, string $alasan, string $aksi): bool
    {
        return DB::transaction(function () use ($periode, $mahasiswaId, $admin, $alasan, $aksi): bool {
            $mahasiswa = Mahasiswa::query()->lockForUpdate()->find($mahasiswaId);
            if (! $mahasiswa) {
                return false;
            }

            $records = $this->periodRecords(Krs::query(), $periode)
                ->where('mahasiswa_id', $mahasiswaId)
                ->lockForUpdate()
                ->get();

            if (! $records->contains(fn (Krs $item) => $item->status !== 'Draft' && ! $item->admin_revision_open)) {
                return false;
            }

            // Nilai yang sudah terbit bergantung pada status KRS Disetujui.
            // Lewati mahasiswa ini supaya reset tidak menyembunyikan nilai/KHS.
            if ($records->contains(fn (Krs $item) => $item->khs()->exists())) {
                return false;
            }

            KrsApprovalReset::create([
                'admin_id' => $admin->id,
                'mahasiswa_id' => $mahasiswaId,
                'periode_krs_id' => $periode->id,
                'aksi' => $aksi,
                'alasan' => $alasan,
                'krs_sebelum' => $records->map(fn (Krs $item) => [
                    'id' => $item->id,
                    'status' => $item->status,
                    'admin_revision_open' => $item->admin_revision_open,
                ])->all(),
                'created_at' => now(),
            ]);

            $this->periodRecords(Krs::query(), $periode)
                ->where('mahasiswa_id', $mahasiswaId)
                ->where('status', '!=', 'Draft')
                ->update([
                    'status' => 'Menunggu',
                    'admin_revision_open' => true,
                    'alasan_penolakan' => null,
                    'updated_at' => now(),
                ]);

            $this->periodRecords(Krs::query(), $periode)
                ->where('mahasiswa_id', $mahasiswaId)
                ->where('status', 'Draft')
                ->update(['admin_revision_open' => true, 'updated_at' => now()]);

            if ($mahasiswa->user_id) {
                Pengumuman::create([
                    'penulis_id' => $admin->id,
                    'penerima' => 'mahasiswa',
                    'target_type' => 'student',
                    'target_user_id' => $mahasiswa->user_id,
                    'judul' => 'Koreksi Persetujuan KRS '.$periode->tahun_akademik.' '.$periode->semester,
                    'isi' => 'Status KRS Anda dikembalikan ke Menunggu Persetujuan oleh admin. Alasan: '.$alasan.'. Silakan periksa kembali pilihan mata kuliah Anda dan ajukan ulang KRS.',
                    'tautan' => route('mahasiswa.krs'),
                    'penting' => true,
                    'status' => 'terbit',
                    'terbit_pada' => now(),
                ]);
            }

            return true;
        });
    }

    private function periodRecords(Builder $query, PeriodeKrs $periode): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('is_manual', false)->orWhereNull('is_manual'))
            ->where('tahun_akademik', $periode->tahun_akademik)
            ->where('semester_akademik', $periode->semester);
    }
}
