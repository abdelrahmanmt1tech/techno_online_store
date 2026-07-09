<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\DTOs\SyncWhatsAppTemplatesResult;
use App\WhatsApp\Services\WhatsAppCloudApiService;
use App\WhatsApp\Services\WhatsAppTemplatePayloadMapper;
use Illuminate\Support\Collection;

class SyncWhatsAppTemplatesFromMetaAction
{
    public function __construct(
        protected WhatsAppCloudApiService $api,
        protected WhatsAppTemplatePayloadMapper $mapper,
    ) {}

    public function execute(?WhatsAppNumber $number = null): SyncWhatsAppTemplatesResult
    {
        $result = new SyncWhatsAppTemplatesResult;

        foreach ($this->resolveNumbers($number) as $whatsappNumber) {
            try {
                $this->syncForNumber($whatsappNumber, $result);
            } catch (\Throwable $exception) {
                $result->errors[] = sprintf(
                    '%s: %s',
                    $whatsappNumber->display_phone_number,
                    $exception->getMessage(),
                );
            }
        }

        return $result;
    }

    /**
     * @return Collection<int, WhatsAppNumber>
     */
    protected function resolveNumbers(?WhatsAppNumber $number): Collection
    {
        if ($number !== null) {
            return collect([$number]);
        }

        return WhatsAppNumber::query()
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->orderByDesc('is_default')
            ->get()
            ->unique('whatsapp_business_account_id')
            ->values();
    }

    protected function syncForNumber(WhatsAppNumber $number, SyncWhatsAppTemplatesResult $result): void
    {
        if (blank($number->access_token) || blank($number->whatsapp_business_account_id)) {
            $result->skipped++;

            return;
        }

        $remoteTemplates = $this->api->fetchAllMessageTemplates($number);

        foreach ($remoteTemplates as $remoteTemplate) {
            if (! is_array($remoteTemplate)) {
                continue;
            }

            $mapped = $this->mapper->mapFromMeta($remoteTemplate);

            if ($mapped['name'] === '') {
                $result->skipped++;

                continue;
            }

            $template = WhatsAppTemplate::query()->updateOrCreate(
                [
                    'name' => $mapped['name'],
                    'language' => $mapped['language'],
                    'whatsapp_business_account_id' => $number->whatsapp_business_account_id,
                ],
                [
                    'whatsapp_number_id' => $number->id,
                    'provider_template_id' => $mapped['provider_template_id'],
                    'category' => $mapped['category'],
                    'status' => $mapped['status'],
                    'components' => $mapped['components'],
                    'variables_schema' => $mapped['variables_schema'],
                    'raw_payload' => $mapped['raw_payload'],
                    'last_synced_at' => now(),
                ],
            );

            if ($template->wasRecentlyCreated) {
                $result->created++;
            } else {
                $result->updated++;
            }
        }
    }
}
