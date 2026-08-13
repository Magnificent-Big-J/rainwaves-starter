<?php

return [

    // Member cap for a team with no active subscription — see the Teams module's
    // Team::maxMembers(), which falls back to this when there's no plan_key to look
    // up in config('billing-plans'). Deliberately not a key inside billing-plans.php
    // itself: that file's entries are all plan-shaped arrays other code iterates over
    // (PayFastController's FormRequests, BillingController::plans()) — a sibling
    // non-plan key there would break that iteration.
    'default_max_members' => 3,

    // How long an invite stays acceptable before TeamInvite::isExpired() is true.
    'invite_expiry_days' => 7,

];
