@extends('layouts.dosen')

@section('title', 'Profil Saya')

@section('content')

<div class="inner-page">

    <div class="page-card">

        <div class="page-card-head">
            <h2>👤 Data Pribadi</h2>
        </div>

        <div class="page-card-body">

            <div class="profile-detail-grid">

                <div>

                    <div class="profile-field">
                        <label>Nama Lengkap</label>
                        <p>Dr. Hendra Saputra, S.T., M.T.</p>
                    </div>

                    <div class="profile-field">
                        <label>NIDN</label>
                        <p>0412038801</p>
                    </div>

                    <div class="profile-field">
                        <label>Program Studi</label>
                        <p>Teknik Mesin (S1)</p>
                    </div>

                    <div class="profile-field">
                        <label>Jabatan Fungsional</label>
                        <p>Lektor</p>
                    </div>

                    <div class="profile-field">
                        <label>Golongan / Ruang</label>
                        <p>III/c</p>
                    </div>

                </div>

                <div>

                    <div class="profile-field">
                        <label>Tempat, Tanggal Lahir</label>
                        <p>Bandung, 12 Maret 1988</p>
                    </div>

                    <div class="profile-field">
                        <label>Email</label>
                        <p>hendra.saputra@sttm.ac.id</p>
                    </div>

                    <div class="profile-field">
                        <label>No. Telepon</label>
                        <p>+62 811 2233 4455</p>
                    </div>

                    <div class="profile-field">
                        <label>Bidang Keahlian</label>
                        <p style="color:var(--blue);font-weight:600">
                            Mekanika Fluida &amp; Termodinamika
                        </p>
                    </div>

                    <div class="profile-field">
                        <label>Total Mata Kuliah Diampu</label>
                        <p>6 MK · 16 SKS</p>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection