<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\UpdateAuthorDTO;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'biography' => ['nullable', 'string'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ];
    }

    public function toDto(): UpdateAuthorDTO
    {
        return new UpdateAuthorDTO(
            name: $this->string('name')->toString(),
            biography: $this->filled('biography')
                ? $this->string('biography')->toString()
                : null,
            birth_date: $this->filled('birth_date')
                ? $this->string('birth_date')->toString()
                : null,
        );
    }
}
