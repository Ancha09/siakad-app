@extends('layouts.admin')

@section('title', 'Preview KTM Mahasiswa')

@section('content')
<div class="page-card">
    <div class="page-card-head"><h2>Preview KTM Mahasiswa</h2></div>
    <div class="page-card-body">
        <a href="{{ route('admin.ktm.index') }}" class="btn-outline" style="display:inline-block;margin-bottom:18px;">Kembali</a>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;align-items:start;">
            <div style="background:#f8fafc;padding:18px;border-radius:12px;">
                <div style="margin-bottom:12px;"><small style="color:#64748b;">NIM</small><br><strong>{{ $mahasiswa->nim }}</strong></div>
                <div style="margin-bottom:12px;"><small style="color:#64748b;">Nama</small><br><strong>{{ $mahasiswa->nama }}</strong></div>
                <div style="margin-bottom:12px;"><small style="color:#64748b;">Program Studi</small><br><strong>{{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</strong></div>
                <div style="margin-bottom:12px;"><small style="color:#64748b;">Status</small><br><strong>{{ $mahasiswa->ktm_photo_locked ? 'Sudah upload / terkunci' : 'Belum upload' }}</strong></div>
                @if($mahasiswa->ktm_photo_locked)
                    <form method="POST" action="{{ route('admin.ktm.reset', $mahasiswa) }}" onsubmit="return confirm('Reset foto KTM mahasiswa ini? Mahasiswa akan dapat upload ulang.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="border:1px solid #dc2626;background:#fff;color:#b91c1c;border-radius:8px;padding:9px 13px;cursor:pointer;">Reset Foto KTM</button>
                    </form>
                @endif
            </div>
            <div>
                @if($mahasiswa->ktm_photo_locked && $mahasiswa->ktm_photo_path)
                    <img src="{{ route('admin.ktm.image', $mahasiswa) }}" alt="KTM {{ $mahasiswa->nama }}" style="display:block;width:100%;height:auto;border-radius:16px;border:1px solid #dbe4f0;box-shadow:0 12px 30px rgba(15,23,42,.12);">
                @else
                    <div style="padding:36px;text-align:center;background:#f8fafc;color:#64748b;border-radius:12px;">Mahasiswa belum mengirim foto KTM.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
