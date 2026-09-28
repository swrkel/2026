<?php

namespace Modules\HelpGuide\Entities;

use Modules\HelpGuide\Entities\Article;
use Modules\HelpGuide\Entities\ArticleTranslation;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['parent_id', 'name'];
    
    public function parent()
    {
        return $this->belongsTo('Modules\HelpGuide\Entities\Category')->select('id','name');
    }

    public function tickets()
    {
        return $this->hasMany('Modules\HelpGuide\Entities\Ticket');
    }

    public function countTickets($all = false){
        $c = Ticket::where('category_id', $this->id);
        if($all) $c->withoutGlobalScope('own_ticket');
        return $c->count();
    }

    public function children()
    {
        return $this->hasMany('Modules\HelpGuide\Entities\Category', 'parent_id');
    }

    public function removeChildren()
    {
        return Category::where('parent_id', $this->id)->update(array('parent_id' => null));
    }

    public function articles()
    {
        return $this->hasMany('Modules\HelpGuide\Entities\Article');
    }

    public function categoryArticles($locale = null)
    {
        return Article::where('category_id', $this->id)
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function recentArticles($locale = null)
    {
        return Article::where('category_id', $this->id)
            ->orderByDesc('id')
            ->take(5)
            ->get();
    }

    public function categoryArticleCount($locale = null)
    {
        return Article::where('category_id', $this->id)->count();
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    public function getUrlAttribute(): string
    {
        return action('\Modules\HelpGuide\Http\Controllers\Frontend\CategoryController@index', [$this->id, $this->slug]);
    }


}
