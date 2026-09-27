@extends('layouts.mahasiswa')

@section('title', 'KTM Mahasiswa')

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <h2>KTM Mahasiswa</h2>
    </div>

    <div class="page-card-body">
        @if(session('success'))
            <div style="background:#dcfce7;color:#166534;padding:13px 16px;border-radius:10px;margin-bottom:18px;">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div style="background:#fee2e2;color:#991b1b;padding:13px 16px;border-radius:10px;margin-bottom:18px;">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
                <strong>Foto belum dapat diproses:</strong>
                <ul style="margin:8px 0 0;padding-left:20px;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @if($mahasiswa->ktm_photo_locked && $mahasiswa->ktm_photo_path)
            <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
                Foto KTM sudah dikirim dan tidak dapat diganti. Hubungi admin jika terdapat kesalahan.
            </div>

            <div style="max-width:820px;margin:0 auto;">
                <img src="{{ route('mahasiswa.ktm.image') }}" alt="KTM {{ $mahasiswa->nama }}" style="display:block;width:100%;height:auto;border-radius:16px;border:1px solid #dbe4f0;box-shadow:0 12px 30px rgba(15,23,42,.12);">
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;justify-content:center;">
                    <a href="{{ route('mahasiswa.ktm.pdf') }}" class="btn-primary">Download PDF</a>
                    <a href="{{ route('mahasiswa.ktm.png') }}" class="btn-outline">Download PNG</a>
                </div>
                @if($mahasiswa->ktm_photo_uploaded_at)
                    <p style="text-align:center;color:#64748b;margin-top:12px;">Dikirim pada {{ $mahasiswa->ktm_photo_uploaded_at->format('d/m/Y H:i') }} WIB</p>
                @endif
            </div>
        @elseif($temporaryPath)
            <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
                <strong>Tahap 1 — Periksa preview.</strong> Pastikan foto dan identitas pada KTM berikut sudah benar sebelum dikirim permanen.
            </div>

            <div style="max-width:820px;margin:0 auto;">
                <img src="{{ route('mahasiswa.ktm.preview-image', ['v' => now()->timestamp]) }}" alt="Preview KTM {{ $mahasiswa->nama }}" style="display:block;width:100%;height:auto;border-radius:16px;border:1px solid #dbe4f0;box-shadow:0 12px 30px rgba(15,23,42,.12);">

                <div style="margin-top:20px;padding:18px;border:1px solid #fecaca;background:#fff7f7;border-radius:12px;">
                    <strong style="display:block;color:#991b1b;margin-bottom:10px;">Tahap 2 — Konfirmasi final</strong>
                    <p style="margin:0 0 14px;color:#7f1d1d;">Foto KTM hanya dapat dikirim satu kali dan tidak dapat diganti tanpa persetujuan admin.</p>
                    <form action="{{ route('mahasiswa.ktm.finalize') }}" method="POST" onsubmit="return confirm('Foto KTM hanya dapat dikirim satu kali dan tidak dapat diganti tanpa persetujuan admin. Lanjutkan?')">
                        @csrf
                        <label style="display:flex;align-items:flex-start;gap:9px;color:#334155;margin-bottom:14px;">
                            <input type="checkbox" name="confirm_final" value="1" required style="margin-top:3px;">
                            <span>Saya sudah memeriksa foto dan data KTM serta memahami bahwa pengiriman ini bersifat final.</span>
                        </label>
                        <button type="submit" class="btn-primary">Gunakan Foto Ini</button>
                    </form>

                    <form action="{{ route('mahasiswa.ktm.cancel') }}" method="POST" style="margin-top:10px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-outline">Batalkan dan Upload Ulang</button>
                    </form>
                </div>
            </div>
        @else
            <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:20px;">
                <strong>Perhatian:</strong> Foto hanya dapat dikirim satu kali untuk selamanya. Pastikan foto resmi, jelas, dan menghadap ke depan.
            </div>

            <form action="{{ route('mahasiswa.ktm.upload') }}" method="POST" enctype="multipart/form-data" style="max-width:620px;">
                @csrf
                <div class="form-group">
                    <label for="photo">Foto KTM <span style="color:#dc2626;">*</span></label>
                    <input id="photo" type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                    <small style="display:block;color:#64748b;margin-top:7px;">JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB. Foto akan dipotong otomatis ke ukuran pas foto.</small>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:14px;">Upload dan Lihat Preview</button>
            </form>
        @endif
    </div>
</div>
@endsection
