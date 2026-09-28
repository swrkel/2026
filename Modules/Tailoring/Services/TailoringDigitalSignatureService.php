<?php
namespace Modules\Tailoring\Services;
class TailoringDigitalSignatureService
{
    public function enabled(): bool
    {
        return (bool) config('tailoring.digital_signature_enabled', false);
    }
}
