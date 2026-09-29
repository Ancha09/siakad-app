<?php

use App\Models\Mahasiswa;
use App\Models\PaymentAccess;
use App\Models\PaymentAudit;
use App\Models\PaymentWebhook;
use App\Models\PembayaranMahasiswa;
use App\Models\Prodi;
use App\Models\TagihanMahasiswa;
use App\Models\User;
use App\Services\PaymentSpreadsheetService;
use App\Services\StudentBillingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    Http::preventStrayRequests();
    config([
        'payments.provider' => 'midtrans', 'payments.enabled' => true, 'payments.is_production' => false,
        'payments.server_key' => 'SB-Mid-server-fake-for-tests',
        'payments.client_key' => 'SB-Mid-client-fake-for-tests',
        'payments.merchant_id' => 'G123456789',
        'payments.snap_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        'payments.va_methods' => ['bni_va' => 'BNI', 'echannel' => 'Mandiri', 'bri_va' => 'BRI', 'bca_va' => 'BCA', 'permata_va' => 'Permata'],
        'payments.default_va_method' => 'bni_va',
        'payments.minimum_payment' => 600000,
        'payments.default_service_fee' => 4000,
    ]);
});

function billingFixture(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'mahasiswa']);
    $other = User::factory()->create(['role' => 'mahasiswa']);
    $lecturer = User::factory()->create(['role' => 'dosen']);
    $prodi = Prodi::create(['kode_prodi' => 'BILL-TP', 'nama_prodi' => 'Teknik Pertambangan', 'jenjang' => 'S1']);
    $student = Mahasiswa::create(['nim' => '00123001', 'nama' => 'Mahasiswa Billing Satu', 'user_id' => $user->id, 'prodi_id' => $prodi->id, 'angkatan' => 2026, 'is_active' => true]);
    $otherStudent = Mahasiswa::create(['nim' => '00123002', 'nama' => 'Mahasiswa Billing Dua', 'user_id' => $other->id, 'prodi_id' => $prodi->id, 'angkatan' => 2025, 'is_active' => true]);
    PaymentAccess::create(['mahasiswa_id' => $student->id, 'enabled' => true, 'updated_by' => $admin->id]);

    return compact('admin', 'user', 'other', 'lecturer', 'prodi', 'student', 'otherStudent');
}

function billingFields(array $overrides = []): array
{
    return array_replace(['jenis_tagihan' => 'SPP', 'deskripsi' => 'SPP Semester Ganjil 2026', 'nominal_pokok' => 1200000, 'biaya_layanan' => 4000, 'boleh_cicil' => true, 'jatuh_tempo' => null], $overrides);
}

function makeSandboxBill(array $fixture, array $overrides = [], ?Mahasiswa $student = null): TagihanMahasiswa
{
    app(StudentBillingService::class)->createMany([billingFields($overrides) + ['mahasiswa_id' => ($student ?? $fixture['student'])->id]], $fixture['admin'], (string) Str::uuid());

    return TagihanMahasiswa::latest('id')->firstOrFail();
}

function fakeMidtransGateway(): void
{
    Http::fake(['https://app.sandbox.midtrans.com/snap/v1/transactions' => function () {
        $token = (string) Str::uuid();

        return Http::response([
            'token' => $token,
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/'.$token,
        ], 201);
    }]);
}

function midtransCallback(PembayaranMahasiswa $payment, array $overrides = []): array
{
    $data = array_replace([
        'order_id' => $payment->external_id,
        'transaction_id' => (string) Str::uuid(),
        'transaction_status' => 'settlement',
        'status_code' => '200',
        'gross_amount' => number_format($payment->amount, 2, '.', ''),
        'currency' => 'IDR',
        'fraud_status' => 'accept',
        'payment_type' => 'bank_transfer',
        'transaction_time' => now()->format('Y-m-d H:i:s'),
        'settlement_time' => now()->format('Y-m-d H:i:s'),
        'merchant_id' => config('payments.merchant_id'),
    ], $overrides);
    $data['signature_key'] = hash('sha512', $data['order_id'].$data['status_code'].$data['gross_amount'].config('payments.server_key'));

    return $data;
}

function billingExcel(array $rows, bool $formula = false): UploadedFile
{
    $book = new Spreadsheet;
    $sheet = $book->getActiveSheet();
    $sheet->fromArray(PaymentSpreadsheetService::HEADINGS);
    foreach ($rows as $i => $row) {
        foreach ($row as $j => $value) {
            $sheet->setCellValueExplicit([$j + 1, $i + 2], $value, DataType::TYPE_STRING);
        }
    }
    if ($formula) {
        $sheet->setCellValue('E2', '=1000+1');
    }
    ob_start();
    (new Xlsx($book))->save('php://output');
    $bytes = ob_get_clean();
    $book->disconnectWorksheets();

    return UploadedFile::fake()->createWithContent('tagihan.xlsx', $bytes);
}

test('admin creates bulk bills with audit duplicate protection and idempotent form submissions', function () {
    $f = billingFixture();
    $payload = billingFields() + ['mahasiswa_ids' => [$f['student']->id, $f['otherStudent']->id], 'request_key' => (string) Str::uuid()];
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    expect(TagihanMahasiswa::count())->toBe(2)->and(TagihanMahasiswa::first()->total_tagihan)->toBe(1204000)->and(PaymentAudit::where('action', 'buat_tagihan')->count())->toBe(2);
    $this->post(route('admin.pembayaran.store'), $payload)->assertSessionHasNoErrors();
    expect(TagihanMahasiswa::count())->toBe(2);
    $payload['request_key'] = (string) Str::uuid();
    $this->post(route('admin.pembayaran.store'), $payload)->assertSessionHasErrors('duplikat');
    expect(TagihanMahasiswa::count())->toBe(2);
    $this->post(route('admin.pembayaran.store'), $payload + ['confirm_duplicates' => 1])->assertSessionHasNoErrors();
    expect(TagihanMahasiswa::count())->toBe(4);
    $this->get(route('admin.pembayaran.index', ['angkatan' => 2025]))->assertOk()->assertSee($f['otherStudent']->nama)->assertDontSee($f['student']->nama);
    foreach (['create', 'access', 'import'] as $page) {
        $this->get(route('admin.pembayaran.'.$page))->assertOk();
    }
});

test('minimum amount and VA bank allowlist are enforced while cash overpayment needs explicit confirmation', function () {
    $f = billingFixture();
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.store'), billingFields(['nominal_pokok' => 599999]) + [
        'mahasiswa_ids' => [$f['student']->id],
        'request_key' => (string) Str::uuid(),
    ])->assertSessionHasErrors(['nominal_pokok' => 'Nominal pembayaran minimal Rp600.000.']);
    expect(TagihanMahasiswa::count())->toBe(0);

    $bill = makeSandboxBill($f);
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), [
        'amount' => 1200000,
        'bank' => 'gopay',
    ])->assertSessionHasErrors('bank');
    expect($bill->pembayaran()->count())->toBe(0);

    $cash = [
        'amount' => 1200001,
        'paid_at' => today()->format('Y-m-d'),
        'nomor_referensi' => 'KWT-LEBIH',
        'catatan' => 'Pencatatan khusus kelebihan pembayaran',
        'request_key' => (string) Str::uuid(),
    ];
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.manual', $bill), $cash)->assertSessionHasErrors('amount');
    $this->post(route('admin.pembayaran.manual', $bill), $cash + ['confirm_overpayment' => 1])->assertSessionHasNoErrors();
    expect($bill->pembayaran()->first()->source)->toBe('cash')
        ->and($bill->fresh()->status)->toBe('lunas')
        ->and($bill->fresh()->total_tagihan)->toBe(1200000)
        ->and($bill->fresh()->kelebihan_pembayaran)->toBe(1);
});

test('roles ownership sandbox mode and early access are enforced server side', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    $otherBill = makeSandboxBill($f, ['deskripsi' => 'Tagihan rahasia mahasiswa dua'], $f['otherStudent']);
    $this->actingAs($f['user'])->get(route('mahasiswa.pembayaran.index'))->assertOk()->assertDontSee($otherBill->deskripsi);
    $this->get(route('mahasiswa.pembayaran.show', $otherBill))->assertNotFound();
    $this->post(route('mahasiswa.pembayaran.pay', $otherBill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertNotFound();
    $this->get(route('admin.pembayaran.index'))->assertForbidden();
    $this->patch(route('admin.pembayaran.update', $bill), ['status' => 'lunas'])->assertForbidden();
    $this->actingAs($f['lecturer'])->get(route('admin.pembayaran.index'))->assertForbidden();
    $this->get(route('mahasiswa.pembayaran.index'))->assertForbidden();
    $this->actingAs($f['other'])->get(route('mahasiswa.pembayaran.show', $otherBill))->assertOk()->assertDontSee('Bayar melalui Midtrans Sandbox');
    $this->post(route('mahasiswa.pembayaran.pay', $otherBill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertForbidden();
    $this->actingAs($f['admin'])->patch(route('admin.pembayaran.access.update', $f['otherStudent']), ['enabled' => 1])->assertSessionHasNoErrors();
    expect(PaymentAudit::where('action', 'ubah_early_access')->count())->toBe(1);
    config(['payments.is_production' => true]);
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertForbidden();
    config(['payments.is_production' => false, 'payments.server_key' => 'Mid-server-live-for-tests']);
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertForbidden();
    config(['payments.server_key' => 'SB-Mid-server-fake-for-tests', 'payments.enabled' => false]);
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertForbidden();
    Http::assertNothingSent();
    expect($bill->fresh()->total_dibayar)->toBe(0);
});

test('installments settle only from signed callbacks and charge the configured fee per Midtrans transaction', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    fakeMidtransGateway();
    $url = route('mahasiswa.pembayaran.pay', $bill);
    $this->actingAs($f['user'])->post($url, ['amount' => 600000, 'bank' => 'bni_va', 'status' => 'paid'])->assertRedirect();
    $payment = $bill->pembayaran()->firstOrFail();
    $this->post($url, ['amount' => 600000, 'bank' => 'bni_va'])->assertRedirect($payment->checkout_url);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['transaction_details']['gross_amount'] === 604000
        && $request['transaction_details']['order_id'] === $payment->external_id
        && $request['enabled_payments'] === ['bni_va']
        && collect($request['item_details'])->sum(fn ($item) => $item['price'] * $item['quantity']) === 604000);
    expect($bill->fresh()->total_dibayar)->toBe(0);
    $this->get(route('mahasiswa.pembayaran.show', $bill, false).'?status=PAID')->assertOk();
    expect($bill->fresh()->total_dibayar)->toBe(0);
    $this->postJson(route('midtrans.callback'), midtransCallback($payment))->assertOk();
    $this->postJson(route('midtrans.callback'), midtransCallback($payment))->assertOk();
    expect($bill->fresh()->total_dibayar)->toBe(604000)->and($bill->fresh()->sisa_tagihan)->toBe(604000)->and($bill->fresh()->status)->toBe('sebagian_dibayar')->and(PaymentAudit::where('action', 'midtrans_payment_paid')->count())->toBe(1);
    $this->post($url, ['amount' => 600000, 'bank' => 'bni_va'])->assertRedirect();
    $second = $bill->pembayaran()->latest('id')->firstOrFail();
    $this->postJson(route('midtrans.callback'), midtransCallback($second))->assertOk();
    expect($bill->fresh()->status)->toBe('lunas')->and($bill->fresh()->sisa_tagihan)->toBe(0)
        ->and($bill->fresh()->total_tagihan)->toBe(1208000)
        ->and((int) $bill->pembayaran()->sum('biaya_layanan'))->toBe(8000)
        ->and((int) $bill->pembayaran()->sum('nominal_pokok'))->toBe(1200000);
    $this->postJson(route('midtrans.callback'), midtransCallback($second, ['transaction_status' => 'expire', 'status_code' => '202']))->assertOk();
    expect($second->fresh()->status)->toBe('paid')->and($bill->fresh()->status)->toBe('lunas');
});

test('invalid callbacks cannot change balances and callback logs exclude sensitive or arbitrary data', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    fakeMidtransGateway();
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bca_va'])->assertRedirect();
    $payment = $bill->pembayaran()->firstOrFail();
    $payload = midtransCallback($payment);
    $invalidSignature = $payload;
    $invalidSignature['signature_key'] = str_repeat('0', 128);
    $this->postJson(route('midtrans.callback'), $invalidSignature)->assertUnauthorized();
    foreach ([
        [['order_id' => 'wrong-order'], 404],
        [['gross_amount' => '1200999.00'], 422],
        [['currency' => 'USD'], 422],
        [['merchant_id' => 'WRONG'], 422],
    ] as [$change, $expectedStatus]) {
        $this->postJson(route('midtrans.callback'), midtransCallback($payment, $change))->assertStatus($expectedStatus);
        expect($bill->fresh()->total_dibayar)->toBe(0);
    }
    $this->postJson(route('midtrans.callback'), $payload + ['payer_email' => 'private@example.test', 'server_key' => 'do-not-store', 'card_number' => 'sensitive-placeholder'])->assertOk();
    expect(json_encode(PaymentWebhook::all()->toArray()))->not->toContain('private@example.test', 'do-not-store', 'sensitive-placeholder', 'signature_key');
    expect($bill->fresh()->status)->toBe('lunas');
});

test('amount validation pending reservations and invoice expiration preserve debt', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f, ['boleh_cicil' => false]);
    fakeMidtransGateway();
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 599999, 'bank' => 'bni_va'])->assertSessionHasErrors('amount');
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200001, 'bank' => 'bni_va'])->assertSessionHasErrors('amount');
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertRedirect();
    $payment = $bill->pembayaran()->firstOrFail();
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.manual', $bill), ['amount' => 600000, 'paid_at' => today()->format('Y-m-d'), 'nomor_referensi' => 'KWT-PENDING', 'catatan' => 'Periksa pembayaran pending', 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('tagihan');
    $this->patch(route('admin.pembayaran.cancel', $bill), ['alasan' => 'Batalkan tagihan ini'])->assertSessionHasErrors('tagihan');
    $this->postJson(route('midtrans.callback'), midtransCallback($payment, ['transaction_status' => 'expire', 'status_code' => '202']))->assertOk();
    expect($bill->fresh()->sisa_tagihan)->toBe(1204000)->and($payment->fresh()->status)->toBe('expired');
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertRedirect();
    Http::assertSentCount(2);
});

test('uncertain Midtrans creation is blocked without repeating the POST', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    $postCalls = 0;
    Http::fake(function ($request) use (&$postCalls) {
        if ($request->method() === 'POST') {
            $postCalls++;

            return Http::failedConnection();
        }

        return Http::response([], 404);
    });
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertSessionHasErrors('gateway');
    $payment = $bill->pembayaran()->firstOrFail();
    expect($payment->status)->toBe('unknown');
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertSessionHasErrors('gateway');
    expect($bill->pembayaran()->count())->toBe(1)->and($payment->fresh()->status)->toBe('unknown')->and($bill->fresh()->total_dibayar)->toBe(0);
    expect($postCalls)->toBe(1);
});

test('cash correction requires complete data and keeps an audited private ledger', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    Storage::fake('local');
    $data = ['amount' => 600000, 'paid_at' => today()->format('Y-m-d'), 'nomor_referensi' => 'KWT-001', 'request_key' => (string) Str::uuid()];
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.manual', $bill), $data)->assertSessionHasErrors('catatan');
    $data['catatan'] = 'Pembayaran cash loket kampus';
    $data['bukti'] = UploadedFile::fake()->image('kwitansi.jpg');
    $this->post(route('admin.pembayaran.manual', $bill), $data)->assertSessionHasNoErrors();
    $this->post(route('admin.pembayaran.manual', $bill), collect($data)->except('bukti')->all())->assertSessionHasNoErrors();
    expect($bill->pembayaran()->count())->toBe(1)->and($bill->fresh()->total_dibayar)->toBe(600000);
    $payment = $bill->pembayaran()->firstOrFail();
    expect($payment->metode_pembayaran)->toBe('cash')->and($payment->created_by)->toBe($f['admin']->id)->and($payment->biaya_layanan)->toBe(0);
    Storage::disk('local')->assertExists($payment->bukti_path);
    $this->actingAs($f['user'])->get(route('mahasiswa.pembayaran.attachment', $payment))->assertOk();
    $this->actingAs($f['other'])->get(route('mahasiswa.pembayaran.attachment', $payment))->assertForbidden();
    $this->actingAs($f['admin'])->patch(route('admin.pembayaran.void', $payment), ['alasan' => 'Nominal salah tercatat'])->assertSessionHasNoErrors();
    expect($payment->fresh()->status)->toBe('void')->and($bill->fresh()->total_dibayar)->toBe(0)->and($bill->pembayaran()->count())->toBe(1)
        ->and(PaymentAudit::where('action', 'batalkan_pembayaran_cash')->first()->reason)->toBe('Nominal salah tercatat');
    $this->get(route('admin.pembayaran.show', $bill))->assertOk()->assertSee('Nominal salah tercatat');
    $this->get(route('admin.pembayaran.receipt', $payment))->assertNotFound();
    $this->patch(route('admin.pembayaran.update', $bill), billingFields() + ['alasan' => 'Edit setelah pembayaran'])->assertSessionHasErrors('tagihan');
    $this->patch(route('admin.pembayaran.cancel', $bill), ['alasan' => 'Batalkan simulasi selesai'])->assertSessionHasNoErrors();
    expect($bill->fresh()->status)->toBe('dibatalkan')->and(Mahasiswa::count())->toBe(2)->and($f['student']->fresh()->nama)->toBe('Mahasiswa Billing Satu');
});

test('import previews errors and formulas without creating bills and protects finalization', function () {
    $f = billingFixture();
    $this->actingAs($f['admin']);
    $row = [$f['student']->nim, '', 'SPP', 'Import test', '1200000', '', '', 'true'];
    $file = billingExcel([$row, array_replace($row, [0 => 'UNKNOWN-NIM'])]);
    $response = $this->post(route('admin.pembayaran.import.preview'), ['file' => $file])->assertSessionHasNoErrors()->assertRedirect();
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('UNKNOWN-NIM')->assertDontSee('Simpan Import Final');
    expect(TagihanMahasiswa::count())->toBe(0);
    $token = session('payment_import_token');
    $this->post(route('admin.pembayaran.import.confirm'), ['token' => $token])->assertSessionHasErrors('file');
    $response = $this->post(route('admin.pembayaran.import.preview'), ['file' => billingExcel([$row], true)])->assertRedirect();
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('rumus Excel tidak diterima');
    $response = $this->post(route('admin.pembayaran.import.preview'), ['file' => billingExcel([$row])])->assertSessionHasNoErrors()->assertRedirect();
    $token = session('payment_import_token');
    $this->get($response->headers->get('Location'))->assertOk()->assertSee('Simpan Import Final');
    $otherAdmin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($otherAdmin)->post(route('admin.pembayaran.import.confirm'), ['token' => $token])->assertSessionHasErrors('file');
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.import.confirm'), ['token' => $token])->assertSessionHasNoErrors();
    expect(TagihanMahasiswa::count())->toBe(1)
        ->and(TagihanMahasiswa::first()->mahasiswa_id)->toBe($f['student']->id)
        ->and(TagihanMahasiswa::first()->biaya_layanan)->toBe(4000);
    $this->post(route('admin.pembayaran.import.confirm'), ['token' => $token])->assertSessionHasErrors('file');
    expect(TagihanMahasiswa::count())->toBe(1);
    $this->post(route('admin.pembayaran.import.preview'), ['file' => billingExcel([$row])])->assertRedirect();
    $token = session('payment_import_token');
    $this->post(route('admin.pembayaran.import.confirm'), ['token' => $token])->assertSessionHasErrors('duplikat');
    $this->post(route('admin.pembayaran.import.confirm'), ['token' => $token, 'confirm_duplicates' => 1])->assertSessionHasNoErrors();
    expect(TagihanMahasiswa::count())->toBe(2);
});

test('receipts reports and spreadsheets render while other students and lecturers cannot access receipts', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    app(StudentBillingService::class)->cashPayment($bill, 1200000, today()->format('Y-m-d'), 'KWT-CETAK', 'Uji cetak bukti cash', null, (string) Str::uuid(), $f['admin']);
    $payment = $bill->pembayaran()->firstOrFail();
    $this->actingAs($f['user'])->get(route('mahasiswa.pembayaran.receipt', $payment))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($f['other'])->get(route('mahasiswa.pembayaran.receipt', $payment))->assertNotFound();
    $this->actingAs($f['lecturer'])->get(route('mahasiswa.pembayaran.receipt', $payment))->assertForbidden();
    $this->actingAs($f['admin'])->get(route('admin.pembayaran.export', ['format' => 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $excel = $this->get(route('admin.pembayaran.export', ['format' => 'excel']))->assertOk();
    expect(substr($excel->streamedContent(), 0, 2))->toBe('PK');
    $template = $this->get(route('admin.pembayaran.import.template'))->assertOk();
    expect(substr($template->streamedContent(), 0, 2))->toBe('PK');
});

test('only the callback is exempt from CSRF and a valid payload still requires a Midtrans signature', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    $this->app['env'] = 'local';
    $this->postJson(route('midtrans.callback'), [])->assertStatus(422);
    $unsigned = midtransCallback(PembayaranMahasiswa::create([
        'tagihan_mahasiswa_id' => $bill->id, 'external_id' => 'STTMI-TEST-CSRF',
        'mahasiswa_id' => $f['student']->id, 'source' => 'midtrans', 'metode_pembayaran' => 'va_midtrans', 'status' => 'pending', 'nominal_pokok' => 1200000,
        'biaya_layanan' => 4000, 'amount' => 1204000, 'payment_method' => 'bni_va', 'invoice_id' => (string) Str::uuid(), 'is_test' => true,
    ]));
    $unsigned['signature_key'] = str_repeat('0', 128);
    $this->postJson(route('midtrans.callback'), $unsigned)->assertUnauthorized();
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertStatus(419);
});

test('late valid payments remain in the ledger with review audit and service fees are not duplicated', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    fakeMidtransGateway();
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertRedirect();
    $old = $bill->pembayaran()->firstOrFail();
    $this->postJson(route('midtrans.callback'), midtransCallback($old, ['transaction_status' => 'expire', 'status_code' => '202']))->assertOk();
    $this->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertRedirect();
    $new = $bill->pembayaran()->latest('id')->firstOrFail();
    $this->postJson(route('midtrans.callback'), midtransCallback($new))->assertOk();
    $this->postJson(route('midtrans.callback'), midtransCallback($old))->assertOk();
    $this->postJson(route('midtrans.callback'), midtransCallback($old))->assertOk();
    expect($bill->fresh()->total_dibayar)->toBe(2408000)->and($bill->fresh()->sisa_tagihan)->toBe(0)
        ->and((int) $bill->pembayaran()->sum('biaya_layanan'))->toBe(8000)
        ->and(PaymentAudit::where('action', 'midtrans_payment_paid')->whereNotNull('reason')->count())->toBe(1);
    $this->get(route('mahasiswa.pembayaran.show', $bill))->assertOk()->assertSee('kelebihan pembayaran sandbox');
});

test('untrusted checkout response cannot redirect students or settle balances', function () {
    $f = billingFixture();
    $change = [];
    Http::fake(function () use (&$change) {
        return Http::response(array_replace([
            'token' => (string) Str::uuid(),
            'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v3/redirection/test-token-safe-1234567890',
        ], $change), 201);
    });
    foreach ([['redirect_url' => 'https://evil.example/phishing'], ['token' => 'short']] as $i => $change) {
        $bill = makeSandboxBill($f, ['deskripsi' => 'Check response '.$i]);
        $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertSessionHasErrors('gateway');
        expect($bill->fresh()->total_dibayar)->toBe(0)->and($bill->pembayaran()->first()->invoice_id)->toBeNull();
    }
});

test('admin edits and cancels unpaid bills with reasons while spreadsheet exports keep user text inert', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    $this->actingAs($f['admin'])->patch(route('admin.pembayaran.update', $bill), billingFields(['nominal_pokok' => 2000000]))->assertSessionHasErrors('alasan');
    $this->patch(route('admin.pembayaran.update', $bill), billingFields(['nominal_pokok' => 2000000, 'deskripsi' => '=2+2']) + ['alasan' => 'Koreksi nominal tagihan'])->assertSessionHasNoErrors();
    expect($bill->fresh()->total_tagihan)->toBe(2004000)->and($bill->fresh()->sisa_tagihan)->toBe(2004000);
    $response = $this->get(route('admin.pembayaran.export', ['format' => 'excel']))->assertOk();
    $file = UploadedFile::fake()->createWithContent('report.xlsx', $response->streamedContent());
    $book = IOFactory::load($file->getRealPath());
    expect($book->getActiveSheet()->getCell('E2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($book->getActiveSheet()->getCell('E2')->getValue())->toBe('=2+2');
    $book->disconnectWorksheets();
    $this->patch(route('admin.pembayaran.cancel', $bill), ['alasan' => 'Tagihan uji selesai'])->assertSessionHasNoErrors();
    expect($bill->fresh()->status)->toBe('dibatalkan')->and(TagihanMahasiswa::count())->toBe(1)
        ->and(PaymentAudit::where('action', 'ubah_tagihan')->count())->toBe(1);
});

test('import rejects oversized row counts and non spreadsheet uploads', function () {
    $f = billingFixture();
    $this->actingAs($f['admin'])->post(route('admin.pembayaran.import.preview'), ['file' => UploadedFile::fake()->createWithContent('script.xlsx', '<?php echo "invalid";')])->assertSessionHasErrors('file');
    $row = [$f['student']->nim, '', 'SPP', 'Batch', '600000', '0', '', 'true'];
    $this->post(route('admin.pembayaran.import.preview'), ['file' => billingExcel(array_fill(0, 501, $row))])->assertSessionHasErrors('file');
    expect(TagihanMahasiswa::count())->toBe(0);
});

test('definitive gateway validation failures release the reservation without settling the bill', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    Http::fake(['*' => Http::response(['error_messages' => ['gross amount invalid']], 400)]);
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertSessionHasErrors('gateway');
    expect($bill->pembayaran()->first()->status)->toBe('failed')->and($bill->fresh()->total_dibayar)->toBe(0);
    $this->actingAs($f['admin'])->patch(route('admin.pembayaran.cancel', $bill), ['alasan' => 'Tagihan test tidak dipakai'])->assertSessionHasNoErrors();
    expect($bill->fresh()->status)->toBe('dibatalkan');
});

test('Midtrans pending fraud and failure statuses never settle a bill while accepted capture does', function () {
    $f = billingFixture();
    $bill = makeSandboxBill($f);
    fakeMidtransGateway();
    $this->actingAs($f['user'])->post(route('mahasiswa.pembayaran.pay', $bill), ['amount' => 1200000, 'bank' => 'bni_va'])->assertRedirect();
    $payment = $bill->pembayaran()->firstOrFail();

    $this->postJson(route('midtrans.callback'), midtransCallback($payment, [
        'transaction_status' => 'pending', 'status_code' => '201',
    ]))->assertOk();
    expect($payment->fresh()->status)->toBe('pending')->and($bill->fresh()->total_dibayar)->toBe(0);

    $this->postJson(route('midtrans.callback'), midtransCallback($payment, [
        'transaction_status' => 'capture', 'fraud_status' => 'challenge',
    ]))->assertOk();
    expect($payment->fresh()->status)->toBe('pending')->and($bill->fresh()->total_dibayar)->toBe(0);

    $this->postJson(route('midtrans.callback'), midtransCallback($payment, [
        'transaction_status' => 'capture', 'fraud_status' => 'deny',
    ]))->assertOk();
    expect($payment->fresh()->status)->toBe('failed')->and($bill->fresh()->total_dibayar)->toBe(0);

    $acceptedBill = makeSandboxBill($f, ['deskripsi' => 'Capture diterima']);
    $this->post(route('mahasiswa.pembayaran.pay', $acceptedBill), ['amount' => 1200000, 'bank' => 'permata_va'])->assertRedirect();
    $accepted = $acceptedBill->pembayaran()->firstOrFail();
    $this->postJson(route('midtrans.callback'), midtransCallback($accepted, [
        'transaction_status' => 'capture', 'fraud_status' => 'accept',
    ]))->assertOk();
    expect($accepted->fresh()->status)->toBe('paid')->and($acceptedBill->fresh()->status)->toBe('lunas');
});
