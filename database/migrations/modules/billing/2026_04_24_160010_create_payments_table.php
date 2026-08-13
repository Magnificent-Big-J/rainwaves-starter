<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('merchant_payment_id')->unique();
            $table->string('provider')->default('payfast');
            $table->string('item_name');
            $table->string('item_description')->nullable();
            $table->decimal('amount_requested', 12, 2);
            $table->decimal('amount_gross', 12, 2)->nullable();
            $table->decimal('amount_fee', 12, 2)->nullable();
            $table->decimal('amount_net', 12, 2)->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('payfast_payment_id')->nullable()->unique();
            $table->string('status')->default('initiated');
            $table->string('payment_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
