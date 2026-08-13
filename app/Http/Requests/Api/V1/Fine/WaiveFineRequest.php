<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Fine;

use App\DTOs\Fine\WaiveFineDTO;
use Illuminate\Foundation\Http\FormRequest;

final class WaiveFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A waived fine must carry the librarian's justification for the audit trail.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function toDto(): WaiveFineDTO
    {
        return new WaiveFineDTO(reason: $this->string('reason')->toString());
    }
}
