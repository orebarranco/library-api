<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\CreateBookCopyDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'acquisition_date' => ['nullable', 'date'],
        ];
    }

    public function toDto(): CreateBookCopyDTO
    {
        return new CreateBookCopyDTO(
            acquisition_date: $this->string('acquisition_date')->toString() ?: null,
        );
    }
}
