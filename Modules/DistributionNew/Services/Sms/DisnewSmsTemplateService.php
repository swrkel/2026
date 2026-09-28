<?php

namespace Modules\DistributionNew\Services\Sms;

use Illuminate\Support\Facades\DB;

class DisnewSmsTemplateService
{
    public function render(int $businessId, string $event, array $data): array
    {
        $template = DB::table('disnew_sms_templates')
            ->where('business_id', $businessId)
            ->where('event', $event)
            ->where('is_active', 1)
            ->first();

        $message = $template->message_template ?? $this->defaultMessage($event);
        foreach ($data as $key => $value) {
            $message = str_replace('{{'.$key.'}}', (string) $value, $message);
        }

        return [
            'template_id' => $template->id ?? null,
            'message' => trim($message),
            'send_to_customer' => (int)($template->send_to_customer ?? 1) === 1,
            'send_to_officers' => (int)($template->send_to_officers ?? 1) === 1,
        ];
    }

    private function defaultMessage(string $event): string
    {
        return match ($event) {
            'sales_order_created' => 'Sales order {{number}} has been created. Amount: {{amount}}.',
            'sales_order_updated' => 'Sales order {{number}} has been updated. Amount: {{amount}}.',
            'sales_invoice_created' => 'Sales invoice {{number}} has been created from order {{order_number}}. Amount: {{amount}}.',
            'loading_completed' => 'Loading {{number}} has been completed for vehicle {{vehicle}}.',
            'unloading_completed' => 'Unloading {{number}} has been completed for vehicle {{vehicle}}.',
            default => 'Distribution update: {{number}}.',
        };
    }
}
