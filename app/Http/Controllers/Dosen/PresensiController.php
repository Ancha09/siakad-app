<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Jadwal;
use App\Models\Krs;
use App\Models\Presensi;
use App\Models\PresensiPertemuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PresensiController extends Controller
{
    // =========================================================
    // DAFTAR JADWAL DOSEN
    // =========================================================

    public function index()
    {
        $dosen = Dosen::where(
            'user_id',
            Auth::id()
        )->firstOrFail();

        $jadwals = Jadwal::with([
            'mataKuliah',
            'ruangan',
            'kelas',
        ])
        ->where(
            'dosen_id',
            $dosen->id
        )
        ->orderBy('hari')
        ->orderBy('jam_mulai')
        ->get();

        return view(
            'dosen.presensi.index',
            compact('jadwals')
        );
    }


    // =========================================================
    // HALAMAN PRESENSI
    // =========================================================

    public function show(
        Request $request,
        Jadwal $jadwal
    ) {
        // ===================== DOSEN =====================

        $dosen = Dosen::where(
            'user_id',
            Auth::id()
        )->firstOrFail();


        // ===================== CEK JADWAL =====================

        if (
            $jadwal->dosen_id !=
            $dosen->id
        ) {
            abort(403);
        }


        // ===================== RELASI JADWAL =====================

        $jadwal->load([
            'mataKuliah',
            'ruangan',
            'kelas',
        ]);


        // =====================================================
        // PERTEMUAN YANG DIPILIH
        // =====================================================

        $pertemuanDipilih =
            (int) $request->get(
                'pertemuan',
                1
            );


        if (
            $pertemuanDipilih < 1 ||
            $pertemuanDipilih > 16
        ) {
            $pertemuanDipilih = 1;
        }


        // =====================================================
        // CARI SESI PERTEMUAN
        // =====================================================

        $pertemuanAktif =
            PresensiPertemuan::where(
                'jadwal_id',
                $jadwal->id
            )
            ->where(
                'pertemuan',
                $pertemuanDipilih
            )
            ->first();


        // =====================================================
        // DATA KRS MAHASISWA
        // =====================================================

        $krs = Krs::with([
            'mahasiswa',
            'presensis',
        ])
        ->where('is_manual', false)
        ->where(
            'jadwal_id',
            $jadwal->id
        )
        ->get();


        // =====================================================
        // REKAP PRESENSI
        // =====================================================

        foreach ($krs as $item) {

            $item->hadir =
                $item->presensis
                    ->where(
                        'status',
                        'Hadir'
                    )
                    ->count();


            $item->izin =
                $item->presensis
                    ->where(
                        'status',
                        'Izin'
                    )
                    ->count();


            $item->sakit =
                $item->presensis
                    ->where(
                        'status',
                        'Sakit'
                    )
                    ->count();


            $item->alpha =
                $item->presensis
                    ->where(
                        'status',
                        'Alpha'
                    )
                    ->count();


            $total =
                $item->hadir +
                $item->izin +
                $item->sakit +
                $item->alpha;


            $item->persentase =
                $total > 0
                    ? round(
                        ($item->hadir / $total) * 100,
                        1
                    )
                    : 0;
        }


        // =====================================================
        // PERTEMUAN YANG SUDAH DIBUAT
        // =====================================================

        $pertemuanSudahAda =
            PresensiPertemuan::where(
                'jadwal_id',
                $jadwal->id
            )
            ->orderBy('pertemuan')
            ->pluck('pertemuan')
            ->toArray();


        return view(
            'dosen.presensi.input',
            compact(
                'jadwal',
                'krs',
                'pertemuanAktif',
                'pertemuanDipilih',
                'pertemuanSudahAda'
            )
        );
    }


    // =========================================================
    // SIMPAN / UPDATE PRESENSI
    // =========================================================

    public function store(
        Request $request
    ) {
        // =====================================================
        // VALIDASI
        // =====================================================

        $request->validate([

            'jadwal_id' =>
                'required|exists:jadwals,id',

            'krs_id' =>
                'required|array',

            'krs_id.*' =>
                'required|exists:krs,id',

            'status' =>
                'required|array',

            'status.*' =>
                'required|in:Hadir,Izin,Sakit,Alpha',

            'pertemuan' =>
                'required|integer|min:1|max:16',

            'tanggal' =>
                'required|date',

            'foto' =>
                'nullable|image|mimes:jpg,jpeg,png|max:2048',

            'materi' =>
                'nullable|file|mimes:pdf,ppt,pptx,doc,docx,xls,xlsx|max:10240',

        ], [

            'foto.max' =>
                'Foto maksimal 2 MB.',

            'foto.mimes' =>
                'Foto harus JPG, JPEG, atau PNG.',

            'materi.mimes' =>
                'Format materi tidak diperbolehkan.',

            'materi.max' =>
                'Materi maksimal 10 MB.',

        ]);


        // =====================================================
        // DOSEN
        // =====================================================

        $dosen = Dosen::where(
            'user_id',
            Auth::id()
        )->firstOrFail();


        // =====================================================
        // JADWAL
        // =====================================================

        $jadwal = Jadwal::findOrFail(
            $request->jadwal_id
        );


        if (
            $jadwal->dosen_id !=
            $dosen->id
        ) {
            abort(403);
        }


        // =====================================================
        // CEK KRS
        // =====================================================

        $krs = Krs::where(
            'jadwal_id',
            $jadwal->id
        )
        ->where('is_manual', false)
        ->whereIn(
            'id',
            $request->krs_id
        )
        ->get();


        if (
            $krs->count() !=
            count($request->krs_id)
        ) {
            return back()
                ->with(
                    'error',
                    'Data mahasiswa tidak sesuai dengan jadwal.'
                )
                ->withInput();
        }


        // =====================================================
        // CARI PERTEMUAN
        // =====================================================

        if (Presensi::whereIn('krs_id', $request->krs_id)->where('pertemuan', $request->pertemuan)->where('is_manual', true)->exists()) {
            return back()->with('error', 'Absensi lama/manual hanya dapat dikoreksi oleh admin.')->withInput();
        }

        $pertemuan =
            PresensiPertemuan::where(
                'jadwal_id',
                $jadwal->id
            )
            ->where(
                'pertemuan',
                $request->pertemuan
            )
            ->first();


        // =====================================================
        // JIKA PERTEMUAN SUDAH ADA
        // HANYA STATUS MAHASISWA YANG BOLEH DIUBAH
        // =====================================================

        if ($pertemuan) {

            foreach (
                $request->krs_id
                as $i => $krsId
            ) {

                Presensi::updateOrCreate(

                    [
                        'krs_id' =>
                            $krsId,

                        'pertemuan' =>
                            $pertemuan->pertemuan,
                    ],

                    [
                        'tanggal' =>
                            $pertemuan->tanggal,

                        'status' =>
                            $request->status[$i],
                    ]

                );
            }


            return redirect()
                ->route(
                    'dosen.presensi.show',
                    [
                        'jadwal' =>
                            $jadwal->id,

                        'pertemuan' =>
                            $pertemuan->pertemuan,
                    ]
                )
                ->with(
                    'success',
                    'Status presensi mahasiswa berhasil diperbarui.'
                );
        }


        // =====================================================
        // PERTEMUAN BELUM ADA
        // BUAT SESI BARU
        // =====================================================

        DB::transaction(function () use (
            $request,
            $jadwal
        ) {

            // ===================== FOTO =====================

            $fotoPath = null;

            if (
                $request->hasFile('foto')
            ) {

                $fotoPath =
                    $request
                        ->file('foto')
                        ->store(
                            'presensi/foto',
                            'public'
                        );
            }


            // ===================== MATERI =====================

            $materiPath = null;

            if (
                $request->hasFile('materi')
            ) {

                $materiPath =
                    $request
                        ->file('materi')
                        ->store(
                            'presensi/materi',
                            'public'
                        );
            }


            // ===================== SESI =====================

            $pertemuan =
                PresensiPertemuan::create([

                    'jadwal_id' =>
                        $jadwal->id,

                    'pertemuan' =>
                        $request->pertemuan,

                    'tanggal' =>
                        $request->tanggal,

                    'foto' =>
                        $fotoPath,

                    'materi' =>
                        $materiPath,

                ]);


            // ===================== PRESENSI MAHASISWA =====================

            foreach (
                $request->krs_id
                as $i => $krsId
            ) {

                Presensi::updateOrCreate(

                    [
                        'krs_id' =>
                            $krsId,

                        'pertemuan' =>
                            $pertemuan->pertemuan,
                    ],

                    [
                        'tanggal' =>
                            $pertemuan->tanggal,

                        'status' =>
                            $request->status[$i],
                    ]

                );
            }

        });


        return redirect()
            ->route(
                'dosen.presensi.show',
                [
                    'jadwal' =>
                        $jadwal->id,

                    'pertemuan' =>
                        $request->pertemuan,
                ]
            )
            ->with(
                'success',
                'Presensi Pertemuan ' .
                $request->pertemuan .
                ' berhasil disimpan.'
            );
    }
}
