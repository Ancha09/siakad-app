@extends('layouts.dosen')

@section('title', 'Profil Saya')

@section('content')

<div class="inner-page">

    <div class="page-card">

        <div class="page-card-head">
            <h2><x-layout-icon name="user" /> <span>Profil Saya</span></h2>
        </div>

        <div class="page-card-body">

            @if(session('success'))
                <div class="alert alert-success" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom:20px;padding:12px 16px;border-radius:8px;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;">
                    <ul style="margin:0;padding-left:20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ===================== FOTO PROFIL ===================== --}}
            <div class="page-section" style="margin-bottom:30px;">
                <div class="section-title">
                    <h3 style="display:flex;align-items:center;gap:8px;">
                        <x-layout-icon name="user" />
                        <span>Foto Profil</span>
                    </h3>
                </div>

                @if($dosen->foto)
                    {{-- Kondisi 1: Foto sudah diupload (Terkunci) --}}
                    <div style="display:flex;gap:22px;align-items:center;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
                        <div style="width:110px;height:110px;border-radius:50%;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:3px solid #ffffff;flex-shrink:0;">
                            <img src="{{ asset('storage/' . $dosen->foto) }}" alt="{{ $dosen->nama }}" style="width:100%;height:100%;object-fit:cover;display:block;">
                        </div>
                        <div style="flex:1;min-width:240px;">
                            <div style="margin-bottom:8px;">
                                <span class="badge-status-pill badge-status-green" style="font-size:12px;padding:5px 12px;">
                                    <x-layout-icon name="check" />
                                    <span>Foto Profil Tersimpan &amp; Terkunci</span>
                                </span>
                            </div>
                            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.5;">
                                Foto profil Anda telah tersimpan dan dikunci untuk menjaga integritas data akademik. Apabila terdapat kesalahan pengunggahan atau perlu memperbarui foto formal, silakan menghubungi <strong>Administrator</strong> untuk melakukan reset foto profil.
                            </p>
                        </div>
                    </div>
                @else
                    {{-- Kondisi 2: Foto belum diupload (Bisa upload 1x dengan preview) --}}
                    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:14px 16px;color:#92400e;font-size:13px;line-height:1.5;margin-bottom:18px;">
                        <strong>Peringatan:</strong> Foto profil hanya dapat diunggah <strong>1 kali</strong>. Pastikan foto formal, jelas, dan sesuai dengan ketentuan akademik (maksimal 10 MB). Setelah disimpan, foto profil terkunci dan perubahan hanya dapat dilakukan melalui permohonan reset ke Administrator.
                    </div>

                    <form action="{{ route('dosen.profil.foto') }}" method="POST" enctype="multipart/form-data" id="formUploadFoto">
                        @csrf
                        <div style="display:flex;gap:22px;align-items:flex-start;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
                            <!-- Lingkaran Preview -->
                            <div style="width:110px;height:110px;border-radius:50%;overflow:hidden;border:2px dashed #94a3b8;background:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.04);" id="avatarPreviewBox">
                                <img id="avatarPreviewImg" src="" alt="Preview Foto" style="width:100%;height:100%;object-fit:cover;display:none;">
                                <span id="avatarPreviewInitials" style="font-size:36px;font-weight:700;color:#94a3b8;">
                                    {{ strtoupper(substr($dosen->nama ?? 'D', 0, 1)) }}
                                </span>
                            </div>

                            <!-- Input & Tombol Aksi -->
                            <div style="flex:1;min-width:260px;">
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label for="fotoInput" style="font-weight:600;font-size:13px;color:#1e293b;margin-bottom:6px;display:block;">Pilih File Foto Profil Formal</label>
                                    <input type="file" name="foto" id="fotoInput" class="form-control" accept=".jpg,.jpeg,.png,.svg,.heic,.heif,.webp" required style="padding:8px 12px;">
                                    <small style="color:#64748b;font-size:11.5px;display:block;margin-top:5px;">
                                        Format didukung: JPG, JPEG, PNG, SVG, HEIC, WEBP &middot; Ukuran maksimal: 10 MB
                                    </small>
                                </div>

                                <div id="previewMetaBox" style="display:none;margin-bottom:12px;background:#e2e8f0;padding:8px 12px;border-radius:6px;font-size:12px;color:#334155;">
                                    File terpilih: <strong id="previewFileName">-</strong> (<span id="previewFileSize">-</span>)
                                </div>

                                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                                    <button type="submit" id="btnSimpanFoto" class="btn-primary" style="display:none;" onclick="return confirm('Apakah Anda yakin ingin menyimpan foto ini? Foto profil hanya dapat diunggah 1 kali dan tidak dapat diubah kembali secara mandiri.')">
                                        <x-layout-icon name="save" />
                                        <span>Konfirmasi Simpan Foto</span>
                                    </button>
                                    <button type="button" id="btnBatalFoto" class="btn-outline" style="display:none;">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            <hr style="margin:25px 0;border:0;border-top:1px solid #e2e8f0;">

            {{-- DATA PRIBADI --}}
            <div class="page-section">

                <div class="section-title">
                    <h3 style="display:flex;align-items:center;gap:8px;">
                        <x-layout-icon name="clipboard" />
                        <span>Data Pribadi</span>
                    </h3>
                </div>

                <div class="profile-detail-grid">

                    <div>

                        <div class="profile-field">
                            <label>Nama Lengkap</label>
                            <p>{{ $dosen->nama ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>NIDN</label>
                            <p>{{ $dosen->nidn ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Program Studi</label>
                            <p>{{ $dosen->prodi->nama_prodi ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Jabatan Fungsional</label>
                            <p>{{ $dosen->jabatan ?? '-' }}</p>
                        </div>

                        <div class="profile-field">
                            <label>Golongan / Ruang</label>
                            <p>{{ $dosen->golongan ?? '-' }}</p>
                        </div>

                    </div>

                    <div>

                        <div class="profile-field">
                            <label>Email</label>
                            <p>{{ $dosen->email ?? '-' }}</p>
                        </div>

                        <div class="profile-field">

                            <label>No. Telepon</label>

                            <form
                                action="{{ route('dosen.profil.update') }}"
                                method="POST"
                            >

                                @csrf
                                @method('PUT')

                                <div style="display:flex;gap:10px;align-items:center;">

                                    <input
                                        type="text"
                                        name="telepon"
                                        class="form-control"
                                        value="{{ old('telepon', $dosen->telepon) }}"
                                        placeholder="Masukkan nomor telepon"
                                        style="max-width:280px;"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-primary"
                                    >
                                        <x-layout-icon name="save" />
                                        <span>Simpan</span>
                                    </button>

                                </div>

                            </form>

                        </div>

                        <div class="profile-field">
                            <label>Status</label>
                            <p style="color:var(--blue);font-weight:600;">
                                Aktif
                            </p>
                        </div>

                    </div>

                </div>

            </div>

            <hr style="margin:30px 0;border:0;border-top:1px solid #e5e7eb;">


            {{-- KEAMANAN AKUN --}}
            <div class="page-section">

                <div class="section-title">
                    <h3 style="display:flex;align-items:center;gap:8px;">
                        <x-layout-icon name="lock" />
                        <span>Keamanan Akun</span>
                    </h3>
                </div>

                <form
                    action="{{ route('dosen.profil.password') }}"
                    method="POST"
                >

                    @csrf
                    @method('PUT')

                    <div class="profile-detail-grid">

                        <div>

                            <div class="profile-field">

                                <label>Password Lama</label>

                                <input
                                    type="password"
                                    name="password_lama"
                                    class="form-control"
                                    placeholder="Masukkan password lama"
                                    required
                                >

                            </div>

                        </div>

                        <div>

                            <div class="profile-field">

                                <label>Password Baru</label>

                                <input
                                    type="password"
                                    name="password_baru"
                                    class="form-control"
                                    placeholder="Minimal 8 karakter"
                                    minlength="8"
                                    required
                                >

                            </div>

                            <div class="profile-field">

                                <label>Konfirmasi Password Baru</label>

                                <input
                                    type="password"
                                    name="password_baru_confirmation"
                                    class="form-control"
                                    placeholder="Ulangi password baru"
                                    minlength="8"
                                    required
                                >

                            </div>

                        </div>

                    </div>

                    <button
                        type="submit"
                        class="btn-primary"
                        style="margin-top:15px;"
                    >
                        <x-layout-icon name="lock" />
                        <span>Ubah Password</span>
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fotoInput = document.getElementById('fotoInput');
    const avatarPreviewImg = document.getElementById('avatarPreviewImg');
    const avatarPreviewInitials = document.getElementById('avatarPreviewInitials');
    const previewMetaBox = document.getElementById('previewMetaBox');
    const previewFileName = document.getElementById('previewFileName');
    const previewFileSize = document.getElementById('previewFileSize');
    const btnSimpanFoto = document.getElementById('btnSimpanFoto');
    const btnBatalFoto = document.getElementById('btnBatalFoto');

    if (fotoInput) {
        fotoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) {
                resetPreview();
                return;
            }

            // Check size (10 MB = 10 * 1024 * 1024 bytes)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Ukuran file melebihi batas maksimal 10 MB. Silakan pilih foto dengan ukuran lebih kecil.');
                fotoInput.value = '';
                resetPreview();
                return;
            }

            // Format size for display
            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            previewFileName.textContent = file.name;
            previewFileSize.textContent = sizeInMB + ' MB';
            previewMetaBox.style.display = 'block';

            // FileReader preview
            const reader = new FileReader();
            reader.onload = function (event) {
                avatarPreviewImg.src = event.target.result;
                avatarPreviewImg.style.display = 'block';
                if (avatarPreviewInitials) {
                    avatarPreviewInitials.style.display = 'none';
                }
                btnSimpanFoto.style.display = 'inline-flex';
                btnBatalFoto.style.display = 'inline-flex';
            };
            reader.readAsDataURL(file);
        });

        if (btnBatalFoto) {
            btnBatalFoto.addEventListener('click', function () {
                fotoInput.value = '';
                resetPreview();
            });
        }
    }

    function resetPreview() {
        if (avatarPreviewImg) {
            avatarPreviewImg.src = '';
            avatarPreviewImg.style.display = 'none';
        }
        if (avatarPreviewInitials) {
            avatarPreviewInitials.style.display = 'block';
        }
        if (previewMetaBox) {
            previewMetaBox.style.display = 'none';
        }
        if (btnSimpanFoto) {
            btnSimpanFoto.style.display = 'none';
        }
        if (btnBatalFoto) {
            btnBatalFoto.style.display = 'none';
        }
    }
});
</script>
@endpush
@endsection