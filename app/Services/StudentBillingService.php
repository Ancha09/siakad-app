<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\PaymentAudit;
use App\Models\PembayaranMahasiswa;
use App\Models\TagihanMahasiswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentBillingService
{
    public static function fingerprint(array $row): string
    {
        return hash('sha256', json_encode([
            (int) $row['mahasiswa_id'],
            mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['jenis_tagihan']))),
            mb_strtolower(preg_replace('/\s+/u', ' ', trim($row['deskripsi']))),
            (int) $row['nominal_pokok'], (int) ($row['biaya_layanan'] ?? 0),
            $row['jatuh_tempo'] ?? null, (bool) $row['boleh_cicil'],
        ], JSON_UNESCAPED_UNICODE));
    }

    public function createMany(array $rows, User $admin, string $requestKey, bool $confirmDuplicates = false): int
    {
        abort_unless($admin->role === 'admin', 403);

        return DB::transaction(function () use ($rows, $admin, $requestKey, $confirmDuplicates) {
            // Lock the master rows in a stable order to serialize overlapping bulk/imports.
            $ids = array_unique(array_column($rows, 'mahasiswa_id'));
            $students = Mahasiswa::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($students->count() !== count($ids)) {
                throw ValidationException::withMessages(['tagihan' => 'Ada mahasiswa yang tidak tersedia. Ulangi preview.']);
            }
            $created = 0;
            foreach ($rows as $index => $row) {
                if (isset($row['nim']) && (string) $students[$row['mahasiswa_id']]->nim !== (string) $row['nim']) {
                    throw ValidationException::withMessages(['tagihan' => 'NIM berubah sejak preview. Upload dan preview ulang.']);
                }
                $creationKey = hash('sha256', $requestKey.'|'.$index.'|'.$row['mahasiswa_id']);
                if (TagihanMahasiswa::where('creation_key', $creationKey)->exists()) {
                    continue;
                }
                $fingerprint = self::fingerprint($row);
                if (! $confirmDuplicates && TagihanMahasiswa::where('fingerprint', $fingerprint)->exists()) {
                    throw ValidationException::withMessages(['duplikat' => 'Tagihan yang sama sudah ada untuk NIM '.$students[$row['mahasiswa_id']]->nim.'. Periksa data dan centang konfirmasi duplikat jika memang diperlukan.']);
                }
                $principal = (int) $row['nominal_pokok'];
                $total = $principal + (int) ($row['biaya_layanan'] ?? 0);
                if ($principal < (int) config('payments.minimum_payment')) {
                    throw ValidationException::withMessages(['nominal_pokok' => 'Nominal pembayaran minimal Rp600.000.']);
                }
                if ($total > config('payments.max_amount')) {
                    throw ValidationException::withMessages(['nominal_pokok' => 'Total tagihan melebihi batas.']);
                }
                $bill = TagihanMahasiswa::create([
                    'mahasiswa_id' => $row['mahasiswa_id'],
                    'kode_tagihan' => 'TEST-'.strtoupper((string) Str::ulid()),
                    'creation_key' => $creationKey, 'fingerprint' => $fingerprint,
                    'jenis_tagihan' => $row['jenis_tagihan'], 'deskripsi' => $row['deskripsi'],
                    'nominal_pokok' => $row['nominal_pokok'], 'biaya_layanan' => $row['biaya_layanan'] ?? 0,
                    'total_tagihan' => $total, 'sisa_tagihan' => $total, 'total_dibayar' => 0,
                    'boleh_cicil' => $row['boleh_cicil'], 'jatuh_tempo' => $row['jatuh_tempo'] ?? null,
                    'status' => 'belum_dibayar', 'is_test' => true, 'created_by' => $admin->id,
                ]);
                $this->audit($admin, $bill, 'buat_tagihan', null, ['total' => $total, 'mahasiswa_id' => $bill->mahasiswa_id]);
                $created++;
            }

            return $created;
        });
    }

    public function update(TagihanMahasiswa $bill, array $data, User $admin, string $reason): void
    {
        DB::transaction(function () use ($bill, $data, $admin, $reason) {
            Mahasiswa::whereKey($bill->mahasiswa_id)->lockForUpdate()->firstOrFail();
            $locked = TagihanMahasiswa::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            if ($locked->pembayaran()->exists() || $locked->status === 'dibatalkan') {
                throw ValidationException::withMessages(['tagihan' => 'Tagihan dengan riwayat transaksi atau yang dibatalkan tidak dapat diubah.']);
            }
            $before = $locked->only(['jenis_tagihan', 'deskripsi', 'nominal_pokok', 'biaya_layanan', 'total_tagihan', 'jatuh_tempo', 'boleh_cicil']);
            $total = (int) $data['nominal_pokok'] + (int) $data['biaya_layanan'];
            if ((int) $data['nominal_pokok'] < (int) config('payments.minimum_payment')) {
                throw ValidationException::withMessages(['nominal_pokok' => 'Nominal pembayaran minimal Rp600.000.']);
            }
            if ($total > config('payments.max_amount')) {
                throw ValidationException::withMessages(['nominal_pokok' => 'Total tagihan melebihi batas.']);
            }
            $fingerprint = self::fingerprint($data + ['mahasiswa_id' => $locked->mahasiswa_id]);
            if (TagihanMahasiswa::where('fingerprint', $fingerprint)->whereKeyNot($locked->id)->exists()) {
                throw ValidationException::withMessages(['tagihan' => 'Perubahan menghasilkan tagihan duplikat.']);
            }
            $locked->update($data + ['total_tagihan' => $total, 'sisa_tagihan' => $total, 'fingerprint' => $fingerprint]);
            $this->audit($admin, $locked, 'ubah_tagihan', $reason, ['sebelum' => $before, 'sesudah' => $data]);
        });
    }

    public function cancel(TagihanMahasiswa $bill, User $admin, string $reason): void
    {
        DB::transaction(function () use ($bill, $admin, $reason) {
            $locked = TagihanMahasiswa::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $this->assertNoOpenPayment($locked);
            if ($locked->total_dibayar > 0) {
                throw ValidationException::withMessages(['tagihan' => 'Tagihan yang sudah menerima pembayaran tidak dapat dibatalkan.']);
            }
            if ($locked->status !== 'dibatalkan') {
                $locked->update(['status' => 'dibatalkan']);
                $this->audit($admin, $locked, 'batalkan_tagihan', $reason);
            }
        });
    }

    public function cashPayment(
        TagihanMahasiswa $bill,
        int $amount,
        string $paidAt,
        string $reference,
        string $note,
        ?string $proofPath,
        string $requestKey,
        User $admin,
        bool $confirmOverpayment = false,
    ): bool {
        abort_unless($admin->role === 'admin', 403);

        return DB::transaction(function () use ($bill, $amount, $paidAt, $reference, $note, $proofPath, $requestKey, $admin, $confirmOverpayment) {
            $locked = TagihanMahasiswa::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $externalId = 'cash-'.$bill->id.'-'.$requestKey;
            if (PembayaranMahasiswa::where('external_id', $externalId)->exists()) {
                return false;
            }
            $this->assertNoOpenPayment($locked);
            $remaining = $this->remainingPrincipal($locked);
            if ($locked->status === 'dibatalkan' || $amount < 1) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran cash harus lebih dari Rp0.']);
            }
            if ($amount > $remaining && ! $confirmOverpayment) {
                throw ValidationException::withMessages(['amount' => 'Nominal cash melebihi sisa tagihan. Centang konfirmasi khusus jika pencatatan ini memang benar.']);
            }

            $payment = $locked->pembayaran()->create([
                'mahasiswa_id' => $locked->mahasiswa_id,
                'external_id' => $externalId,
                'source' => 'cash',
                'metode_pembayaran' => 'cash',
                'status' => 'paid',
                'nominal_pokok' => $amount,
                'biaya_layanan' => 0,
                'amount' => $amount,
                'payment_method' => 'Cash / Manual',
                'nomor_referensi' => $reference,
                'bukti_path' => $proofPath,
                'catatan' => $note,
                'created_by' => $admin->id,
                'paid_at' => Carbon::parse($paidAt),
                'is_test' => $locked->is_test,
            ]);
            $this->recalculate($locked);
            $this->audit($admin, $locked, 'catat_pembayaran_cash', $note, [
                'amount' => $amount,
                'nomor_referensi' => $reference,
                'bukti_path' => $proofPath,
                'konfirmasi_kelebihan' => $confirmOverpayment,
            ], $payment);

            return true;
        });
    }

    public function voidManual(PembayaranMahasiswa $payment, User $admin, string $reason): void
    {
        DB::transaction(function () use ($payment, $admin, $reason) {
            $bill = TagihanMahasiswa::whereKey($payment->tagihan_mahasiswa_id)->lockForUpdate()->firstOrFail();
            $locked = PembayaranMahasiswa::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $this->assertNoOpenPayment($bill);
            if (! in_array($locked->source, ['cash', 'manual'], true) || $locked->status !== 'paid') {
                throw ValidationException::withMessages(['pembayaran' => 'Hanya pembayaran cash/manual berhasil yang bisa dikoreksi.']);
            }
            $locked->update(['status' => 'void']);
            $this->recalculate($bill);
            $this->audit($admin, $bill, 'batalkan_pembayaran_cash', $reason, ['amount' => $locked->amount], $locked);
        });
    }

    public function assertNoOpenPayment(TagihanMahasiswa $bill): void
    {
        if ($bill->pembayaran()->whereIn('status', PembayaranMahasiswa::OPEN_STATUSES)->exists()) {
            throw ValidationException::withMessages(['tagihan' => 'Masih ada transaksi gateway yang belum selesai. Tunggu callback atau periksa order agar tidak terjadi pembayaran ganda.']);
        }
    }

    public function assertGatewayPayablePrincipal(TagihanMahasiswa $bill, int $principal): void
    {
        $remaining = $this->remainingPrincipal($bill);
        $minimum = (int) config('payments.minimum_payment');
        if ($principal < $minimum) {
            throw ValidationException::withMessages(['amount' => 'Nominal pembayaran minimal Rp600.000.']);
        }
        if ($bill->status === 'dibatalkan' || $principal > $remaining
            || (! $bill->boleh_cicil && $principal !== $remaining)
            || $principal + (int) $bill->biaya_layanan > config('payments.max_amount')) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pokok harus sesuai sisa tagihan. Pembayaran sebagian hanya tersedia untuk tagihan yang mengizinkan cicilan.',
            ]);
        }
        if (($remaining - $principal) > 0 && ($remaining - $principal) < $minimum) {
            throw ValidationException::withMessages([
                'amount' => 'Sisa pokok setelah pembayaran juga harus minimal Rp600.000 atau dibayar lunas.',
            ]);
        }
    }

    public function allocateGateway(TagihanMahasiswa $bill, int $principal): array
    {
        $fee = (int) $bill->biaya_layanan;

        return [
            'amount' => $principal + $fee,
            'biaya_layanan' => $fee,
            'nominal_pokok' => $principal,
        ];
    }

    public function remainingPrincipal(TagihanMahasiswa $bill): int
    {
        $paidPrincipal = (int) $bill->pembayaran()->where('status', 'paid')->sum('nominal_pokok');

        return max(0, (int) $bill->nominal_pokok - $paidPrincipal);
    }

    public function recalculate(TagihanMahasiswa $bill): void
    {
        $payments = $bill->pembayaran()->where('status', 'paid');
        $paid = (int) (clone $payments)->sum('amount');
        $paidPrincipal = (int) (clone $payments)->sum('nominal_pokok');
        $paidFees = (int) (clone $payments)->sum('biaya_layanan');
        $remainingPrincipal = max(0, (int) $bill->nominal_pokok - $paidPrincipal);
        $nextFee = $remainingPrincipal > 0 ? (int) $bill->biaya_layanan : 0;
        $remaining = $remainingPrincipal + $nextFee;
        $bill->update([
            'total_tagihan' => (int) $bill->nominal_pokok + $paidFees + $nextFee,
            'total_dibayar' => $paid,
            'sisa_tagihan' => $remaining,
            'status' => $paidPrincipal >= $bill->nominal_pokok ? 'lunas' : ($paidPrincipal > 0 ? 'sebagian_dibayar' : 'belum_dibayar'),
        ]);
    }

    public function audit(?User $actor, ?TagihanMahasiswa $bill, string $action, ?string $reason = null, array $details = [], ?PembayaranMahasiswa $payment = null): void
    {
        PaymentAudit::create([
            'actor_id' => $actor?->id, 'tagihan_mahasiswa_id' => $bill?->id,
            'pembayaran_mahasiswa_id' => $payment?->id, 'action' => $action,
            'reason' => $reason, 'details' => $details, 'created_at' => now(),
        ]);
    }
}
