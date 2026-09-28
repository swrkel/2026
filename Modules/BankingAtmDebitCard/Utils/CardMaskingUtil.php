<?php
namespace Modules\BankingAtmDebitCard\Utils;
class CardMaskingUtil
{
    public static function mask(?string $cardNumber): string
    {
        $digits = preg_replace('/\D+/', '', (string) $cardNumber);
        if (strlen($digits) < 8) { return $digits; }
        return substr($digits, 0, 6).str_repeat('*', max(0, strlen($digits)-10)).substr($digits, -4);
    }
}
