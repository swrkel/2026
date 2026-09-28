<?php

namespace Modules\Membership\Services;

use Illuminate\Support\Facades\DB;
use Modules\Membership\Entities\MembershipCardSetting;

class CardSettingService
{
    public function queryForBusiness(int $businessId)
    {
        return MembershipCardSetting::query()
            ->leftJoin('users as created_user', 'created_user.id', '=', 'membership_card_settings.created_by')
            ->where('membership_card_settings.business_id', $businessId)
            ->select(
                'membership_card_settings.id',
                'membership_card_settings.length',
                'membership_card_settings.width',
                'membership_card_settings.created_by',
                'membership_card_settings.created_at',
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(created_user.first_name, ''), ' ', COALESCE(created_user.last_name, ''))), ''), created_user.username, '-') as added_by_name")
            )
            ->orderBy('membership_card_settings.created_at', 'desc');
    }

    public function create(int $businessId, int $userId, array $data): MembershipCardSetting
    {
        return MembershipCardSetting::create([
            'business_id' => $businessId,
            'length' => $this->normaliseDecimal($data['length'] ?? 0),
            'width' => $this->normaliseDecimal($data['width'] ?? 0),
            'created_by' => $userId,
        ]);
    }

    public function findForBusiness(int $businessId, int $id): MembershipCardSetting
    {
        return MembershipCardSetting::where('business_id', $businessId)->findOrFail($id);
    }

    public function update(MembershipCardSetting $cardSetting, array $data): MembershipCardSetting
    {
        $cardSetting->update([
            'length' => $this->normaliseDecimal($data['length'] ?? $cardSetting->length),
            'width' => $this->normaliseDecimal($data['width'] ?? $cardSetting->width),
        ]);

        return $cardSetting->fresh();
    }

    public function delete(MembershipCardSetting $cardSetting): bool
    {
        return (bool) $cardSetting->delete();
    }

    public function makeSampleHtml($length, $width, int $maxPx = 80): string
    {
        $length = (float) $length;
        $width = (float) $width;

        if ($length <= 0 || $width <= 0) {
            return '-';
        }

        $maxMm = max($length, $width);
        $widthPx = round($maxPx * ($width / $maxMm));
        $heightPx = round($maxPx * ($length / $maxMm));

        return '<div style="border:2px solid #333;background:#fff;width:' . $widthPx . 'px;height:' . $heightPx . 'px;display:inline-block;vertical-align:middle;"></div>'
            . '<div class="small text-muted">' . e(number_format($length, 2)) . ' mm × ' . e(number_format($width, 2)) . ' mm</div>';
    }

    private function normaliseDecimal($value): float
    {
        return round((float) str_replace(',', '', (string) $value), 2);
    }
}
