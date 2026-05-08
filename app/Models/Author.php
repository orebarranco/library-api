<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $name
 * @property string|null $biography
 * @property CarbonInterface|null $birth_date
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
final class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'biography',
        'birth_date',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'birth_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
