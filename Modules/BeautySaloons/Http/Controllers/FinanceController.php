<?php
namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyFinanceAccountMapping;
use Modules\BeautySaloons\Entities\BeautyFinancePosting;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function mappings()
    {
        $mappings = BeautyFinanceAccountMapping::orderBy('account_key')->get();
        return view('beautysaloons::finance.mappings', compact('mappings'));
    }

    public function saveMappings(Request $request)
    {
        foreach ($request->input('mappings', []) as $key => $accountId) {
            BeautyFinanceAccountMapping::updateOrCreate(
                ['business_id' => session('business.id'), 'account_key' => $key],
                ['account_id' => $accountId, 'is_active' => 1]
            );
        }
        return redirect()->back()->with('status', ['success' => 1, 'msg' => __('beautysaloons::finance.mappings_saved')]);
    }

    public function postings(Request $request)
    {
        $postings = BeautyFinancePosting::query()
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('posting_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('posting_date', '<=', $request->end_date))
            ->latest('id')->paginate(50);
        return view('beautysaloons::finance.postings', compact('postings'));
    }
}
