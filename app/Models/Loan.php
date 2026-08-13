<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LoanStatus;
use Carbon\CarbonInterface;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $user_id
 * @property string $book_copy_id
 * @property string $reservation_id
 * @property CarbonInterface $loaned_at
 * @property CarbonInterface $due_date
 * @property CarbonInterface|null $returned_at
 * @property int $renewal_count
 * @property LoanStatus $status
 * @property-read int $days_overdue
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read User $user
 * @property-read BookCopy $bookCopy
 * @property-read Reservation $reservation
 * @property-read Collection<int, Fine> $fines
 */
final class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    use HasUlids;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'book_copy_id',
        'reservation_id',
        'loaned_at',
        'due_date',
        'returned_at',
        'renewal_count',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'status' => LoanStatus::class,
            'loaned_at' => 'datetime',
            'due_date' => 'datetime',
            'returned_at' => 'datetime',
            'renewal_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<BookCopy, $this> */
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    /** @return BelongsTo<Reservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /** @return HasMany<Fine, $this> */
    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen() && $this->due_date->isPast();
    }

    /**
     * Days elapsed past the due date, measured at return time for closed loans
     * and at the present moment for open ones.
     *
     * @return Attribute<int, never>
     */
    protected function daysOverdue(): Attribute
    {
        return Attribute::get(function (): int {
            $reference = $this->returned_at ?? now();

            if ($reference->lessThanOrEqualTo($this->due_date)) {
                return 0;
            }

            return (int) $this->due_date->diffInDays($reference, absolute: true);
        });
    }
}
