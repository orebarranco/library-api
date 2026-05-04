<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Auth\ResetPasswordDTO;
use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDto(): ResetPasswordDTO
    {
        return new ResetPasswordDTO(
            email: $this->string('email')->toString(),
            token: $this->string('token')->toString(),
            password: $this->string('password')->toString(),
        );
    }
}
