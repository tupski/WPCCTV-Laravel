<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationPreferencesRequest;
use App\Models\NotificationPreference;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Browser notification settings for the signed-in admin.
 *
 * Rendering this page never triggers a permission prompt: the subscription
 * widget only asks the browser after the admin presses a button.
 */
class PushSettingsViewController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('admin');

        // Rendering the screen materialises the defaults (pre-existing
        // behaviour, kept so the row mirrors what the admin sees). The push
        // pipeline does not depend on this row existing — see H1.
        $preferences = NotificationPreference::firstOrCreateForUser($request->user()->id);

        $key = Setting::get('vapaid_public_key') ?? config('vapaid.public_key');

        return view('admin.push-settings.index', [
            'preferences' => $preferences,
            'pushCapable' => is_string($key) && $key !== '',
            'vapidPublicKey' => is_string($key) && $key !== '' ? $key : null,
        ]);
    }

    public function update(UpdateNotificationPreferencesRequest $request): RedirectResponse
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

        return redirect()
            ->route('admin.push-settings.index')
            ->with('status', 'Preferensi notifikasi tersimpan.');
    }
}
