<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Krs;
use App\Models\Kuesioner;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KuesionerController extends Controller
{
    public function index()
    {
        $mahasiswa = $this->mahasiswa();

        $krs = Krs::with([
            'jadwal.mataKuliah',
            'jadwal.dosen',
            'jadwal.kelas',
            'khs',
            'kuesioner',
        ])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('status', 'Disetujui')
            ->whereHas('khs')
            ->orderByDesc('tahun_akademik')
            ->orderByDesc('semester_akademik')
            ->get();

        return view('mahasiswa.kuesioner.index', compact('mahasiswa', 'krs'));
    }

    public function create(Krs $krs)
    {
        $mahasiswa = $this->mahasiswa();
        $this->pastikanBolehMengisi($krs, $mahasiswa->id);

        if ($krs->kuesioner()->exists()) {
            return redirect()
                ->route('mahasiswa.kuesioner')
                ->with('info', 'Kuesioner untuk mata kuliah tersebut sudah pernah dikirim.');
        }

        $krs->load(['jadwal.mataKuliah', 'jadwal.dosen', 'jadwal.kelas']);
        $pertanyaan = Kuesioner::PERTANYAAN;

        return view('mahasiswa.kuesioner.form', compact('krs', 'pertanyaan'));
    }

    public function store(Request $request, Krs $krs)
    {
        $mahasiswa = $this->mahasiswa();
        $this->pastikanBolehMengisi($krs, $mahasiswa->id);

        if ($krs->kuesioner()->exists()) {
            return redirect()
                ->route('mahasiswa.kuesioner')
                ->with('info', 'Kuesioner untuk mata kuliah tersebut sudah pernah dikirim.');
        }

        $aturan = collect(array_keys(Kuesioner::PERTANYAAN))
            ->mapWithKeys(fn (string $kolom) => [$kolom => ['required', 'integer', 'between:1,5']])
            ->all();
        $aturan['komentar'] = ['nullable', 'string', 'max:2000'];

        $data = $request->validate($aturan, [
            '*.required' => 'Semua pertanyaan wajib dijawab.',
            '*.between' => 'Jawaban harus berada pada skala 1 sampai 5.',
        ]);

        $krs->kuesioner()->create([
            ...$data,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('mahasiswa.khs')
            ->with('success', 'Kuesioner berhasil dikirim. Nilai mata kuliah tersebut sekarang terbuka; IPS dan IPK terbuka setelah seluruh kuesioner selesai.');
    }

    private function mahasiswa(): Mahasiswa
    {
        abort_unless(Auth::user()?->role === 'mahasiswa', 403);

        return Mahasiswa::where('user_id', Auth::id())->firstOrFail();
    }

    private function pastikanBolehMengisi(Krs $krs, int $mahasiswaId): void
    {
        abort_unless(
            $krs->mahasiswa_id === $mahasiswaId
            && $krs->status === 'Disetujui'
            && $krs->khs()->exists(),
            403
        );
    }
}
