<div class="sk-scroll"><table>
    <thead><tr><th>Mahasiswa</th><th>Judul / dosen tujuan</th><th>Pengajuan</th><th>Status / keputusan</th><th>Tindakan</th></tr></thead>
    <tbody>@forelse($submissions as $submission)
        <tr>
            <td>{{ $submission->mahasiswa->nama }}<br><span class="sk-muted">{{ $submission->mahasiswa->nim }}<br>{{ $submission->mahasiswa->prodi?->nama_prodi ?? '-' }}</span></td>
            <td class="sk-title">{{ $submission->judul }}<p class="sk-muted">{{ $submission->dosen->nama }}</p></td>
            <td>{{ $submission->created_at->format('d/m/Y H:i') }}<p class="sk-muted">Oleh {{ $submission->pembuat->name }} ({{ $submission->jenis_pembuat }})</p></td>
            <td><span class="sk-badge sk-{{ $submission->status }}">{{ $submission->status }}</span>
                @if($submission->alasan_keputusan)<p>{{ $submission->alasan_keputusan }}</p>@endif
                @if($submission->diputuskan_pada)<p class="sk-muted">{{ $submission->diputuskan_pada->format('d/m/Y H:i') }}</p>@endif
                @if($submission->status === 'Menunggu' && $period && now()->gt($period->berakhir))<p class="sk-muted">Belum diputuskan; periode sudah ditutup.</p>@endif
            </td>
            <td>
                <a href="{{ route($role.'.skripsi.show', $submission) }}">Lihat riwayat</a>
                @if($submission->status === 'Menunggu' && ! $submission->mahasiswa->memenuhiSyaratSemesterSkripsi())
                    <p class="sk-muted">Belum memenuhi syarat semester {{ \App\Models\Mahasiswa::MIN_SEMESTER_SKRIPSI }} ke atas. Hubungi admin untuk memeriksa data semester.</p>
                @endif
                @if($role === 'dosen' && $submission->status === 'Menunggu' && $submission->mahasiswa->memenuhiSyaratSemesterSkripsi() && $period?->terbuka())
                    <details><summary>Berikan keputusan</summary>
                        <form class="sk-stack" method="POST" action="{{ route('dosen.skripsi.decide', $submission) }}" onsubmit="return confirm('Terima pengajuan ini dan menjadi pembimbing resmi?')">
                            @csrf @method('PUT')<input type="hidden" name="status" value="Diterima"><button>Terima pengajuan</button>
                        </form>
                        <form class="sk-stack" method="POST" action="{{ route('dosen.skripsi.decide', $submission) }}" onsubmit="return confirm('Tolak pengajuan ini dengan alasan yang telah diisi?')">
                            @csrf @method('PUT')<input type="hidden" name="status" value="Ditolak">
                            <label>Alasan penolakan<textarea name="alasan" required maxlength="2000">{{ old('alasan') }}</textarea></label><button class="sk-danger">Tolak pengajuan</button>
                        </form>
                    </details>
                @endif
            </td>
        </tr>
    @empty<tr><td colspan="5" class="sk-muted">Belum ada pengajuan sesuai pilihan ini.</td></tr>@endforelse</tbody>
</table></div>
@include('skripsi.partials.pagination', ['paginator' => $submissions])
