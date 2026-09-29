@extends('layouts.mahasiswa')
@section('title', 'Pembayaran Mahasiswa')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    @include('payments.totals')
    <div class="page-card"><div class="page-card-head"><h2>Tagihan Saya</h2></div><div class="page-card-body"><div class="table-wrap"><table>
        <thead><tr><th>No</th><th>Tagihan</th><th>Total</th><th>Sudah Dibayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($bills as $bill)<tr><td>{{ $bills->firstItem()+$loop->index }}</td><td>{{ $bill->jenis_tagihan }}<br><small>{{ $bill->deskripsi }}</small></td>
            @foreach(['total_tagihan','total_dibayar','sisa_tagihan'] as $field)<td class="payment-money">Rp {{ number_format($bill->$field,0,',','.') }}</td>@endforeach
            <td>{{ ucwords(str_replace('_',' ',$bill->status_tampil)) }}</td><td><a class="btn-primary" href="{{ route('mahasiswa.pembayaran.show',$bill) }}">Detail / Riwayat</a></td>
        </tr>@empty<tr><td colspan="7">Belum ada tagihan untuk Anda.</td></tr>@endforelse
        </tbody></table></div>{{ $bills->links() }}</div></div>
</div>
@endsection
