@extends('layouts.admin')

@section('title','Edit KHS')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2>✏️ Edit Data KHS</h2>
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

                    <label>Nilai Angka</label>

                    <input
                        type="number"
                        name="nilai_angka"
                        class="form-control"
                        min="0"
                        max="100"
                        step="0.01"
                        value="{{ old(
                            'nilai_angka',
                            $khs->nilai_angka
                        ) }}"
                        required
                    >

                    <small style="color:#64748b;">
                        Nilai 0 sampai 100. Nilai huruf dan bobot dihitung otomatis.
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
                        class="form-control"
                        value="{{ $khs->nilai_huruf ?? '-' }}"
                        readonly
                        style="background:#f8fafc;"
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
                        class="form-control"
                        value="{{ $khs->bobot ?? '-' }}"
                        readonly
                        style="background:#f8fafc;"
                    >

                    <small style="color:#64748b;">
                        Bobot dihitung otomatis berdasarkan nilai angka.
                    </small>

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

@endsection
