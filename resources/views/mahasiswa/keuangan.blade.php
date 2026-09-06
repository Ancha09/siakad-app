@extends('layouts.mahasiswa')

@section('content')

<!-- ═══ KEUANGAN ═══════════════════════════════════════ -->
      <div class="view" id="view-keuangan">
        <div class="inner-page">
          <div class="finance-summary">
            <div class="finance-card" style="background:linear-gradient(135deg,#16a34a,#22c55e)">
              <p>Status Pembayaran</p>
              <h3>Lunas</h3>
              <small>Semester Gasal 2024/2025</small>
            </div>
            <div class="finance-card" style="background:linear-gradient(135deg,var(--navy),var(--blue))">
              <p>Total UKT/SPP</p>
              <h3>Rp 4.200.000</h3>
              <small>Per Semester</small>
            </div>
            <div class="finance-card" style="background:linear-gradient(135deg,#d97706,#f59e0b)">
              <p>Sisa Tanggungan</p>
              <h3>Rp 0</h3>
              <small>Tidak ada tunggakan</small>
            </div>
          </div>

          <div class="page-card">
            <div class="page-card-head"><h2>💰 Riwayat Pembayaran</h2></div>
            <div class="page-card-body">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr><th>No</th><th>Semester</th><th>Jenis</th><th>Jumlah</th><th>Tanggal Bayar</th><th>Metode</th><th>Status</th><th>Bukti</th></tr>
                  </thead>
                  <tbody>
                    <tr><td>1</td><td>Gasal 2024/25</td><td>UKT</td><td><strong>Rp 4.200.000</strong></td><td>5 Agt 2024</td><td>Transfer Bank</td><td><span class="badge badge-green">Lunas</span></td><td><button class="btn-outline" style="padding:4px 10px;font-size:11px">Unduh</button></td></tr>
                    <tr><td>2</td><td>Genap 2023/24</td><td>UKT</td><td><strong>Rp 4.200.000</strong></td><td>2 Feb 2024</td><td>QRIS</td><td><span class="badge badge-green">Lunas</span></td><td><button class="btn-outline" style="padding:4px 10px;font-size:11px">Unduh</button></td></tr>
                    <tr><td>3</td><td>Gasal 2023/24</td><td>UKT</td><td><strong>Rp 4.200.000</strong></td><td>8 Agt 2023</td><td>Transfer Bank</td><td><span class="badge badge-green">Lunas</span></td><td><button class="btn-outline" style="padding:4px 10px;font-size:11px">Unduh</button></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

@endsection
 