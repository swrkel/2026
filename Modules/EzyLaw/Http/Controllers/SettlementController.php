<?php

namespace Modules\EzyLaw\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawSettlement, LawMediationSession, LawMatter};
use Modules\EzyLaw\Services\SettingsService;
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class SettlementController extends Controller
{
    public function index()
    {
        return view('ezylaw::settlements.index', [
            'settlements' => LawSettlement::with(['matter', 'client', 'sessions'])->orderByDesc('id')->paginate(25),
            'matters' => LawMatter::with('client')->whereIn('status', ['open', 'pending'])->orderBy('matter_no')->get(),
        ]);
    }

    public function store(Request $r, SettingsService $s)
    {
        $d = $r->validate([
            'matter_id' => 'required|integer',
            'settlement_type' => 'required|in:negotiation,mediation,consent,arbitration,other',
            'offer_amount' => 'nullable|numeric|min:0', 'agreed_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,offered,accepted,rejected,completed',
            'offered_on' => 'nullable|date', 'accepted_on' => 'nullable|date', 'completed_on' => 'nullable|date',
            'terms' => 'nullable|string', 'confidential' => 'nullable|boolean',
        ]);

        $matter = EzyLawReferenceGuard::matter($d['matter_id']);
        LawSettlement::create($d + [
            'business_id' => EzyLawTenantGuard::businessId(),
            'client_id' => $matter->client_id,
            'settlement_no' => $s->nextNumber('settlement'),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Settlement record created.');
    }

    public function status(Request $r, LawSettlement $settlement)
    {
        $d = $r->validate([
            'status' => 'required|in:draft,offered,accepted,rejected,completed',
            'agreed_amount' => 'nullable|numeric|min:0', 'terms' => 'nullable|string',
        ]);
        $status = $d['status'];
        if ($status === 'offered' && !$settlement->offered_on) $d['offered_on'] = now()->toDateString();
        if ($status === 'accepted') $d['accepted_on'] = now()->toDateString();
        if ($status === 'completed') $d['completed_on'] = now()->toDateString();
        $settlement->update($d);
        return back()->with('success', 'Settlement status updated.');
    }

    public function mediation(Request $r)
    {
        $d = $r->validate([
            'settlement_id' => 'nullable|integer', 'matter_id' => 'required|integer',
            'mediator' => 'nullable|string|max:191', 'venue' => 'nullable|string|max:191',
            'session_at' => 'required|date', 'status' => 'required|in:scheduled,completed,cancelled,adjourned',
            'outcome' => 'nullable|string', 'next_session_at' => 'nullable|date', 'notes' => 'nullable|string',
        ]);

        EzyLawReferenceGuard::matter($d['matter_id']);
        if (!empty($d['settlement_id'])) {
            LawSettlement::whereKey($d['settlement_id'])->where('matter_id', $d['matter_id'])->firstOrFail();
        }

        LawMediationSession::create($d + [
            'business_id' => EzyLawTenantGuard::businessId(),
            'created_by' => auth()->id(),
        ]);
        return back()->with('success', 'Mediation session saved.');
    }
}
