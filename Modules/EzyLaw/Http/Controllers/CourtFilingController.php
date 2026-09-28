<?php

namespace Modules\EzyLaw\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawCourtFiling, LawMatter, LawCourt};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class CourtFilingController extends Controller
{
    public function index(Request $r)
    {
        $q = LawCourtFiling::with(['matter', 'court'])->orderByDesc('filed_on')->orderByDesc('id');
        if ($r->filled('status')) $q->where('status', $r->status);

        return view('ezylaw::court_filings.index', [
            'filings' => $q->paginate(30),
            'matters' => LawMatter::whereIn('status', ['open', 'pending'])->orderBy('matter_no')->get(),
            'courts' => LawCourt::where('active', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'matter_id' => 'required|integer', 'court_id' => 'nullable|integer',
            'filing_no' => 'required|string|max:80', 'filing_type' => 'required|string|max:100',
            'title' => 'required|string|max:191', 'filed_on' => 'nullable|date',
            'filed_by_user_id' => 'nullable|integer', 'reference_no' => 'nullable|string|max:120',
            'fee_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,filed,accepted,rejected,returned',
            'response_due_at' => 'nullable|date', 'notes' => 'nullable|string',
        ]);

        EzyLawReferenceGuard::matter($d['matter_id']);
        EzyLawReferenceGuard::court($d['court_id'] ?? null);

        LawCourtFiling::create($d + [
            'business_id' => EzyLawTenantGuard::businessId(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Court filing saved.');
    }

    public function update(Request $r, LawCourtFiling $filing)
    {
        $d = $r->validate([
            'status' => 'required|in:draft,filed,accepted,rejected,returned',
            'response_due_at' => 'nullable|date', 'notes' => 'nullable|string',
        ]);
        $filing->update($d);
        return back()->with('success', 'Court filing updated.');
    }
}
