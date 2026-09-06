  
@extends('layouts.mahasiswa')

@section('content')

<!-- ═══ JADWAL ═════════════════════════════════════════ -->
      <div class="view" id="view-jadwal">
        <div class="inner-page">
          <div class="page-card">
            <div class="page-card-head">
              <h2>📅 Jadwal Kuliah Semester 7</h2>
              <span style="font-size:12px;color:rgba(255,255,255,0.7)">T.A 2024/2025 Gasal</span>
            </div>
            <div class="page-card-body">
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr><th>Hari</th><th>Jam</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th><th>Ruang</th><th>Status</th></tr>
                  </thead>
                  <tbody>
                    <tr><td><strong>Senin</strong></td><td>07:30 – 09:00</td><td>TM601</td><td>Mekanika Fluida</td><td>3</td><td>Dr. Hendra S.</td><td>A-201</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td></td><td>09:15 – 11:15</td><td>TM602</td><td>Termodinamika Lanjut</td><td>3</td><td>Prof. Agus W.</td><td>B-104</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td></td><td>13:00 – 15:00</td><td>TM603</td><td>Praktikum Mesin Perkakas</td><td>2</td><td>Ir. Budi P.</td><td>Lab. Mfg</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td></td><td>15:30 – 17:00</td><td>TM604</td><td>Matematika Teknik III</td><td>3</td><td>Dr. Rina T.</td><td>C-302</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td><strong>Selasa</strong></td><td>07:30 – 09:00</td><td>TM605</td><td>Teknik Pengelasan</td><td>3</td><td>Ir. Slamet R.</td><td>Lab. Las</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td><strong>Rabu</strong></td><td>10:00 – 13:00</td><td>TM606</td><td>Perancangan Mesin</td><td>4</td><td>Dr. Wahyu N.</td><td>A-104</td><td><span class="badge badge-green">Aktif</span></td></tr>
                    <tr><td><strong>Kamis</strong></td><td>13:00 – 14:30</td><td>TM607</td><td>Manajemen Produksi</td><td>2</td><td>Dr. Lina K.</td><td>B-201</td><td><span class="badge badge-green">Aktif</span></td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
@endsection
  
  
  