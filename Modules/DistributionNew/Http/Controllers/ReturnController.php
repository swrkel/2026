<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewReturn;
use Modules\DistributionNew\Services\Returns\ReturnService;
use Modules\DistributionNew\Services\CreditNotes\CreditNoteService;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = DisnewReturn::query()->where('business_id', session('business.id'))->latest()->paginate(25);
        return view('distributionnew::returns.index', compact('returns'));
    }

    public function create()
    {
        return view('distributionnew::returns.create');
    }

    public function store(Request $request, ReturnService $service)
    {
        $payload = $request->except('lines');
        $payload['business_id'] = session('business.id');
        $payload['created_by'] = auth()->id();
        $return = $service->create($payload, $request->input('lines', []));
        return redirect()->route('distribution-new.returns.show', $return)->with('status', __('distributionnew::lang.return_saved'));
    }

    public function show(DisnewReturn $return)
    {
        return view('distributionnew::returns.show', compact('return'));
    }

    public function approve(DisnewReturn $return, ReturnService $service)
    {
        $service->approve($return);
        return back()->with('status', __('distributionnew::lang.return_approved'));
    }

    public function createCreditNote(DisnewReturn $return, CreditNoteService $service)
    {
        $note = $service->createFromReturn($return);
        return redirect()->route('distribution-new.credit-notes.show', $note)->with('status', __('distributionnew::lang.credit_note_created'));
    }
}
