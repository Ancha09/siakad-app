@extends('layouts.admin')
@section('title', 'Buat Tagihan Mahasiswa')
@section('content')
<div class="payment-page">
    @include('payments.feedback')
    <div class="payment-actions"><a href="{{ route('admin.pembayaran.index') }}" class="btn-outline">Kembali</a></div>
    <div class="page-card"><div class="page-card-body"><form method="GET" class="payment-grid">@include('admin.pembayaran.student-filters')<div style="align-self:end"><button class="btn-primary">Cari Mahasiswa</button></div></form></div></div>
    <form method="POST" action="{{ route('admin.pembayaran.store') }}">@csrf
        <input type="hidden" name="request_key" value="{{ old('request_key',$requestKey) }}">
        <div class="page-card"><div class="page-card-head"><h2>Data Tagihan</h2></div><div class="page-card-body">@include('admin.pembayaran.fields')</div></div>
        <div class="page-card"><div class="page-card-head"><h2>Pilih Penerima Tagihan</h2></div><div class="page-card-body">
            <p>Pilih satu atau beberapa mahasiswa pada halaman ini. Untuk daftar besar, gunakan import Excel.</p>
            <div class="table-wrap"><table><thead><tr><th>Pilih</th><th>NIM</th><th>Nama</th><th>Prodi</th><th>Angkatan</th></tr></thead><tbody>
            @forelse($students as $student)<tr><td><input type="checkbox" name="mahasiswa_ids[]" value="{{ $student->id }}" aria-label="Pilih {{ $student->nama }}" @checked(in_array($student->id,old('mahasiswa_ids',[])))></td><td>{{ $student->nim }}</td><td>{{ $student->nama }}</td><td>{{ $student->prodi?->nama_prodi ?? '-' }}</td><td>{{ $student->angkatan ?? '-' }}</td></tr>@empty<tr><td colspan="5">Mahasiswa tidak ditemukan.</td></tr>@endforelse
            </tbody></table></div>
            <label class="payment-inline" style="margin-top:18px"><input type="checkbox" name="confirm_duplicates" value="1"> Saya sudah memeriksa dan mengizinkan tagihan identik jika ditemukan duplikat.</label>
            <button class="btn-primary" style="margin-top:12px">Simpan Tagihan Sandbox</button>
        </div></div>
    </form>
    {{ $students->links() }}
</div>
@endsection
