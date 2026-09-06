<?php

namespace App\Policies;

use App\Models\PengajuanSkripsi;
use App\Models\User;

class PengajuanSkripsiPolicy
{
    public function view(User $user, PengajuanSkripsi $pengajuan): bool
    {
        return $user->role === 'admin'
            || ($user->role === 'mahasiswa' && $pengajuan->mahasiswa->user_id === $user->id)
            || ($user->role === 'dosen' && $pengajuan->dosen->user_id === $user->id);
    }

    public function decide(User $user, PengajuanSkripsi $pengajuan): bool
    {
        return $user->role === 'dosen' && $pengajuan->dosen->user_id === $user->id;
    }
}
