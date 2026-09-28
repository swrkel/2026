<?php

namespace Modules\Membership\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use App\Business;
use Modules\Membership\Entities\MembershipBusinessType;
use Modules\Membership\Entities\MembershipPointSetting;
use Modules\Membership\Entities\MembershipSetting;
use Yajra\DataTables\Facades\DataTables;
use Modules\Membership\Entities\MembershipMember;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Modules\Membership\Entities\MembershipBusinessName;
use Modules\Membership\Entities\MembershipCardSetting;
use Modules\Membership\Entities\MembershipSignature;

class MembershipSettingController extends Controller
{

    private function membershipSettingsHasColumn(string $column): bool
    {
        try {
            return Schema::hasColumn('membership_settings', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function membershipSettingData(array $data): array
    {
        $allowed = ['business_id', 'created_by'];
        foreach (['region', 'prefix', 'starting_number', 'next_sequence'] as $column) {
            if ($this->membershipSettingsHasColumn($column)) {
                $allowed[] = $column;
            }
        }

        return array_intersect_key($data, array_flip($allowed));
    }

    private function membershipSettingRegionQuery($query, ?string $region)
    {
        if ($this->membershipSettingsHasColumn('region')) {
            return $query->where('region', $region);
        }

        return $query;
    }
    private function membershipBusinessTypeHasActiveColumn(): bool
    {
        return Schema::hasColumn('membership_business_types', 'is_active');
    }

    private function businessTypeIsActive(MembershipBusinessType $businessType): bool
    {
        return ! $this->membershipBusinessTypeHasActiveColumn()
            || (bool) ($businessType->is_active ?? true);
    }

    private function businessTypeIsInUse(MembershipBusinessType $businessType): bool
    {
        return MembershipPointSetting::where('membership_business_type_id', $businessType->id)->exists()
            || MembershipBusinessName::where('membership_business_type_id', $businessType->id)->exists()
            || MembershipMember::where('membership_business_type_id', $businessType->id)->exists();
    }

    public function index()
    {

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $businessTypes = MembershipBusinessType::pluck('business_type', 'id');
        $businessNameBusinessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
            ->orderByRaw("business_id = 0 DESC")
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            })
            ->pluck('business_type', 'id');

        $businessNames = MembershipBusinessName::where('business_id', $business_id)
            ->with(['businessType:id,business_type', 'createdBy:id,username,first_name,last_name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $settings = MembershipSetting::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $cardSettings = MembershipCardSetting::where('business_id', $business_id)
            ->with('createdBy:id,username,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('membership::index', 
            compact('business', 'settings', 'businessTypes', 'businessNameBusinessTypes', 'cardSettings'));
    }

    public function getBusinessTypes(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $is_superadmin = auth()->user()->can('superadmin');

            $businessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
                ->with('createdBy:id,username,first_name,last_name')
                ->select('membership_business_types.*')
                ->orderByRaw("CASE WHEN business_id = {$business_id} THEN 0 ELSE 1 END")
                ->orderBy('created_at', 'desc')
                ->get()
                ->unique(function ($row) {
                    return mb_strtolower(trim($row->business_type));
                })
                ->values();

            return DataTables::of($businessTypes)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('user', function ($row) {
                    return optional($row->createdBy)->username ?? '-';
                })
                ->addColumn('action', function ($row) use ($business_id, $is_superadmin) {
                    $canEditSettings = auth()->user() && auth()->user()->can('edit_membership_settings');
                    $html = '
                        <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@viewBusinessType', $row->id) . '"
                            data-container=".business_type_modal"
                            class="btn btn-xs btn-info btn-modal">
                            <i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '
                    ';

                    if ($canEditSettings && ($is_superadmin || (int) $row->business_id === (int) $business_id)) {
                        $isInUse = $this->businessTypeIsInUse($row);
                        $isActive = $this->businessTypeIsActive($row);

                        $html .= '
                            <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@editBusinessType', $row->id) . '"
                                data-container=".business_type_modal"
                                class="btn btn-xs btn-primary btn-modal edit_btn">
                                <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '
                            </button>';

                        if ($isInUse && $isActive) {
                            $html .= '
                                <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@disableBusinessType', $row->id) . '"
                                    class="btn btn-xs btn-warning business_type_disable">
                                    <i class="glyphicon glyphicon-ban-circle"></i> ' . __('membership::lang.disable') . '
                                </button>';
                        } elseif ($isInUse) {
                            $html .= '
                                <span class="label label-default">' . __('membership::lang.disabled') . '</span>';
                        } else {
                            $html .= '
                                <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@destroyBusinessType', $row->id) . '"
                                    class="btn btn-xs btn-danger business_type_delete">
                                    <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '
                                </button>';
                        }
                    }

                    return $html;
                })
                ->rawColumns(['users', 'action'])
                ->make(true);
        }
    }

    public function getUsersByBusinessType($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $businessType = MembershipBusinessType::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $users = $businessType->users()
            ->where('users.business_id', $business_id)
            ->select('users.id', 'users.username', 'users.email', 'users.first_name', 'users.last_name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'email' => $user->email
                ];
            });

        return response()->json($users);
    }

    public function createBusinessType()
    {
        return view('membership::partials.business_type_create');
    }

    public function storeBusinessType(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'business_type' => 'required|string'
            ]);

            try {
                // Split input by comma OR new line
                $businessTypes = preg_split('/[\r\n,]+/', $request->business_type);

                $createdCount = 0;
                $skippedCount = 0;

                foreach ($businessTypes as $type) {
                    $type = trim($type);

                    if (empty($type)) {
                        continue;
                    }

                    // Case-insensitive duplicate check per business (uniqueness)
                    $exists = MembershipBusinessType::whereIn('business_id', [0, $business_id])
                        ->whereRaw('LOWER(business_type) = ?', [strtolower($type)])
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        continue;
                    }

                    MembershipBusinessType::create([
                        'business_id' => $business_id,
                        'business_type' => $type,
                        'created_by' => auth()->id(),
                    ]);

                    $createdCount++;
                }

                if ($createdCount === 0) {
                    return response()->json([
                        'success' => false,
                        'msg' => __('membership::lang.business_type_already_exists')
                    ]);
                }

                $msg = __('membership::lang.business_type_created_successfully');
                if ($skippedCount > 0) {
                    $msg .= ' ' . $skippedCount . ' ' . __('membership::lang.business_type') . '(s) ' . __('membership::lang.duplicate_skipped');
                }

                return response()->json([
                    'success' => true,
                    'msg' => $msg
                ]);

            } catch (\Exception $e) {
                \Log::emergency(
                    "File:" . $e->getFile() .
                    " Line:" . $e->getLine() .
                    " Message:" . $e->getMessage()
                );

                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ]);
            }
        }
    }

    public function viewBusinessType($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $businessType = MembershipBusinessType::where('id', $id)
            ->whereIn('business_id', [0, $business_id])
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();

        return view('membership::partials.business_type_view', compact('businessType'));
    }

    public function editBusinessType($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $allowedBusinessIds = auth()->user()->can('superadmin') ? [0, $business_id] : [$business_id];
        $businessType = MembershipBusinessType::where('id', $id)
            ->whereIn('business_id', $allowedBusinessIds)
            ->firstOrFail();

        return view('membership::partials.business_type_edit', compact('businessType'));
    }

    public function updateBusinessType(Request $request, $id)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $allowedBusinessIds = auth()->user()->can('superadmin') ? [0, $business_id] : [$business_id];

            $request->validate([
                'business_type' => [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($business_id, $id) {
                        $value = trim($value);
                        if ($value === '') {
                            return;
                        }
                        $exists = MembershipBusinessType::whereIn('business_id', [0, $business_id])
                            ->where('id', '!=', $id)
                            ->whereRaw('LOWER(business_type) = ?', [strtolower($value)])
                            ->exists();

                        if ($exists) {
                            $fail(__('membership::lang.business_type_already_exists'));
                        }
                    },
                ],
            ]);

            try {
                $businessType = MembershipBusinessType::where('id', $id)
                    ->whereIn('business_id', $allowedBusinessIds)
                    ->firstOrFail();

                $businessType->update([
                    'business_type' => trim($request->business_type),
                ]);

                $createdCount = 0;
                $additional = $request->input('additional_business_types', '');
                if (is_string($additional) && $additional !== '') {
                    $additionalTypes = preg_split('/[\r\n,]+/', $additional);
                    foreach ($additionalTypes as $type) {
                        $type = trim($type);
                        if ($type === '') {
                            continue;
                        }
                        $exists = MembershipBusinessType::whereIn('business_id', [0, $business_id])
                            ->whereRaw('LOWER(business_type) = ?', [strtolower($type)])
                            ->exists();
                        if (!$exists) {
                            MembershipBusinessType::create([
                                'business_id' => $business_id,
                                'business_type' => $type,
                                'created_by' => auth()->id(),
                            ]);
                            $createdCount++;
                        }
                    }
                }

                $output = [
                    'success' => true,
                    'msg' => __('membership::lang.business_type_updated_successfully'),
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return response()->json($output);
        }
    }

    public function destroyBusinessType($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $allowedBusinessIds = auth()->user()->can('superadmin') ? [0, $business_id] : [$business_id];

            $businessType = MembershipBusinessType::where('id', $id)
                ->whereIn('business_id', $allowedBusinessIds)
                ->firstOrFail();

            $isInUse = $this->businessTypeIsInUse($businessType);

            if ($isInUse) {
                return response()->json([
                    'success' => false,
                    'msg' => 'This business type is already in use and cannot be deleted.'
                ]);
            }

            $businessType->delete();

            $output = [
                'success' => true,
                'msg' => __('membership::lang.business_type_deleted_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    public function disableBusinessType($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $allowedBusinessIds = auth()->user()->can('superadmin') ? [0, $business_id] : [$business_id];

            if (! $this->membershipBusinessTypeHasActiveColumn()) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ]);
            }

            $businessType = MembershipBusinessType::where('id', $id)
                ->whereIn('business_id', $allowedBusinessIds)
                ->firstOrFail();

            if (! $this->businessTypeIsInUse($businessType)) {
                return response()->json([
                    'success' => false,
                    'msg' => __('membership::lang.business_type_not_in_use_delete_instead'),
                ]);
            }

            $businessType->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'msg' => __('membership::lang.business_type_disabled_successfully'),
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function getPointSettings(Request $request)
    {
        if ($request->ajax()) {
            
            $business_id = request()->session()->get('user.business_id');

            $pointSettings = MembershipPointSetting::where('business_id', $business_id)
                ->with([
                    'businessType:id,business_type',
                    'createdBy:id,username,first_name,last_name'
                ])
                ->select('id', 'membership_business_type_id', 'reward_point_percent', 'min_bill_total_to_earn',
                    'max_points_per_bill', 'min_bill_total_to_redeem', 'min_redeem_point',
                    'max_redeem_point_per_bill', 'expiry_period_months', 'expiry_period_years',
                    'created_by', 'created_at')
                ->orderBy('created_at', 'desc');

            return DataTables::of($pointSettings)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('business_type_name', function ($row) {
                    return $row->businessType ? $row->businessType->business_type : '-';
                })
                ->addColumn('expiry_period', function ($row) {
                    $period = [];
                    if ($row->expiry_period_years) {
                        $period[] = $row->expiry_period_years . ' ' . __('membership::lang.years');
                    }
                    if ($row->expiry_period_months) {
                        $period[] = $row->expiry_period_months . ' ' . __('membership::lang.months');
                    }
                    return !empty($period) ? implode(', ', $period) : '-';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->createdBy ? trim($row->createdBy->first_name . ' ' . $row->createdBy->last_name) : '-';
                })
                ->addColumn('action', function ($row) {
                    $canEditSettings = auth()->user() && auth()->user()->can('edit_membership_settings');
                    $html = '
                    <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@showPointSetting', $row->id) . '"
                        data-container=".point_setting_modal"
                        class="btn btn-xs btn-info btn-modal">
                        <i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '
                    </button>';
                    if ($canEditSettings) {
                        $html .= '
                    <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@editPointSetting', $row->id) . '"
                        data-container=".point_setting_modal"
                        class="btn btn-xs btn-primary btn-modal">
                        <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '
                    </button>
                    <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipSettingController@destroyPointSetting', $row->id) . '"
                        class="btn btn-xs btn-danger delete_point_setting_btn">
                        <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '
                    </button>';
                    }

                    return $html;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function createPointSetting()
    {
        $business_id = request()->session()->get('user.business_id');

        $businessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
            ->orderByRaw("business_id = 0 DESC") // Global types (e.g. Main System) on top
            ->orderBy('business_type')
            ->get()
            // Ensure we only show one entry per business_type (e.g. only one "Main System")
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            });

        return view('membership::partials.point_setting_create', compact('businessTypes'));
    }

    public function storePointSetting(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'membership_business_type_id' => 'required|exists:membership_business_types,id',
            'reward_point_percent' => 'required|numeric|min:0',
            'min_bill_total_to_earn' => 'required|numeric|min:0',
            'max_points_per_bill' => 'required|integer|min:0',
            'min_bill_total_to_redeem' => 'required|numeric|min:0',
            'min_redeem_point' => 'required|integer|min:0',
            'max_redeem_point_per_bill' => 'required|integer|min:0',
            'expiry_period_months' => 'nullable|integer|min:0',
        ]);

        try {
            MembershipPointSetting::create([
                'business_id' => $business_id,
                'membership_business_type_id' => $request->membership_business_type_id,
                'reward_point_percent' => $request->reward_point_percent,
                'min_bill_total_to_earn' => $request->min_bill_total_to_earn,
                'max_points_per_bill' => $request->max_points_per_bill,
                'min_bill_total_to_redeem' => $request->min_bill_total_to_redeem,
                'min_redeem_point' => $request->min_redeem_point,
                'max_redeem_point_per_bill' => $request->max_redeem_point_per_bill,
                'expiry_period_months' => $request->expiry_period_months,
                'expiry_period_years' => null,
                'created_by' => auth()->id(),
            ]);

            $output = [
                'success' => true,
                'msg' => __('membership::lang.point_setting_created_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    public function showPointSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $pointSetting = MembershipPointSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->with('businessType:id,business_type')
            ->firstOrFail();

        return view('membership::partials.point_setting_show', compact('pointSetting'));
    }

    public function editPointSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $pointSetting = MembershipPointSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $businessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
            ->orderByRaw("business_id = 0 DESC") // Global types (e.g. Main System) on top
            ->orderBy('business_type')
            ->get()
            // Ensure we only show one entry per business_type (e.g. only one "Main System")
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            });

        return view('membership::partials.point_setting_edit', compact('pointSetting', 'businessTypes'));
    }

    public function updatePointSetting(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'membership_business_type_id' => 'required|exists:membership_business_types,id',
            'reward_point_percent' => 'required|numeric|min:0',
            'min_bill_total_to_earn' => 'required|numeric|min:0',
            'max_points_per_bill' => 'required|integer|min:0',
            'min_bill_total_to_redeem' => 'required|numeric|min:0',
            'min_redeem_point' => 'required|integer|min:0',
            'max_redeem_point_per_bill' => 'required|integer|min:0',
            'expiry_period_months' => 'nullable|integer|min:0',
        ]);

        try {
            $pointSetting = MembershipPointSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $pointSetting->update([
                'membership_business_type_id' => $request->membership_business_type_id,
                'reward_point_percent' => $request->reward_point_percent,
                'min_bill_total_to_earn' => $request->min_bill_total_to_earn,
                'max_points_per_bill' => $request->max_points_per_bill,
                'min_bill_total_to_redeem' => $request->min_bill_total_to_redeem,
                'min_redeem_point' => $request->min_redeem_point,
                'max_redeem_point_per_bill' => $request->max_redeem_point_per_bill,
                'expiry_period_months' => $request->expiry_period_months,
                'expiry_period_years' => null,
            ]);

            $output = [
                'success' => true,
                'msg' => __('membership::lang.point_setting_updated_successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    public function destroyPointSetting($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $pointSetting = MembershipPointSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $pointSetting->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_successfully')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function editMembershipSettings()
    {
        $business_id = request()->session()->get('user.business_id');

        $settings = MembershipSetting::where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('membership::partials.membership_settings', compact('settings'));
    }

    public function storeMembershipSetting(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'region'          => 'required|string|max:255',
            'prefix'          => 'nullable|string|max:10',
            'starting_number' => 'required|integer|min:1',
        ]);

        try {
            $existingQuery = MembershipSetting::where('business_id', $business_id);
            $existing = $this->membershipSettingRegionQuery($existingQuery, $request->region)->first();

            if ($existing && $this->membershipSettingsHasColumn('region')) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('membership::lang.region_already_exists')
                ]);
            }

            if ($existing && ! $this->membershipSettingsHasColumn('region')) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('membership::lang.setting_already_exists')
                ]);
            }

            $data = $this->membershipSettingData([
                'business_id'     => $business_id,
                'region'          => $request->region,
                'prefix'          => $request->prefix,
                'starting_number' => $request->starting_number,
                'next_sequence'   => $request->starting_number,
                'created_by'      => auth()->id(),
            ]);

            $setting = MembershipSetting::create($data);

            return response()->json([
                'success' => true,
                'id'      => $setting->id,
                'region'  => $this->membershipSettingsHasColumn('region') ? $setting->region : $request->region,
                'msg'     => __('membership::lang.setting_created_successfully')
            ], 200);
        } catch (\Throwable $e) {
            \Log::emergency('Membership setting save failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function viewMembershipSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $setting = MembershipSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();

        return view('membership::partials.membership_setting_view', compact('setting'));
    }

    public function editMembershipSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $setting = MembershipSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        return response()->json($setting);
    }

    public function updateMembershipSetting(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'region'          => 'required|string|max:255',
            'prefix'          => 'nullable|string|max:10',
            'starting_number' => 'required|integer|min:1',
        ]);

        try {
            $setting = MembershipSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            if ($this->membershipSettingsHasColumn('region')) {
                $existingRegion = MembershipSetting::where('business_id', $business_id)
                    ->where('region', $request->region)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingRegion) {
                    return response()->json([
                        'success' => false,
                        'msg'     => __('membership::lang.region_already_exists')
                    ]);
                }
            }

            $setting->update($this->membershipSettingData([
                'region'          => $request->region,
                'prefix'          => $request->prefix,
                'starting_number' => $request->starting_number,
            ]));

            return response()->json([
                'success' => true,
                'msg'     => __('membership::lang.setting_updated_successfully')
            ]);
        } catch (\Throwable $e) {
            \Log::emergency('Membership setting update failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function destroyMembershipSetting($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $setting = MembershipSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $setting->delete();

            return response()->json([
                'success' => true,
                'msg'     => __('membership::lang.setting_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ]);
        }
    }

    public function updateMembershipSettings(Request $request)
    {
        return $this->storeMembershipSetting($request);
    }

    public function getMembers(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $members = MembershipMember::where('business_id', $business_id)
                ->with([
                    'businessType:id,business_type',
                    'createdBy:id,username,first_name,last_name'
                ])
                ->select('id', 'member_number', 'member_name', 'membership_business_type_id',
                    'default_mobile_number', 'qr_code_path', 'created_by', 'created_at')
                ->orderBy('created_at', 'desc');

            return DataTables::of($members)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('business_type_name', function ($row) {
                    return $row->businessType ? $row->businessType->business_type : '-';
                })
                ->addColumn('action', function ($row) {
                    return '
                    <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipController@editMember', $row->id) . '"
                        data-container=".member_modal"
                        class="btn btn-xs btn-primary btn-modal">
                        <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '
                    </button>
                    <button data-href="' . action('\Modules\Membership\Http\Controllers\MembershipController@destroyMember', $row->id) . '"
                        class="btn btn-xs btn-danger member_delete">
                        <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '
                    </button>
                    <a href="' . asset('uploads/' . $row->qr_code_path) . '" 
                       target="_blank" class="btn btn-xs btn-info">
                        <i class="fa fa-qrcode"></i> QR Code
                    </a>
                ';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function createMember()
    {
        $business_id = request()->session()->get('user.business_id');

        $businessTypes = MembershipBusinessType::where('business_id', $business_id)
            ->select('id', 'business_type')
            ->get();

        return view('membership::partials.member_create', compact('businessTypes'));
    }

    public function editMember($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $member = MembershipMember::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $businessTypes = MembershipBusinessType::where('business_id', $business_id)
            ->select('id', 'business_type')
            ->get();

        return view('membership::partials.member_edit', compact('member', 'businessTypes'));
    }

    public function updateMember(Request $request, $id)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'member_name' => 'required|string|max:255',
                'membership_business_type_id' => 'required|exists:membership_business_types,id',
                'default_mobile_number' => 'required|string|max:20',
                'other_mobile_numbers' => 'nullable|string',
            ]);

            try {
                $member = MembershipMember::where('id', $id)
                    ->where('business_id', $business_id)
                    ->firstOrFail();

                $member->update([
                    'member_name' => $request->member_name,
                    'membership_business_type_id' => $request->membership_business_type_id,
                    'default_mobile_number' => $request->default_mobile_number,
                    'other_mobile_numbers' => $request->other_mobile_numbers,
                ]);

                // Regenerate QR Code
                $qrData = json_encode([
                    'member_no' => $member->member_number,
                    'member_name' => $member->member_name,
                    'mobile_no' => $member->default_mobile_number
                ]);

                $fullPath = public_path('uploads/' . $member->qr_code_path);

                $writer = new PngWriter();
                $qrCode = QrCode::create($qrData)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->setSize(300)
                    ->setMargin(10)
                    ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());

                $result = $writer->write($qrCode);
                $result->saveToFile($fullPath);

                $output = [
                    'success' => true,
                    'msg' => __('membership::lang.member_updated_successfully')
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }

            return response()->json($output);
        }
    }

    public function destroyMember($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $member = MembershipMember::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Delete QR code file
            if ($member->qr_code_path && file_exists(public_path('uploads/' . $member->qr_code_path))) {
                unlink(public_path('uploads/' . $member->qr_code_path));
            }

            $member->delete();

            $output = [
                'success' => true,
                'msg' => __('Member deleted successfully')
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return response()->json($output);
    }

    public function storeMember(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'member_name' => 'required|string|max:255',
                'membership_business_type_id' => 'required|exists:membership_business_types,id',
                'default_mobile_number' => 'required|string|max:20',
                'other_mobile_numbers' => 'nullable|string',
            ]);

            try {
                $settings = MembershipSetting::where('business_id', $business_id)->first();

                if (!$settings) {
                    $settings = MembershipSetting::create([
                        'business_id' => $business_id,
                        'prefix' => 'MS',
                        'starting_number' => 1
                    ]);
                }

                $lastMember = MembershipMember::where('business_id', $business_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = $lastMember
                    ? intval(str_replace($settings->prefix, '', $lastMember->member_number)) + 1
                    : $settings->starting_number;

                $memberNumber = $settings->prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

                $member = MembershipMember::create([
                    'business_id' => $business_id,
                    'member_number' => $memberNumber,
                    'member_name' => $request->member_name,
                    'membership_business_type_id' => $request->membership_business_type_id,
                    'default_mobile_number' => $request->default_mobile_number,
                    'other_mobile_numbers' => $request->other_mobile_numbers,
                    'created_by' => auth()->id(),
                ]);

                // Generate QR Code
                $qrData = json_encode([
                    'member_no' => $member->member_number,
                    'member_name' => $member->member_name,
                    'mobile_no' => $member->default_mobile_number
                ]);

                $qrCodePath = 'qrcodes/' . $memberNumber . '.png';
                $fullPath = public_path('uploads/' . $qrCodePath);

                if (!file_exists(dirname($fullPath))) {
                    mkdir(dirname($fullPath), 0777, true);
                }

                // Tạo QR Code
                $writer = new PngWriter();
                $qrCode = QrCode::create($qrData)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
                    ->setSize(300)
                    ->setMargin(10)
                    ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin());

                $result = $writer->write($qrCode);
                $result->saveToFile($fullPath);

                $member->update(['qr_code_path' => $qrCodePath]);

                $output = [
                    'success' => true,
                    'msg' => __('membership::lang.member_created_successfully')
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong')
                ];
            }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->to('/membership/setting')->with('status', $output);
        }

        return redirect()->to('/membership/setting');
    }

    public function createBusinessName()
    {
        $business_id = request()->session()->get('user.business_id');
        $businessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
            ->orderByRaw("business_id = 0 DESC")
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            })
            ->pluck('business_type', 'id');

        return view('membership::partials.business_name_create', compact('businessTypes'));
    }

    public function getBusinessNames(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }
        
        $business_id = request()->session()->get('user.business_id');
        $businessNames = MembershipBusinessName::where('business_id', $business_id)
            ->with(['businessType:id,business_type', 'createdBy:id,username,first_name,last_name'])
            ->select('id', 'membership_business_type_id', 'business_name', 'created_by', 'created_at');

        return DataTables::of($businessNames)
            ->addColumn('business_type', function ($bn) {
                return optional($bn->businessType)->business_type ?? '-';
            })
            ->addColumn('added_by', function ($bn) {
                return optional($bn->createdBy)->username ?? '-';
            })
            ->addColumn('date_time', function ($bn) {
                return $bn->created_at ? $bn->created_at->format('Y-m-d H:i') : '-';
            })
            ->addColumn('action', function ($bn) {
                $canEditSettings = auth()->user() && auth()->user()->can('edit_membership_settings');
                $html = '<button data-href="' . action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'viewBusinessName'], $bn->id) . '" 
                            data-container=".business_name_modal" 
                            class="btn btn-xs btn-info btn-modal"><i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '</button> ';
                if ($canEditSettings) {
                    $html .= '<button data-href="' . action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'editBusinessName'], $bn->id) . '" 
                            data-container=".business_name_modal" 
                            class="btn btn-xs btn-primary btn-modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</button> ';
                    $html .= '<button data-id="' . $bn->id . '" class="btn btn-xs btn-danger delete_business_name_btn"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>';
                }

                return $html;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function storeBusinessName(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $request->validate([
            'membership_business_type_id' => 'required|exists:membership_business_types,id',
            'business_names' => 'required|array',
            'business_names.*' => 'required|string|max:255',
        ]);

        $typeId = $request->membership_business_type_id;
        $names = array_unique(array_filter(array_map('trim', $request->business_names)));

        if (empty($names)) {
            $output = ['success' => false, 'msg' => __('membership::lang.please_enter_business_name')];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output);
            }

            return redirect()->to('/membership/setting')->with('status', $output);
        }

        $existingNames = MembershipBusinessName::where('business_id', $business_id)
            ->whereIn(\DB::raw('LOWER(business_name)'), array_map('mb_strtolower', $names))
            ->pluck('business_name')
            ->map(fn ($name) => mb_strtolower($name))
            ->all();

        $existingLookup = array_flip($existingNames);
        $createdCount = 0;
        $skippedCount = 0;
        $rowsToInsert = [];

        foreach ($names as $name) {
            $normalizedName = mb_strtolower($name);

            if (isset($existingLookup[$normalizedName])) {
                $skippedCount++;
                continue;
            }

            $rowsToInsert[] = [
                'business_id'                 => $business_id,
                'membership_business_type_id' => $typeId,
                'business_name'               => $name,
                'created_by'                  => auth()->id(),
                'created_at'                  => now(),
                'updated_at'                  => now(),
            ];
            $existingLookup[$normalizedName] = true;
            $createdCount++;
        }

        if (!empty($rowsToInsert)) {
            MembershipBusinessName::insert($rowsToInsert);
        }

        if ($createdCount === 0) {
            $output = [
                'success' => false,
                'msg' => __('membership::lang.business_name_already_exists')
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output);
            }

            return redirect()->to('/membership/setting')->with('status', $output);
        }

        $msg = __('messages.saved_successfully');
        if ($skippedCount > 0) {
            $msg .= ' ' . $skippedCount . ' ' . __('membership::lang.Business_name') . '(s) ' . __('membership::lang.duplicate_skipped');
        }

        $output = ['success' => true, 'msg' => $msg];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->to('/membership/setting')->with('status', $output);
    }

    public function viewBusinessName($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $businessName = MembershipBusinessName::where('id', $id)
            ->where('business_id', $business_id)
            ->with(['businessType:id,business_type', 'createdBy:id,username,first_name,last_name'])
            ->firstOrFail();

        return view('membership::partials.business_name_view', compact('businessName'));
    }

    public function editBusinessName($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $businessName = MembershipBusinessName::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $businessTypes = MembershipBusinessType::whereIn('business_id', [0, $business_id])
            ->orderByRaw("business_id = 0 DESC")
            ->orderBy('business_type')
            ->get()
            ->unique(function ($type) {
                return mb_strtolower($type->business_type);
            })
            ->pluck('business_type', 'id');

        return view('membership::partials.business_name_edit', compact('businessName', 'businessTypes'));
    }

    public function updateBusinessName(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $businessName = MembershipBusinessName::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        $request->validate([
            'membership_business_type_id' => 'required|exists:membership_business_types,id',
            'business_name' => 'required|string|max:255',
            'additional_business_names' => 'nullable|array',
            'additional_business_names.*' => 'nullable|string|max:255',
        ]);

        $primaryName = trim($request->business_name);
        
        // Check duplicate for primary name
        $duplicateExists = MembershipBusinessName::where('business_id', $business_id)
            ->where('id', '!=', $id)
            ->whereRaw('LOWER(business_name) = ?', [strtolower($primaryName)])
            ->exists();
            
        if ($duplicateExists) {
            return response()->json([
                'success' => false, 
                'msg' => __('membership::lang.business_name_already_exists')
            ]);
        }

        $businessName->update([
            'membership_business_type_id' => $request->membership_business_type_id,
            'business_name' => $primaryName,
        ]);

        $typeId = $request->membership_business_type_id;
        $additionalNames = $request->input('additional_business_names', []);
        
        $createdCount = 0;
        $skippedCount = 0;
        
        if (!empty($additionalNames)) {
            $additionalNames = array_unique(array_filter(array_map('trim', $additionalNames)));
            
            foreach ($additionalNames as $name) {
                if ($name === '') continue;
                
                // Case-insensitive check
                $exists = MembershipBusinessName::where('business_id', $business_id)
                    ->whereRaw('LOWER(business_name) = ?', [strtolower($name)])
                    ->exists();
                    
                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                MembershipBusinessName::create([
                    'business_id'                 => $business_id,
                    'membership_business_type_id' => $typeId,
                    'business_name'               => $name,
                    'created_by'                  => auth()->id(),
                ]);
                $createdCount++;
            }
        }

        $msg = __('messages.updated_successfully');
        if ($skippedCount > 0) {
            $msg .= ' ' . $skippedCount . ' ' . __('membership::lang.Business_name') . '(s) ' . __('membership::lang.duplicate_skipped');
        }

        return response()->json(['success' => true, 'msg' => $msg]);
    }

    public function destroyBusinessName($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $businessName = MembershipBusinessName::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $businessName->delete();

            return response()->json([
                'success' => true,
                'msg'     => __('messages.deleted_success')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Show create card setting form
     */
    public function createCardSetting()
    {
        return view('membership::partials.card_setting_create');
    }

    /**
     * Get card settings for DataTable
     */
    public function getCardSettings(Request $request)
    {
        if ($request->ajax()) {
            
            $business_id = request()->session()->get('user.business_id');

            $cardSettings = MembershipCardSetting::where('business_id', $business_id)
                ->with('createdBy:id,username,first_name,last_name')
                ->select('id', 'length', 'width', 'created_by', 'created_at')
                ->orderBy('created_at', 'desc');

            return DataTables::of($cardSettings)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('size_details', function ($row) {
                    return $row->length . ' mm × ' . $row->width . ' mm';
                })
                ->addColumn('card_sample', function ($row) {
                    $l = (float) $row->length;
                    $w = (float) $row->width;
                    if ($l <= 0 || $w <= 0) {
                        return '-';
                    }
                    $maxPx = 80;
                    $maxMm = max($l, $w);
                    $wPx = round($maxPx * ($w / $maxMm));
                    $hPx = round($maxPx * ($l / $maxMm));
                    return '<div style="border:2px solid #333;background:#fff;width:' . $wPx . 'px;height:' . $hPx . 'px;display:inline-block;vertical-align:middle;"></div><div class="small text-muted">' . e($row->length) . ' mm × ' . e($row->width) . ' mm</div>';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->createdBy ? trim($row->createdBy->first_name . ' ' . $row->createdBy->last_name) : '-';
                })
                ->addColumn('action', function ($row) {
                    $canEditSettings = auth()->user() && auth()->user()->can('edit_membership_settings');
                    $viewUrl = action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'viewCardSetting'], [$row->id]);
                    $editUrl = action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'editCardSetting'], [$row->id]);
                    $deleteUrl = action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'destroyCardSetting'], [$row->id]);

                    // IS1618: keep Action column visible. Use direct buttons instead of a dropdown
                    // because the previous dropdown was clipped/hidden inside the responsive table.
                    $html = '<div class="membership-card-action-buttons" style="white-space:nowrap; min-width:150px;">';
                    $html .= '<button type="button" class="btn btn-xs btn-info membership-card-setting-modal-trigger" data-href="' . e($viewUrl) . '"><i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '</button> ';
                    if ($canEditSettings) {
                        $html .= '<button type="button" class="btn btn-xs btn-primary membership-card-setting-modal-trigger" data-href="' . e($editUrl) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</button> ';
                        $html .= '<button type="button" class="btn btn-xs btn-danger delete_card_setting_btn" data-href="' . e($deleteUrl) . '"><i class="glyphicon glyphicon-trash"></i> ' . __('messages.delete') . '</button>';
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->rawColumns(['action', 'card_sample'])
                ->make(true);
        }
    }

    /**
     * Store card setting
     */
    public function storeCardSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'length' => 'required|numeric|min:0.01',
                'width' => 'required|numeric|min:0.01'
            ]);

            MembershipCardSetting::create([
                'business_id' => $business_id,
                'length' => $request->length,
                'width' => $request->width,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully')
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * View card setting
     */
    public function viewCardSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $cardSetting = MembershipCardSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();

        return view('membership::partials.card_setting_view', compact('cardSetting'));
    }

    /**
     * Edit card setting
     */
    public function editCardSetting($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $cardSetting = MembershipCardSetting::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        return view('membership::partials.card_setting_edit', compact('cardSetting'));
    }

    /**
     * Update card setting
     */
    public function updateCardSetting(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $cardSetting = MembershipCardSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $data = $request->only(['length', 'width']);
            $cardSetting->update($data);

            return response()->json([
                'success' => true,
                'msg' => __('messages.updated_successfully')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Delete card setting
     */
    public function destroyCardSetting($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $cardSetting = MembershipCardSetting::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $cardSetting->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_success')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Show create signature form
     */
    public function createSignature()
    {
        return view('membership::partials.signature_create');
    }

    /**
     * Get signatures for DataTable
     */
    public function getSignatures(Request $request)
    {
        if ($request->ajax()) {
            
            $business_id = request()->session()->get('user.business_id');

            $signatures = MembershipSignature::where('business_id', $business_id)
                ->with('createdBy:id,username,first_name,last_name')
                ->select('id', 'signature_path', 'is_active', 'created_by', 'created_at')
                ->orderBy('created_at', 'desc');

            return DataTables::of($signatures)
                ->addColumn('date_time', function ($row) {
                    return $row->created_at->format('Y-m-d H:i');
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_active) {
                        return '<span class="label label-success">' . __('membership::lang.active') . '</span>';
                    }
                    return '<span class="label label-danger">' . __('membership::lang.inactive') . '</span>';
                })
                ->addColumn('signature_preview', function ($row) {
                    if (!$row->signature_path || !file_exists(public_path('uploads/' . $row->signature_path))) {
                        return '-';
                    }
                    $ext = strtolower(pathinfo($row->signature_path, PATHINFO_EXTENSION));
                    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif'];
                    if (in_array($ext, $imageExts)) {
                        return '<img src="' . asset('uploads/' . $row->signature_path) . '" style="max-width: 150px; max-height: 60px;" alt="Signature">';
                    }
                    return '<span class="text-muted"><i class="fa fa-file"></i> ' . e($ext) . '</span>';
                })
                ->addColumn('added_by', function ($row) {
                    return $row->createdBy ? trim($row->createdBy->first_name . ' ' . $row->createdBy->last_name) : '-';
                })
                ->addColumn('action', function ($row) {
                    $canEditSettings = auth()->user() && auth()->user()->can('edit_membership_settings');
                    $html = '<div class="btn-group">';
                    $html .= '<button type="button" class="btn btn-info btn-xs btn-modal view_signature_btn" 
                        data-href="' . action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'viewSignature'], [$row->id]) . '"
                        data-container=".signature_modal">
                        <i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '
                    </button>';
                    if ($canEditSettings) {
                        $html .= '<button type="button" class="btn btn-primary btn-xs btn-modal edit_signature_btn" 
                        data-href="' . action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'editSignature'], [$row->id]) . '"
                        data-container=".signature_modal">
                        <i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '
                    </button>';
                        $html .= '<button type="button" class="btn btn-danger btn-xs delete_signature_btn" 
                        data-href="' . action([\Modules\Membership\Http\Controllers\MembershipSettingController::class, 'destroySignature'], [$row->id]) . '">
                        <i class="fa fa-trash"></i> ' . __('messages.delete') . '
                    </button>';
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->rawColumns(['action', 'signature_preview', 'status'])
                ->make(true);
        }
    }

    /**
     * Store signature
     */
    public function storeSignature(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $request->validate([
                'signature' => 'required|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,tif,pdf,doc,docx|max:2048'
            ]);

            if ($request->hasFile('signature')) {
                $file = $request->file('signature');
                
                $uploadPath = public_path('uploads/signatures');
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                
                $file->move($uploadPath, $filename);

                MembershipSignature::where('business_id', $business_id)
                    ->update(['is_active' => 0]);

                $signature = MembershipSignature::create([
                    'business_id' => $business_id,
                    'signature_path' => 'signatures/' . $filename,
                    'is_active' => 1,
                    'created_by' => auth()->id(),
                ]);

                return response()->json([
                    'success' => true,
                    'msg' => __('messages.saved_successfully')
                ], 200);
            }

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 400);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * View signature
     */
    public function viewSignature($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $signature = MembershipSignature::where('id', $id)
            ->where('business_id', $business_id)
            ->with('createdBy:id,username,first_name,last_name')
            ->firstOrFail();

        return view('membership::partials.signature_view', compact('signature'));
    }

    /**
     * Edit signature
     */
    public function editSignature($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $signature = MembershipSignature::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        return view('membership::partials.signature_edit', compact('signature'));
    }

    /**
     * Update signature
     */
    public function updateSignature(Request $request, $id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $signature = MembershipSignature::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            if ($request->hasFile('signature')) {
                $request->validate([
                    'signature' => 'required|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,tif,pdf,doc,docx|max:2048'
                ]);

                if ($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path))) {
                    unlink(public_path('uploads/' . $signature->signature_path));
                }

                $file = $request->file('signature');
                
                $uploadPath = public_path('uploads/signatures');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }
                
                $filename = time() . '_' . $file->getClientOriginalName();
                
                $file->move($uploadPath, $filename);
                
                $signature->update([
                    'signature_path' => 'signatures/' . $filename,
                    'is_active' => 1,
                ]);

                MembershipSignature::where('business_id', $business_id)
                    ->where('id', '!=', $id)
                    ->update(['is_active' => 0]);

                return response()->json([
                    'success' => true,
                    'msg' => __('messages.updated_successfully')
                ]);
            }

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    /**
     * Delete signature
     */
    public function destroySignature($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $signature = MembershipSignature::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Delete file if exists
            if ($signature->signature_path && file_exists(public_path('uploads/' . $signature->signature_path))) {
                unlink(public_path('uploads/' . $signature->signature_path));
            }

            $signature->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_success')
            ]);
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }
}
