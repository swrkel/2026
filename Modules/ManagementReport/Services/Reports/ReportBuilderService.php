<?php
namespace Modules\ManagementReport\Services\Reports;
use Modules\ManagementReport\Support\TenantConnection;

use Illuminate\Support\Str;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Entities\ReportRunSection;
use Modules\ManagementReport\Support\ReportContext;

class ReportBuilderService
{
    protected $registry;
    protected $reviewSummary;

    public function __construct(SectionRegistry $registry, ReviewSummaryService $reviewSummary)
    {
        $this->registry = $registry;
        $this->reviewSummary = $reviewSummary;
    }

    public function preview(ReportContext $context)
    {
        $definitions = $this->registry->selected($context->sectionKeys);
        $sections = [];
        foreach ($definitions as $key => $definition) {
            $sections[$key] = [
                'key' => $key,
                'label' => $definition['label'],
                'view' => $definition['view'],
                'order' => (int) $definition['order'],
                'payload' => $this->registry->service($key)->build($context),
            ];
        }

        return [
            'meta' => $this->meta($context),
            'sections' => $sections,
            'review_summary' => $this->reviewSummary->build($sections),
        ];
    }

    public function persist(ReportContext $context)
    {
        $report = $this->preview($context);

        return TenantConnection::db()->transaction(function () use ($context, $report) {
            $run = ReportRun::create([
                'uuid' => (string) Str::uuid(),
                'business_id' => $context->businessId,
                'location_id' => $context->locationId,
                'store_id' => $context->storeId,
                'shift_id' => $context->shiftId,
                'report_type' => 'daily_management',
                'report_title' => 'Daily Management Report',
                'period_start' => $context->startDate->toDateString(),
                'period_end' => $context->endDate->toDateString(),
                'filter_payload' => $context->scopeArray(),
                'snapshot_payload' => $report,
                'status' => 'generated',
                'generated_by' => $context->userId,
                'generated_at' => now(),
            ]);

            foreach ($report['sections'] as $section) {
                ReportRunSection::create([
                    'report_run_id' => $run->id,
                    'section_key' => $section['key'],
                    'section_label' => $section['label'],
                    'sort_order' => $section['order'],
                    'section_payload' => $section['payload'],
                ]);
            }

            return $run->fresh(['sections']);
        });
    }

    protected function meta(ReportContext $context)
    {
        $business = $context->business();
        $location = $context->locationId ? TenantConnection::db()->table('business_locations')->where('id', $context->locationId)->first() : null;
        $store = ($context->storeId && TenantConnection::schema()->hasTable('stores')) ? TenantConnection::db()->table('stores')->where('id', $context->storeId)->first() : null;
        $user = auth()->user();

        return [
            'business_id' => $context->businessId,
            'business_name' => optional($business)->name ?: 'Business',
            'location_name' => optional($location)->name ?: 'All Locations',
            'store_name' => optional($store)->name ?: 'All Stores',
            'period_label' => $context->periodLabel(),
            'start_date' => $context->startDate->toDateString(),
            'end_date' => $context->endDate->toDateString(),
            'generated_at' => now()->toIso8601String(),
            'data_as_of' => $context->endDate->toIso8601String(),
            'data_basis' => 'date_effective',
            'generated_by' => $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System',
            'currency_decimals' => $context->currencyDecimals,
        ];
    }
}
