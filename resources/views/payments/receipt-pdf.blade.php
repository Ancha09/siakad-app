<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Bukti Pembayaran Sandbox</title><style>
    @page{margin:40px}body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#172b4d}h1{font-size:20px;border-bottom:3px solid #123d72;padding-bottom:12px}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #dde4ed}td:first-child{width:37%}.notice{padding:15px;background:#fff2d5;margin:18px 0}.total{font-weight:bold;font-size:14px}
</style></head><body>
<h1>STTMI — Bukti Pembayaran</h1>
<div class="notice"><strong>SANDBOX / SIMULASI</strong><br>Dokumen ini merupakan bukti uji coba, tidak berlaku sebagai bukti penerimaan uang nyata.</div>
<table>
    <tr><td>Nama mahasiswa</td><td>{{ $bill->mahasiswa?->nama }}</td></tr>
    <tr><td>NIM</td><td>{{ $bill->mahasiswa?->nim }}</td></tr>
    <tr><td>Program studi</td><td>{{ $bill->mahasiswa?->prodi?->nama_prodi ?? '-' }}</td></tr>
    <tr><td>Jenis tagihan</td><td>{{ $bill->jenis_tagihan }}</td></tr>
    <tr><td>Kode tagihan</td><td>{{ $bill->kode_tagihan }}</td></tr>
    <tr><td>Nominal pembayaran pokok</td><td>Rp {{ number_format($payment->nominal_pokok,0,',','.') }}</td></tr>
    <tr><td>Biaya layanan</td><td>Rp {{ number_format($payment->biaya_layanan,0,',','.') }}</td></tr>
    <tr class="total"><td>Total bayar</td><td>Rp {{ number_format($payment->amount,0,',','.') }}</td></tr>
    <tr><td>Tanggal bayar</td><td>{{ $payment->paid_at?->format('d/m/Y H:i') }}</td></tr>
    <tr><td>Metode</td><td>{{ $payment->metode_label }}</td></tr>
    <tr><td>Nomor referensi / kwitansi</td><td style="overflow-wrap:break-word">{{ $payment->nomor_referensi ?: $payment->external_id }}</td></tr>
    <tr><td>Transaction ID</td><td style="overflow-wrap:break-word">{{ $payment->provider_payment_id ?? '-' }}</td></tr>
    @if($payment->catatan)<tr><td>Catatan</td><td>{{ $payment->catatan }}</td></tr>@endif
    <tr><td>Status transaksi</td><td>Berhasil ({{ in_array($payment->source,['cash','manual'],true) ? 'pencatatan cash oleh admin' : 'callback Midtrans tervalidasi' }})</td></tr>
    <tr><td>Status tagihan saat dicetak</td><td>{{ ucwords(str_replace('_',' ',$bill->status_tampil)) }}</td></tr>
    <tr><td>Sisa tagihan saat dicetak</td><td>Rp {{ number_format($bill->sisa_tagihan,0,',','.') }}</td></tr>
</table><p>Dicetak {{ now()->format('d/m/Y H:i') }}</p></body></html>
