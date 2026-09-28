<?php
namespace Modules\EggManagement\Models;

class Collection extends EggModel
{
    protected $table = 'egg_collections';
    protected $casts = ['collection_date'=>'date'];
}
