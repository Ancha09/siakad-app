<div class="page-card"><div class="page-card-head"><h2>{{ $bill->jenis_tagihan }}</h2></div><div class="page-card-body">
    <p><strong>{{ $bill->kode_tagihan }}</strong></p><p>{{ $bill->deskripsi }}</p>
    <div class="payment-grid">
        <div>Pokok<strong style="display:block">Rp {{ number_format($bill->nominal_pokok,0,',','.') }}</strong></div>
        <div>Biaya layanan VA / transaksi<strong style="display:block">Rp {{ number_format($bill->biaya_layanan,0,',','.') }}</strong></div>
        <div>Total<strong style="display:block">Rp {{ number_format($bill->total_tagihan,0,',','.') }}</strong></div>
        <div>Dibayar<strong style="display:block">Rp {{ number_format($bill->total_dibayar,0,',','.') }}</strong></div>
        <div>Sisa<strong style="display:block">Rp {{ number_format($bill->sisa_tagihan,0,',','.') }}</strong></div>
    </div>
    <p>Status: <strong>{{ ucwords(str_replace('_',' ',$bill->status_tampil)) }}</strong> &middot; Jatuh tempo: {{ $bill->jatuh_tempo?->format('d/m/Y') ?? '-' }} &middot; {{ $bill->boleh_cicil ? 'Boleh dicicil' : 'Pembayaran penuh' }}</p>
    @if($bill->kelebihan_pembayaran > 0)<div class="payment-notice">Tercatat kelebihan pembayaran sandbox Rp {{ number_format($bill->kelebihan_pembayaran,0,',','.') }}. Hubungi admin untuk pemeriksaan riwayat.</div>@endif
</div></div>
