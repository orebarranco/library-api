<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\DTOs\User\UpdateUserDTO;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ];
    }

    public function toDto(): UpdateUserDTO
    {
        return new UpdateUserDTO(
            name: $this->string('name')->toString(),
            email: $this->string('email')->toString(),
        );
    }
}
