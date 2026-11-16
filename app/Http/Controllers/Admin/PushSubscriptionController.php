<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Current user's subscriptions. Users may always manage their own;
     * `manage-settings` additionally unlocks the same listing without scoping.
     */
    public function index(Request $request)
    {
        $subscriptions = PushSubscription::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $subscriptions]);
    }

    /**
     * Upsert by endpoint. `user_id` is server-forced and never read from input.
     */
    public function store(StorePushSubscriptionRequest $request)
    {
        $data = $request->safe()->only([
            'endpoint', 'p256dh', 'auth', 'user_agent', 'browser', 'expires_at',
        ]);

        // `endpoint` carries a global unique index: one browser endpoint can only
        // ever map to one row. Ownership is re-stamped to the current session user.
        $subscription = PushSubscription::updateOrCreate(
            [
                'endpoint' => $data['endpoint'],
            ],
            [
                'user_id' => $request->user()->id,
                'p256dh' => $data['p256dh'],
                'auth' => $data['auth'],
                'user_agent' => $data['user_agent'] ?? null,
                'browser' => $data['browser'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'active' => true,
            ],
        );

        return response()->json(['data' => $subscription], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, PushSubscription $subscription)
    {
        $this->authorizeSubscription($request, $subscription);

        $subscription->delete();

        return response()->json(['message' => 'Langganan notifikasi berhasil dihapus.']);
    }

    /**
     * Manual unsubscribe from the dashboard UI.
     */
    public function toggleActive(Request $request, PushSubscription $subscription)
    {
        $this->authorizeSubscription($request, $subscription);

        $subscription->active = ! $subscription->active;
        $subscription->save();

        return response()->json(['data' => $subscription->fresh()]);
    }

    /**
     * Own subscription OR `manage-settings`.
     */
    protected function authorizeSubscription(Request $request, PushSubscription $subscription): void
    {
        $user = $request->user();

        abort_unless(
            $subscription->user_id === $user->id || $user->can('manage-settings'),
            403,
            'Anda tidak berwenang mengubah langganan notifikasi ini.',
        );
    }
}
