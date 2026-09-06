@push('styles')
<style>
    .curriculum-page .curriculum-summary { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
    .curriculum-page .summary-chip { padding:9px 13px; border:1px solid #dbe3f0; border-radius:10px; background:#fff; color:#475569; font-size:12px; }
    .curriculum-page .curriculum-section { margin-bottom:22px; }
    .curriculum-page .curriculum-meta { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .curriculum-page .semester-title { margin:20px 0 8px; color:#0a1f5c; font-size:14px; }
    .curriculum-page .empty-state { padding:44px 20px; text-align:center; color:#64748b; }
    .curriculum-page .silabus-missing { color:#94a3b8; font-size:11px; }
    @media (max-width:600px) { .curriculum-page .page-card-head { align-items:flex-start; flex-direction:column; gap:8px; } }
</style>
@endpush

<div class="inner-page curriculum-page">
    @forelse($kurikulums as $kurikulum)
        <section class="page-card curriculum-section">
            <div class="page-card-head">
                <div>
                    <h2>{{ $kurikulum->nama_kurikulum }}</h2>
                    <p style="margin-top:5px;color:#64748b;font-size:12px;">
                        {{ $kurikulum->prodi->jenjang }} {{ $kurikulum->prodi->nama_prodi }} · Berlaku {{ $kurikulum->tahun_mulai }}{{ $kurikulum->tahun_selesai ? '–'.$kurikulum->tahun_selesai : '' }}
                    </p>
                </div>
                <span class="badge {{ $kurikulum->status === 'Aktif' ? 'badge-green' : 'badge-gray' }}">{{ $kurikulum->status }}</span>
            </div>
            <div class="page-card-body">
                @php
                    $totalSks = $kurikulum->mataKuliahKurikulum->sum(fn ($item) => $item->mataKuliah->sks ?? 0);
                    $perSemester = $kurikulum->mataKuliahKurikulum->groupBy('semester');
                @endphp
                <div class="curriculum-summary">
                    <span class="summary-chip"><strong>{{ $kurikulum->mataKuliahKurikulum->count() }}</strong> mata kuliah</span>
                    <span class="summary-chip"><strong>{{ $totalSks }}</strong> total SKS</span>
                    <span class="summary-chip"><strong>{{ $kurikulum->mataKuliahKurikulum->whereNotNull('silabus_path')->count() }}</strong> silabus tersedia</span>
                </div>

                @forelse($perSemester as $semester => $items)
                    <h3 class="semester-title">Semester {{ $semester }}</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Jenis</th>
                                    <th>Silabus / RPS</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><span class="badge badge-blue">{{ $item->mataKuliah->kode_mk }}</span></td>
                                        <td><strong>{{ $item->mataKuliah->nama_mk }}</strong></td>
                                        <td>{{ $item->mataKuliah->sks }}</td>
                                        <td>{{ $item->jenis }}</td>
                                        <td>
                                            @if($item->silabus_path)
                                                <a class="btn-outline" style="padding:5px 10px;font-size:11px;" href="{{ route($silabusRoute, $item) }}">Unduh PDF</a>
                                            @else
                                                <span class="silabus-missing">Belum tersedia</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <div class="empty-state">Susunan mata kuliah pada kurikulum ini belum diisi oleh admin.</div>
                @endforelse
            </div>
        </section>
    @empty
        <div class="page-card">
            <div class="empty-state">
                <strong>Kurikulum belum tersedia</strong>
                <p style="margin-top:6px;">Admin belum menerbitkan kurikulum untuk program studi Anda.</p>
            </div>
        </div>
    @endforelse
</div>
