<?php

namespace Modules\EzyLaw\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawResearchItem, LawMatter, LawPracticeArea};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class ResearchController extends Controller
{
    public function index(Request $r)
    {
        $q = LawResearchItem::with(['matter', 'practiceArea'])->orderByDesc('decision_date')->orderByDesc('id');
        if ($r->filled('q')) {
            $term = '%' . $r->q . '%';
            $q->where(function ($x) use ($term) {
                $x->where('title', 'like', $term)
                    ->orWhere('citation', 'like', $term)
                    ->orWhere('keywords', 'like', $term)
                    ->orWhere('summary', 'like', $term);
            });
        }

        return view('ezylaw::research.index', [
            'items' => $q->paginate(30),
            'matters' => LawMatter::whereIn('status', ['open', 'pending'])->orderBy('matter_no')->get(),
            'areas' => LawPracticeArea::where('active', 1)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'matter_id' => 'nullable|integer', 'practice_area_id' => 'nullable|integer',
            'title' => 'required|string|max:191', 'citation' => 'nullable|string|max:255',
            'source_type' => 'required|in:case,statute,regulation,article,opinion,precedent,other',
            'source_url' => 'nullable|url|max:1000', 'court' => 'nullable|string|max:191',
            'jurisdiction' => 'nullable|string|max:120', 'decision_date' => 'nullable|date',
            'summary' => 'nullable|string', 'key_points' => 'nullable|string',
            'keywords' => 'nullable|string', 'confidential' => 'nullable|boolean',
        ]);

        EzyLawReferenceGuard::matter($d['matter_id'] ?? null);
        EzyLawReferenceGuard::practiceArea($d['practice_area_id'] ?? null);

        LawResearchItem::create($d + [
            'business_id' => EzyLawTenantGuard::businessId(),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Research / precedent saved.');
    }

    public function destroy(LawResearchItem $research)
    {
        $research->delete();
        return back()->with('success', 'Research item deleted.');
    }
}
