<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owned by the Teams module, not Billing — these columns only exist when Teams is
 * enabled (TeamsModule::dependencies() requires Billing, never the reverse). A team's
 * plan/subscription belongs to the team, not an individual: team_id stays null for
 * existing/ordinary personal checkout, set only when a checkout is initiated for a
 * team. plan_key persists which config('billing-plans') key a checkout resolved to —
 * PayFastCheckoutService previously only stored the resolved item_name/amount
 * snapshot, not the plan identity itself, which is what Team::maxMembers() needs to
 * look up a team's current usage limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('plan_key')->nullable()->after('item_description');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('plan_key')->nullable()->after('item_name');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('plan_key');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn('plan_key');
        });
    }
};
