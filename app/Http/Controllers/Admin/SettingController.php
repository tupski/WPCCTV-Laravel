<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\ActivityLogService;
use App\Jobs\RetentionCleanupJob;

/**
 * Settings JSON. Only whitelisted keys are readable or writable.
 * The VAPID private key is env-only and can never be written here.
 */
class SettingController extends Controller
{
    /**
     * Writable keys. Anything outside this list is ignored.
     */
    public const WRITABLE_KEYS = [
        'whatsapp_number',
        'retention',
    ];

    /**
     * Explicitly forbidden keys — rejected, never silently written.
     */
    public const FORBIDDEN_KEYS = [
        'vapaid_private_key',
        'vapid_private_key',
    ];

    /**
     * API retention field → stored retention field (RetentionCleanupJob contract).
     */
    public const RETENTION_FIELD_MAP = [
        'page_views_days' => 'analytics_page_views',
        'whatsapp_clicks_days' => 'whatsapp_clicks',
        'activity_logs_days' => 'activity_logs',
    ];

    public function __construct(protected ActivityLogService $activityLog) {}

    public function index()
    {
        return response()->json([
            'data' => [
                'whatsapp_number' => Setting::get('whatsapp_number'),
                'vapaid_public_key' => Setting::get('vapaid_public_key'),
                'retention' => $this->currentRetention(),
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $admin = $request->user();

        foreach (self::FORBIDDEN_KEYS as $forbidden) {
            if ($request->has($forbidden)) {
                $this->activityLog->log('settings.forbidden_key_rejected', null, [
                    'key' => $forbidden,
                ], $admin);

                abort(422, 'Kunci pengaturan tidak diizinkan.');
            }
        }

        $data = $request->safe()->only(self::WRITABLE_KEYS);

        $changed = [];

        if (array_key_exists('whatsapp_number', $data)) {
            Setting::set('whatsapp_number', $data['whatsapp_number'], $admin->id);
            $changed[] = 'whatsapp_number';
        }

        if (array_key_exists('retention', $data) && is_array($data['retention'])) {
            Setting::set('retention', $this->normalizeRetention($data['retention']), $admin->id);
            $changed[] = 'retention';
        }

        if ($changed !== []) {
            $this->activityLog->log('settings.updated', null, [
                'changed' => $changed,
            ], $admin);
        }

        return response()->json([
            'data' => [
                'whatsapp_number' => Setting::get('whatsapp_number'),
                'vapaid_public_key' => Setting::get('vapaid_public_key'),
                'retention' => $this->currentRetention(),
            ],
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function currentRetention(): array
    {
        $stored = array_merge(
            RetentionCleanupJob::DEFAULT_TIERS,
            (array) Setting::get('retention', [])
        );

        $result = [];

        foreach (self::RETENTION_FIELD_MAP as $apiField => $storedField) {
            $result[$apiField] = (int) ($stored[$storedField] ?? RetentionCleanupJob::DEFAULT_TIERS[$storedField]);
        }

        return $result;
    }

    /**
     * Map API field names onto the stored retention keys. Unknown keys are dropped.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, int>
     */
    protected function normalizeRetention(array $input): array
    {
        $retention = [];

        foreach (self::RETENTION_FIELD_MAP as $apiField => $storedField) {
            if (array_key_exists($apiField, $input)) {
                $retention[$storedField] = (int) $input[$apiField];
            }
        }

        return $retention;
    }
}
