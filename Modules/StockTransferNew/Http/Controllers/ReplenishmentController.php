<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\MinStockRule;
use Modules\StockTransferNew\Entities\ReplenishmentProposal;
use Modules\StockTransferNew\Services\StockTransferReplenishmentService;

class ReplenishmentController extends Controller
{
    protected StockTransferReplenishmentService $service;

    public function __construct(StockTransferReplenishmentService $service)
    {
        $this->service = $service;
    }

    public function rules(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rules = MinStockRule::where('business_id', $businessId)->latest()->paginate(25);
        return view('stocktransfernew::replenishment.rules', compact('rules'));
    }

    public function storeRule(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $request->validate([
            'location_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'source_location_id' => 'nullable|integer',
            'source_store_id' => 'nullable|integer',
            'product_id' => 'required|integer',
            'variation_id' => 'nullable|integer',
            'min_qty' => 'required|numeric|min:0',
            'reorder_qty' => 'required|numeric|min:0',
            'preferred_transfer_qty' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['business_id'] = $businessId;
        $data['is_active'] = $request->boolean('is_active', true);
        $this->service->createRule($data, (int) auth()->id());
        return back()->with('status', __('stocktransfernew::lang.rule_saved'));
    }

    public function proposals(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $proposals = ReplenishmentProposal::where('business_id', $businessId)->latest()->paginate(50);
        return view('stocktransfernew::replenishment.proposals', compact('proposals'));
    }

    public function generate(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $count = $this->service->generateProposals($businessId, (int) auth()->id());
        return back()->with('status', trans('stocktransfernew::lang.proposals_generated', ['count' => $count]));
    }

    public function ignore(Request $request, ReplenishmentProposal $proposal)
    {
        $proposal->update(['status' => 'ignored', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'remarks' => $request->input('remarks')]);
        return back()->with('status', __('stocktransfernew::lang.proposal_ignored'));
    }
}
