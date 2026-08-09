<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('book_copy_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('reservation_id')
                ->constrained()
                ->restrictOnDelete();
            $table->dateTime('loaned_at');
            $table->dateTime('due_date');
            $table->dateTime('returned_at')->nullable();
            $table->unsignedInteger('renewal_count')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['book_copy_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
