<?php

namespace Modules\Membership\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Services\CardSettingService;
use Yajra\DataTables\Facades\DataTables;

class CardSettingController extends Controller
{
    protected CardSettingService $cardSettingService;

    public function __construct(CardSettingService $cardSettingService)
    {
        $this->cardSettingService = $cardSettingService;
    }

    public function index(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $businessId = (int) $request->session()->get('user.business_id');
        $query = $this->cardSettingService->queryForBusiness($businessId);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $canEdit = auth()->user() && auth()->user()->can('edit_membership_settings');
                $viewUrl = url('/membership/setting/card-settings/' . $row->id);
                $editUrl = url('/membership/setting/card-settings/' . $row->id . '/edit');
                $deleteUrl = url('/membership/setting/card-settings/' . $row->id);

                $html = '<div class="btn-group">';
                $html .= '<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">';
                $html .= __('messages.action') . ' <span class="caret"></span></button>';
                $html .= '<ul class="dropdown-menu" role="menu">';
                $html .= '<li><a href="#" class="membership-card-setting-modal-trigger" data-href="' . e($viewUrl) . '"><i class="glyphicon glyphicon-eye-open"></i> ' . __('messages.view') . '</a></li>';

                if ($canEdit) {
                    $html .= '<li><a href="#" class="membership-card-setting-modal-trigger" data-href="' . e($editUrl) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    $html .= '<li><a href="#" class="delete_card_setting_btn" data-href="' . e($deleteUrl) . '"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->addColumn('date_time', function ($row) {
                return ! empty($row->created_at) ? date('Y-m-d H:i', strtotime($row->created_at)) : '';
            })
            ->editColumn('length', function ($row) {
                return number_format((float) $row->length, 2, '.', ',');
            })
            ->editColumn('width', function ($row) {
                return number_format((float) $row->width, 2, '.', ',');
            })
            ->addColumn('size_details', function ($row) {
                return number_format((float) $row->length, 2, '.', ',') . ' mm × ' . number_format((float) $row->width, 2, '.', ',') . ' mm';
            })
            ->addColumn('card_sample', function ($row) {
                return $this->cardSettingService->makeSampleHtml($row->length, $row->width);
            })
            ->addColumn('added_by', function ($row) {
                return $row->added_by_name ?: '-';
            })
            ->rawColumns(['action', 'card_sample'])
            ->make(true);
    }

    public function create()
    {
        return view('membership::partials.card_settings.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'length' => 'required|numeric|min:0.01',
                'width' => 'required|numeric|min:0.01',
            ]);

            $businessId = (int) $request->session()->get('user.business_id');
            $this->cardSettingService->create($businessId, (int) auth()->id(), $validated);

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Membership Card Setting store failed', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $cardSetting = $this->cardSettingService->findForBusiness($businessId, $id);

        return view('membership::partials.card_settings.show', compact('cardSetting'));
    }

    public function edit(Request $request, int $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $cardSetting = $this->cardSettingService->findForBusiness($businessId, $id);

        return view('membership::partials.card_settings.edit', compact('cardSetting'));
    }

    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'length' => 'required|numeric|min:0.01',
                'width' => 'required|numeric|min:0.01',
            ]);

            $businessId = (int) $request->session()->get('user.business_id');
            $cardSetting = $this->cardSettingService->findForBusiness($businessId, $id);
            $this->cardSettingService->update($cardSetting, $validated);

            return response()->json([
                'success' => true,
                'msg' => __('messages.updated_successfully'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Membership Card Setting update failed', [
                'id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $businessId = (int) $request->session()->get('user.business_id');
            $cardSetting = $this->cardSettingService->findForBusiness($businessId, $id);
            $this->cardSettingService->delete($cardSetting);

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_success'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Membership Card Setting delete failed', [
                'id' => $id,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}
