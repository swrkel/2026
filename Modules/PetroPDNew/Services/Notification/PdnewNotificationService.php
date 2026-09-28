<?php

namespace Modules\PetroPDNew\Services\Notification;

use Modules\PetroPDNew\Entities\PdnewNotificationLog;
use Modules\PetroPDNew\Entities\PdnewNotificationTemplate;
use Modules\PetroPDNew\Entities\PdnewSettlement;

class PdnewNotificationService
{
    private const CHANNELS = ['system', 'email', 'sms', 'whatsapp'];

    public function queueForSettlement(PdnewSettlement $settlement, string $eventKey): int
    {
        $templates = PdnewNotificationTemplate::query()
            ->where('business_id', $settlement->business_id)
            ->where('event_key', $eventKey)
            ->where('is_active', true)
            ->get();

        $created = 0;
        foreach ($templates as $template) {
            $channels = array_values(array_unique(array_filter(
                array_map('strval', (array) $template->channels),
                static fn (string $channel): bool => in_array($channel, self::CHANNELS, true)
            )));

            foreach ($channels as $channel) {
                // The outbox processor has a single-row processing claim. Using
                // firstOrCreate additionally makes a retry after a partial failure
                // safe: channels already queued are not duplicated.
                $log = PdnewNotificationLog::query()->firstOrCreate(
                    [
                        'business_id' => $settlement->business_id,
                        'template_id' => $template->id,
                        'settlement_id' => $settlement->id,
                        'event_key' => $eventKey,
                        'channel' => $channel,
                    ],
                    [
                        'status' => 'queued',
                        'payload' => [
                            'subject' => $this->render((string) $template->subject, $settlement),
                            'body' => $this->render((string) $template->body, $settlement),
                        ],
                    ]
                );

                if ($log->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        return $created;
    }

    private function render(string $value, PdnewSettlement $settlement): string
    {
        return strtr($value, [
            '{settlement_number}' => (string) $settlement->settlement_number,
            '{settlement_date}' => (string) $settlement->settlement_date,
            '{operator_name}' => (string) $settlement->operator_name,
            '{expected_total}' => number_format((float) $settlement->expected_total, 4, '.', ','),
            '{received_total}' => number_format((float) $settlement->received_total, 4, '.', ','),
            '{source_shortage}' => number_format((float) $settlement->source_shortage_total, 4, '.', ','),
            '{source_excess}' => number_format((float) $settlement->source_excess_total, 4, '.', ','),
            '{operational_variance}' => number_format((float) $settlement->operational_variance_amount, 4, '.', ','),
            '{variance}' => number_format((float) $settlement->variance_amount, 4, '.', ','),
        ]);
    }
}
