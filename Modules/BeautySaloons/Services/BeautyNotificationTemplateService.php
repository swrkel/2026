<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyNotificationTemplate;

class BeautyNotificationTemplateService
{
    public function render(BeautyNotificationTemplate $template, array $data = []): array
    {
        $subject = (string) $template->subject;
        $body = (string) $template->body;

        foreach ($data as $key => $value) {
            $subject = str_replace('{{'.$key.'}}', (string) $value, $subject);
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    public function defaultVariables(): array
    {
        return [
            'customer_name', 'staff_name', 'appointment_date', 'appointment_time',
            'service_name', 'branch_name', 'invoice_no', 'amount', 'wallet_balance',
            'membership_name', 'package_name', 'voucher_no', 'loyalty_points',
        ];
    }
}
