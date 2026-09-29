@extends('layouts.admin')
@section('title', 'Detail Tagihan')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    <div class="payment-actions"><a class="btn-outline" href="{{ route('admin.pembayaran.index') }}">Kembali ke Tagihan</a></div>
    <p><strong>{{ $bill->mahasiswa?->nama }}</strong> &middot; {{ $bill->mahasiswa?->nim }} &middot; {{ $bill->mahasiswa?->prodi?->nama_prodi }}</p>
    @include('payments.bill-details')
    @include('payments.history')
    @php($hasOpenPayment = $payments->contains(fn ($item) => in_array($item->status, \App\Models\PembayaranMahasiswa::OPEN_STATUSES, true)))
    @if($hasOpenPayment)<div class="payment-notice">Ada transaksi gateway yang belum selesai. Koreksi manual dan pembatalan tagihan dikunci sampai status order dipastikan oleh callback.</div>@endif
    <div class="page-card"><div class="page-card-head"><h2>Tindakan Admin</h2></div><div class="page-card-body">
        @if($payments->isEmpty() && $bill->status !== 'dibatalkan')
            <details><summary>Edit tagihan</summary><form method="POST" action="{{ route('admin.pembayaran.update',$bill) }}">@csrf @method('PATCH')
                @include('admin.pembayaran.fields')
                <label>Alasan perubahan *</label><textarea name="alasan" required minlength="5" maxlength="1000" rows="2"></textarea>
                <button class="btn-primary" style="margin-top:12px">Simpan Perubahan</button>
            </form></details>
        @endif
        @if(!$hasOpenPayment && $bill->sisa_tagihan > 0 && $bill->status !== 'dibatalkan')
            <details><summary>Catat pembayaran cash/manual</summary>
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.pembayaran.manual',$bill) }}" onsubmit="return confirm('Catat pembayaran cash ini? Identitas admin dan transaksi akan masuk audit.')">@csrf
                    <input type="hidden" name="request_key" value="{{ old('request_key',$requestKey) }}">
                    <div class="payment-grid">
                        <div><label>Nominal pembayaran cash *</label><input type="number" name="amount" min="1" step="1" value="{{ old('amount',$remainingPrincipal) }}" required><small class="payment-small">Sisa pokok saat ini Rp {{ number_format($remainingPrincipal,0,',','.') }}.</small></div>
                        <div><label>Tanggal pembayaran *</label><input type="date" name="paid_at" max="{{ today()->format('Y-m-d') }}" value="{{ old('paid_at',today()->format('Y-m-d')) }}" required></div>
                        <div><label>Nomor kwitansi / bukti *</label><input name="nomor_referensi" required maxlength="100" value="{{ old('nomor_referensi') }}"></div>
                        <div><label>Upload bukti (opsional, maks. 2 MB)</label><input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"><small class="payment-small">JPG, JPEG, PNG, atau PDF. File disimpan privat.</small></div>
                    </div>
                    <label>Catatan pembayaran *</label><textarea name="catatan" required minlength="5" maxlength="2000" rows="2">{{ old('catatan') }}</textarea>
                    <label style="display:flex;gap:8px;align-items:flex-start;margin-top:10px"><input type="checkbox" name="confirm_overpayment" value="1" @checked(old('confirm_overpayment')) style="width:auto;margin-top:3px"> Saya mengonfirmasi nominal ini boleh melebihi sisa pokok tagihan.</label>
                    <button class="btn-primary" style="margin-top:12px">Catat Pembayaran Cash</button>
                </form>
            </details>
        @endif
        @unless($hasOpenPayment)
            @foreach($payments->whereIn('source',['cash','manual'])->where('status','paid') as $payment)
                <details><summary>Koreksi pembayaran manual #{{ $payment->id }} — Rp {{ number_format($payment->amount,0,',','.') }}</summary>
                    <form method="POST" action="{{ route('admin.pembayaran.void',$payment) }}" onsubmit="return confirm('Batalkan pencatatan manual ini? Riwayat transaksi dan alasan tetap tersimpan.')">@csrf @method('PATCH')
                        <label>Alasan koreksi *</label><textarea name="alasan" required minlength="5" maxlength="1000" rows="2"></textarea>
                        <button class="btn-outline" style="margin-top:12px">Batalkan Pencatatan Manual</button>
                    </form>
                </details>
            @endforeach
            @if($bill->total_dibayar === 0 && $bill->status !== 'dibatalkan')
                <details><summary>Batalkan tagihan</summary><form method="POST" action="{{ route('admin.pembayaran.cancel',$bill) }}" onsubmit="return confirm('Batalkan tagihan ini? Riwayat tetap disimpan.')">@csrf @method('PATCH')
                    <label>Alasan pembatalan *</label><textarea name="alasan" required minlength="5" maxlength="1000" rows="2"></textarea>
                    <button class="btn-outline" style="margin-top:12px">Batalkan Tagihan</button>
                </form></details>
            @endif
        @endunless
    </div></div>
    <div class="page-card"><div class="page-card-head"><h2>Audit Pembayaran</h2></div><div class="page-card-body"><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Alasan</th></tr></thead><tbody>
        @forelse($audits as $audit)<tr><td>{{ $audit->created_at?->format('d/m/Y H:i') }}</td><td>{{ $audit->actor?->name ?? 'Webhook Midtrans' }}</td><td>{{ str_replace('_',' ',$audit->action) }}</td><td>{{ $audit->reason ?? '-' }}</td></tr>@empty<tr><td colspan="4">Belum ada audit.</td></tr>@endforelse
    </tbody></table></div>
    @if($callbacks->isNotEmpty())<details><summary>Log callback terakhir</summary><div class="table-wrap"><table><thead><tr><th>Waktu</th><th>Status Midtrans</th><th>Hasil pemeriksaan</th></tr></thead><tbody>@foreach($callbacks as $callback)<tr><td>{{ $callback->updated_at?->format('d/m/Y H:i') }}</td><td>{{ $callback->status }}</td><td>{{ $callback->outcome }}</td></tr>@endforeach</tbody></table></div></details>@endif
    </div></div>
</div>
@endsection
