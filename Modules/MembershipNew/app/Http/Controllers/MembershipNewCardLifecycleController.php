<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewIdentityCard;
use Modules\MembershipNew\app\Services\MembershipNewCardLifecycleService;
use Modules\MembershipNew\app\Services\MembershipNewCardService;

class MembershipNewCardLifecycleController extends Controller
{
    public function index()
    {
        $records = MembershipNewIdentityCard::with('member')
            ->forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())
            ->latest()
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        return view('membershipnew::card_lifecycle.index', compact('records'));
    }

    public function block(Request $request, int $cardId, MembershipNewCardLifecycleService $service)
    {
        $service->block(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $cardId, $request->blocked_reason);

        return back()->with('status', 'Card blocked.');
    }

    public function replace(int $cardId, MembershipNewCardLifecycleService $lifecycle, MembershipNewCardService $cardService)
    {
        $lifecycle->replace(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $cardId, $cardService);

        return back()->with('status', 'Card replaced.');
    }
}
