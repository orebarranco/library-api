<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Auth\ForgotPasswordDTO;
use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
        ];
    }

    public function toDto(): ForgotPasswordDTO
    {
        return new ForgotPasswordDTO(
            email: $this->string('email')->toString(),
        );
    }
}
