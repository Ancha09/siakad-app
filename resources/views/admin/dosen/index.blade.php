@extends('layouts.admin')

@section('title', 'Data Dosen')

@section('content')

@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

<div class="toolbar">
    <a href="{{ route('admin.dosen.create') }}" class="btn-primary">➕ Tambah Dosen</a>
</div>

<div class="page-card">
    <div class="page-card-head">
        <h2>👨‍🏫 Data Dosen</h2>
    </div>

    <div class="page-card-body">
        <form method="GET" action="{{ route('admin.dosen') }}" role="search"
              style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px;margin-bottom:20px;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;align-items:end;">
                <div class="form-group">
                    <label for="dosen-q">Cari Dosen</label>
                    <input id="dosen-q" name="q" type="search" class="form-control"
                           value="{{ request('q') }}"
                           placeholder="NIDN, nama, prodi, email, atau jabatan...">
                </div>

                <div class="form-group">
                    <label for="fakultas-filter">Fakultas</label>
                    <select id="fakultas-filter" name="fakultas_id" class="form-control">
                        <option value="">Semua Fakultas</option>
                        @foreach($fakultas as $item)
                            <option value="{{ $item->id }}" @selected((string) request('fakultas_id') === (string) $item->id)>
                                {{ $item->nama_fakultas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="prodi-filter">Program Studi</label>
                    <select id="prodi-filter" name="prodi_id" class="form-control">
                        <option value="">Semua Program Studi</option>
                        @foreach($prodis as $prodi)
                            <option value="{{ $prodi->id }}"
                                    data-fakultas-id="{{ $prodi->fakultas_id }}"
                                    @selected((string) request('prodi_id') === (string) $prodi->id)>
                                {{ $prodi->nama_prodi }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px;">
                <button type="submit" class="btn-primary">Terapkan Filter</button>
                @if(request()->filled('q') || request()->filled('fakultas_id') || request()->filled('prodi_id'))
                    <a href="{{ route('admin.dosen') }}" class="btn-outline">Reset</a>
                @endif
            </div>
        </form>

        <div style="margin-bottom:15px;color:#64748b;font-size:13px;">
            Menampilkan <strong style="color:#1e293b;">{{ $dosens->total() }}</strong> dosen
            @if(request()->filled('q') || request()->filled('fakultas_id') || request()->filled('prodi_id'))
                berdasarkan filter yang dipilih.
            @endif
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIDN</th>
                        <th>Nama</th>
                        <th>Fakultas</th>
                        <th>Program Studi</th>
                        <th>Jabatan</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($dosens as $dosen)
                        <tr>
                            <td>{{ $dosens->firstItem() + $loop->index }}</td>
                            <td>{{ $dosen->nidn }}</td>
                            <td>{{ $dosen->nama }}</td>
                            <td>{{ $dosen->prodi?->fakultas?->nama_fakultas ?? '-' }}</td>
                            <td>{{ $dosen->prodi?->nama_prodi ?? '-' }}</td>
                            <td>{{ $dosen->jabatan ?? '-' }}</td>
                            <td>
                                @if($dosen->is_active)
                                    <span class="badge badge-green">Aktif</span>
                                @else
                                    <span class="badge badge-gold">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="{{ route('admin.dosen.edit', $dosen->id) }}" class="btn-edit">✏ Edit</a>

                                    <form action="{{ route('admin.dosen.destroy', $dosen->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-delete"
                                                @disabled(! $dosen->is_active)
                                                onclick="return confirm('Nonaktifkan dosen ini? Akun tidak dapat login, tetapi riwayat akademik tetap tersimpan.')">
                                            Nonaktifkan
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;padding:35px">
                                {{ request()->filled('q') || request()->filled('fakultas_id') || request()->filled('prodi_id') ? 'Tidak ada dosen yang sesuai dengan filter.' : 'Belum ada data dosen.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($dosens->hasPages())
            <div style="margin-top:20px">{{ $dosens->links() }}</div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fakultas = document.getElementById('fakultas-filter');
        const prodi = document.getElementById('prodi-filter');

        if (!fakultas || !prodi) return;

        function sesuaikanPilihanProdi(resetPilihan) {
            const fakultasId = fakultas.value;

            Array.from(prodi.options).forEach(function (option) {
                if (!option.value) return;

                const sesuai = !fakultasId || option.dataset.fakultasId === fakultasId;
                option.hidden = !sesuai;
                option.disabled = !sesuai;
            });

            if (resetPilihan && prodi.selectedOptions[0]?.disabled) {
                prodi.value = '';
            }
        }

        fakultas.addEventListener('change', function () {
            sesuaikanPilihanProdi(true);
        });

        sesuaikanPilihanProdi(false);
    });
</script>
@endpush
