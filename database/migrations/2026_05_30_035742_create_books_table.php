<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->string('isbn', 17)->unique();
            $table->year('publication_year');
            $table->decimal('book_value', 10, 2)->default(0);
            $table->foreignUlid('author_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('category_id')
                ->constrained()
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('author_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
