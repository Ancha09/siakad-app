@extends('layouts.admin')
@section('title', 'Early Access Pembayaran')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    <div class="payment-actions"><a class="btn-outline" href="{{ route('admin.pembayaran.index') }}">Kembali</a></div>
    <div class="page-card"><div class="page-card-body"><form method="GET" class="payment-grid">@include('admin.pembayaran.student-filters')<div style="align-self:end"><button class="btn-primary">Cari</button></div></form>
    <p>Hanya mahasiswa yang diberi akses di sini dapat membuat transaksi Midtrans sandbox setelah konfigurasi gateway diaktifkan.</p>
    <div class="table-wrap"><table><thead><tr><th>NIM</th><th>Nama</th><th>Akses Sandbox</th><th>Tindakan</th></tr></thead><tbody>
    @forelse($students as $student)
        @php($enabled = $accesses->get($student->id)?->enabled ?? false)
        <tr><td>{{ $student->nim }}</td><td>{{ $student->nama }}</td><td>{{ $enabled ? 'Dibuka' : 'Ditutup' }}</td><td><form method="POST" action="{{ route('admin.pembayaran.access.update',$student) }}">@csrf @method('PATCH')<input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}"><button class="btn-outline">{{ $enabled ? 'Tutup Akses' : 'Buka Akses Uji Coba' }}</button></form></td></tr>
    @empty<tr><td colspan="4">Mahasiswa tidak ditemukan.</td></tr>@endforelse
    </tbody></table></div>{{ $students->links() }}</div></div>
</div>
@endsection
