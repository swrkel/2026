<?php

namespace Modules\HelpGuide\Http\Controllers\Frontend;

use Modules\HelpGuide\Entities\User;
use Modules\HelpGuide\Entities\Article;

use Modules\HelpGuide\Entities\Category;
use Illuminate\Http\Request;
use Modules\HelpGuide\Http\Controllers\Controller;
use Modules\HelpGuide\Http\Resources\ArticleResource;

class IndexController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return view('helpguide::frontend.error');
        }
        // Do not hide normal Help Desk categories behind the "featured" flag.
        // A saved category with published articles must be discoverable from the
        // Help Desk home page immediately.
        $articlesByCategory = Category::select('id','name','is_featured','active','category_order')
            ->orderByDesc('is_featured')
            ->orderBy('category_order')
            ->orderBy('name')
            ->get()->map(function ($category) {
                $category['articles_count'] = $category->categoryArticleCount();
                $category['articles'] = $category->recentArticles();
                return $category;
            })->filter(function ($category) {
                return (int) $category['articles_count'] > 0 || (bool) ($category->active ?? true);
            })->values();
        
        // Load Featured articles
        $featuredArticles = Article::where('featured','=',1)->where('published','=','1')->select(['id','title']);
        $featuredArticles = $featuredArticles->take(9)->get();

        $tplData = [
            'articles_by_category' => $articlesByCategory,
            'featured_articles' => $featuredArticles
        ];
        return view('helpguide::frontend.index', $tplData);
    }
}
