<?php
namespace Modules\ManagementReport\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReportContext
{
    public $businessId;
    public $locationId;
    public $storeId;
    public $shiftId;
    public $startDate;
    public $endDate;
    public $sectionKeys;
    public $userId;
    public $currencyDecimals;

    public static function fromRequest(Request $request)
    {
        $context = new static();
        $context->businessId = (int) ($request->input('business_id') ?: session('user.business_id'));
        $locationId = (int) $request->input('location_id', 0);
        $storeId = (int) $request->input('store_id', 0);
        $shiftId = (int) $request->input('shift_id', 0);
        $context->locationId = $locationId > 0 ? $locationId : null;
        $context->storeId = $storeId > 0 ? $storeId : null;
        $context->shiftId = $shiftId > 0 ? $shiftId : null;
        $context->startDate = Carbon::parse($request->input('start_date', now()->toDateString()))->startOfDay();
        $context->endDate = Carbon::parse($request->input('end_date', now()->toDateString()))->endOfDay();
        $context->userId = Auth::id();
        $context->currencyDecimals = (int) config('managementreport.default_currency_decimals', 2);
        if (TenantConnection::schema()->hasTable('mgmt_report_settings')) {
            $stored = TenantConnection::db()->table('mgmt_report_settings')
                ->where('business_id', $context->businessId)
                ->whereNull('location_id')
                ->whereNull('store_id')
                ->where('setting_key', 'currency_decimals')
                ->value('setting_value');
            if ($stored !== null) {
                $decoded = json_decode($stored, true);
                $context->currencyDecimals = (int) ($decoded === null ? $stored : $decoded);
            }
        }

        if (!$context->businessId) {
            throw ValidationException::withMessages(['business_id' => 'A business must be selected.']);
        }

        $maxDays = (int) config('managementreport.max_date_range_days', 366);
        if ($context->startDate->gt($context->endDate) || $context->startDate->diffInDays($context->endDate) > $maxDays) {
            throw ValidationException::withMessages(['start_date' => 'Please select a valid date range of ' . $maxDays . ' days or less.']);
        }

        $sections = array_keys(config('managementreport_sections', []));
        $selected = (array) $request->input('sections', []);

        // 8053 backwards compatibility: old open report forms/bookmarks may
        // still submit the retired add_less key. Translate it to the restored/new
        // independently selectable sections before applying the registry.
        if (in_array('add_less', $selected, true)) {
            $selected = array_values(array_unique(array_merge(
                array_diff($selected, ['add_less']),
                ['received_in', 'out', 'total_add']
            )));
        }

        $context->sectionKeys = array_values(array_intersect($sections, $selected ?: $sections));

        return $context;
    }

    public function applyScope($query, $table = null)
    {
        $prefix = $table ? $table . '.' : '';
        $query->where($prefix . 'business_id', $this->businessId);
        if ($this->locationId) {
            $query->where($prefix . 'location_id', $this->locationId);
        }
        if ($this->storeId) {
            $query->where($prefix . 'store_id', $this->storeId);
        }
        return $query;
    }

    public function periodLabel()
    {
        if ($this->startDate->isSameDay($this->endDate)) {
            return $this->startDate->format('d M Y');
        }
        return $this->startDate->format('d M Y') . ' - ' . $this->endDate->format('d M Y');
    }

    public function scopeArray()
    {
        return [
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'store_id' => $this->storeId,
            'shift_id' => $this->shiftId,
            'start_date' => $this->startDate->toDateString(),
            'end_date' => $this->endDate->toDateString(),
            'sections' => $this->sectionKeys,
        ];
    }

    public function business()
    {
        return TenantConnection::db()->table('business')->where('id', $this->businessId)->first();
    }
}
