<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\StockTransferSetting;
use Modules\StockTransferNew\Services\StockTransferPermissionService;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class SettingsController extends Controller
{
    public function index(StockTransferPermissionService $permissionService)
    {
        $businessId = StockTransferTenant::businessId();
        $settings = StockTransferSetting::firstOrCreate(['business_id' => $businessId], []);
        $permissions = $permissionService->all();
        return view('stocktransfernew::settings.index', compact('settings','permissions'));
    }

    public function store(Request $request)
    {
        StockTransferSetting::updateOrCreate(['business_id' => StockTransferTenant::businessId()], [
            'approval_required' => $request->boolean('approval_required'),
            'allow_partial_receive' => $request->boolean('allow_partial_receive'),
            'require_stock_before_dispatch' => $request->boolean('require_stock_before_dispatch'),
            'auto_generate_document_numbers' => $request->boolean('auto_generate_document_numbers'),
        ]);
        return back()->with('status', 'Stock Transfer-New settings updated.');
    }
}
