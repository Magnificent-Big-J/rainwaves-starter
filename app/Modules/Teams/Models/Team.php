<?php

namespace App\Modules\Teams\Models;

use App\Enums\SubscriptionStatus;
use App\Models\User;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = ['name', 'slug', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMembership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_memberships')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    /** Teams depends on Billing (see TeamsModule::dependencies()) — a team's plan/subscription. */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->whereNotIn('status', [SubscriptionStatus::Cancelled->value, SubscriptionStatus::Failed->value])
            ->latest()
            ->first();
    }

    /** The member cap for this team's current plan — the default when there's no active subscription. */
    public function maxMembers(): int
    {
        $planKey = $this->activeSubscription()?->plan_key;

        if ($planKey && config()->has("billing-plans.{$planKey}.max_members")) {
            return (int) config("billing-plans.{$planKey}.max_members");
        }

        return (int) config('teams.default_max_members', 3);
    }
}
