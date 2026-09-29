<div class="payment-grid">
    <div class="payment-stat">Total tagihan aktif<strong>Rp {{ number_format($totals->total ?? 0, 0, ',', '.') }}</strong></div>
    <div class="payment-stat">Sudah dibayar<strong>Rp {{ number_format($totals->paid ?? 0, 0, ',', '.') }}</strong></div>
    <div class="payment-stat">Sisa tagihan<strong>Rp {{ number_format($totals->remaining ?? 0, 0, ',', '.') }}</strong></div>
    @isset($paymentTotals)
        <div class="payment-stat">Dibayar via VA<strong>Rp {{ number_format($paymentTotals['va'], 0, ',', '.') }}</strong></div>
        <div class="payment-stat">Dibayar cash/manual<strong>Rp {{ number_format($paymentTotals['cash'], 0, ',', '.') }}</strong></div>
        <div class="payment-stat">Total biaya layanan<strong>Rp {{ number_format($paymentTotals['fees'], 0, ',', '.') }}</strong></div>
    @endisset
</div>
