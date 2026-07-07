<?php

namespace App\Providers;

use App\Services\Messaging\Contracts\SmsGatewayInterface;
use App\Services\Messaging\Contracts\WhatsAppGatewayInterface;
use App\Services\Messaging\Drivers\FakeSmsGateway;
use App\Services\Messaging\Drivers\FakeWhatsAppGateway;
use App\Services\Messaging\Drivers\OktaWhatsAppGateway;
use App\Services\Messaging\Drivers\TaqnyatSmsGateway;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the SMS/WhatsApp gateway interfaces to a concrete driver chosen
 * from config('services.sms.driver') / config('services.whatsapp.driver').
 * Defaults to the in-memory "fake" driver so local dev/tests never make
 * real outbound calls unless explicitly configured otherwise.
 */
class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SmsGatewayInterface::class, function (): SmsGatewayInterface {
            $driver = config('services.sms.driver', 'fake');

            return match ($driver) {
                'taqnyat' => new TaqnyatSmsGateway(
                    apiKey: (string) config('services.taqnyat.api_key'),
                    sender: (string) config('services.taqnyat.sender'),
                ),
                default => new FakeSmsGateway,
            };
        });

        $this->app->singleton(WhatsAppGatewayInterface::class, function (): WhatsAppGatewayInterface {
            $driver = config('services.whatsapp.driver', 'fake');

            return match ($driver) {
                'okta' => new OktaWhatsAppGateway(
                    baseUrl: (string) config('services.okta_connect.base_url'),
                    token: (string) config('services.okta_connect.token'),
                    channelId: (string) config('services.okta_connect.channel_id'),
                ),
                default => new FakeWhatsAppGateway,
            };
        });
    }
}
