<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewModuleSetting;

class PdnewSettingsService
{
    public function __construct(private PdnewReferenceGuardService $references) {}

    public function forScope(int $businessId, ?int $locationId): array
    {
        $scope = implode(':', [$businessId, $locationId ?: 0]);
        $defaults = (array) config('petropdnew.defaults', []);
        $now = now();

        DB::table('pdnew_module_settings')->insertOrIgnore([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'scope_key' => $scope,
            'settlement_prefix' => $defaults['settlement_prefix'] ?? 'PDN-SET-',
            'day_end_prefix' => $defaults['day_end_prefix'] ?? 'PDN-DE-',
            'amount_decimals' => $defaults['amount_decimals'] ?? 4,
            'quantity_decimals' => $defaults['quantity_decimals'] ?? 3,
            'require_review' => $defaults['require_review'] ?? true,
            'require_approval' => $defaults['require_approval'] ?? true,
            'require_zero_variance' => $defaults['require_zero_variance'] ?? true,
            'allow_reopen' => $defaults['allow_reopen'] ?? true,
            'auto_import_closed_shifts' => $defaults['auto_import_closed_shifts'] ?? false,
            'is_active' => true,
            'settings' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $model = PdnewModuleSetting::query()
            ->where('scope_key', $scope)
            ->firstOrFail();

        return array_merge($defaults, $model->toArray(), (array) $model->settings);
    }

    public function update(int $businessId, ?int $locationId, array $data): PdnewModuleSetting
    {
        $this->references->assertLocation($businessId, $locationId);
        $scope = implode(':', [$businessId, $locationId ?: 0]);
        $known = [
            'settlement_prefix', 'day_end_prefix', 'amount_decimals', 'quantity_decimals',
            'require_review', 'require_approval', 'require_zero_variance', 'allow_reopen',
            'auto_import_closed_shifts', 'is_active',
        ];
        $base = array_intersect_key($data, array_flip($known));
        $extra = array_diff_key($data, array_flip(array_merge($known, ['location_id', '_token', '_method'])));

        return DB::transaction(function () use ($businessId, $locationId, $scope, $base, $extra): PdnewModuleSetting {
            $this->forScope($businessId, $locationId);
            $model = PdnewModuleSetting::query()
                ->where('scope_key', $scope)
                ->lockForUpdate()
                ->firstOrFail();
            $model->update(array_merge([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'settings' => $extra,
            ], $base));

            return $model->fresh();
        }, 3);
    }
}
