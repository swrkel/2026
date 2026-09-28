<?php
namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\HRManager\Services\HrEmployeeDocumentService;

class HrEmployeeDocumentController extends Controller
{
    protected HrEmployeeDocumentService $service;
    public function __construct(HrEmployeeDocumentService $service){ $this->service=$service; }

    public function index(Request $request)
    {
        $b=session('business.id');
        $employees=$this->rows('hr_employees',$b,300);
        $categories=$this->rows('hr_document_categories',$b,100);
        $documents=$this->documentRows($request,$b);
        $versions=$this->rows('hr_employee_document_versions',$b,25);
        $alerts=$this->rows('hr_employee_document_expiry_alerts',$b,25);
        $requests=$this->rows('hr_employee_document_requests',$b,25);
        $signatures=$this->rows('hr_employee_document_signatures',$b,25);
        $accessLogs=$this->rows('hr_employee_document_access_logs',$b,25);

        $stats=[
            'employees'=>$this->count('hr_employees',$b),
            'categories'=>$this->count('hr_document_categories',$b),
            'documents'=>$this->count('hr_employee_document_files',$b),
            'requests'=>$this->count('hr_employee_document_requests',$b),
            'pending_requests'=>$this->count('hr_employee_document_requests',$b,['request_status'=>'pending']),
            'expiry_alerts'=>$this->count('hr_employee_document_expiry_alerts',$b),
            'signatures'=>$this->count('hr_employee_document_signatures',$b),
            'access_logs'=>$this->count('hr_employee_document_access_logs',$b),
        ];

        return view('hrmanager::documents.index', compact('employees','categories','documents','versions','alerts','requests','signatures','accessLogs','stats'));
    }

    public function storeDocument(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','document_name'=>'required|string|max:180']);
        $this->service->createDocument([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'document_category_id'=>$request->document_category_id,
            'document_name'=>$request->document_name,
            'document_type'=>$request->document_type,
            'file_path'=>$request->file_path ?: 'pending-upload',
            'issue_date'=>$request->issue_date,
            'expiry_date'=>$request->expiry_date,
            'visibility'=>$request->visibility ?? 'hr_only',
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    public function storeRequest(Request $request)
    {
        $request->validate(['employee_id'=>'required|integer','request_title'=>'required|string|max:180']);
        $this->service->createRequest([
            'business_id'=>session('business.id'),
            'employee_id'=>$request->employee_id,
            'document_category_id'=>$request->document_category_id,
            'request_title'=>$request->request_title,
            'request_note'=>$request->request_note,
            'user_id'=>auth()->id(),
        ]);
        return back();
    }

    private function hasTable($t){ try{return DB::getSchemaBuilder()->hasTable($t);}catch(\Throwable $e){return false;} }
    private function rows($t,$b,$l){ return $this->hasTable($t)?DB::table($t)->where('business_id',$b)->orderByDesc('id')->limit($l)->get():collect(); }
    private function count($t,$b,$w=[]){ if(!$this->hasTable($t)) return 0; $q=DB::table($t)->where('business_id',$b); foreach($w as $k=>$v){$q->where($k,$v);} return $q->count(); }
    private function documentRows(Request $r,$b){ if(!$this->hasTable('hr_employee_document_files')) return collect(); $q=DB::table('hr_employee_document_files')->where('business_id',$b); if($r->search){$q->where('document_no','like','%'.$r->search.'%')->orWhere('document_name','like','%'.$r->search.'%')->orWhere('document_status','like','%'.$r->search.'%');} return $q->orderByDesc('id')->paginate($r->per_page==='all'?1000:(int)$r->get('per_page',25)); }
}
