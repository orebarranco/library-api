<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\CreateCategoryDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCategoryRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDto(): CreateCategoryDTO
    {
        return new CreateCategoryDTO(
            name: $this->string('name')->toString(),
            description: $this->filled('description')
                ? $this->string('description')->toString()
                : null,
        );
    }
}
