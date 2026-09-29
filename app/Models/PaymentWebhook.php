<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['safe_payload' => 'array'];
    }
}
