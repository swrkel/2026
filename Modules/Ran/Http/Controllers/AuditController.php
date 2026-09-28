<?php
namespace Modules\Ran\Http\Controllers;use Modules\Ran\Entities\AuditLog;class AuditController extends RanController{public function index(){return view('ran::audit.index',['logs'=>AuditLog::query()->latest('created_at')->paginate(50)]);}}
