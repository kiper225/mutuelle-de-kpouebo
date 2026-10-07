<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Cotisation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';

    public const PROVIDERS = ['orange_money', 'mtn_momo', 'moov_money', 'wave', 'card'];

    protected $fillable = ['period', 'amount', 'provider'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newReference(): string
    {
        return 'COT-'.strtoupper(Str::random(12));
    }
}
