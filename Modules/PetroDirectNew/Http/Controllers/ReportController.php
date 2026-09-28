<?php

namespace Modules\PetroDirectNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroDirectNew\Services\ReportService;
use Modules\PetroDirectNew\Services\SharedMasterDataService;
use Modules\PetroDirectNew\Support\PermissionGate;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports, private SharedMasterDataService $master) {}
    public function index(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.reports.view');
        $type=$request->string('type')->toString() ?: 'settlements';
        return view('petrodirectnew::reports.index',['types'=>$this->reports->types(),'type'=>$type,'rows'=>$this->reports->rows($type,$request->all()),'locations'=>$this->master->locations()]);
    }
    public function export(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.reports.export');
        $type=$request->string('type')->toString() ?: 'settlements'; $rows=$this->reports->rows($type,$request->all());
        $filename='petro-direct-new-'.$type.'-'.date('Ymd-His').'.csv';
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');$first=$rows->first();if($first){$headers=array_keys($first->getAttributes());fputcsv($out,$headers);foreach($rows as $row)fputcsv($out,array_values($row->getAttributes()));}fclose($out);},$filename,['Content-Type'=>'text/csv']);
    }
    public function print(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.reports.view');
        $type=$request->string('type')->toString() ?: 'settlements'; return view('petrodirectnew::print.report',['type'=>$type,'rows'=>$this->reports->rows($type,$request->all())]);
    }
}
