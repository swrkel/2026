<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewCommandCenterPreference extends Model
{
    protected $table = 'products_new_command_center_preferences';
    protected $guarded = ['id'];
    protected $casts = ['visible_widgets'=>'array','saved_searches'=>'array','quick_actions'=>'array'];
}
