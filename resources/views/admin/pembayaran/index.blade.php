@extends('layouts.admin')
@section('title', 'Tagihan & Pembayaran')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    @unless($gatewayReady)<div class="payment-notice">Gateway sandbox belum diaktifkan atau konfigurasinya belum lengkap. Pengelolaan tagihan dan import tetap tersedia.</div>@endunless
    <div class="payment-actions">
        <a class="btn-primary" href="{{ route('admin.pembayaran.create') }}">Buat Tagihan</a>
        <a class="btn-outline" href="{{ route('admin.pembayaran.import') }}">Import Excel</a>
        <a class="btn-outline" href="{{ route('admin.pembayaran.access') }}">Mahasiswa Early Access</a>
        <a class="btn-outline" href="{{ route('admin.pembayaran.export', ['format'=>'pdf'] + request()->only(['search','prodi_id','angkatan','status'])) }}">Laporan PDF</a>
        <a class="btn-outline" href="{{ route('admin.pembayaran.export', ['format'=>'excel'] + request()->only(['search','prodi_id','angkatan','status'])) }}">Laporan Excel</a>
    </div>
    <div class="page-card"><div class="page-card-body"><form method="GET" class="payment-grid">
        @include('admin.pembayaran.student-filters')
        <div><label>Status</label><select name="status"><option value="">Semua status</option>@foreach(['belum_dibayar','sebagian_dibayar','lunas','expired','dibatalkan'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucwords(str_replace('_',' ',$status)) }}</option>@endforeach</select></div>
        <div class="payment-actions" style="align-self:end;margin:0"><button class="btn-primary">Filter</button><a class="btn-outline" href="{{ route('admin.pembayaran.index') }}">Reset</a></div>
    </form></div></div>
    @include('payments.totals')
    <div class="page-card"><div class="page-card-head"><h2>Daftar Tagihan</h2></div><div class="page-card-body"><div class="table-wrap"><table>
        <thead><tr><th>No</th><th>Mahasiswa</th><th>Tagihan</th><th>Total</th><th>Dibayar</th><th>Sisa</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        @forelse($bills as $bill)<tr>
            <td>{{ $bills->firstItem()+$loop->index }}</td><td>{{ $bill->mahasiswa?->nama }}<br><small>{{ $bill->mahasiswa?->nim }}</small></td>
            <td>{{ $bill->jenis_tagihan }}<br><small>{{ $bill->kode_tagihan }}</small></td>
            @foreach(['total_tagihan','total_dibayar','sisa_tagihan'] as $field)<td class="payment-money">Rp {{ number_format($bill->$field,0,',','.') }}</td>@endforeach
            <td>{{ ucwords(str_replace('_',' ',$bill->status_tampil)) }}</td><td><a class="btn-primary" href="{{ route('admin.pembayaran.show',$bill) }}">Detail</a></td>
        </tr>@empty<tr><td colspan="8">Belum ada tagihan untuk filter ini.</td></tr>@endforelse
        </tbody></table></div>{{ $bills->links() }}</div></div>
</div>
@endsection
