<div class="page-card"><div class="page-card-head"><h2>Riwayat Pembayaran</h2></div><div class="page-card-body"><div class="table-wrap"><table>
    <thead><tr><th>Tanggal</th><th>Referensi</th><th>Nominal pokok</th><th>Biaya layanan</th><th>Total bayar</th><th>Metode</th><th>Status</th><th>Bukti</th></tr></thead><tbody>
    @forelse($payments as $payment)<tr>
        <td>{{ ($payment->paid_at ?? $payment->created_at)->format('d/m/Y H:i') }}</td>
        <td style="overflow-wrap:anywhere;max-width:230px">{{ $payment->nomor_referensi ?: $payment->external_id }}@if($payment->provider_payment_id)<br><small>{{ $payment->provider_payment_id }}</small>@endif</td>
        @foreach(['nominal_pokok','biaya_layanan','amount'] as $field)<td class="payment-money">Rp {{ number_format($payment->$field,0,',','.') }}</td>@endforeach
        <td>{{ $payment->metode_label }}</td><td>{{ ['creating'=>'Diproses','pending'=>'Menunggu pembayaran','unknown'=>'Perlu pemeriksaan transaksi','paid'=>'Berhasil','expired'=>'Transaksi kedaluwarsa','void'=>'Dikoreksi','failed'=>'Gagal / dibatalkan'][$payment->status] ?? $payment->status }}</td>
        <td>@if($payment->status === 'paid')<a class="btn-outline" href="{{ route(auth()->user()->role.'.pembayaran.receipt',$payment) }}">PDF</a>@if($payment->bukti_path) <a class="btn-outline" href="{{ route(auth()->user()->role.'.pembayaran.attachment',$payment) }}">Lampiran</a>@endif @else - @endif</td>
    </tr>@empty<tr><td colspan="8">Belum ada transaksi.</td></tr>@endforelse
    </tbody></table></div></div></div>
