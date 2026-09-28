<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\AutoService\Entities\AutoServiceDocument;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceVehicle;

class DocumentController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $documents = AutoServiceDocument::query()
            ->when($request->job_id, fn($q)=>$q->where('job_id', $request->job_id))
            ->when($request->vehicle_id, fn($q)=>$q->where('vehicle_id', $request->vehicle_id))
            ->orderByDesc('id')->paginate(25);
        return view('autoservice::documents.index', compact('documents'));
    }

    public function create(Request $request)
    {
        $jobs = AutoServiceJob::orderByDesc('id')->limit(100)->pluck('job_no', 'id');
        $vehicles = AutoServiceVehicle::orderByDesc('id')->limit(100)->pluck('registration_no', 'id');
        return view('autoservice::documents.form', compact('jobs','vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'job_id' => 'nullable|integer', 'vehicle_id' => 'nullable|integer', 'document_type' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:191', 'notes' => 'nullable|string', 'visible_to_customer' => 'nullable',
            'file' => 'nullable|file|max:10240',
        ]);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('auto_service/documents', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_path'] = $path;
            $data['mime_type'] = $file->getClientMimeType();
            $data['file_size'] = $file->getSize();
        }
        $data['visible_to_customer'] = $request->boolean('visible_to_customer');
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        AutoServiceDocument::create($data);
        return redirect()->route('autoservice.documents.index')->with('status', __('Saved successfully'));
    }
}
