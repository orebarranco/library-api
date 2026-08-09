<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('book_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->dateTime('reserved_at');
            $table->dateTime('approved_at')->nullable();
            $table->foreignUlid('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->dateTime('expires_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
