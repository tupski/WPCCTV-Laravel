<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\User;
use App\Policies\LeadPolicy;
use App\Services\WhatsAppTrackingService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit registration: LeadPolicy is not discovered automatically
        // because the model lives outside a conventional domain folder.
        Gate::policy(Lead::class, LeadPolicy::class);

        // WhatsApp tracking: 10 requests per minute per visitor + context.
        //
        // The key must never be empty. The visitor cookie is minted on the
        // response, so a first-time visitor's click arrives with no cookie at
        // all; keying on it alone collapsed every cookie-less visitor on the
        // site into one shared bucket (audit C4: 12 requests across distinct
        // IPs → 429). Fall back to a value the request actually carries:
        // resolved visitor token first, then a hash of client IP + user agent
        // + context. The context stays part of the key so distinct page types
        // never share a budget.
        RateLimiter::for('whatsapp-tracking', function (Request $request) {
            $context = (string) $request->query('context', 'unknown');
            $identity = $this->rateLimitIdentity($request);

            return Limit::perMinute(10)->by('whatsapp:'.$identity.':'.$context);
        });

        // Public contact form: 5 submissions per minute per IP. A human fills
        // one form; a bot floods, and the honeypot catches the polite ones.
        RateLimiter::for('public-lead', function (Request $request) {
            return Limit::perMinute(5)->by('lead:'.$request->ip());
        });

        // Legacy alias — keeps Phase 3 routes and existing 74 tests green.
        Gate::define('admin', function (User $user): bool {
            return $user->hasRole(...User::ADMIN_ROLES);
        });

        Gate::define('manage-content', function (User $user): bool {
            return $user->hasRole(
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
                User::ROLE_CONTENT_EDITOR,
            );
        });

        Gate::define('manage-leads', function (User $user): bool {
            return $user->hasRole(
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
                User::ROLE_LEAD_MANAGER,
            );
        });

        Gate::define('view-analytics', function (User $user): bool {
            return $user->hasRole(
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
                User::ROLE_ANALYST,
            );
        });

        Gate::define('manage-settings', function (User $user): bool {
            return $user->hasRole(
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
            );
        });

        Gate::define('manage-users', function (User $user): bool {
            return $user->hasRole(User::ROLE_SUPER_ADMIN);
        });

        Gate::define('view-activity-logs', function (User $user): bool {
            return $user->hasRole(
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMINISTRATOR,
                User::ROLE_LEAD_MANAGER,
                User::ROLE_ANALYST,
            );
        });
    }

    /**
     * Stable per-requester identity for the WhatsApp tracking limiter.
     *
     * Preference order:
     *  1. the visitor token the request actually carries (cookie, or a token
     *     resolved earlier in the request lifecycle),
     *  2. a hash of client IP + user agent + context.
     *
     * Returning an empty string is what caused C4 — every cookie-less visitor
     * shared `whatsapp-::product`. The hash branch guarantees a non-empty,
     * per-requester key even on a first click with no cookie at all.
     */
    protected function rateLimitIdentity(Request $request): string
    {
        $token = $request->cookie(WhatsAppTrackingService::VISITOR_COOKIE);

        if (is_string($token) && $token !== '') {
            return 'token:'.$token;
        }

        $context = (string) $request->query('context', 'unknown');

        return 'fp:'.hash('sha256', implode('|', [
            (string) $request->ip(),
            (string) $request->userAgent(),
            $context,
        ]));
    }
}
