<?php

namespace App\Services\Mail;

use App\Support\Settings;

/**
 * Applies the admin-managed SMTP settings (host/port/encryption/username/
 * password + from address/name — set from the notifications settings
 * screen and persisted via {@see Settings}) onto the live mailer config at
 * send time, overriding the config/mail.php + .env defaults.
 *
 * When no host has been saved, nothing is touched and the shipped .env
 * configuration keeps working as-is — so this is safe to call
 * unconditionally before every send. The SMTP password is stored as an
 * encrypted secret and only ever read here (never sent back to the
 * browser), mirroring how the Taqnyat key / Okta token are handled.
 */
class ApplyMailSettings
{
    public function __construct(private readonly Settings $settings) {}

    public function apply(): void
    {
        $host = trim((string) $this->settings->get('mail_host'));

        // No admin override saved — leave the config/.env mailer untouched.
        if ($host === '') {
            $this->applyFrom();

            return;
        }

        $encryption = $this->settings->get('mail_encryption') ?: 'tls';
        $port = (int) ($this->settings->get('mail_port') ?: ($encryption === 'ssl' ? 465 : 587));
        $username = trim((string) $this->settings->get('mail_username'));
        $password = $this->settings->getSecret('mail_password');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username !== '' ? $username : null,
            'mail.mailers.smtp.password' => $password !== null && $password !== '' ? $password : null,
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
            // Symfony's ESMTP transport derives STARTTLS/implicit-TLS from
            // the DSN scheme: 'smtps' for SSL, 'smtp' (with automatic
            // STARTTLS) otherwise.
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
        ]);

        $this->applyFrom();
    }

    /**
     * Apply the saved global "from" address/name when present, so outbound
     * mail is sent from the charity's own address rather than the .env
     * placeholder.
     */
    private function applyFrom(): void
    {
        $fromAddress = trim((string) $this->settings->get('mail_from_address'));
        $fromName = trim((string) $this->settings->get('mail_from_name'));

        if ($fromAddress !== '') {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName !== '') {
            config(['mail.from.name' => $fromName]);
        }
    }
}
