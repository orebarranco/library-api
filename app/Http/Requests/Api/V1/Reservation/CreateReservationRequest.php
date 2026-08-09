<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reservation;

use App\DTOs\Reservation\CreateReservationDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateReservationRequest extends FormRequest
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
            'book_id' => ['required', 'string', 'exists:books,id'],
        ];
    }

    public function toDto(): CreateReservationDTO
    {
        return new CreateReservationDTO(
            book_id: $this->string('book_id')->toString(),
        );
    }
}
