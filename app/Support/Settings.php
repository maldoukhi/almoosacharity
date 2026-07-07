<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Thin read/write wrapper around the `settings` key/value table, cached
 * forever (invalidated on every write) so hot paths — e.g. the Taqnyat
 * sender lookup on every outbound SMS — never hit the database.
 */
class Settings
{
    private const CACHE_KEY = 'app-settings.all';

    /**
     * All settings, keyed by their `key` column.
     *
     * @return array<string, ?string>
     */
    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget(self::CACHE_KEY);
    }
}
