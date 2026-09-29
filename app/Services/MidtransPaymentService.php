<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Mahasiswa;
use App\Models\PaymentAccess;
use App\Models\PaymentWebhook;
use App\Models\PembayaranMahasiswa;
use App\Models\TagihanMahasiswa;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MidtransPaymentService implements PaymentGateway
{
    public function __construct(private readonly StudentBillingService $billing) {}

    public function ready(): bool
    {
        return config('payments.provider') === 'midtrans'
            && config('payments.enabled')
            && ! config('payments.is_production')
            && str_starts_with((string) config('payments.server_key'), 'SB-Mid-server-')
            && str_starts_with((string) config('payments.client_key'), 'SB-Mid-client-');
    }

    public function canPay(Mahasiswa $student): bool
    {
        return $this->ready() && $student->is_active
            && PaymentAccess::where('mahasiswa_id', $student->id)->where('enabled', true)->exists();
    }

    public function checkout(TagihanMahasiswa $bill, Mahasiswa $student, int $principal, string $bank): PembayaranMahasiswa
    {
        abort_unless($bill->mahasiswa_id === $student->id && $this->canPay($student) && $bill->is_test, 403, 'Pembayaran sandbox belum tersedia untuk akun ini.');
        if (! array_key_exists($bank, config('payments.va_methods', []))) {
            throw ValidationException::withMessages(['bank' => 'Bank Virtual Account tidak tersedia.']);
        }

        [$payment, $action] = DB::transaction(function () use ($bill, $principal, $bank) {
            $locked = TagihanMahasiswa::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $this->billing->assertGatewayPayablePrincipal($locked, $principal);
            $existing = $locked->pembayaran()->whereIn('status', PembayaranMahasiswa::OPEN_STATUSES)->lockForUpdate()->first();

            if ($existing && $existing->status === 'unknown'
                && $existing->created_at->lte(now()->subSeconds((int) config('payments.invoice_duration')))) {
                $existing->update(['status' => 'expired']);
                $existing = null;
            }

            if ($existing) {
                if ($existing->nominal_pokok !== $principal || $existing->payment_method !== $bank) {
                    throw ValidationException::withMessages(['amount' => 'Selesaikan transaksi yang masih berjalan sebelum mengganti nominal atau bank VA.']);
                }
                if ($existing->checkout_url || ($existing->status === 'creating' && $existing->updated_at->gt(now()->subSeconds(30)))) {
                    return [$existing, 'reuse'];
                }

                throw ValidationException::withMessages([
                    'gateway' => 'Status transaksi sebelumnya belum dapat dipastikan. Periksa order tersebut di dashboard Midtrans; sistem tidak membuat order baru agar tidak terjadi pembayaran ganda.',
                ]);
            }

            $payment = $locked->pembayaran()->create($this->billing->allocateGateway($locked, $principal) + [
                'external_id' => 'STTMI-TEST-'.Str::uuid(),
                'status' => 'creating',
                'source' => 'midtrans',
                'metode_pembayaran' => 'va_midtrans',
                'mahasiswa_id' => $locked->mahasiswa_id,
                'payment_method' => $bank,
                'is_test' => true,
            ]);

            return [$payment, 'create'];
        });

        if ($action === 'reuse') {
            return $payment;
        }

        try {
            $response = Http::withBasicAuth((string) config('payments.server_key'), '')
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->withOptions(['allow_redirects' => false])
                ->post((string) config('payments.snap_url'), $this->snapPayload($bill, $student, $payment));

            $remote = $response->json();
            if (! $response->successful() || ! $this->validSnapResponse($remote)) {
                $definitive = in_array($response->status(), [400, 401, 402, 406, 410, 422], true);
                PembayaranMahasiswa::whereKey($payment->id)->where('status', 'creating')->update([
                    'status' => $definitive ? 'failed' : 'unknown',
                    'updated_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'gateway' => $definitive
                        ? 'Midtrans menolak pembuatan transaksi. Hubungi admin untuk memeriksa konfigurasi atau nominal.'
                        : 'Transaksi belum dapat dipastikan. Periksa order yang sama di dashboard Midtrans dan jangan membuat pembayaran baru.',
                ]);
            }
        } catch (ConnectionException) {
            PembayaranMahasiswa::whereKey($payment->id)->where('status', 'creating')->update([
                'status' => 'unknown',
                'updated_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'gateway' => 'Midtrans belum merespons. Order disimpan untuk pemeriksaan agar tidak dibuat dua kali.',
            ]);
        }

        return DB::transaction(function () use ($bill, $payment, $remote) {
            TagihanMahasiswa::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $locked = PembayaranMahasiswa::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($locked->status, PembayaranMahasiswa::OPEN_STATUSES, true)) {
                $locked->update([
                    'invoice_id' => $remote['token'],
                    'checkout_url' => $remote['redirect_url'],
                    'expires_at' => now()->addSeconds((int) config('payments.invoice_duration')),
                    'status' => 'pending',
                ]);
            }

            return $locked;
        });
    }

    private function snapPayload(TagihanMahasiswa $bill, Mahasiswa $student, PembayaranMahasiswa $payment): array
    {
        $items = [[
            'id' => 'POKOK-'.$payment->id,
            'price' => $payment->nominal_pokok,
            'quantity' => 1,
            'name' => Str::limit('Pokok '.$bill->jenis_tagihan, 45, ''),
        ]];
        if ($payment->biaya_layanan > 0) {
            $items[] = [
                'id' => 'LAYANAN-'.$payment->id,
                'price' => $payment->biaya_layanan,
                'quantity' => 1,
                'name' => 'Biaya layanan VA',
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $payment->external_id,
                'gross_amount' => $payment->amount,
            ],
            'item_details' => $items,
            'customer_details' => [
                'first_name' => Str::limit($student->nama, 50, ''),
            ],
            // Allowlist exactly one selected VA method; never expose QRIS, cards,
            // wallets, retail outlets or paylater in the Snap transaction.
            'enabled_payments' => [$payment->payment_method],
            'callbacks' => [
                'finish' => route('mahasiswa.pembayaran.show', $bill),
            ],
            'expiry' => [
                'duration' => max(1, (int) ceil(config('payments.invoice_duration') / 3600)),
                'unit' => 'hour',
            ],
        ];

        $email = $student->email ?: $student->user?->email;
        if (is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload['customer_details']['email'] = $email;
        }

        return $payload;
    }

    private function validSnapResponse(mixed $data): bool
    {
        return is_array($data)
            && is_string($data['token'] ?? null)
            && preg_match('/^[a-zA-Z0-9_-]{20,100}$/D', $data['token'])
            && $this->safeCheckoutUrl($data['redirect_url'] ?? null);
    }

    public function safeCheckoutUrl(mixed $url): bool
    {
        if (! is_string($url) || strlen($url) > 2048 || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);

        return ($parts['scheme'] ?? '') === 'https'
            && strtolower($parts['host'] ?? '') === 'app.sandbox.midtrans.com'
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443);
    }

    public function sameAmount(mixed $amount, int $expected): bool
    {
        return is_scalar($amount)
            && preg_match('/^\d+(?:\.0+)?$/D', (string) $amount)
            && (float) $amount === (float) $expected;
    }

    public function validSignature(array $data): bool
    {
        if (config('payments.is_production') || ! str_starts_with((string) config('payments.server_key'), 'SB-Mid-server-')) {
            return false;
        }

        $expected = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].config('payments.server_key'));

        return hash_equals($expected, strtolower($data['signature_key']));
    }

    public function callback(array $data): array
    {
        $payment = PembayaranMahasiswa::where('external_id', $data['order_id'])
            ->where('source', 'midtrans')
            ->first();
        if (! $payment) {
            $this->logCallback($data, 'unknown_reference');

            return [404, 'Unknown order'];
        }

        return DB::transaction(function () use ($payment, $data) {
            $bill = TagihanMahasiswa::whereKey($payment->tagihan_mahasiswa_id)->lockForUpdate()->firstOrFail();
            $locked = PembayaranMahasiswa::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $merchantMatches = blank(config('payments.merchant_id'))
                || (filled($data['merchant_id'] ?? null)
                    && hash_equals((string) config('payments.merchant_id'), (string) $data['merchant_id']));

            if (! $locked->invoice_id) {
                $this->logCallback($data, 'transaction_not_ready');

                return [409, 'Transaction registration pending; retry'];
            }
            if (! $locked->is_test || ! $bill->is_test || ! $merchantMatches
                || ! $this->sameAmount($data['gross_amount'], $locked->amount)
                || (($data['currency'] ?? 'IDR') !== 'IDR')) {
                $this->logCallback($data, 'mismatch');

                return [422, 'Order or amount mismatch'];
            }

            $status = $data['transaction_status'];
            $fraud = $data['fraud_status'] ?? null;
            $fraudAccepted = in_array($fraud, [null, 'accept'], true);
            $isPaid = in_array($status, ['settlement', 'capture'], true) && $fraudAccepted;
            if ($locked->status === 'paid') {
                $this->logCallback($data, 'duplicate_or_stale');

                return [200, 'Already processed'];
            }

            $needsReview = false;
            if ($isPaid) {
                $needsReview = $bill->status === 'dibatalkan'
                    || $locked->nominal_pokok > $this->billing->remainingPrincipal($bill);
                $locked->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::parse($data['settlement_time'] ?? $data['transaction_time'] ?? now()),
                    // Keep the selected VA bank. Midtrans usually returns the
                    // generic `bank_transfer` payment_type in the callback.
                    'payment_method' => $locked->payment_method ?: ($data['payment_type'] ?? 'bank_transfer'),
                    'provider_payment_id' => $data['transaction_id'] ?? null,
                ]);
                $this->billing->recalculate($bill);
                $this->billing->audit(
                    null,
                    $bill,
                    'midtrans_payment_paid',
                    $needsReview ? 'Perlu review admin: pembayaran valid terlambat atau melebihi sisa tagihan.' : null,
                    ['amount' => $locked->amount, 'order_id' => $locked->external_id, 'needs_review' => $needsReview],
                    $locked,
                );
            } elseif ($status === 'pending' || ($status === 'capture' && $fraud === 'challenge')) {
                $locked->update([
                    'status' => 'pending',
                    'payment_method' => $locked->payment_method ?: ($data['payment_type'] ?? 'bank_transfer'),
                    'provider_payment_id' => $data['transaction_id'] ?? $locked->provider_payment_id,
                ]);
            } elseif ($status === 'expire') {
                $locked->update(['status' => 'expired']);
            } elseif (in_array($status, ['cancel', 'deny', 'failure'], true)
                || ($status === 'capture' && $fraud === 'deny')) {
                $locked->update(['status' => 'failed']);
            }

            $this->logCallback($data, $needsReview ? 'processed_requires_review' : 'processed');

            return [200, 'Processed'];
        });
    }

    public function logCallback(array $data, string $outcome): void
    {
        // Allowlist only: never store signatures, keys, card/VA details or arbitrary payload fields.
        $safe = array_intersect_key($data, array_flip([
            'order_id', 'transaction_id', 'transaction_status', 'status_code', 'gross_amount',
            'currency', 'fraud_status', 'payment_type', 'transaction_time', 'settlement_time', 'merchant_id',
        ]));
        ksort($safe);
        PaymentWebhook::upsert([[
            'event_key' => hash('sha256', json_encode($safe)),
            'invoice_id' => $safe['transaction_id'] ?? null,
            'external_id' => $safe['order_id'] ?? null,
            'status' => $safe['transaction_status'] ?? null,
            'safe_payload' => json_encode($safe),
            'outcome' => $outcome,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['event_key'], ['outcome', 'updated_at']);
    }
}
