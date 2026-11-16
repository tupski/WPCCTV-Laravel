<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Push subscriptions are an admin-only capability (AGENTS.md §10).
        return $this->user()->can('admin');
    }

    public function rules(): array
    {
        return [
            // No `unique` rule: re-posting the same endpoint must upsert the
            // existing row (multi-device = multiple endpoints, one per browser).
            'endpoint' => ['required', 'string', 'max:255'],
            'p256dh' => ['required', 'string', 'max:255'],
            'auth' => ['required', 'string', 'max:255'],
            'user_agent' => ['nullable', 'string', 'max:255'],
            'browser' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
