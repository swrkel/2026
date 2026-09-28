<?php

namespace Modules\CommunicationHub\Services\Templates;

use Modules\CommunicationHub\Entities\CommunicationHubTemplate;

class TemplateDesignerService
{
    public function templateSummary()
    {
        return CommunicationHubTemplate::orderBy('channel')->orderBy('name')->get();
    }

    public function commonVariables(): array
    {
        return [
            '{BusinessName}' => 'Business name',
            '{CustomerName}' => 'Customer or member name',
            '{MemberName}' => 'My Health member name',
            '{OTP}' => 'One-time password',
            '{Amount}' => 'Amount',
            '{InvoiceNo}' => 'Invoice number',
            '{AppointmentDate}' => 'Appointment date',
            '{DoctorName}' => 'Doctor name',
            '{LoginLink}' => 'Login link',
        ];
    }

    public function validateVariables(string $content, array $allowedVariables): array
    {
        preg_match_all('/\{[A-Za-z0-9_]+\}/', $content, $matches);
        $used = array_unique($matches[0] ?? []);
        return array_values(array_diff($used, array_keys($allowedVariables)));
    }
}
