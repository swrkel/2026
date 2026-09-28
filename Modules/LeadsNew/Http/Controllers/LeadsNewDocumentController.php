<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\LeadsNew\Models\LeadsNewDocument;

class LeadsNewDocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = LeadsNewDocument::query()
            ->when($request->lead_id, fn ($q, $leadId) => $q->where('lead_id', $leadId))
            ->latest('id')
            ->paginate(25);

        return view('leadsnew::documents.index', compact('documents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'lead_id' => ['required','integer'],
            'document' => ['required','file','max:10240'],
        ]);

        $file = $request->file('document');
        $path = $file->store('leads-new/documents');

        LeadsNewDocument::create([
            'lead_id' => $request->lead_id,
            'business_id' => session('business.id') ?? session('user.business_id'),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => optional($request->user())->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('status', __('leadsnew::lang.document_uploaded'));
    }

    public function destroy(LeadsNewDocument $document)
    {
        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }
        $document->delete();

        return back()->with('status', __('leadsnew::lang.document_deleted'));
    }
}
