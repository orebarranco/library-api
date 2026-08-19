<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // RESTRICT, not cascade: deleting the actor must never erase the
            // record of what they did.
            $table->foreignUlid('user_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('action');
            $table->string('model_type');
            $table->string('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address');
            // No updated_at: an entry that can be revised is not an audit trail.
            $table->timestamp('created_at')->nullable();

            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
