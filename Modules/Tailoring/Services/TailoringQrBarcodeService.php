<?php
namespace Modules\Tailoring\Services;
class TailoringQrBarcodeService
{
    public function makeJobCardCode(int $jobCardId): string
    {
        return 'TJ-' . str_pad((string) $jobCardId, 8, '0', STR_PAD_LEFT);
    }
}
