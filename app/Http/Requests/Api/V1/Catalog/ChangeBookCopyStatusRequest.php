<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\ChangeBookCopyStatusDTO;
use App\Enums\BookCopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class ChangeBookCopyStatusRequest extends FormRequest
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
            'status' => [
                'required',
                new Enum(BookCopyStatus::class),
                'not_in:loaned',
            ],
        ];
    }

    public function toDto(): ChangeBookCopyStatusDTO
    {
        return new ChangeBookCopyStatusDTO(
            status: BookCopyStatus::from($this->string('status')->toString()),
        );
    }
}
