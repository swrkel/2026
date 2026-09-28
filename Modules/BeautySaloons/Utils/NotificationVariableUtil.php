<?php

namespace Modules\BeautySaloons\Utils;

class NotificationVariableUtil
{
    public static function appointmentVariables(): array
    {
        return [
            'customer_name', 'customer_mobile', 'staff_name', 'service_name',
            'appointment_date', 'appointment_time', 'branch_name', 'business_name',
        ];
    }

    public static function billingVariables(): array
    {
        return ['customer_name', 'invoice_no', 'amount', 'paid_amount', 'balance', 'payment_method'];
    }
}
