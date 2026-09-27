@extends('layouts.admin')

@section('title', 'KTM Mahasiswa')

@section('content')
<div class="page-card">
    <div class="page-card-head"><h2>KTM Mahasiswa</h2></div>
    <div class="page-card-body">
        @if(session('success'))
            <div style="background:#dcfce7;color:#166534;padding:13px 16px;border-radius:10px;margin-bottom:18px;">{{ session('success') }}</div>
        @endif

        <form method="GET" action="{{ route('admin.ktm.index') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:20px;align-items:end;">
            <div class="form-group">
                <label for="search">Mahasiswa / NIM</label>
                <input id="search" type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nama atau NIM">
            </div>
            <div class="form-group">
                <label for="prodi_id">Program Studi</label>
                <select id="prodi_id" name="prodi_id" class="form-control">
                    <option value="">Semua program studi</option>
                    @foreach($prodis as $prodi)
                        <option value="{{ $prodi->id }}" @selected((string) request('prodi_id') === (string) $prodi->id)>{{ $prodi->nama_prodi }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status KTM</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Semua status</option>
                    <option value="locked" @selected(request('status') === 'locked')>Sudah upload / terkunci</option>
                    <option value="empty" @selected(request('status') === 'empty')>Belum upload</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="btn-primary" type="submit">Terapkan Filter</button>
                <a class="btn-outline" href="{{ route('admin.ktm.index') }}">Reset</a>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead><tr><th>No</th><th>NIM</th><th>Mahasiswa</th><th>Program Studi</th><th>Status KTM</th><th>Dikirim</th><th style="width:190px;">Aksi</th></tr></thead>
                <tbody>
                    @forelse($mahasiswas as $mahasiswa)
                        <tr>
                            <td>{{ ($mahasiswas->firstItem() ?? 1) + $loop->index }}</td>
                            <td>{{ $mahasiswa->nim }}</td>
                            <td>{{ $mahasiswa->nama }}</td>
                            <td>{{ $mahasiswa->prodi?->nama_prodi ?? '-' }}</td>
                            <td>
                                @if($mahasiswa->ktm_photo_locked)
                                    <span class="badge-success">Sudah upload / terkunci</span>
                                @else
                                    <span style="display:inline-block;padding:5px 9px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:700;">Belum upload</span>
                                @endif
                            </td>
                            <td>{{ $mahasiswa->ktm_photo_uploaded_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>
                                <div style="display:flex;gap:7px;align-items:center;flex-wrap:wrap;">
                                    <a href="{{ route('admin.ktm.show', $mahasiswa) }}" class="btn-outline" style="padding:7px 10px;">Lihat</a>
                                    @if($mahasiswa->ktm_photo_locked)
                                        <form method="POST" action="{{ route('admin.ktm.reset', $mahasiswa) }}" onsubmit="return confirm('Reset foto KTM mahasiswa ini? Mahasiswa akan dapat upload ulang.')">
                                            @csrf
                                            @method('DELETE')
                                            @foreach(request()->query() as $key => $value)
                                                @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                                            @endforeach
                                            <button type="submit" style="border:1px solid #dc2626;background:#fff;color:#b91c1c;border-radius:8px;padding:7px 10px;cursor:pointer;">Reset</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:28px;color:#64748b;">Data mahasiswa tidak ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:18px;">{{ $mahasiswas->links() }}</div>
    </div>
</div>
@endsection
