<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Fine;

use App\DTOs\Fine\PayFineDTO;
use Illuminate\Foundation\Http\FormRequest;

final class PayFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The upper bound matches the `decimal(8,2)` column; whether the amount fits
     * the fine's remaining balance is a business rule, not a validation rule.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function toDto(): PayFineDTO
    {
        return new PayFineDTO(amount: $this->float('amount'));
    }
}
