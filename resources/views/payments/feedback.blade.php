<style>
    .payment-page .payment-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:18px}
    .payment-page .payment-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:20px}
    .payment-page .payment-stat{background:#eef5ff;padding:18px;border-radius:10px;color:#123d72}
    .payment-page .payment-stat strong{display:block;font-size:23px;margin-top:6px}
    .payment-page .payment-notice{padding:14px 18px;border-radius:8px;background:#fff7ed;color:#9a3412;margin-bottom:18px}
    .payment-page .payment-success{background:#dcfce7;color:#166534}
    .payment-page .payment-errors{background:#fee2e2;color:#991b1b}
    .payment-page label{display:block;font-weight:600;margin-bottom:6px}
    .payment-page input:not([type=checkbox]):not([type=hidden]),.payment-page select,.payment-page textarea{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;background:#fff;color:#0f172a;box-sizing:border-box}
    .payment-page input[type=checkbox]{width:18px;height:18px;vertical-align:middle}
    .payment-page .payment-inline{display:flex;align-items:center;gap:8px;font-weight:400}
    .payment-page .page-card{margin-bottom:20px}
    .payment-page .payment-small{font-size:13px;color:#64748b}
    .payment-page .payment-money{white-space:nowrap}
    .payment-page details{margin:16px 0}.payment-page summary{cursor:pointer;font-weight:700;margin-bottom:12px}
    .payment-page .btn-primary,.payment-page .btn-outline{display:inline-flex;align-items:center;justify-content:center;text-decoration:none;gap:6px;padding:9px 14px}
    .payment-page .btn-primary{color:#fff!important}.payment-page .btn-outline{color:#123d72;background:#fff}
    .payment-page .table-wrap{overflow-x:auto}.payment-page td{vertical-align:top}
</style>
<div class="payment-notice">Mode sandbox / uji coba. Tagihan dan bukti di halaman ini merupakan simulasi, bukan pembayaran uang nyata.</div>
@if(session('success'))<div class="payment-notice payment-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="payment-notice payment-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
