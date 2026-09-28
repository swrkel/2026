<?php
namespace Modules\RestaurantNew\Entities;
use Illuminate\Database\Eloquent\Model;
use Modules\RestaurantNew\Support\ScopesBusiness;
abstract class RestnewModel extends Model
{
    use ScopesBusiness;
    protected $guarded = [];
}
