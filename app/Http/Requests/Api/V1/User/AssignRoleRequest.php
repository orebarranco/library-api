<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\User;

use App\DTOs\User\AssignRoleDTO;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignRoleRequest extends FormRequest
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
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
        ];
    }

    public function toDto(): AssignRoleDTO
    {
        return new AssignRoleDTO(
            role: UserRole::from($this->string('role')->toString()),
        );
    }
}
