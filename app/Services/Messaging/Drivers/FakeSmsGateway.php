<?php

namespace App\Services\Messaging\Drivers;

use App\Services\Messaging\Contracts\SmsGatewayInterface;
use App\Services\Messaging\GatewayResponse;
use Illuminate\Support\Str;

/**
 * In-memory SMS gateway used locally and in tests. Always succeeds unless
 * explicitly told to fail via {@see FakeSmsGateway::failNext()}, so tests
 * can exercise the job's failure/retry path deterministically.
 */
class FakeSmsGateway implements SmsGatewayInterface
{
    /**
     * @var list<array{to: string, message: string}>
     */
    public static array $sent = [];

    protected static bool $shouldFail = false;

    protected static ?string $failureMessage = null;

    public function send(string $to, string $message): GatewayResponse
    {
        if (static::$shouldFail) {
            return GatewayResponse::failure(static::$failureMessage ?? 'Fake SMS gateway forced failure.');
        }

        static::$sent[] = ['to' => $to, 'message' => $message];

        return GatewayResponse::success('fake-sms-'.Str::uuid(), ['to' => $to, 'message' => $message]);
    }

    /**
     * Always "succeeds" (mirroring a healthy Taqnyat account) unless
     * {@see failNext()} was called, so local dev/tests can exercise the
     * settings screen's "verify connection" action without real
     * credentials.
     */
    public function verify(): GatewayResponse
    {
        if (static::$shouldFail) {
            return GatewayResponse::failure(static::$failureMessage ?? 'Fake SMS gateway forced failure.');
        }

        return GatewayResponse::success(null, ['balance' => '100.00', 'currency' => 'SAR']);
    }

    /**
     * A fixed, always-accepted fake sender list, so local dev/tests can
     * exercise the settings screen's "fetch available senders" action
     * without real Taqnyat credentials.
     */
    public function senders(): GatewayResponse
    {
        if (static::$shouldFail) {
            return GatewayResponse::failure(static::$failureMessage ?? 'Fake SMS gateway forced failure.');
        }

        $normalized = [
            ['name' => 'Almoosa', 'status' => 'accepted'],
            ['name' => 'Charity', 'status' => 'accepted'],
        ];

        return GatewayResponse::success(null, ['senders' => $normalized, 'normalizedSenders' => $normalized]);
    }

    /**
     * Force every subsequent call to fail, for testing the retry/failure path.
     */
    public static function failNext(?string $message = null): void
    {
        static::$shouldFail = true;
        static::$failureMessage = $message;
    }

    /**
     * Reset recorded state and forced-failure flag between tests.
     */
    public static function reset(): void
    {
        static::$sent = [];
        static::$shouldFail = false;
        static::$failureMessage = null;
    }
}
