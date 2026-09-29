@extends('layouts.admin')
@section('title', 'Import Tagihan Excel')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    <div class="payment-actions"><a class="btn-outline" href="{{ route('admin.pembayaran.index') }}">Kembali</a><a class="btn-primary" href="{{ route('admin.pembayaran.import.template') }}">Download Template XLSX</a></div>
    <div class="page-card"><div class="page-card-body">
        <p>Gunakan sheet pertama, maksimal 500 baris / 5 MB. NIM harus sesuai master dan disimpan sebagai teks agar angka nol di depan tetap ada. Nama dan jatuh tempo boleh kosong; biaya layanan kosong memakai default Midtrans VA Rp {{ number_format(config('payments.default_service_fee'),0,',','.') }} per transaksi. Isi boleh_cicil dengan true/false.</p>
        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.pembayaran.import.preview') }}">@csrf
            <label>File Excel *</label><input type="file" name="file" accept=".xlsx" required><button class="btn-primary" style="margin-top:14px">Preview Import</button>
        </form>
    </div></div>
    @isset($token)
        <div class="page-card"><div class="page-card-head"><h2>Preview Import</h2></div><div class="page-card-body">
            <p>{{ count($rows) }} baris valid, {{ count($rowErrors) }} baris bermasalah, {{ $duplicates }} potensi duplikat. Preview berlaku 30 menit.</p>
            @if(!empty($rowErrors))<div class="payment-notice payment-errors"><ul>@foreach($rowErrors as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
            <div class="table-wrap" style="max-height:520px;overflow:auto"><table><thead><tr><th>Baris</th><th>NIM</th><th>Nama</th><th>Jenis</th><th>Total</th><th>Cicilan</th><th>Duplikat</th></tr></thead><tbody>
            @foreach($rows as $row)<tr><td>{{ $row['line'] }}</td><td>{{ $row['nim'] }}</td><td>{{ $row['nama'] }}</td><td>{{ $row['jenis_tagihan'] }}</td><td>Rp {{ number_format((int)$row['nominal_pokok']+(int)$row['biaya_layanan'],0,',','.') }}</td><td>{{ $row['boleh_cicil'] ? 'Ya' : 'Tidak' }}</td><td>{{ $row['duplicate'] ? 'Perlu konfirmasi' : '-' }}</td></tr>@endforeach
            </tbody></table></div>
            @if(empty($rowErrors) && count($rows))
                <form method="POST" action="{{ route('admin.pembayaran.import.confirm') }}" style="margin-top:16px" onsubmit="return confirm('Simpan semua tagihan sandbox dari preview ini?')">@csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <label class="payment-inline"><input type="checkbox" name="confirm_duplicates" value="1" @required($duplicates>0)> Saya telah memeriksa dan mengizinkan tagihan identik jika ditemukan duplikat.</label>
                    <button class="btn-primary" style="margin-top:12px">Simpan Import Final</button>
                </form>
            @else<p>Perbaiki semua baris bermasalah lalu upload ulang. Belum ada tagihan disimpan.</p>@endif
        </div></div>
    @endisset
</div>
@endsection
