@extends('layouts.mahasiswa')

@section('title', 'Isi Kuesioner')

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <h2>📝 Isi Kuesioner Evaluasi Dosen</h2>
    </div>

    <div class="page-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;margin-bottom:20px;">
            <div style="background:#f8fafc;padding:14px;border-radius:10px;">
                <small style="color:#64748b;">Mata Kuliah</small>
                <div style="font-weight:700;margin-top:4px;">{{ $krs->mata_kuliah_efektif?->nama_mk ?? '-' }}</div>
            </div>
            <div style="background:#f8fafc;padding:14px;border-radius:10px;">
                <small style="color:#64748b;">Dosen</small>
                <div style="font-weight:700;margin-top:4px;">{{ $krs->khs?->dosen_efektif?->nama ?? '-' }}</div>
            </div>
            <div style="background:#f8fafc;padding:14px;border-radius:10px;">
                <small style="color:#64748b;">Periode</small>
                <div style="font-weight:700;margin-top:4px;">{{ $krs->tahun_akademik }} · {{ $krs->semester_akademik }}</div>
            </div>
        </div>

        <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:14px 16px;border-radius:10px;margin-bottom:22px;line-height:1.6;">
            Isi secara jujur menggunakan skala 1 (sangat tidak setuju) sampai 5 (sangat setuju). Setelah dikirim, jawaban tidak dapat diubah dan nilai mata kuliah ini akan terbuka. IPS dan IPK terbuka setelah seluruh kuesioner selesai.
        </div>

        @if($errors->any())
            <div style="background:#fef2f2;color:#b91c1c;padding:13px 16px;border-radius:10px;margin-bottom:18px;">
                <strong>Periksa kembali jawaban Anda.</strong>
                <ul style="margin:8px 0 0 18px;">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('mahasiswa.kuesioner.store', $krs) }}">
            @csrf

            @foreach($pertanyaan as $kolom => $label)
                <div style="padding:18px 0;border-bottom:1px solid #e2e8f0;">
                    <div style="font-weight:700;margin-bottom:12px;">{{ $loop->iteration }}. {{ $label }}</div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        @for($nilai = 1; $nilai <= 5; $nilai++)
                            <label style="display:flex;align-items:center;gap:6px;padding:9px 13px;border:1px solid #cbd5e1;border-radius:9px;cursor:pointer;">
                                <input type="radio" name="{{ $kolom }}" value="{{ $nilai }}"
                                       {{ (string) old($kolom) === (string) $nilai ? 'checked' : '' }} required>
                                <span>{{ $nilai }}</span>
                            </label>
                        @endfor
                    </div>
                    <div style="display:flex;justify-content:space-between;max-width:420px;color:#64748b;font-size:11px;margin-top:7px;">
                        <span>Sangat tidak setuju</span><span>Sangat setuju</span>
                    </div>
                </div>
            @endforeach

            <div class="form-group" style="margin-top:20px;">
                <label for="komentar">Pesan dan saran <span style="color:#dc2626;">*</span></label>
                <textarea id="komentar" name="komentar" class="form-control" rows="5"
                          required minlength="10" maxlength="2000"
                          placeholder="Tuliskan masukan yang membangun untuk dosen (minimal 10 karakter)...">{{ old('komentar') }}</textarea>
                <small style="display:block;margin-top:6px;color:#64748b;">Wajib diisi, minimal 10 karakter.</small>
            </div>

            <div style="display:flex;gap:10px;margin-top:22px;flex-wrap:wrap;">
                <button type="submit" class="btn-primary" onclick="return confirm('Kirim kuesioner? Jawaban tidak dapat diubah setelah dikirim.')">
                    Kirim &amp; Buka Nilai Mata Kuliah
                </button>
                <a href="{{ route('mahasiswa.kuesioner') }}" class="btn-outline">Kembali</a>
            </div>
        </form>
    </div>
</div>
@endsection
