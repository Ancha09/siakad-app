<?php

namespace App\Contracts;

use App\Models\Mahasiswa;
use App\Models\PembayaranMahasiswa;
use App\Models\TagihanMahasiswa;

interface PaymentGateway
{
    public function ready(): bool;

    public function canPay(Mahasiswa $student): bool;

    public function checkout(TagihanMahasiswa $bill, Mahasiswa $student, int $principal, string $bank): PembayaranMahasiswa;

    public function safeCheckoutUrl(mixed $url): bool;
}
