<?php

namespace App\Services;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\PengajuanSkripsi;
use App\Models\PeriodeSkripsi;
use App\Models\RiwayatSkripsi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SkripsiService
{
    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['skripsi' => $message]);
        }
    }

    private function lockPeriod(int $id, bool $mustBeOpen = true): PeriodeSkripsi
    {
        // First write acquires the period lock before reading state (including SQLite).
        PeriodeSkripsi::whereKey($id)->increment('lock_version');
        $periode = PeriodeSkripsi::findOrFail($id);
        $this->ensure(! $mustBeOpen || $periode->terbuka(), 'Periode belum dibuka atau sudah ditutup. Tindakan tidak dapat diproses.');

        return $periode;
    }

    private function eligibleStudent(int $id): Mahasiswa
    {
        $mahasiswa = Mahasiswa::with('user')->lockForUpdate()->findOrFail($id);
        $this->ensure($mahasiswa->prodi_id !== null && $mahasiswa->user?->role === 'mahasiswa', 'Mahasiswa harus memiliki prodi dan akun mahasiswa yang valid.');
        $this->ensure($mahasiswa->memenuhiSyaratSemesterSkripsi(), 'Pengajuan skripsi hanya diperbolehkan untuk mahasiswa semester '.Mahasiswa::MIN_SEMESTER_SKRIPSI.' ke atas. Pastikan data semester mahasiswa sudah diisi dengan benar.');

        return $mahasiswa;
    }

    private function eligibleDosen(int $id): Dosen
    {
        $dosen = Dosen::pembimbingAktif()->lockForUpdate()->find($id);
        $this->ensure($dosen !== null, 'Dosen tidak aktif atau belum memenuhi syarat sebagai pembimbing skripsi.');

        return $dosen;
    }

    public function audit(User $actor, string $action, array $changes, ?int $period = null, ?int $submission = null): void
    {
        RiwayatSkripsi::create([
            'pelaku_id' => $actor->id, 'tindakan' => $action, 'perubahan' => $changes,
            'pelaku_nama' => $actor->name, 'pelaku_role' => $actor->role,
            'periode_skripsi_id' => $period, 'pengajuan_skripsi_id' => $submission,
        ]);
    }

    public function submit(User $actor, array $data): PengajuanSkripsi
    {
        abort_unless($actor->role === 'mahasiswa', 403);

        return DB::transaction(function () use ($actor, $data) {
            $period = $this->lockPeriod($data['periode_skripsi_id']);
            $student = $this->eligibleStudent(Mahasiswa::where('user_id', $actor->id)->firstOrFail()->id);
            $dosen = $this->eligibleDosen($data['dosen_id']);
            $last = $student->pengajuanSkripsi()->where('periode_skripsi_id', $period->id)->latest('id')->first();
            $this->ensure(! $last || $last->status === 'Ditolak', 'Sudah ada pengajuan aktif atau pembimbing yang disetujui pada periode ini.');
            $this->ensure(! $last || $last->dosen_id !== $dosen->id, 'Pengajuan ulang harus ditujukan kepada dosen lain.');
            $submission = PengajuanSkripsi::create([
                'periode_skripsi_id' => $period->id, 'mahasiswa_id' => $student->id,
                'dosen_id' => $dosen->id, 'judul' => $data['judul'], 'status' => 'Menunggu',
                'dibuat_oleh' => $actor->id, 'jenis_pembuat' => 'mahasiswa', 'pengajuan_asal_id' => $last?->id,
            ]);
            $this->audit($actor, $last ? 'Pengajuan ulang' : 'Pengajuan', [
                'judul_lama' => $last?->judul, 'judul_baru' => $submission->judul,
                'dosen_asal_id' => $last?->dosen_id, 'dosen_tujuan_id' => $dosen->id,
                'status' => 'Menunggu',
            ], $period->id, $submission->id);

            return $submission;
        }, 5);
    }

    public function decide(User $actor, PengajuanSkripsi $submission, array $data): void
    {
        Gate::forUser($actor)->authorize('decide', $submission);
        DB::transaction(function () use ($actor, $submission, $data) {
            $this->lockPeriod($submission->periode_skripsi_id);
            $current = PengajuanSkripsi::lockForUpdate()->findOrFail($submission->id);
            Gate::forUser($actor)->authorize('decide', $current);
            $this->ensure($current->status === 'Menunggu', 'Pengajuan sudah diproses atau dialihkan.');
            $this->eligibleStudent($current->mahasiswa_id);
            $this->eligibleDosen($current->dosen_id);
            $this->ensure(in_array($data['status'], ['Diterima', 'Ditolak'], true), 'Keputusan tidak valid.');
            $reason = trim($data['alasan'] ?? '');
            $this->ensure($data['status'] !== 'Ditolak' || $reason !== '', 'Alasan penolakan wajib diisi.');
            $current->update([
                'status' => $data['status'], 'alasan_keputusan' => $data['status'] === 'Ditolak' ? $reason : null,
                'diputuskan_pada' => now(),
            ]);
            $this->audit($actor, $data['status'], [
                'judul' => $current->judul, 'dosen_id' => $current->dosen_id,
                'status_lama' => 'Menunggu', 'status_baru' => $current->status,
                'alasan' => $current->alasan_keputusan,
            ], $current->periode_skripsi_id, $current->id);
        }, 5);
    }

    public function updateTitle(User $actor, PengajuanSkripsi $submission, string $title): void
    {
        abort_unless($actor->role === 'mahasiswa', 403);

        DB::transaction(function () use ($actor, $submission, $title) {
            $this->lockPeriod($submission->periode_skripsi_id);
            $current = PengajuanSkripsi::lockForUpdate()->findOrFail($submission->id);
            $student = $this->eligibleStudent(Mahasiswa::where('user_id', $actor->id)->firstOrFail()->id);
            $latest = $student->pengajuanSkripsi()->where('periode_skripsi_id', $current->periode_skripsi_id)->latest('id')->first();
            $this->ensure($current->mahasiswa_id === $student->id && $latest?->id === $current->id, 'Hanya judul pengajuan terakhir milik Anda yang dapat diubah.');
            $this->ensure(in_array($current->status, ['Menunggu', 'Diterima'], true), 'Judul pengajuan ini tidak dapat diubah. Gunakan formulir pengajuan ulang setelah ditolak.');
            $title = trim($title);
            $this->ensure($title !== '' && $title !== $current->judul, 'Masukkan judul baru yang berbeda dari judul sebelumnya.');
            $oldTitle = $current->judul;
            $current->update(['judul' => $title]);
            $this->audit($actor, 'Perubahan judul', [
                'judul_lama' => $oldTitle, 'judul_baru' => $title, 'status' => $current->status,
            ], $current->periode_skripsi_id, $current->id);
        }, 5);
    }

    public function transfer(User $actor, PengajuanSkripsi $submission, array $data): PengajuanSkripsi
    {
        abort_unless($actor->role === 'admin', 403);

        return DB::transaction(function () use ($actor, $submission, $data) {
            $this->lockPeriod($submission->periode_skripsi_id);
            $current = PengajuanSkripsi::lockForUpdate()->findOrFail($submission->id);
            $student = $this->eligibleStudent($current->mahasiswa_id);
            $last = $student->pengajuanSkripsi()->where('periode_skripsi_id', $current->periode_skripsi_id)->latest('id')->first();
            $this->ensure($last->id === $current->id && in_array($current->status, ['Menunggu', 'Ditolak'], true), 'Hanya pengajuan terakhir yang belum diterima dapat dialihkan.');
            $dosen = $this->eligibleDosen($data['dosen_id']);
            $this->ensure($dosen->id !== $current->dosen_id, 'Pilih dosen pengganti yang berbeda.');
            $reason = trim($data['alasan']);
            $this->ensure($reason !== '', 'Alasan pengalihan wajib diisi.');
            $oldStatus = $current->status;
            if ($oldStatus === 'Menunggu') {
                $current->update(['status' => 'Dialihkan', 'alasan_keputusan' => $reason, 'diputuskan_pada' => now()]);
            }
            $new = PengajuanSkripsi::create([
                'periode_skripsi_id' => $current->periode_skripsi_id, 'mahasiswa_id' => $current->mahasiswa_id,
                'dosen_id' => $dosen->id, 'judul' => $current->judul, 'status' => 'Menunggu',
                'dibuat_oleh' => $actor->id, 'jenis_pembuat' => 'admin', 'pengajuan_asal_id' => $current->id,
            ]);
            $changes = [
                'judul' => $current->judul, 'dosen_asal_id' => $current->dosen_id, 'dosen_tujuan_id' => $dosen->id,
                'pengajuan_asal_id' => $current->id, 'pengajuan_baru_id' => $new->id,
                'status_lama' => $oldStatus, 'status_baru' => $current->status, 'alasan' => $reason,
            ];
            $this->audit($actor, 'Pengalihan', $changes, $current->periode_skripsi_id, $current->id);
            $this->audit($actor, 'Pengajuan oleh admin', $changes + ['status' => 'Menunggu'], $new->periode_skripsi_id, $new->id);

            return $new;
        }, 5);
    }

    public function savePeriod(User $actor, array $data, ?PeriodeSkripsi $period = null): PeriodeSkripsi
    {
        abort_unless($actor->role === 'admin', 403);

        return DB::transaction(function () use ($actor, $data, $period) {
            $period = $period ? $this->lockPeriod($period->id, false) : new PeriodeSkripsi;
            $old = $period->only(['nama', 'mulai', 'berakhir']);
            $period->fill($data)->save();
            $this->audit($actor, 'Pengaturan periode', ['sebelum' => $old, 'sesudah' => $period->only(['nama', 'mulai', 'berakhir']), 'timezone' => config('app.timezone')], $period->id);

            return $period;
        }, 5);
    }

    public function setDosen(User $actor, Dosen $dosen, bool $active): void
    {
        abort_unless($actor->role === 'admin', 403);
        DB::transaction(function () use ($actor, $dosen, $active) {
            $dosen = Dosen::with('user')->lockForUpdate()->findOrFail($dosen->id);
            $this->ensure(! $active || ($dosen->prodi_id !== null && $dosen->user?->role === 'dosen'), 'Dosen harus memiliki prodi dan akun dosen yang valid.');
            $old = (bool) $dosen->skripsi_aktif;
            $dosen->skripsi_aktif = $active;
            $dosen->save();
            $this->audit($actor, 'Kelayakan dosen', ['dosen_id' => $dosen->id, 'nama' => $dosen->nama, 'sebelum' => $old, 'sesudah' => $active]);
        }, 5);
    }
}
