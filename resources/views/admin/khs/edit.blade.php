@extends('layouts.admin')

@section('title','Edit KHS')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Data KHS &amp; Komponen Penilaian Standar</h2>
    </div>

    <div class="page-card-body">

        {{-- ERROR VALIDASI --}}
        @if ($errors->any())
            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px;
                border-radius:8px;
                margin-bottom:20px;
            ">
                <strong>Terjadi kesalahan:</strong>

                <ul style="margin-top:10px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ERROR SESSION --}}
        @if(session('error'))
            <div style="
                background:#fee2e2;
                color:#991b1b;
                padding:15px;
                border-radius:8px;
                margin-bottom:20px;
            ">
                ❌ {{ session('error') }}
            </div>
        @endif


        <form
            action="{{ route('admin.khs.update', $khs->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.khs" />
            @method('PUT')


            <div class="krs-form-grid">

                {{-- ================= KRS ================= --}}
                <div class="form-group">

                    <label>Pilih KRS</label>

                    <select
                        name="krs_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih KRS --
                        </option>

                        @foreach($krs as $item)

                            <option
                                value="{{ $item->id }}"
                                {{ old(
                                    'krs_id',
                                    $khs->krs_id
                                ) == $item->id
                                    ? 'selected'
                                    : ''
                                }}
                            >

                                {{ $item->mahasiswa->nim ?? '-' }}
                                -
                                {{ $item->mahasiswa->nama ?? '-' }}

                                |

                                {{ $item->jadwal->mataKuliah->kode_mk ?? '-' }}
                                -
                                {{ $item->jadwal->mataKuliah->nama_mk ?? '-' }}

                                |

                                {{ $item->jadwal->dosen->nama ?? '-' }}

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">
                        Pilih KRS yang berkaitan dengan nilai mahasiswa.
                    </small>

                </div>


                {{-- ================= NILAI ANGKA ================= --}}
                <div class="form-group">

                    <label>Nilai Akhir (Angka)</label>

                    <input
                        type="number"
                        id="input-nilai-angka"
                        name="nilai_angka"
                        class="form-control"
                        min="0"
                        max="100"
                        step="0.01"
                        value="{{ old(
                            'nilai_angka',
                            $khs->nilai_angka
                        ) }}"
                        oninput="updateKonversiPreview(this.value)"
                        required
                    >

                    <small style="color:#64748b;">
                        Nilai 0 sampai 100. Otomatis dihitung dari rincian komponen di bawah atau dapat diisi langsung.
                    </small>

                </div>


                {{-- ================= TAHUN AKADEMIK ================= --}}
                <div class="form-group">

                    <label>Tahun Akademik</label>

                    <input
                        type="text"
                        name="tahun_akademik"
                        class="form-control"
                        value="{{ old(
                            'tahun_akademik',
                            $khs->tahun_akademik
                        ) }}"
                        placeholder="Contoh: 2026/2027"
                        required
                    >

                </div>


                {{-- ================= SEMESTER AKADEMIK ================= --}}
                <div class="form-group">

                    <label>Semester Akademik</label>

                    <select
                        name="semester_akademik"
                        class="form-control"
                        required
                    >

                        <option
                            value="Ganjil"
                            {{ old(
                                'semester_akademik',
                                $khs->semester_akademik
                            ) == 'Ganjil'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Ganjil
                        </option>

                        <option
                            value="Genap"
                            {{ old(
                                'semester_akademik',
                                $khs->semester_akademik
                            ) == 'Genap'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Genap
                        </option>

                    </select>

                </div>


                {{-- ================= NILAI HURUF ================= --}}
                <div class="form-group">

                    <label>Nilai Huruf</label>

                    <input
                        type="text"
                        id="preview-nilai-huruf"
                        class="form-control"
                        value="{{ $khs->nilai_huruf ?? '-' }}"
                        readonly
                        style="background:#f8fafc; font-weight:700;"
                    >

                    <small style="color:#64748b;">
                        Nilai huruf dihitung otomatis berdasarkan nilai angka.
                    </small>

                </div>


                {{-- ================= BOBOT ================= --}}
                <div class="form-group">

                    <label>Bobot</label>

                    <input
                        type="text"
                        id="preview-bobot"
                        class="form-control"
                        value="{{ $khs->bobot ?? '-' }}"
                        readonly
                        style="background:#f8fafc; font-weight:700;"
                    >

                    <small style="color:#64748b;">
                        Bobot dihitung otomatis berdasarkan nilai angka.
                    </small>

                </div>

            </div>

            {{-- ================= KOMPONEN & BOBOT PENILAIAN STANDAR (TANPA RPS) ================= --}}
            @php
                $komponenStandar = $komponenStandar ?? [
                    ['nama' => 'Kehadiran', 'bobot' => 10],
                    ['nama' => 'Tugas', 'bobot' => 20],
                    ['nama' => 'UTS', 'bobot' => 30],
                    ['nama' => 'UAS', 'bobot' => 40],
                ];
                $detailNilai = $detailNilai ?? (is_array($khs->detail_nilai) ? $khs->detail_nilai : []);
            @endphp

            <div style="margin-top: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; padding: 18px 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                    <div>
                        <h3 style="margin: 0; font-size: 15px; color: #0a1f5c;">⚙️ Komponen &amp; Bobot Penilaian Standar (Tanpa RPS)</h3>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
                            Admin dapat mengubah persentase bobot, menambah/menghapus komponen penilaian sendiri (total 100%), serta mengisi nilai per komponen untuk menghitung Nilai Akhir secara otomatis.
                        </p>
                    </div>
                    <button type="button" class="btn-outline" onclick="khsTambahKomponen()" style="font-size: 12px; padding: 6px 12px;">
                        + Tambah Komponen Sendiri
                    </button>
                </div>

                <div class="table-wrap" style="background: #fff; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <table style="width: 100%; border-collapse: collapse;" id="table-khs-komponen" data-row-count="{{ count($komponenStandar ?? []) + 10 }}">
                        <thead>
                            <tr style="background: #f1f5f9; border-bottom: 1px solid #cbd5e1;">
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>Nama Komponen Penilaian</th>
                                <th style="width: 130px; text-align: center;">Bobot (%)</th>
                                <th style="width: 150px; text-align: center;">Nilai Mahasiswa (0-100)</th>
                                <th style="width: 70px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-khs-komponen">
                            @foreach($komponenStandar as $idx => $ks)
                                @php
                                    $skorVal = $detailNilai[$ks['nama']] ?? '';
                                @endphp
                                <tr class="khs-ks-row">
                                    <td style="text-align: center; font-weight: 600; color: #64748b;" class="khs-ks-no">{{ $loop->iteration }}</td>
                                    <td style="padding: 8px;">
                                        <input type="text"
                                               name="komponen_standar[{{ $idx }}][nama]"
                                               value="{{ $ks['nama'] }}"
                                               class="form-control khs-ks-nama"
                                               placeholder="Contoh: Tugas / Kuis / UTS / UAS"
                                               required>
                                    </td>
                                    <td style="padding: 8px; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                            <input type="number"
                                                   name="komponen_standar[{{ $idx }}][bobot]"
                                                   value="{{ $ks['bobot'] }}"
                                                   class="form-control khs-ks-bobot"
                                                   min="1"
                                                   max="100"
                                                   step="0.5"
                                                   oninput="recalcKhsKomponen()"
                                                   style="width: 80px; text-align: center; font-weight: 700;"
                                                   required>
                                            <span style="font-weight: 700; color: #64748b;">%</span>
                                        </div>
                                    </td>
                                    <td style="padding: 8px; text-align: center;">
                                        <input type="number"
                                               name="komponen_standar[{{ $idx }}][skor]"
                                               value="{{ $skorVal }}"
                                               class="form-control khs-ks-skor"
                                               min="0"
                                               max="100"
                                               step="0.5"
                                               placeholder="Opsional (0-100)"
                                               oninput="recalcKhsKomponen()"
                                               style="width: 110px; text-align: center; font-weight: 700; margin: 0 auto;">
                                    </td>
                                    <td style="padding: 8px; text-align: center;">
                                        <button type="button" class="btn-outline" style="padding: 4px 8px; color: #dc2626; border-color: #fca5a5;" onclick="khsHapusKomponen(this)" title="Hapus Komponen">&times;</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                                <td colspan="2" style="text-align: right; padding: 10px 14px;">Total Bobot Komponen:</td>
                                <td style="text-align: center; font-size: 14px; font-weight: 800;" id="khs-total-bobot">
                                    {{ array_sum(array_column($komponenStandar, 'bobot')) }}%
                                </td>
                                <td colspan="2" style="font-size: 12px; color: #475569; padding: 10px;">
                                    <span id="khs-calc-info">Isi nilai komponen untuk hitung otomatis Nilai Akhir</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>


            {{-- ================= BUTTON ================= --}}
            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary"
                >
                    💾 Simpan Perubahan
                </button>

                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.khs') }}"
                    class="btn-outline"
                >
                    Batal
                </a>

            </div>

        </form>

    </div>

</div>

<script>
    var khsKsTableEl = document.getElementById('table-khs-komponen');
    var khsKsRowIdx = khsKsTableEl ? parseInt(khsKsTableEl.getAttribute('data-row-count') || '20', 10) : 20;

    function khsTambahKomponen() {
        var tbody = document.getElementById('tbody-khs-komponen');
        var idx = khsKsRowIdx++;
        var tr = document.createElement('tr');
        tr.className = 'khs-ks-row';
        tr.innerHTML =
            '<td style="text-align: center; font-weight: 600; color: #64748b;" class="khs-ks-no"></td>' +
            '<td style="padding: 8px;">' +
                '<input type="text" name="komponen_standar[' + idx + '][nama]" class="form-control khs-ks-nama" placeholder="Nama Komponen Baru" required>' +
            '</td>' +
            '<td style="padding: 8px; text-align: center;">' +
                '<div style="display: flex; align-items: center; justify-content: center; gap: 4px;">' +
                    '<input type="number" name="komponen_standar[' + idx + '][bobot]" value="10" class="form-control khs-ks-bobot" min="1" max="100" step="0.5" oninput="recalcKhsKomponen()" style="width: 80px; text-align: center; font-weight: 700;" required>' +
                    '<span style="font-weight: 700; color: #64748b;">%</span>' +
                '</div>' +
            '</td>' +
            '<td style="padding: 8px; text-align: center;">' +
                '<input type="number" name="komponen_standar[' + idx + '][skor]" value="" class="form-control khs-ks-skor" min="0" max="100" step="0.5" placeholder="Opsional (0-100)" oninput="recalcKhsKomponen()" style="width: 110px; text-align: center; font-weight: 700; margin: 0 auto;">' +
            '</td>' +
            '<td style="padding: 8px; text-align: center;">' +
                '<button type="button" class="btn-outline" style="padding: 4px 8px; color: #dc2626; border-color: #fca5a5;" onclick="khsHapusKomponen(this)" title="Hapus Komponen">&times;</button>' +
            '</td>';
        tbody.appendChild(tr);
        khsRenumberRows();
        recalcKhsKomponen();
    }

    function khsHapusKomponen(btn) {
        var rows = document.querySelectorAll('#tbody-khs-komponen .khs-ks-row');
        if (rows.length <= 1) {
            alert('Minimal harus terdapat 1 komponen penilaian.');
            return;
        }
        var tr = btn.closest('tr');
        if (tr) tr.remove();
        khsRenumberRows();
        recalcKhsKomponen();
    }

    function khsRenumberRows() {
        document.querySelectorAll('#tbody-khs-komponen .khs-ks-no').forEach(function (td, i) {
            td.textContent = i + 1;
        });
    }

    function recalcKhsKomponen() {
        var totalBobot = 0;
        var totalWeighted = 0;
        var hasAnyScore = false;

        document.querySelectorAll('#tbody-khs-komponen .khs-ks-row').forEach(function (tr) {
            var bobotInp = tr.querySelector('.khs-ks-bobot');
            var skorInp = tr.querySelector('.khs-ks-skor');
            var b = bobotInp ? parseFloat(bobotInp.value) : 0;
            if (!isNaN(b) && b > 0) {
                totalBobot += b;
            }
            if (skorInp && skorInp.value !== '') {
                var s = parseFloat(skorInp.value);
                if (!isNaN(s) && !isNaN(b) && b > 0) {
                    hasAnyScore = true;
                    totalWeighted += s * (b / 100.0);
                }
            }
        });

        totalBobot = Math.round(totalBobot * 10) / 10;
        var totalEl = document.getElementById('khs-total-bobot');
        if (totalEl) {
            totalEl.textContent = totalBobot + '%';
            totalEl.style.color = Math.abs(totalBobot - 100) < 0.05 ? '#15803d' : '#dc2626';
        }

        if (hasAnyScore) {
            var na = Math.round(totalWeighted * 100) / 100;
            var naInput = document.getElementById('input-nilai-angka');
            if (naInput) {
                naInput.value = na;
                updateKonversiPreview(na);
            }
            var infoEl = document.getElementById('khs-calc-info');
            if (infoEl) {
                infoEl.textContent = 'Nilai Akhir terhitung dari komponen: ' + na;
            }
        }
    }

    function updateKonversiPreview(val) {
        var n = parseFloat(val);
        if (isNaN(n)) return;
        var huruf = 'E';
        var bobot = '0.00';
        if (n >= 85) { huruf = 'A'; bobot = '4.00'; }
        else if (n >= 80) { huruf = 'A-'; bobot = '3.75'; }
        else if (n >= 75) { huruf = 'B+'; bobot = '3.50'; }
        else if (n >= 70) { huruf = 'B'; bobot = '3.00'; }
        else if (n >= 65) { huruf = 'B-'; bobot = '2.75'; }
        else if (n >= 60) { huruf = 'C+'; bobot = '2.50'; }
        else if (n >= 55) { huruf = 'C'; bobot = '2.00'; }
        else if (n >= 40) { huruf = 'D'; bobot = '1.00'; }

        var hEl = document.getElementById('preview-nilai-huruf');
        var bEl = document.getElementById('preview-bobot');
        if (hEl) hEl.value = huruf;
        if (bEl) bEl.value = bobot;
    }
</script>

@endsection
