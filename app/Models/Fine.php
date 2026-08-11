<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FineStatus;
use App\Enums\FineType;
use Carbon\CarbonInterface;
use Database\Factories\FineFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $loan_id
 * @property FineType $type
 * @property float $amount
 * @property float $amount_paid
 * @property FineStatus $status
 * @property string $description
 * @property string|null $waived_by
 * @property string|null $waived_reason
 * @property-read float $balance
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read User $user
 * @property-read Loan|null $loan
 * @property-read User|null $waivedBy
 */
final class Fine extends Model
{
    /** @use HasFactory<FineFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'loan_id',
        'type',
        'amount',
        'amount_paid',
        'status',
        'description',
        'waived_by',
        'waived_reason',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'type' => FineType::class,
            'status' => FineStatus::class,
            'amount' => 'float',
            'amount_paid' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Loan, $this> */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function waivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /**
     * Amount still owed on this fine. Waived fines keep their original amount on
     * record, so callers must check the status before treating this as debt.
     *
     * @return Attribute<float, never>
     */
    protected function balance(): Attribute
    {
        return Attribute::get(fn (): float => round($this->amount - $this->amount_paid, 2));
    }
}
