@extends('layouts.admin')

@section('title', 'IPK CPL')
@section('page-subtitle', 'Laporan capaian pembelajaran lulusan per program studi')

@push('styles')
<style>
    .cpl-program-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px; }
    .cpl-program-card { border:1px solid #dbe6f1;border-radius:16px;padding:24px;background:#fff;box-shadow:0 8px 25px rgba(10,31,92,.08); }
    .cpl-program-card.is-active { border-top:5px solid #0b5b9d; }
    .cpl-program-icon { width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:#e3eff9;color:#0a1f5c;margin-bottom:16px; }
    .cpl-program-card h3 { margin:0 0 8px;color:#0a1f5c;font-size:19px; }
    .cpl-program-card p { margin:0 0 18px;color:#64748b;line-height:1.6; }
</style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-card-head">
        <div>
            <h2 class="icon-heading"><x-layout-icon name="chart" /> IPK CPL</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">Pilih program studi untuk membuka laporan mapping CPL.</p>
        </div>
    </div>
    <div class="page-card-body">
        <div class="cpl-program-grid">
            <article class="cpl-program-card is-active">
                <div class="cpl-program-icon"><x-layout-icon name="chart" /></div>
                <h3>CPL Teknik Pertambangan</h3>
                <p>
                    @if($mining)
                        {{ $activeMiningCplCount }} CPL aktif tersedia dari mapping kurikulum Teknik Pertambangan.
                    @else
                        Program Studi Teknik Pertambangan belum ditemukan pada master data.
                    @endif
                </p>
                @if($mining)
                    <a href="{{ route('admin.ipk-cpl.program', $mining) }}" class="btn-primary">Buka Laporan CPL</a>
                @else
                    <span class="btn-outline" style="opacity:.65;cursor:not-allowed;">Belum tersedia</span>
                @endif
            </article>
        </div>
    </div>
</div>
@endsection
