<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodeKrs;
use App\Services\KrsApprovalResetService;
use App\Services\LegacyListNavigation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KrsApprovalResetController extends Controller
{
    public function __construct(
        private readonly KrsApprovalResetService $resets,
        private readonly LegacyListNavigation $navigation,
    ) {}

    public function reset(Request $request, PeriodeKrs $periodeKrs)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['single', 'selected', 'all'])],
            'mahasiswa_id' => ['required_if:mode,single', 'nullable', 'integer', 'exists:mahasiswas,id'],
            'mahasiswa_ids' => ['required_if:mode,selected', 'array', 'max:500'],
            'mahasiswa_ids.*' => ['integer', 'distinct', 'exists:mahasiswas,id'],
            'alasan' => ['required', 'string', 'min:5', 'max:1000', 'regex:/\S/u'],
            'return_url' => ['nullable', 'string', 'max:4096'],
        ], [
            'alasan.required' => 'Alasan reset persetujuan KRS wajib diisi.',
            'alasan.min' => 'Alasan reset minimal 5 karakter.',
        ]);

        $count = 0;
        $mode = $data['mode'];
        $ids = match ($mode) {
            'single' => [(int) $data['mahasiswa_id']],
            'selected' => array_map('intval', $data['mahasiswa_ids']),
            default => [],
        };

        if ($mode === 'all') {
            $this->resets->eligibleStudents($periodeKrs)->select('id')->chunkById(100, function ($students) use ($periodeKrs, $request, $data, &$count) {
                foreach ($students as $student) {
                    $count += (int) $this->resets->resetStudent($periodeKrs, $student->id, $request->user(), trim($data['alasan']), 'semua');
                }
            });
        } else {
            foreach ($ids as $id) {
                $count += (int) $this->resets->resetStudent($periodeKrs, $id, $request->user(), trim($data['alasan']), $mode === 'single' ? 'satu' : 'terpilih');
            }
        }

        return redirect()->to($this->navigation->returnUrl($request, 'admin.krs-mahasiswa.index'))
            ->with($count ? 'success' : 'error', $count
                ? "Persetujuan KRS {$count} mahasiswa dikembalikan untuk koreksi. Notifikasi dikirim kepada mahasiswa terkait."
                : 'Tidak ada KRS yang perlu direset pada periode tersebut.');
    }
}
