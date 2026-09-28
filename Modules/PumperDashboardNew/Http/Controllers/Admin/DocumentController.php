<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Admin;
use Illuminate\Support\Facades\Storage;
use Modules\PumperDashboardNew\Entities\PoneOperatorDocument;
use Modules\PumperDashboardNew\Entities\PoneOperatorNote;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
class DocumentController extends Controller
{
    public function index(){ $businessId=$this->businessId();$operators=PonePdOperator::query()->where('business_id',$businessId)->orderBy('display_name')->get();$operatorId=(int)request('operator_profile_id');$documents=PoneOperatorDocument::query()->where('business_id',$businessId)->when($operatorId,fn($q)=>$q->where('operator_profile_id',$operatorId))->with('operatorProfile')->latest()->paginate(100,['*'],'documents_page');$notes=PoneOperatorNote::query()->where('business_id',$businessId)->when($operatorId,fn($q)=>$q->where('operator_profile_id',$operatorId))->with('operatorProfile')->latest()->paginate(100,['*'],'notes_page');return view('pumperdashboardnew::admin.documents.index',compact('operators','documents','notes','operatorId'));}
    public function download(int $document){$document=PoneOperatorDocument::query()->whereKey($document)->where('business_id',$this->businessId())->firstOrFail();abort_unless(Storage::disk($document->disk)->exists($document->path),404);return Storage::disk($document->disk)->download($document->path,$document->original_name);}
}
