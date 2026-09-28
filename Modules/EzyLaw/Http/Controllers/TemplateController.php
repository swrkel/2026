<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawDocumentTemplate,LawClient,LawMatter};
use Modules\EzyLaw\Services\TemplateService;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class TemplateController extends Controller
{
    public function index(){return view('ezylaw::templates.index',['templates'=>LawDocumentTemplate::orderBy('category')->orderBy('name')->get(),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderByDesc('id')->get()]);}
    public function store(Request $r){$d=$r->validate(['name'=>'required|string|max:191','category'=>'nullable|string|max:100','description'=>'nullable|string','body_html'=>'required|string','active'=>'nullable|boolean']);$d['active']=$r->boolean('active');LawDocumentTemplate::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]);return back()->with('success','Document template saved.');}
    public function destroy(LawDocumentTemplate $template){$template->delete();return back()->with('success','Document template removed.');}
    public function render(Request $r,LawDocumentTemplate $template,TemplateService $s){$client=$r->filled('client_id')?LawClient::find((int)$r->input('client_id')):null;$matter=$r->filled('matter_id')?LawMatter::with('court')->find((int)$r->input('matter_id')):null;return view('ezylaw::templates.render',['template'=>$template,'html'=>$s->render($template,$client,$matter),'client'=>$client,'matter'=>$matter]);}
}
