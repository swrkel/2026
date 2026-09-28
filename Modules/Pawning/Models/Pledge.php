<?php

namespace Modules\Pawning\Models;

use Illuminate\Database\Eloquent\Model;

class Pledge extends Model
{
    protected $table = 'pawning_pledges';
    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(PawningProduct::class, 'pawning_product_id');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'pawning_pledge_articles', 'pawning_pledge_id', 'pawning_article_id')
            ->withPivot('article_value')
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(PawningTransaction::class, 'pawning_pledge_id');
    }
}
