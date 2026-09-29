<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Contracts\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\TagihanMahasiswa;
use App\Services\StudentBillingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user()->mahasiswa;
        abort_unless($student, 403);
        $query = TagihanMahasiswa::where('mahasiswa_id', $student->id);
        $totals = (clone $query)->where('status', '!=', 'dibatalkan')->selectRaw('COALESCE(SUM(total_tagihan),0) as total, COALESCE(SUM(total_dibayar),0) as paid, COALESCE(SUM(sisa_tagihan),0) as remaining')->first();

        return view('mahasiswa.pembayaran.index', ['bills' => $query->latest('id')->paginate(20)->appends($request->query()), 'totals' => $totals]);
    }

    public function show(Request $request, TagihanMahasiswa $tagihan, PaymentGateway $gateway)
    {
        $student = $request->user()->mahasiswa;
        abort_unless($student && $tagihan->mahasiswa_id === $student->id, 404);

        return view('mahasiswa.pembayaran.show', [
            'bill' => $tagihan,
            'payments' => $tagihan->pembayaran()->latest('id')->get(),
            'canPay' => $gateway->canPay($student),
            'remainingPrincipal' => app(StudentBillingService::class)->remainingPrincipal($tagihan),
            'vaMethods' => config('payments.va_methods'),
            'defaultVaMethod' => config('payments.default_va_method'),
            'minimumPayment' => (int) config('payments.minimum_payment'),
        ]);
    }

    public function pay(Request $request, TagihanMahasiswa $tagihan, PaymentGateway $gateway)
    {
        $student = $request->user()->mahasiswa;
        abort_unless($student && $tagihan->mahasiswa_id === $student->id, 404);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:'.config('payments.minimum_payment'), 'max:1000000000'],
            'bank' => ['required', Rule::in(array_keys(config('payments.va_methods', [])))],
        ], ['amount.min' => 'Nominal pembayaran minimal Rp600.000.']);
        $payment = $gateway->checkout($tagihan, $student, (int) $data['amount'], $data['bank']);
        if (! $gateway->safeCheckoutUrl($payment->checkout_url)) {
            return back()->with('success', 'Invoice sedang diproses. Tunggu sebentar lalu periksa kembali.');
        }

        return redirect()->away($payment->checkout_url);
    }
}
