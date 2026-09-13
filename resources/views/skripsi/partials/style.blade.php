<style>
    .skripsi { display:grid; gap:20px; padding:24px; }
    .skripsi .sk-card { background:var(--white,#fff); border:1px solid var(--border,#e2e8f0); border-radius:14px; padding:22px; min-width:0; }
    .skripsi h1 { font-size:24px; margin:0 0 8px; } .skripsi h2 { font-size:18px; margin:0 0 16px; }
    .skripsi p { margin:8px 0; } .skripsi .sk-muted { color:var(--text-muted,#64748b); font-size:13px; }
    .skripsi .sk-grid { display:flex; flex-wrap:wrap; gap:14px; align-items:end; }
    .skripsi label { display:grid; gap:6px; font-size:13px; flex:1 1 180px; }
    .skripsi input,.skripsi select,.skripsi textarea { width:100%; border:1px solid #cbd5e1; padding:10px; border-radius:8px; color:#0f172a; background:#fff; font:inherit; box-sizing:border-box; }
    .skripsi textarea { resize:vertical; min-height:80px; }
    .skripsi button,.skripsi .sk-link { display:inline-block; padding:9px 14px; border-radius:8px; border:0; background:var(--blue,#2563eb); color:white; cursor:pointer; text-decoration:none; font:inherit; font-size:13px; }
    .skripsi .sk-danger { background:#1e40af; } .skripsi .sk-secondary { background:#1d4ed8; }
    .skripsi .sk-scroll { overflow-x:auto; } .skripsi table { width:100%; border-collapse:collapse; text-align:left; font-size:13px; }
    .skripsi th,.skripsi td { padding:12px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    .skripsi td { min-width:100px; } .skripsi .sk-title { min-width:220px; max-width:440px; overflow-wrap:anywhere; }
    .skripsi .sk-badge { display:inline-block; border-radius:20px; padding:4px 10px; background:#e2e8f0; color:#334155; white-space:nowrap; }
    .skripsi .sk-Menunggu { background:#eff6ff; color:#1d4ed8; } .skripsi .sk-Diterima { background:#dbeafe; color:#1e3a8a; }
    .skripsi .sk-Ditolak { background:#e0e7ff; color:#3730a3; } .skripsi .sk-Dialihkan { background:#bfdbfe; color:#1e40af; }
    .skripsi .sk-alert { padding:14px; border-radius:10px; background:#eff6ff; color:#1e40af; }
    .skripsi .sk-errors { background:#eff6ff; color:#1e40af; } .skripsi .sk-success { background:#dbeafe; color:#1e3a8a; }
    .skripsi .sk-download-card { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .skripsi .sk-summary { display:flex; flex-wrap:wrap; gap:12px; } .skripsi .sk-summary>div { flex:1 1 150px; background:#f8fafc; border-radius:10px; padding:14px; }
    .skripsi .sk-summary strong { display:block; font-size:25px; margin-bottom:6px; }
    .skripsi details { margin:10px 0; } .skripsi summary { cursor:pointer; font-weight:600; } .skripsi details form { margin-top:14px; }
    .skripsi .sk-stack { display:grid; gap:10px; min-width:210px; } .skripsi .sk-pagination { display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-top:16px; font-size:13px; }
    .skripsi pre { white-space:pre-wrap; overflow-wrap:anywhere; font:inherit; margin:0; }
    @media(max-width:640px) { .skripsi { padding:12px; } .skripsi .sk-card { padding:14px; } }
</style>
@if(session('success'))<div class="sk-alert sk-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())
    <div class="sk-alert sk-errors" role="alert"><strong>Periksa kembali isian:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
