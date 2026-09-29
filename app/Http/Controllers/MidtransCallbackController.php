<?php

namespace App\Http\Controllers;

use App\Services\MidtransPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MidtransCallbackController extends Controller
{
    public function __invoke(Request $request, MidtransPaymentService $gateway)
    {
        if (strlen($request->getContent()) > 65536) {
            return response()->json(['message' => 'Payload too large'], 413);
        }

        $validator = Validator::make($request->all(), [
            'order_id' => ['required', 'string', 'max:80', 'regex:/^[a-zA-Z0-9._-]+$/D'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'transaction_status' => ['required', Rule::in(['pending', 'settlement', 'capture', 'expire', 'cancel', 'deny', 'failure'])],
            'status_code' => ['required', 'string', 'size:3', 'regex:/^\d{3}$/D'],
            'gross_amount' => ['required', 'numeric', 'min:1', 'max:1000000000'],
            'signature_key' => ['required', 'string', 'size:128', 'regex:/^[a-fA-F0-9]{128}$/D'],
            'currency' => ['nullable', Rule::in(['IDR'])],
            'fraud_status' => ['nullable', Rule::in(['accept', 'deny', 'challenge'])],
            'payment_type' => ['nullable', 'string', 'max:100'],
            'transaction_time' => ['nullable', 'date'],
            'settlement_time' => ['nullable', 'date'],
            'merchant_id' => ['nullable', 'string', 'max:100'],
        ]);
        if ($validator->fails()) {
            Log::warning('Midtrans callback rejected: invalid payload schema.');

            return response()->json(['message' => 'Invalid payload'], 422);
        }

        $data = $validator->validated();
        if (! $gateway->validSignature($data)) {
            Log::warning('Midtrans callback rejected: invalid signature or unsupported environment.');

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        [$status, $message] = $gateway->callback($data);

        return response()->json(['message' => $message], $status);
    }
}
