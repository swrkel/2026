<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewCreditNote;
use Modules\DistributionNew\Services\CreditNotes\CreditNoteService;

class CreditNoteController extends Controller
{
    public function index(Request $request)
    {
        $creditNotes = DisnewCreditNote::query()->where('business_id', session('business.id'))->latest()->paginate(25);
        return view('distributionnew::credit_notes.index', compact('creditNotes'));
    }

    public function show(DisnewCreditNote $creditNote)
    {
        return view('distributionnew::credit_notes.show', compact('creditNote'));
    }

    public function approve(DisnewCreditNote $creditNote, CreditNoteService $service)
    {
        $service->approve($creditNote);
        return back()->with('status', __('distributionnew::lang.credit_note_approved'));
    }
}
