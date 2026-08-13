<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('op_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource');
            $table->string('type', 50);
            $table->uuid('client_id')->nullable();
            $table->string('server_id')->nullable();
            $table->string('status', 20);
            $table->json('errors')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
    }
};
