<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\DTOs\Auth\LoginUserDTO;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'password' => ['required', 'string'],
        ];
    }

    public function toDto(): LoginUserDTO
    {
        return new LoginUserDTO(
            email: $this->string('email')->toString(),
            password: $this->string('password')->toString()
        );
    }
}
