<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('payfast');
            $table->string('event_type')->default('itn_received');
            $table->string('event_ref')->nullable();
            $table->json('payload');
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_type', 'event_ref'], 'payment_events_provider_type_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
