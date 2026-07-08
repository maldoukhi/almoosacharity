<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Thin read/write wrapper around the `settings` key/value table, cached
 * forever (invalidated on every write) so hot paths — e.g. the Taqnyat
 * sender lookup on every outbound SMS — never hit the database.
 *
 * Secrets (provider API keys/tokens) are handled through
 * {@see setSecret()}/{@see getSecret()} rather than {@see set()}/{@see get()}
 * directly: the value persisted to the `settings` table (and therefore the
 * value that ends up in the `all()` cache entry above) is always the
 * ciphertext produced by `Crypt::encryptString()`, never the plaintext
 * secret. Decryption only ever happens on demand, inside {@see getSecret()},
 * and the decrypted value is never itself cached.
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

    /**
     * Encrypt-and-store a secret value (e.g. a provider API key/token). An
     * empty/null value deletes the stored secret rather than persisting an
     * empty ciphertext, so callers can treat "clear the field" and "leave
     * it unset" the same way.
     */
    public function setSecret(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            $this->set($key, null);

            return;
        }

        $this->set($key, Crypt::encryptString($value));
    }

    /**
     * Decrypt and return a secret previously stored via {@see setSecret()},
     * or null when unset. A ciphertext that fails to decrypt (e.g. the app
     * key rotated) is treated as unset rather than thrown, since the
     * calling code always has a config/.env fallback to use instead.
     */
    public function getSecret(string $key): ?string
    {
        $encrypted = $this->get($key);

        if ($encrypted === null || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * A display-safe "•••• 1234" (last 4 characters) placeholder for a
     * stored secret, for settings screens — the decrypted value itself
     * must never be sent back to the browser.
     */
    public function maskedSecret(string $key): ?string
    {
        $value = $this->getSecret($key);

        if ($value === null || $value === '') {
            return null;
        }

        return '•••• '.Str::substr($value, -4);
    }
}
