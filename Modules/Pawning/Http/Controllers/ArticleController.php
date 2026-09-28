<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Pawning\Models\Article;
use Modules\Pawning\Models\CollateralType;
use Modules\Pawning\Models\VaultLocation;
use Modules\Pawning\Services\PawningNumberService;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['collateralType','vaultLocation'])->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('article_no', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        $articles = $query->paginate(20);
        return view('pawning::articles.index', compact('articles'));
    }

    public function create(PawningNumberService $numberService)
    {
        $article = new Article(['article_no' => $numberService->nextArticleNo()]);
        return $this->form($article, route('pawning.articles.store'));
    }

    public function store(Request $request)
    {
        Article::create($request->all());
        return redirect()->route('pawning.articles.index')->with('status', ['success' => 1, 'msg' => 'Article saved successfully']);
    }

    public function show($id)
    {
        $article = Article::with(['collateralType','vaultLocation'])->findOrFail($id);
        return view('pawning::articles.show', compact('article'));
    }

    public function edit($id)
    {
        $article = Article::findOrFail($id);
        return $this->form($article, route('pawning.articles.update', $id));
    }

    public function update(Request $request, $id)
    {
        Article::findOrFail($id)->update($request->all());
        return redirect()->route('pawning.articles.index')->with('status', ['success' => 1, 'msg' => 'Article updated successfully']);
    }

    protected function form(Article $article, $action)
    {
        $types = CollateralType::orderBy('name')->pluck('name', 'id');
        $vaults = VaultLocation::orderBy('vault_name')->get()->pluck('vault_label', 'id');
        if ($vaults->isEmpty()) {
            $vaults = VaultLocation::orderBy('vault_name')->pluck('vault_name', 'id');
        }
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        return view('pawning::articles.form', compact('article', 'action', 'types', 'vaults', 'locations'));
    }
}
