<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_copies', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('book_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default('available')->index();
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};
