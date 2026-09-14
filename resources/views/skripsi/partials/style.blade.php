<style>
    .skripsi { display:grid; gap:20px; padding:24px; color:#172033; }
    .skripsi .sk-page-header { display:flex; justify-content:space-between; align-items:flex-end; gap:18px; padding:22px 24px; border:1px solid #dbeafe; border-radius:18px; background:linear-gradient(135deg,#f8fafc,#eff6ff); }
    .skripsi .sk-eyebrow { display:block; margin-bottom:7px; color:#3359d8; font-size:10px; font-weight:800; letter-spacing:1.4px; }
    .skripsi .sk-actions { display:flex; gap:9px; flex-wrap:wrap; flex-shrink:0; }
    .skripsi .sk-card { min-width:0; overflow:hidden; padding:20px 22px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 8px 28px rgba(15,42,85,.06); }
    .skripsi .sk-card > h2:first-child { margin:-20px -22px 18px; padding:15px 20px; color:#fff; background:linear-gradient(90deg,#0f2a55,#3359d8); font:700 14px 'Sora',sans-serif; }
    .skripsi h1 { margin:0 0 6px; color:#0f2a55; font:700 24px 'Sora',sans-serif; }
    .skripsi h2 { margin:0 0 14px; color:#0f2a55; font-size:17px; }
    .skripsi p { margin:8px 0; line-height:1.55; }
    .skripsi .sk-muted { color:#64748b; font-size:12px; }
    .skripsi .sk-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:13px; align-items:end; }
    .skripsi label { display:grid; gap:6px; color:#334155; font-size:10px; font-weight:700; letter-spacing:.45px; text-transform:uppercase; }
    .skripsi input,.skripsi select,.skripsi textarea { width:100%; min-height:41px; box-sizing:border-box; padding:9px 12px; border:1.5px solid #d7ddeb; border-radius:9px; color:#172033; background:#fff; font:400 13px 'Plus Jakarta Sans',sans-serif; outline:none; }
    .skripsi input:focus,.skripsi select:focus,.skripsi textarea:focus { border-color:#3359d8; box-shadow:0 0 0 3px rgba(51,89,216,.12); }
    .skripsi textarea { min-height:86px; resize:vertical; }
    .skripsi button,.skripsi .sk-link { display:inline-flex; min-height:39px; align-items:center; justify-content:center; box-sizing:border-box; padding:9px 15px; border:0; border-radius:9px; color:#fff; background:linear-gradient(90deg,#0f2a55,#3359d8); font:600 12px 'Plus Jakarta Sans',sans-serif; text-decoration:none; cursor:pointer; }
    .skripsi .sk-link.sk-pdf { color:#1d4ed8; border:1px solid #93c5fd; background:#fff; }
    .skripsi .sk-link.sk-excel { background:linear-gradient(90deg,#166534,#16a34a); }
    .skripsi .sk-danger { background:#1e40af; }
    .skripsi .sk-secondary { background:#1d4ed8; }
    .skripsi .sk-scroll { overflow-x:auto; }
    .skripsi table { width:100%; border-collapse:collapse; text-align:left; font-size:12px; }
    .skripsi thead tr { background:#f8fafc; }
    .skripsi th { padding:10px 12px; border-bottom:2px solid #e2e8f0; color:#64748b; font-size:10px; font-weight:700; letter-spacing:.45px; text-transform:uppercase; }
    .skripsi td { min-width:90px; padding:11px 12px; border-bottom:1px solid #edf2f7; vertical-align:top; }
    .skripsi tbody tr:hover td { background:#f8fafc; }
    .skripsi .sk-title { min-width:220px; max-width:440px; overflow-wrap:anywhere; }
    .skripsi .sk-badge { display:inline-block; padding:4px 10px; border-radius:20px; color:#334155; background:#e2e8f0; font-size:10px; font-weight:700; white-space:nowrap; }
    .skripsi .sk-Menunggu { color:#92400e; background:#fef3c7; }
    .skripsi .sk-Diterima { color:#166534; background:#dcfce7; }
    .skripsi .sk-Ditolak { color:#991b1b; background:#fee2e2; }
    .skripsi .sk-Dialihkan { color:#1e40af; background:#dbeafe; }
    .skripsi .sk-alert { padding:13px 15px; border:1px solid #bfdbfe; border-radius:10px; color:#1e40af; background:#eff6ff; font-size:12px; }
    .skripsi .sk-errors { color:#991b1b; border-color:#fecaca; background:#fef2f2; }
    .skripsi .sk-success { color:#166534; border-color:#bbf7d0; background:#f0fdf4; }
    .skripsi .sk-download-card { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .skripsi .sk-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(135px,1fr)); gap:12px; }
    .skripsi .sk-summary > div { padding:15px; border:1px solid #dbeafe; border-radius:12px; color:#64748b; background:#f8fafc; font-size:11px; }
    .skripsi .sk-summary strong { display:block; margin-bottom:4px; color:#0f2a55; font-size:25px; }
    .skripsi details { margin:10px 0; padding:10px 12px; border:1px solid #e2e8f0; border-radius:9px; background:#f8fafc; }
    .skripsi summary { color:#1d4ed8; font-size:12px; font-weight:700; cursor:pointer; }
    .skripsi details form { margin-top:14px; }
    .skripsi .sk-stack { display:grid; gap:10px; min-width:210px; }
    .skripsi .sk-pagination { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:16px; color:#64748b; font-size:12px; }
    .skripsi pre { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; font:inherit; }
    @media(max-width:720px) { .skripsi { padding:14px; } .skripsi .sk-page-header { align-items:flex-start; flex-direction:column; padding:18px; } .skripsi .sk-card { padding:16px; } .skripsi .sk-card > h2:first-child { margin:-16px -16px 16px; } }
</style>
@if(session('success'))<div class="sk-alert sk-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="sk-alert sk-errors" role="alert"><strong>Periksa kembali isian:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
