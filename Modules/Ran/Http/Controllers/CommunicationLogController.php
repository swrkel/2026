<?php
namespace Modules\Ran\Http\Controllers;use Modules\Ran\Entities\CommunicationLog;class CommunicationLogController extends RanController{public function index(){return view('ran::communications.index',['logs'=>CommunicationLog::query()->latest('id')->paginate(50)]);}}
