<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Pawning\Models\Article;
use Modules\Pawning\Models\PawningProduct;
use Modules\Pawning\Models\Pledge;
use Modules\Pawning\Services\PawningNumberService;
use Modules\Pawning\Services\PledgeService;

class PledgeController extends Controller
{
    public function index(Request $request)
    {
        $query = Pledge::with('product')->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('pledge_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $pledges = $query->paginate(20);
        return view('pawning::pledges.index', compact('pledges'));
    }

    public function create(PawningNumberService $numberService)
    {
        $pledge = new Pledge(['pledge_no' => $numberService->nextPledgeNo(), 'pledged_on' => date('Y-m-d'), 'due_on' => date('Y-m-d', strtotime('+30 days'))]);
        return $this->form($pledge, route('pawning.pledges.store'));
    }

    public function store(Request $request, PledgeService $service)
    {
        $data = $request->except(['article_ids']);
        $data['outstanding_amount'] = $data['outstanding_amount'] ?? ($data['advance_amount'] ?? 0);
        $service->create($data, (array) $request->get('article_ids', []));
        return redirect()->route('pawning.pledges.index')->with('status', ['success' => 1, 'msg' => 'Pledge saved successfully']);
    }

    public function show($id)
    {
        $pledge = Pledge::with(['product','articles','transactions'])->findOrFail($id);
        return view('pawning::pledges.show', compact('pledge'));
    }

    public function edit($id)
    {
        $pledge = Pledge::with('articles')->findOrFail($id);
        return $this->form($pledge, route('pawning.pledges.update', $id));
    }

    public function update(Request $request, $id)
    {
        $pledge = Pledge::findOrFail($id);
        $data = $request->except(['article_ids']);
        $data['outstanding_amount'] = $data['outstanding_amount'] ?? ($data['advance_amount'] ?? 0);
        $pledge->update($data);
        $sync = [];
        foreach ((array) $request->get('article_ids', []) as $articleId) {
            if ($articleId) {
                $sync[$articleId] = ['article_value' => $data['assessed_value'] ?? 0];
            }
        }
        $pledge->articles()->sync($sync);
        return redirect()->route('pawning.pledges.index')->with('status', ['success' => 1, 'msg' => 'Pledge updated successfully']);
    }

    protected function form(Pledge $pledge, $action)
    {
        $products = PawningProduct::orderBy('name')->pluck('name', 'id');
        $articles = Article::where('status', 'available')->orWhereIn('id', $pledge->articles->pluck('id')->toArray())->orderBy('article_no')->get();
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        $selectedArticles = $pledge->exists ? $pledge->articles->pluck('id')->toArray() : [];
        return view('pawning::pledges.form', compact('pledge', 'action', 'products', 'articles', 'locations', 'selectedArticles'));
    }
}
