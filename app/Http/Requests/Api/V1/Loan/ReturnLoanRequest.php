<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Loan;

use App\DTOs\Loan\ReturnLoanDTO;
use Illuminate\Foundation\Http\FormRequest;

final class ReturnLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Damage fines are set by the librarian's own assessment, capped to the
     * $5–$50 range defined in the business rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'damaged' => ['sometimes', 'boolean'],
            'damage_amount' => ['required_if_accepted:damaged', 'nullable', 'numeric', 'min:5', 'max:50'],
        ];
    }

    public function toDto(): ReturnLoanDTO
    {
        return new ReturnLoanDTO(
            damaged: $this->boolean('damaged'),
            damage_amount: $this->input('damage_amount') === null ? null : $this->float('damage_amount'),
        );
    }
}
