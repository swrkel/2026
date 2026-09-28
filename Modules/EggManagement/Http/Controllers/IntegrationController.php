<?php
namespace Modules\EggManagement\Http\Controllers;
use Modules\EggManagement\Models\IntegrationOutbox;
class IntegrationController extends BaseController
{
    public function index(){return view('egg::integrations.index',['rows'=>$this->scope(IntegrationOutbox::query())->latest('id')->paginate(100)]);}
}
