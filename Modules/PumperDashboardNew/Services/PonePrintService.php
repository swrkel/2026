<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\PumperDashboardNew\Entities\PonePrintLog;

class PonePrintService
{
    public function __construct(private PoneSettingsService $settings) {}

    public function record(string $type, Model $model, string $template, ?string $paperSize = null, string $copyType = 'original'): PonePrintLog
    {
        $businessId = (int) ($model->business_id ?? optional($model->shift)->business_id ?? 0);
        $locationId = (int) ($model->location_id ?? optional($model->shift)->location_id ?? 0) ?: null;
        $config = $this->settings->get($businessId, $locationId);
        $log = PonePrintLog::query()->create([
            'business_id' => $businessId,
            'operator_profile_id' => $model->operator_profile_id ?? optional($model->shift)->operator_profile_id,
            'shift_id' => $model->shift_id ?? ($model instanceof \Modules\PumperDashboardNew\Entities\PoneShift ? $model->id : null),
            'printable_type' => $type,
            'printable_id' => (int) $model->getKey(),
            'template_name' => $template,
            'paper_size' => $paperSize ?: ($config['receipt_paper_size'] ?? '80mm'),
            'copy_type' => $copyType,
            'printed_at' => now(),
            'printed_by' => (int) auth()->id() ?: null,
            'ip_address' => request()?->ip(),
        ]);

        $updates = [];
        if (array_key_exists('printed_count', $model->getAttributes())) $updates['printed_count'] = (int) $model->printed_count + 1;
        if (array_key_exists('last_printed_at', $model->getAttributes())) $updates['last_printed_at'] = now();
        if (array_key_exists('print_copy_option', $model->getAttributes())) $updates['print_copy_option'] = $copyType;
        if ($updates) $model->forceFill($updates)->saveQuietly();
        return $log;
    }
}
