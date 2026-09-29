<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentSpreadsheetService;
use App\Services\StudentBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentImportController extends Controller
{
    public function index(Request $request)
    {
        $token = $request->validate(['preview' => ['nullable', 'uuid']])['preview'] ?? null;
        $preview = $token ? Cache::get('payment-import:'.$request->user()->id.':'.$token) : null;

        return view('admin.pembayaran.import', $preview ? [
            'rows' => $preview['rows'], 'rowErrors' => $preview['errors'],
            'duplicates' => $preview['duplicates'], 'token' => $token,
        ] : []);
    }

    public function template(PaymentSpreadsheetService $sheets)
    {
        return $sheets->download('template-tagihan.xlsx', PaymentSpreadsheetService::HEADINGS, [['GANTI_NIM', '', 'SPP', 'SPP semester berjalan', 1000000, config('payments.default_service_fee'), '', 'true']]);
    }

    public function preview(Request $request, PaymentSpreadsheetService $sheets)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:5120']]);
        try {
            $preview = $sheets->preview($request->file('file'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['file' => 'Workbook tidak dapat dibaca. Gunakan template XLSX tanpa password atau rumus.']);
        }
        if ($previous = $request->session()->get('payment_import_token')) {
            Cache::forget('payment-import:'.$request->user()->id.':'.$previous);
        }
        $token = (string) Str::uuid();
        Cache::put('payment-import:'.$request->user()->id.':'.$token, $preview, now()->addMinutes(30));
        $request->session()->put('payment_import_token', $token);

        return redirect()->route('admin.pembayaran.import', ['preview' => $token]);
    }

    public function confirm(Request $request, StudentBillingService $billing)
    {
        $data = $request->validate(['token' => ['required', 'uuid'], 'confirm_duplicates' => ['sometimes', 'accepted']]);
        $key = 'payment-import:'.$request->user()->id.':'.$data['token'];
        $preview = Cache::get($key);
        if (! $preview || $preview['errors'] || ! $preview['rows']) {
            throw ValidationException::withMessages(['file' => 'Preview kedaluwarsa atau belum valid. Upload dan preview ulang.']);
        }
        $count = $billing->createMany($preview['rows'], $request->user(), $data['token'], $request->boolean('confirm_duplicates'));
        Cache::forget($key);
        $request->session()->forget('payment_import_token');

        return redirect()->route('admin.pembayaran.index')->with('success', "{$count} tagihan sandbox diimport.");
    }
}
