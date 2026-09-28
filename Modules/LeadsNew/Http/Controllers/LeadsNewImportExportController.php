<?php
namespace Modules\LeadsNew\Http\Controllers;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\LeadsNew\Models\LeadsNewLead;
class LeadsNewImportExportController extends Controller { public function import(){ return view('leadsnew::imports.index'); } public function exportCsv(){ $rows=LeadsNewLead::latest()->get(); $csv="Lead No,Name,Mobile,Status
"; foreach($rows as $r){$csv.=implode(',',[$r->lead_no,$r->name,$r->mobile,$r->status])."
";} return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename=leads-new.csv']); } }
