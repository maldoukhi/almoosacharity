<?php

namespace App\Actions\Approvals;

use App\Actions\Confirmations\CreateAidConfirmation;
use App\Actions\Confirmations\SendConfirmationLink;
use App\Models\Aid;
use App\Models\ApprovalFlowStage;
use App\Models\BeneficiaryStageResponse;
use App\Services\Messaging\Messenger;
use App\Support\BeneficiaryStageLink;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * (Re)issues the single "respond to this stage" link for an aid that has
 * entered a beneficiary_response approval stage: a fresh random token
 * (only its sha256 digest is persisted), a new expiry, and a clean
 * tracking slate — then sends it to the beneficiary's mobile over SMS
 * (always) and WhatsApp (when whatsapp_enabled is on), both via
 * {@see Messenger} so the send itself is queued.
 *
 * Keyed on (aid_id, approval_flow_stage_id) via updateOrCreate, so a
 * resend rotates the same row rather than accumulating history: the
 * previous link stops working the instant a new one is issued. Mirrors
 * {@see CreateAidConfirmation} +
 * {@see SendConfirmationLink}.
 */
class RequestBeneficiaryStageResponse
{
    public function __construct(
        private readonly Messenger $messenger,
        private readonly Settings $settings,
        private readonly BeneficiaryStageLink $link,
    ) {}

    /**
     * Issue and send the link. Returns the response record, or null when
     * the beneficiary has no mobile number on file (nothing to send to).
     */
    public function handle(Aid $aid, ApprovalFlowStage $stage): ?BeneficiaryStageResponse
    {
        $beneficiary = $aid->beneficiary()->first();

        // 24 alphanumeric chars ≈ 140 bits of entropy: unguessable while
        // keeping the token-only public link short.
        $rawToken = Str::random(24);

        $response = BeneficiaryStageResponse::query()->updateOrCreate(
            ['aid_id' => $aid->id, 'approval_flow_stage_id' => $stage->id],
            [
                'stage_name' => $stage->name,
                'token_hash' => BeneficiaryStageResponse::hashToken($rawToken),
                'expires_at' => now()->addDays((int) config('confirmations.ttl_days')),
                'sent_at' => null,
                'opened_at' => null,
                'responded_at' => null,
                'responded_ip' => null,
                'responded_user_agent' => null,
                'note' => null,
                'channel' => null,
            ],
        );

        if (blank($beneficiary?->mobile)) {
            Log::info('Skipped beneficiary stage-response link send: no mobile number on file.', [
                'beneficiary_stage_response_id' => $response->id,
            ]);

            return $response;
        }

        $url = $this->link->url($response, $rawToken);

        $body = strtr($this->settings->get('beneficiary_stage_body') ?: __('beneficiary_stage.default_body'), [
            '{name}' => $beneficiary->full_name,
            '{short_name}' => $beneficiary->short_name,
            '{stage}' => $stage->name,
            '{link}' => $url,
        ]);

        $body = $this->ensureLinkPresent($body, $url);

        $channel = 'sms';

        $this->messenger->sms($beneficiary->mobile, $body, related: $aid);

        if ($this->settings->get('whatsapp_enabled') === '1') {
            $this->messenger->whatsappText(
                $beneficiary->mobile,
                $body,
                related: $aid,
                idempotencyKey: "beneficiary-stage-{$response->id}-{$rawToken}-wa",
            );

            $channel = 'sms+whatsapp';
        }

        $response->update(['sent_at' => now(), 'channel' => $channel]);

        activity('beneficiary-stage-response')
            ->performedOn($response)
            ->log('beneficiary stage-response link sent');

        return $response;
    }

    private function ensureLinkPresent(string $body, string $link): string
    {
        return str_contains($body, $link) ? $body : $body."\n".$link;
    }
}
