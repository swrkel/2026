<?php

namespace Modules\Membership\Utils;

class MembershipCardGenerator
{
    public function generateCardNumber(string $prefix, int $nextNumber, int $padLength = 6): string
    {
        return trim($prefix) . str_pad((string) $nextNumber, $padLength, '0', STR_PAD_LEFT);
    }

    public function maskCardNumber(?string $cardNumber): string
    {
        if (empty($cardNumber)) {
            return '';
        }

        $plain = preg_replace('/\D+/', '', $cardNumber);
        if (strlen($plain) <= 4) {
            return $plain;
        }

        return str_repeat('*', max(strlen($plain) - 4, 0)) . substr($plain, -4);
    }
}
