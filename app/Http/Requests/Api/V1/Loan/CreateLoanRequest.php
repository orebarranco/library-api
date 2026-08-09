<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Loan;

use App\DTOs\Loan\CreateLoanDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateLoanRequest extends FormRequest
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
            'reservation_id' => ['required', 'string', 'exists:reservations,id'],
        ];
    }

    public function toDto(): CreateLoanDTO
    {
        return new CreateLoanDTO(
            reservation_id: $this->string('reservation_id')->toString(),
        );
    }
}
