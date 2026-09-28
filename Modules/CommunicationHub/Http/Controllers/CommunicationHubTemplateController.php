<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;

use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubTemplate;

class CommunicationHubTemplateController extends Controller
{
    public function index() { $templates = CommunicationHubTemplate::orderBy('category')->orderBy('name')->paginate(25); return view('communicationhub::templates.index', compact('templates')); }
    public function create() { return view('communicationhub::templates.form', ['template' => new CommunicationHubTemplate()]); }
    public function store(Request $request) { CommunicationHubTemplate::create($this->validated($request)); return redirect()->route('communicationhub.templates.index')->with('status', 'Template created successfully.'); }
    public function edit(CommunicationHubTemplate $template) { return view('communicationhub::templates.form', compact('template')); }
    public function update(Request $request, CommunicationHubTemplate $template) { $template->update($this->validated($request)); return redirect()->route('communicationhub.templates.index')->with('status', 'Template updated successfully.'); }
    public function destroy(CommunicationHubTemplate $template) { $template->delete(); return back()->with('status', 'Template deleted.'); }
    protected function validated(Request $request): array { return $request->validate(['code'=>'required|string|max:100','name'=>'required|string|max:191','category'=>'nullable|string|max:100','channel'=>'required|string|max:50','subject'=>'nullable|string|max:191','body'=>'required|string','is_active'=>'nullable|boolean','placeholders'=>'nullable|array']); }
}
