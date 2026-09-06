<div class="sk-card">
    <form method="GET" action="{{ route($role.'.skripsi') }}" class="sk-grid">
        <label>Periode pengajuan
            <select name="periode_id" onchange="this.form.submit()">
                @forelse($periods as $item)<option value="{{ $item->id }}" @selected($period?->id === $item->id)>{{ $item->nama }}</option>
                @empty<option value="">Belum ada periode</option>@endforelse
            </select>
        </label>
        @if($role === 'admin')
            <label>Cari nama, NIM, judul<input name="q" value="{{ request('q') }}" maxlength="200"></label>
            <label>Prodi<select name="prodi_id"><option value="">Semua prodi</option>@foreach($prodis as $prodi)<option value="{{ $prodi->id }}" @selected(request('prodi_id') == $prodi->id)>{{ $prodi->nama_prodi }}</option>@endforeach</select></label>
            <label>Dosen<select name="dosen_id"><option value="">Semua dosen</option>@foreach($allDosens as $dosen)<option value="{{ $dosen->id }}" @selected(request('dosen_id') == $dosen->id)>{{ $dosen->nama }}</option>@endforeach</select></label>
        @endif
        @if($role !== 'mahasiswa')
            <label>Status<select name="status"><option value="">Semua status</option>
                @foreach($role === 'admin' ? ['Belum mengajukan', 'Belum mendapat pembimbing', 'Menunggu', 'Diterima', 'Ditolak', 'Dialihkan'] : \App\Models\PengajuanSkripsi::STATUS as $status)
                    <option @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select></label>
        @endif
        <button type="submit">Tampilkan</button>
    </form>
    @if($period)
        <p><strong>{{ $period->nama }}</strong> &middot; {{ $period->mulai->format('d/m/Y H:i') }} – {{ $period->berakhir->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>
        <div class="sk-alert">
            <strong>{{ $period->terbuka() ? 'Periode dibuka.' : (now()->lt($period->mulai) ? 'Periode belum dimulai.' : 'Periode sudah ditutup.') }}</strong>
            Rentang waktu yang sama berlaku untuk pengajuan mahasiswa, keputusan dosen, dan pengalihan admin.
            @if(! $period->terbuka()) Pengajuan menunggu tetap belum diputuskan. Riwayat tetap dapat dibaca. @endif
        </div>
    @else
        <p class="sk-muted">Admin belum mengatur periode pengajuan skripsi.</p>
    @endif
</div>
