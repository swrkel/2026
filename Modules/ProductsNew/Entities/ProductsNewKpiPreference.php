<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewKpiPreference extends Model { protected $table = 'products_new_kpi_preferences'; protected $guarded = ['id']; protected $casts = ['visible_cards' => 'array', 'settings' => 'array']; }
