<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    public function index(Request $request)
    {
        // Visiting the screen materialises the defaults, so the stored row is
        // an explicit mirror of what the user was shown. Reading preferences
        // never *depends* on the row existing — see NotificationPreference::forUser().
        $preferences = NotificationPreference::firstOrCreateForUser($request->user()->id);

        return response()->json(['data' => $preferences]);
    }

    /**
     * `user_id` is server-forced from the session; never read from input.
     */
    public function update(UpdateNotificationPreferencesRequest $request)
    {
        $data = $request->safe()->only([
            'whatsapp_clicks_enabled', 'lead_updates_enabled',
        ]);

        $preferences = NotificationPreference::firstOrCreateForUser($request->user()->id);

        if ($data !== []) {
            $preferences->fill($data);
        }

        $preferences->updated_at = now();
        $preferences->save();

        return response()->json(['data' => $preferences->fresh()]);
    }
}
