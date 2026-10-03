@extends(auth()->user()->role === 'dosen' ? 'layouts.dosen' : 'layouts.admin')

@section('title', 'Detail Presensi & BAP Mata Kuliah')

@push('styles')
<style>
    /* Tab Navigasi BAP & Rekap */
    .bap-tab-nav {
        display: flex;
        gap: 8px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 5px;
        margin-bottom: 22px;
        flex-wrap: wrap;
    }
    .bap-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: #64748b;
        font-size: 13.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        outline: none;
    }
    .bap-tab-btn .layout-icon {
        width: 16px;
        height: 16px;
        stroke: currentColor;
    }
    .bap-tab-btn:hover {
        color: #0f172a;
        background: rgba(255, 255, 255, 0.6);
    }
    .bap-tab-btn.active {
        background: #ffffff;
        color: #2563eb;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
    }
    .bap-tab-pane {
        display: none;
    }
    .bap-tab-pane.active {
        display: block;
    }

    /* Tombol Lihat Foto & Lihat Materi */
    .btn-view-photo,
    .btn-view-material {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        padding: 6px 14px !important;
        border: 1.5px solid #2563eb !important;
        border-radius: 6px !important;
        background: #ffffff !important;
        color: #2563eb !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        line-height: 1 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        transition: all 0.15s ease-in-out;
        vertical-align: middle;
    }
    .btn-view-photo:hover,
    .btn-view-material:hover {
        background: #eff6ff !important;
        border-color: #1d4ed8 !important;
        color: #1d4ed8 !important;
    }
    .btn-view-photo .layout-icon,
    .btn-view-material .layout-icon {
        display: inline-block !important;
        width: 14px !important;
        height: 14px !important;
        flex-shrink: 0 !important;
        stroke: currentColor !important;
        vertical-align: middle !important;
    }

    /* Status Pills */
    .badge-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        white-space: nowrap;
        line-height: 1.2;
    }
    .badge-status-green { background: #dcfce7; color: #15803d; }
    .badge-status-blue  { background: #dbeafe; color: #1d4ed8; }
    .badge-status-amber { background: #fef3c7; color: #b45309; }
    .badge-status-red   { background: #fee2e2; color: #b91c1c; }
    .badge-status-gray  { background: #f1f5f9; color: #64748b; font-weight: 500; font-size: 11px; }
</style>
@endpush

@section('content')
<div class="inner-page">
    {{-- ===================== HEADER INFORMASI MATA KULIAH ===================== --}}
    <div class="page-card" style="margin-bottom: 20px;">
        <div class="page-card-head">
            <h2>
                <x-layout-icon name="clipboard" />
                <span>Detail Presensi &amp; Berita Acara Perkuliahan (BAP)</span>
            </h2>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <a href="{{ auth()->user()->role === 'dosen' ? route('dosen.presensi.bap-pdf', $jadwal->id) : route('admin.presensi.bap-pdf', $jadwal->id) }}" class="btn-primary btn-sm btn-table-action" style="background: #ffffff; color: var(--navy); border-color: #ffffff;">
                    <x-layout-icon name="download" />
                    <span>Download BAP (PDF)</span>
                </a>
                <a href="{{ auth()->user()->role === 'dosen' ? route('dosen.presensi') : route('admin.presensi') }}" class="btn-outline btn-sm btn-table-action" style="background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.35);">
                    <x-layout-icon name="arrow-left" />
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="page-card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Mata Kuliah</div>
                    <div style="font-size: 17px; font-weight: 800; color: var(--navy); margin-bottom: 3px;">
                        {{ $jadwal->mataKuliah?->nama_mk ?? '-' }}
                    </div>
                    <div style="color: #475569; font-size: 13px; font-weight: 600;">
                        Kode: {{ $jadwal->mataKuliah?->kode_mk ?? '-' }} &middot; {{ $jadwal->mataKuliah?->sks ?? 0 }} SKS &middot; Semester {{ $jadwal->mataKuliah?->semester ?? '-' }}
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Dosen Pengampu</div>
                    <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 3px;">
                        {{ $jadwal->semua_dosen_nama }}
                    </div>
                    <div style="color: #64748b; font-size: 12.5px;">
                        @if($jadwal->dosens->count() > 1)
                            Team Teaching ({{ $jadwal->dosens->count() }} Dosen)
                        @else
                            NIDN: {{ $jadwal->dosen?->nidn ?? '-' }} &middot; {{ $jadwal->dosen?->prodi?->nama_prodi ?? '-' }}
                        @endif
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Kelas &amp; Ruangan</div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 3px;">
                        Kelas {{ $jadwal->kelas?->nama_kelas ?? $jadwal->kelas ?? '-' }} &middot; Ruang {{ $jadwal->ruangan?->nama_ruangan ?? '-' }}
                    </div>
                    <div style="color: #64748b; font-size: 12.5px;">
                        Jadwal: {{ $jadwal->hari }}, {{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }} WIB
                    </div>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;">
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Periode Akademik</div>
                    <div style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 3px;">
                        Tahun {{ $jadwal->tahun_akademik ?? '-' }}
                    </div>
                    <div style="color: #64748b; font-size: 12.5px;">
                        Semester {{ ucfirst($jadwal->semester_akademik ?? 'Ganjil') }} &middot; Program Studi {{ $jadwal->mataKuliah?->prodi?->nama_prodi ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== TAB NAVIGASI ===================== --}}
    <div class="page-card">
        <div class="page-card-body" style="padding-top: 14px;">
            <div class="bap-tab-nav" role="tablist" style="display:flex;gap:8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;padding:6px;margin-bottom:22px;flex-wrap:wrap;">
                <button type="button" class="bap-tab-btn active" data-tab="tab-rekap" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:none;border-radius:8px;background:#ffffff;color:#2563eb;font-size:13.5px;font-weight:700;cursor:pointer;box-shadow:0 1px 4px rgba(0,0,0,0.08);outline:none;">
                    <x-layout-icon name="users" />
                    <span>Rekap Absensi Mahasiswa ({{ $totalPeserta }})</span>
                </button>
                <button type="button" class="bap-tab-btn" data-tab="tab-bap" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border:none;border-radius:8px;background:transparent;color:#64748b;font-size:13.5px;font-weight:700;cursor:pointer;outline:none;">
                    <x-layout-icon name="calendar" />
                    <span>Berita Acara Perkuliahan ({{ $pertemuans->count() }} Sesi)</span>
                </button>
            </div>

            {{-- ===================== TAB 1: REKAP ABSENSI MAHASISWA ===================== --}}
            <div id="tab-rekap" class="bap-tab-pane active">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Total Mahasiswa Terdaftar</small>
                        <div style="font-size: 22px; font-weight: 800; color: var(--navy); margin-top: 4px;">{{ $totalPeserta }} Mahasiswa</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Rata-rata Kehadiran Kelas</small>
                        <div style="font-size: 22px; font-weight: 800; color: #10b981; margin-top: 4px;">{{ $rataRataKehadiranKelas }}%</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px;">
                        <small style="color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase;">Pertemuan Dilaksanakan</small>
                        <div style="font-size: 22px; font-weight: 800; color: var(--blue); margin-top: 4px;">{{ $pertemuans->count() }} / 16 Sesi</div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 120px;">NIM</th>
                                <th>Nama Mahasiswa</th>
                                <th>Program Studi</th>
                                <th>Kelas</th>
                                <th style="text-align: center; width: 65px;">Hadir</th>
                                <th style="text-align: center; width: 65px;">Izin</th>
                                <th style="text-align: center; width: 65px;">Sakit</th>
                                <th style="text-align: center; width: 65px;">Alpa</th>
                                <th style="text-align: center; width: 90px;">Total Sesi</th>
                                <th style="text-align: center; width: 130px;">% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($krs as $item)
                                <tr>
                                    <td style="text-align: center; color: #64748b;">{{ $loop->iteration }}</td>
                                    <td><strong style="color: var(--navy);">{{ $item->mahasiswa?->nim ?? '-' }}</strong></td>
                                    <td>
                                        <div style="font-weight: 700; color: #1e293b;">{{ $item->mahasiswa?->nama ?? '-' }}</div>
                                    </td>
                                    <td>{{ $item->mahasiswa?->prodi?->nama_prodi ?? '-' }}</td>
                                    <td>{{ $item->mahasiswa?->kelas?->nama_kelas ?? '-' }}</td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-pill badge-status-green">{{ $item->hadir }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-pill badge-status-blue">{{ $item->izin }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-pill badge-status-amber">{{ $item->sakit }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-pill {{ $item->alpha > 0 ? 'badge-status-red' : 'badge-status-gray' }}">{{ $item->alpha }}</span>
                                    </td>
                                    <td style="text-align: center; font-weight: 600; color: #475569;">
                                        {{ $item->total_pertemuan_mhs }}
                                    </td>
                                    <td style="text-align: center;">
                                        @if($item->persentase >= 75)
                                            <span class="badge-status-pill badge-status-green">{{ $item->persentase }}%</span>
                                        @elseif($item->persentase >= 50)
                                            <span class="badge-status-pill badge-status-amber">{{ $item->persentase }}%</span>
                                        @else
                                            <span class="badge-status-pill badge-status-red">{{ $item->persentase }}%</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                        <div style="font-weight: 600;">Belum ada mahasiswa yang terdaftar di kelas mata kuliah ini.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ===================== TAB 2: BERITA ACARA PERKULIAHAN (BAP) ===================== --}}
            <div id="tab-bap" class="bap-tab-pane">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 100px; text-align: center;">Pertemuan</th>
                                <th style="width: 130px;">Hari &amp; Tanggal</th>
                                <th style="min-width: 200px;">Materi Kuliah / Pokok Bahasan</th>
                                <th>Keterangan / Catatan</th>
                                <th style="text-align: center; width: 140px;">Rekap Kehadiran</th>
                                <th style="text-align: center; width: 120px;">Foto Bukti</th>
                                <th style="text-align: center; width: 120px;">File Materi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pertemuans as $p)
                                <tr>
                                    <td style="text-align: center;">
                                        <strong style="color: var(--navy); font-size: 13.5px;">Ke-{{ $p->pertemuan }}</strong>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b;">{{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('l') }}</div>
                                        <small style="color: #64748b;">{{ \Carbon\Carbon::parse($p->tanggal)->format('d-m-Y') }}</small>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #1e293b; line-height: 1.4;">{{ $p->materi_kuliah ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div style="color: #475569; font-size: 12.5px; line-height: 1.4;">{{ $p->keterangan ?? '-' }}</div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status-pill badge-status-green">
                                            {{ $p->total_hadir }} / {{ $totalPeserta }} Hadir
                                        </span>
                                        @if(($p->total_izin + $p->total_sakit + $p->total_alpha) > 0)
                                            <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                                I: {{ $p->total_izin }} &middot; S: {{ $p->total_sakit }} &middot; A: {{ $p->total_alpha }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($p->foto)
                                            <a href="{{ asset('storage/' . $p->foto) }}" target="_blank" class="btn-view-photo" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:6px 14px;border:1.5px solid #2563eb;border-radius:6px;background:#ffffff;color:#2563eb;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;line-height:1;">
                                                <x-layout-icon name="search" />
                                                <span>Lihat Foto</span>
                                            </a>
                                        @else
                                            <span class="badge-status-pill badge-status-gray">Belum ada foto</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($p->materi)
                                            <a href="{{ asset('storage/' . $p->materi) }}" target="_blank" class="btn-view-material" style="display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:6px 14px;border:1.5px solid #0284c7;border-radius:6px;background:#ffffff;color:#0284c7;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;line-height:1;">
                                                <x-layout-icon name="download" />
                                                <span>Lihat Materi</span>
                                            </a>
                                        @else
                                            <span class="badge-status-pill badge-status-gray">Tidak ada file</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px 20px; color: #64748b;">
                                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 4px;">Belum ada sesi pertemuan perkuliahan yang diisi oleh dosen pengampu.</div>
                                        <div style="font-size: 12px;">Sesi perkuliahan akan otomatis terdata saat dosen mengisi presensi harian.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabBtns = document.querySelectorAll('.bap-tab-btn');
    const tabPanes = document.querySelectorAll('.bap-tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const target = this.getAttribute('data-tab');

            tabBtns.forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = '#64748b';
                b.style.boxShadow = 'none';
            });
            tabPanes.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            this.style.background = '#ffffff';
            this.style.color = '#2563eb';
            this.style.boxShadow = '0 1px 4px rgba(0, 0, 0, 0.08)';

            const targetPane = document.getElementById(target);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
});
</script>
@endpush
@endsection
