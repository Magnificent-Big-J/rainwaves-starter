<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_tombstones', function (Blueprint $table) {
            $table->id();
            $table->string('resource');
            $table->string('resource_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('deleted_at');
            $table->timestamps();

            $table->index(['resource', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_tombstones');
    }
};
