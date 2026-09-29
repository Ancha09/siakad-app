<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\PaymentAccess;
use App\Models\PaymentWebhook;
use App\Models\PembayaranMahasiswa;
use App\Models\Prodi;
use App\Models\TagihanMahasiswa;
use App\Services\PaymentSpreadsheetService;
use App\Services\StudentBillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request, PaymentGateway $gateway)
    {
        $query = $this->query($request);
        $totals = (clone $query)->where('status', '!=', 'dibatalkan')->selectRaw('COALESCE(SUM(total_tagihan),0) as total, COALESCE(SUM(total_dibayar),0) as paid, COALESCE(SUM(sisa_tagihan),0) as remaining')->first();
        $billIds = (clone $query)->select('id');
        $transactions = PembayaranMahasiswa::query()->whereIn('tagihan_mahasiswa_id', $billIds)->where('status', 'paid');
        $paymentTotals = [
            'va' => (int) (clone $transactions)->where(fn ($q) => $q->where('metode_pembayaran', 'va_midtrans')->orWhere(fn ($legacy) => $legacy->whereNull('metode_pembayaran')->where('source', 'midtrans')))->sum('amount'),
            'cash' => (int) (clone $transactions)->where(fn ($q) => $q->where('metode_pembayaran', 'cash')->orWhereIn('source', ['cash', 'manual']))->sum('amount'),
            'fees' => (int) (clone $transactions)->sum('biaya_layanan'),
        ];

        return view('admin.pembayaran.index', [
            'bills' => $query->with('mahasiswa.prodi')->latest('id')->paginate(20)->appends($request->query()),
            'totals' => $totals, 'paymentTotals' => $paymentTotals, 'prodis' => Prodi::orderBy('nama_prodi')->get(), 'gatewayReady' => $gateway->ready(),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.pembayaran.create', [
            'students' => $this->studentQuery($request)->with('prodi')->orderBy('nama')->paginate(50)->appends($request->query()),
            'prodis' => Prodi::orderBy('nama_prodi')->get(), 'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, StudentBillingService $billing)
    {
        $data = $this->fields($request);
        $selection = $request->validate([
            'mahasiswa_ids' => ['required', 'array', 'min:1', 'max:500'],
            'mahasiswa_ids.*' => ['required', 'integer', 'distinct', 'exists:mahasiswas,id'],
            'request_key' => ['required', 'uuid'], 'confirm_duplicates' => ['sometimes', 'accepted'],
        ]);
        $rows = array_map(fn ($id) => $data + ['mahasiswa_id' => (int) $id], $selection['mahasiswa_ids']);
        $count = $billing->createMany($rows, $request->user(), $selection['request_key'], $request->boolean('confirm_duplicates'));

        return redirect()->route('admin.pembayaran.index')->with('success', "{$count} tagihan sandbox dibuat.");
    }

    public function show(TagihanMahasiswa $tagihan)
    {
        $tagihan->load('mahasiswa.prodi');

        return view('admin.pembayaran.show', [
            'bill' => $tagihan, 'payments' => $tagihan->pembayaran()->latest('id')->get(),
            'audits' => $tagihan->audits()->with('actor')->latest('id')->limit(100)->get(),
            'callbacks' => PaymentWebhook::whereIn('external_id', $tagihan->pembayaran()->select('external_id'))->latest('id')->limit(30)->get(),
            'requestKey' => (string) Str::uuid(),
            'remainingPrincipal' => app(StudentBillingService::class)->remainingPrincipal($tagihan),
        ]);
    }

    public function update(Request $request, TagihanMahasiswa $tagihan, StudentBillingService $billing)
    {
        $billing->update($tagihan, $this->fields($request), $request->user(), $this->reason($request));

        return back()->with('success', 'Tagihan diperbarui dan dicatat di audit.');
    }

    public function cancel(Request $request, TagihanMahasiswa $tagihan, StudentBillingService $billing)
    {
        $billing->cancel($tagihan, $request->user(), $this->reason($request));

        return back()->with('success', 'Tagihan dibatalkan. Riwayat tetap tersimpan.');
    }

    public function manual(Request $request, TagihanMahasiswa $tagihan, StudentBillingService $billing)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'nomor_referensi' => ['required', 'string', 'max:100', 'regex:/\S/u'],
            'catatan' => ['required', 'string', 'min:5', 'max:2000', 'regex:/\S/u'],
            'bukti' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max('2mb')],
            'confirm_overpayment' => ['sometimes', 'accepted'],
            'request_key' => ['required', 'uuid'],
        ]);
        $proofPath = $request->file('bukti')?->store('payment-cash-receipts', 'local');

        try {
            $created = $billing->cashPayment(
                $tagihan,
                (int) $data['amount'],
                $data['paid_at'],
                $data['nomor_referensi'],
                $data['catatan'],
                $proofPath,
                $data['request_key'],
                $request->user(),
                $request->boolean('confirm_overpayment'),
            );
            if (! $created && $proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
        } catch (Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        return back()->with('success', 'Pembayaran cash dicatat dan audit admin tersimpan.');
    }

    public function void(Request $request, PembayaranMahasiswa $pembayaran, StudentBillingService $billing)
    {
        $billing->voidManual($pembayaran, $request->user(), $this->reason($request));

        return back()->with('success', 'Pembayaran manual dikoreksi. Catatan transaksi tetap tersimpan.');
    }

    public function access(Request $request)
    {
        $students = $this->studentQuery($request)->orderBy('nama')->paginate(50)->appends($request->query());

        return view('admin.pembayaran.access', [
            'students' => $students, 'prodis' => Prodi::orderBy('nama_prodi')->get(),
            'accesses' => PaymentAccess::whereIn('mahasiswa_id', $students->pluck('id'))->get()->keyBy('mahasiswa_id'),
        ]);
    }

    public function updateAccess(Request $request, Mahasiswa $mahasiswa, StudentBillingService $billing)
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        DB::transaction(function () use ($request, $mahasiswa, $data, $billing) {
            Mahasiswa::whereKey($mahasiswa->id)->lockForUpdate()->firstOrFail();
            PaymentAccess::updateOrCreate(['mahasiswa_id' => $mahasiswa->id], ['enabled' => $data['enabled'], 'updated_by' => $request->user()->id]);
            $billing->audit($request->user(), null, 'ubah_early_access', null, ['mahasiswa_id' => $mahasiswa->id, 'enabled' => (bool) $data['enabled']]);
        });

        return back()->with('success', 'Akses sandbox diperbarui.');
    }

    public function export(Request $request, string $format, PaymentSpreadsheetService $sheets)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        $query = $this->query($request);
        $limit = $format === 'pdf' ? 1000 : 5000;
        if ((clone $query)->count() > $limit) {
            return back()->withErrors(['export' => "Maksimal {$limit} tagihan per export. Persempit filter terlebih dahulu."]);
        }
        $bills = $query->with(['mahasiswa.prodi', 'pembayaran' => fn ($q) => $q->where('status', 'paid')->orderBy('id')])->orderBy('id')->get();
        if ($format === 'pdf') {
            return Pdf::loadView('payments.report-pdf', ['bills' => $bills, 'filters' => $request->only(['search', 'prodi_id', 'angkatan', 'status'])])
                ->setOption('isRemoteEnabled', false)->setPaper('a4', 'landscape')->download('laporan-tagihan-sandbox.pdf');
        }
        $rows = $bills->map(function ($bill) {
            $paid = $bill->pembayaran;
            $va = $paid->filter(fn ($payment) => $payment->metode_pembayaran === 'va_midtrans' || ($payment->metode_pembayaran === null && $payment->source === 'midtrans'));
            $cash = $paid->filter(fn ($payment) => $payment->metode_pembayaran === 'cash' || in_array($payment->source, ['cash', 'manual'], true));

            return [$bill->kode_tagihan, $bill->mahasiswa?->nim, $bill->mahasiswa?->nama, $bill->jenis_tagihan, $bill->deskripsi, $bill->nominal_pokok, $bill->biaya_layanan, $bill->total_tagihan, $va->sum('amount'), $cash->sum('amount'), $paid->sum('biaya_layanan'), $bill->total_dibayar, $bill->sisa_tagihan, $paid->map->metode_label->unique()->implode('; '), $paid->map(fn ($payment) => $payment->nomor_referensi ?: $payment->external_id)->implode('; '), $bill->status_tampil, $bill->jatuh_tempo?->format('Y-m-d'), 'SANDBOX'];
        });

        return $sheets->download('laporan-tagihan-sandbox.xlsx', ['Kode', 'NIM', 'Nama', 'Jenis', 'Deskripsi', 'Pokok', 'Biaya layanan per transaksi', 'Total', 'Dibayar VA', 'Dibayar Cash', 'Total biaya layanan', 'Dibayar keseluruhan', 'Sisa', 'Metode pembayaran', 'Nomor referensi / kwitansi', 'Status', 'Jatuh tempo', 'Mode'], $rows);
    }

    private function fields(Request $request): array
    {
        $data = $request->validate([
            'jenis_tagihan' => ['required', 'string', 'max:100'], 'deskripsi' => ['required', 'string', 'max:2000'],
            'nominal_pokok' => ['required', 'integer', 'min:'.config('payments.minimum_payment'), 'max:1000000000'],
            'biaya_layanan' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'jatuh_tempo' => ['nullable', 'date_format:Y-m-d'], 'boleh_cicil' => ['required', 'boolean'],
        ], ['nominal_pokok.min' => 'Nominal pembayaran minimal Rp600.000.']);
        $data['jatuh_tempo'] = $data['jatuh_tempo'] ?? null;

        return $data;
    }

    private function reason(Request $request): string
    {
        return $request->validate(['alasan' => ['required', 'string', 'min:5', 'max:1000', 'regex:/\S/u']])['alasan'];
    }

    private function studentQuery(Request $request): Builder
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'prodi_id' => ['nullable', 'integer', 'exists:prodis,id'], 'angkatan' => ['nullable', 'integer', 'between:1900,2100']]);

        return Mahasiswa::query()
            ->when($filters['search'] ?? null, fn ($q, $value) => $q->where(fn ($s) => $s->where('nim', 'like', '%'.$value.'%')->orWhere('nama', 'like', '%'.$value.'%')))
            ->when($filters['prodi_id'] ?? null, fn ($q, $value) => $q->where('prodi_id', $value))
            ->when($filters['angkatan'] ?? null, fn ($q, $value) => $q->where('angkatan', $value));
    }

    private function query(Request $request): Builder
    {
        $students = $this->studentQuery($request);
        $filters = $request->validate(['status' => ['nullable', Rule::in(['belum_dibayar', 'sebagian_dibayar', 'lunas', 'expired', 'dibatalkan'])]]);

        return TagihanMahasiswa::query()->whereIn('mahasiswa_id', $students->select('id'))
            ->when($filters['status'] ?? null, function ($query, $status) {
                if ($status === 'expired') {
                    $query->where('status', 'belum_dibayar')->whereDate('jatuh_tempo', '<', today());
                } elseif ($status === 'belum_dibayar') {
                    $query->where('status', $status)->where(fn ($due) => $due->whereNull('jatuh_tempo')->orWhereDate('jatuh_tempo', '>=', today()));
                } else {
                    $query->where('status', $status);
                }
            });
    }
}
