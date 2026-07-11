<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\CreateBookDTO;
use Illuminate\Foundation\Http\FormRequest;

final class CreateBookRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:17', 'unique:books,isbn'],
            'publication_year' => ['required', 'integer', 'min:1000', 'max:'.date('Y')],
            'book_value' => ['required', 'numeric', 'min:0'],
            'author_id' => ['required', 'string', 'exists:authors,id'],
            'category_id' => ['required', 'string', 'exists:categories,id'],
        ];
    }

    public function toDto(): CreateBookDTO
    {
        return new CreateBookDTO(
            title: $this->string('title')->toString(),
            isbn: $this->string('isbn')->toString(),
            publication_year: $this->integer('publication_year'),
            book_value: $this->string('book_value')->toString(),
            author_id: $this->string('author_id')->toString(),
            category_id: $this->string('category_id')->toString(),
        );
    }
}
