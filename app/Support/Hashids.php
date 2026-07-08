<?php

namespace App\Support;

/**
 * Dependency-free, reversible obfuscation of numeric model ids for URLs
 * (e.g. /aids/1 → /aids/aB3xK). Sequential ids are scrambled with a small
 * keyed Feistel cipher (keyed off the app key) and base62-encoded, so the
 * emitted route key is non-sequential and not guessable without the key —
 * without needing the bcmath/gmp extension that hashids/sqids require.
 *
 * Scope: 32-bit ids (up to ~4.29e9), ample for this application.
 */
class Hashids
{
    private const ROUNDS = 4;

    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private string $salt;

    public function __construct()
    {
        $this->salt = hash('sha256', (string) config('app.key'));
    }

    public function encode(int $id): string
    {
        $l = ($id >> 16) & 0xFFFF;
        $r = $id & 0xFFFF;

        for ($i = 0; $i < self::ROUNDS; $i++) {
            [$l, $r] = [$r, $l ^ $this->round($r, $i)];
        }

        return $this->toBase62((($l & 0xFFFF) << 16) | ($r & 0xFFFF));
    }

    /**
     * Decode a route key back to its id, or null if it is not a valid
     * hashid for this key.
     */
    public function decode(string $value): ?int
    {
        $combined = $this->fromBase62($value);

        if ($combined === null) {
            return null;
        }

        $l = ($combined >> 16) & 0xFFFF;
        $r = $combined & 0xFFFF;

        for ($i = self::ROUNDS - 1; $i >= 0; $i--) {
            [$l, $r] = [$r ^ $this->round($l, $i), $l];
        }

        return (($l & 0xFFFF) << 16) | ($r & 0xFFFF);
    }

    private function round(int $value, int $index): int
    {
        return hexdec(substr(hash_hmac('sha256', $value.':'.$index, $this->salt), 0, 4)) & 0xFFFF;
    }

    private function toBase62(int $number): string
    {
        if ($number === 0) {
            return '00000';
        }

        $base = strlen(self::ALPHABET);
        $out = '';

        while ($number > 0) {
            $out = self::ALPHABET[$number % $base].$out;
            $number = intdiv($number, $base);
        }

        // Pad to a stable minimum width so short ids aren't obviously small.
        return str_pad($out, 5, '0', STR_PAD_LEFT);
    }

    private function fromBase62(string $value): ?int
    {
        if ($value === '' || strlen($value) > 8) {
            return null;
        }

        $base = strlen(self::ALPHABET);
        $number = 0;

        foreach (str_split($value) as $char) {
            $position = strpos(self::ALPHABET, $char);

            if ($position === false) {
                return null;
            }

            $number = $number * $base + $position;
        }

        return $number;
    }
}
