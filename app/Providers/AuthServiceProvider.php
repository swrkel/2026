<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Services\Authorization\SuperAdminImpersonation;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            // Grant the tenant bypass only to a cryptographically bound,
            // currently active Login As Business session. Loose/stale session
            // markers must never turn a normal role into an unrestricted user.
            if (class_exists(SuperAdminImpersonation::class)
                && SuperAdminImpersonation::isActive($user)) {
                return true;
            }

            if ($ability === 'superadmin') { //'backup' removed from here
                $administratorList = array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) config('constants.administrator_usernames', ''))
                )));

                // A database role/direct permission named `superadmin` must not
                // elevate a tenant user. Only the configured central usernames
                // may ever satisfy this ability.
                return in_array((string) $user->username, $administratorList, true);
            }

            if ($user->hasRole('Admin#' . $user->business_id)) {
                return true;
            }
        });
    }
}
