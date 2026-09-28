<?php

namespace Modules\SettlementSW\Services;

/** SW_SEP_003 preview service. */
class SettlementSwPreviewService extends SettlementSwBaseService
{
    public function buildPreview(array $sections): array
    {
        $total = 0;
        foreach ($sections as $section) {
            $total += (float) ($section['total'] ?? 0);
        }

        return [
            'sections' => $sections,
            'grand_total' => $this->money($total),
        ];
    }
}
