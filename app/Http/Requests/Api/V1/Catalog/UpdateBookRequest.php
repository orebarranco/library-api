<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\DTOs\Catalog\UpdateBookDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBookRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'isbn' => ['sometimes', 'string', 'max:17', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'description' => ['sometimes', 'string'],
            'publication_year' => ['sometimes', 'integer', 'min:1000', 'max:'.date('Y')],
            'publisher' => ['sometimes', 'nullable', 'string', 'max:255'],
            'book_value' => ['sometimes', 'numeric', 'min:0'],
            'author_id' => ['sometimes', 'string', 'exists:authors,id'],
            'category_id' => ['sometimes', 'string', 'exists:categories,id'],
        ];
    }

    public function toDto(): UpdateBookDTO
    {
        return new UpdateBookDTO(
            title: $this->filled('title') ? $this->string('title')->toString() : null,
            isbn: $this->filled('isbn') ? $this->string('isbn')->toString() : null,
            description: $this->filled('description') ? $this->string('description')->toString() : null,
            publication_year: $this->filled('publication_year') ? $this->integer('publication_year') : null,
            publisher: $this->filled('publisher') ? $this->string('publisher')->toString() : null,
            book_value: $this->filled('book_value') ? $this->string('book_value')->toString() : null,
            author_id: $this->filled('author_id') ? $this->string('author_id')->toString() : null,
            category_id: $this->filled('category_id') ? $this->string('category_id')->toString() : null,
        );
    }
}
