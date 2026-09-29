@extends('layouts.mahasiswa')
@section('title', 'Detail Pembayaran')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    <div class="payment-actions"><a class="btn-outline" href="{{ route('mahasiswa.pembayaran.index') }}">Kembali ke Tagihan</a></div>
    @include('payments.bill-details')
    @if($bill->sisa_tagihan > 0 && $bill->status !== 'dibatalkan')
        @if($canPay && $remainingPrincipal >= $minimumPayment)
            @php($pending = $payments->first(fn ($item) => in_array($item->status, \App\Models\PembayaranMahasiswa::OPEN_STATUSES, true)))
            <div class="page-card"><div class="page-card-head"><h2>Pembayaran Virtual Account</h2></div><div class="page-card-body">
                <p>Setelah simulasi selesai, status hanya diperbarui dari notifikasi Midtrans yang valid. Muat ulang halaman ini untuk melihat hasilnya.</p>
                <form method="POST" action="{{ route('mahasiswa.pembayaran.pay',$bill) }}">@csrf
                    <label>Nominal pokok yang dibayar (rupiah)</label>
                    <input id="vaPrincipal" type="number" name="amount" value="{{ old('amount',$pending?->nominal_pokok ?? $remainingPrincipal) }}" required min="{{ $minimumPayment }}" max="{{ $remainingPrincipal }}" step="1" data-service-fee="{{ $bill->biaya_layanan }}" @readonly(!$bill->boleh_cicil || $pending)>
                    <label style="margin-top:12px">Bank Virtual Account</label>
                    @if($pending)<input type="hidden" name="bank" value="{{ $pending->payment_method ?: $defaultVaMethod }}">@endif
                    <select name="bank" required @disabled($pending)>
                        @foreach($vaMethods as $code => $label)<option value="{{ $code }}" @selected(old('bank',$pending?->payment_method ?? $defaultVaMethod) === $code)>{{ $label }}</option>@endforeach
                    </select>
                    <p class="payment-small">Metode yang tersedia hanya Virtual Account. {{ $bill->boleh_cicil ? 'Cicilan dan sisa pokok masing-masing minimal Rp '.number_format($minimumPayment,0,',','.').'.' : 'Tagihan ini harus dibayar penuh.' }}<br>Pokok: <span id="vaPrincipalText">Rp {{ number_format($pending?->nominal_pokok ?? $remainingPrincipal,0,',','.') }}</span> + biaya layanan VA: Rp {{ number_format($bill->biaya_layanan,0,',','.') }} = <strong>total bayar <span id="vaTotalText">Rp {{ number_format(($pending?->nominal_pokok ?? $remainingPrincipal) + $bill->biaya_layanan,0,',','.') }}</span></strong>.</p>
                    <button class="btn-primary">{{ $pending ? 'Lanjutkan Pembayaran VA' : 'Buat Virtual Account' }}</button>
                </form>
            </div></div>
        @elseif($canPay && $remainingPrincipal > 0)
            <div class="payment-notice">Nominal pembayaran minimal Rp600.000. Hubungi admin untuk penyelesaian sisa tagihan melalui pencatatan cash/manual.</div>
        @else<div class="payment-notice">Pembayaran gateway sedang dalam uji coba terbatas dan belum tersedia untuk akun ini.</div>@endif
    @endif
    @include('payments.history')
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('vaPrincipal');
    const principalText = document.getElementById('vaPrincipalText');
    const totalText = document.getElementById('vaTotalText');
    if (!input || !principalText || !totalText) return;
    const format = value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    const render = () => {
        const principal = Number.parseInt(input.value, 10) || 0;
        const fee = Number.parseInt(input.dataset.serviceFee, 10) || 0;
        principalText.textContent = format(principal);
        totalText.textContent = format(principal + fee);
    };
    input.addEventListener('input', render);
    render();
});
</script>
@endsection
