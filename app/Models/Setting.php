<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'value', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get a setting value by key (JSON-decoded).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting || $setting->value === null) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key (JSON-encoded). Creates or updates.
     */
    public static function set(string $key, mixed $value, ?int $userId = null): static
    {
        $setting = static::firstOrCreate(['key' => $key], ['value' => $value]);
        $setting->value = $value;
        $setting->updated_by = $userId;
        $setting->save();

        return $setting;
    }
}
