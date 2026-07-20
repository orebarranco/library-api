<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservation;

use App\DTOs\Reservation\RejectReservationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class RejectReservationRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function toDto(): RejectReservationDTO
    {
        return new RejectReservationDTO(
            reason: $this->string('reason')->toString(),
        );
    }
}
