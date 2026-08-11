<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignUlid('loan_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('type');
            $table->decimal('amount', 8, 2);
            $table->decimal('amount_paid', 8, 2)->default(0);
            $table->string('status')->default('pending');
            $table->text('description');
            $table->foreignUlid('waived_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('waived_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
