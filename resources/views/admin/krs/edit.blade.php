@extends('layouts.admin')

@section('title','Edit KRS')

@section('content')

<div class="page-card">

    <div class="page-card-head">
        <h2 class="icon-heading"><x-layout-icon name="edit" /> Edit Data KRS</h2>
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

                <span class="icon-inline"><x-layout-icon name="x" /> {{ session('error') }}</span>

            </div>

        @endif


        <form
            action="{{ route('admin.krs.update', $krs->id) }}"
            method="POST"
        >

            @csrf
            <x-list-return-url list-route="admin.krs" />
            @method('PUT')


            <div class="krs-form-grid">


                {{-- ================= MAHASISWA ================= --}}

                <div class="form-group">

                    <label>Mahasiswa</label>

                    <select
                        name="mahasiswa_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Mahasiswa --
                        </option>

                        @foreach($mahasiswas as $mahasiswa)

                            <option
                                value="{{ $mahasiswa->id }}"
                                {{ old(
                                    'mahasiswa_id',
                                    $krs->mahasiswa_id
                                ) == $mahasiswa->id
                                    ? 'selected'
                                    : ''
                                }}
                            >

                                {{ $mahasiswa->nim }}
                                -
                                {{ $mahasiswa->nama }}

                                @if($mahasiswa->kelas)

                                    | Kelas:
                                    {{ $mahasiswa->kelas->nama_kelas }}

                                @endif

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">

                        Pilih mahasiswa yang mengambil mata kuliah.

                    </small>

                </div>



                {{-- ================= JADWAL ================= --}}

                <div class="form-group">

                    <label>Jadwal / Mata Kuliah</label>

                    <select
                        name="jadwal_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Pilih Jadwal Kuliah --
                        </option>

                        @foreach($jadwals as $jadwal)

                            <option
                                value="{{ $jadwal->id }}"
                                {{ old(
                                    'jadwal_id',
                                    $krs->jadwal_id
                                ) == $jadwal->id
                                    ? 'selected'
                                    : ''
                                }}
                            >

                                {{ $jadwal->mataKuliah->kode_mk ?? '-' }}
                                -
                                {{ $jadwal->mataKuliah->nama_mk ?? '-' }}

                                |

                                {{ $jadwal->hari }}

                                |

                                {{ $jadwal->jam_mulai }}
                                -
                                {{ $jadwal->jam_selesai }}

                                |

                                Kelas:
                                {{ $jadwal->kelas->nama_kelas ?? '-' }}

                                |

                                Dosen:
                                {{ $jadwal->dosen->nama ?? '-' }}

                            </option>

                        @endforeach

                    </select>

                    <small style="color:#64748b;">

                        Jadwal terhubung dengan mata kuliah,
                        dosen, ruangan, dan kelas.

                    </small>

                </div>



                {{-- ================= STATUS ================= --}}

                <div class="form-group">

                    <label>Status KRS</label>

                    <select
                        name="status"
                        class="form-control"
                        required
                    >

                        <option
                            value="Diambil"
                            {{ old(
                                'status',
                                $krs->status
                            ) == 'Diambil'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Diambil
                        </option>

                        <option
                            value="Disetujui"
                            {{ old(
                                'status',
                                $krs->status
                            ) == 'Disetujui'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Disetujui
                        </option>

                        <option
                            value="Ditolak"
                            {{ old(
                                'status',
                                $krs->status
                            ) == 'Ditolak'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Ditolak
                        </option>

                    </select>

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
                            $krs->tahun_akademik
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
                                $krs->semester_akademik
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
                                $krs->semester_akademik
                            ) == 'Genap'
                                ? 'selected'
                                : ''
                            }}
                        >
                            Genap
                        </option>

                    </select>

                </div>


            </div>


            {{-- ================= BUTTON ================= --}}

            <div style="margin-top:25px;">

                <button
                    type="submit"
                    class="btn-primary"
                >

                    <span class="icon-inline"><x-layout-icon name="save" /> Simpan Perubahan</span>

                </button>


                <a
                    href="{{ app(\App\Services\LegacyListNavigation::class)->returnUrl(request(), 'admin.krs') }}"
                    class="btn-outline"
                >

                    Batal

                </a>

            </div>


        </form>

    </div>

</div>

@endsection
