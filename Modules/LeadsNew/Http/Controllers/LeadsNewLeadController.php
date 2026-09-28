<?php

namespace Modules\LeadsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\LeadsNew\Models\LeadsNewLead;
use Modules\LeadsNew\Services\LeadsNewNumberService;
use Modules\LeadsNew\Services\LeadsNewTableGuard;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadsNewLeadController extends Controller
{
    public function index(Request $request, LeadsNewTableGuard $tableGuard)
    {
        if (! $tableGuard->exists('leads_new_leads')) {
            $leads = new LengthAwarePaginator([], 0, 25, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            $missingTables = $tableGuard->missingCoreTables();
            return view('leadsnew::leads.index', compact('leads', 'missingTables'));
        }

        $query = LeadsNewLead::query()->latest('id');
        if ($tableGuard->hasColumn('leads_new_leads', 'business_id')) {
            $businessId = session('business.id') ?? $request->session()->get('business.id');
            if ($businessId) {
                $query->where(function ($q) use ($businessId) {
                    $q->where('business_id', $businessId)->orWhereNull('business_id');
                });
            }
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('lead_no', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nic', 'like', "%{$q}%");
            });
        }
        $leads = $query->paginate(25);
        return view('leadsnew::leads.index', compact('leads'));
    }

    public function create(LeadsNewNumberService $numberService, LeadsNewTableGuard $tableGuard)
    {
        $leadNo = $numberService->next();
        $missingTables = $tableGuard->missingCoreTables();
        return view('leadsnew::leads.create', compact('leadNo', 'missingTables'));
    }

    public function store(Request $request, LeadsNewNumberService $numberService, LeadsNewTableGuard $tableGuard)
    {
        if (! $tableGuard->exists('leads_new_leads')) {
            return redirect(url('/leads-new/leads'))
                ->with('status', __('leadsnew::messages.database_setup_pending'));
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'transaction_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);
        $data['lead_no'] = $request->lead_no ?: $numberService->next();
        $data['business_id'] = session('business.id') ?? request()->session()->get('business.id');
        $data['location_id'] = $request->input('location_id') ?: session('business_location.id');
        $data['created_by'] = auth()->id();
        LeadsNewLead::create($data);
        return redirect(url('/leads-new/leads'))->with('status', __('leadsnew::messages.saved_successfully'));
    }

    public function show(LeadsNewLead $lead)
    {
        return view('leadsnew::leads.show', compact('lead'));
    }

    public function edit(LeadsNewLead $lead)
    {
        return view('leadsnew::leads.edit', compact('lead'));
    }

    public function update(Request $request, LeadsNewLead $lead)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:100',
            'priority' => 'nullable|string|max:50',
            'transaction_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);
        $data['updated_by'] = auth()->id();
        if (empty($lead->business_id)) { $data['business_id'] = session('business.id') ?? request()->session()->get('business.id'); }
        $lead->update($data);
        return redirect(url('/leads-new/leads'))->with('status', __('leadsnew::messages.updated_successfully'));
    }

    public function destroy(LeadsNewLead $lead)
    {
        $lead->delete();
        return back()->with('status', __('leadsnew::messages.deleted_successfully'));
    }

    public function duplicate(LeadsNewLead $lead, LeadsNewNumberService $numberService)
    {
        $copy = $lead->replicate();
        $copy->lead_no = $numberService->next();
        $copy->name = $lead->name . ' - Copy';
        $copy->created_by = auth()->id();
        $copy->save();
        return redirect(url('/leads-new/leads/' . $copy->id . '/edit'))->with('status', __('leadsnew::messages.duplicated_successfully'));
    }

    public function archive(LeadsNewLead $lead)
    {
        $lead->update(['is_archived' => 1, 'archived_at' => now(), 'archived_by' => auth()->id()]);
        return back()->with('status', __('leadsnew::messages.archived_successfully'));
    }

    public function restore(LeadsNewLead $lead)
    {
        $lead->update(['is_archived' => 0, 'archived_at' => null, 'archived_by' => null]);
        return back()->with('status', __('leadsnew::messages.restored_successfully'));
    }
}
