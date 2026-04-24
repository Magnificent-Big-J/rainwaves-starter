<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('merchant_payment_id')->unique();
            $table->string('provider')->default('payfast');
            $table->string('token')->nullable()->unique();
            $table->string('item_name');
            $table->decimal('amount_requested', 12, 2);
            $table->decimal('recurring_amount', 12, 2);
            $table->date('billing_date');
            $table->unsignedTinyInteger('frequency')->default(3);
            $table->unsignedInteger('cycles')->default(0);
            $table->string('status')->default('initiated');
            $table->string('payment_status')->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
