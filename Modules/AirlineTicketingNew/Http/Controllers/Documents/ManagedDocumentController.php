<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Documents;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\ManagedDocument;
use Modules\AirlineTicketingNew\Services\Documents\DocumentStorageService;

class ManagedDocumentController extends Controller
{
    public function index()
    {
        $records = ManagedDocument::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::documents.index', compact('records'));
    }

    public function store(Request $request, DocumentStorageService $service)
    {
        $data = $request->validate([
            'file' => ['required','file','max:10240'],
            'document_type' => ['required','string','max:80'],
            'owner_type' => ['nullable','string','max:190'],
            'owner_id' => ['nullable','integer'],
            'expiry_date' => ['nullable','date'],
            'is_confidential' => ['nullable','boolean'],
            'notes' => ['nullable','string'],
        ]);

        $service->store($request->file('file'), [
            'business_id' => (int) session('business.id'),
            'business_location_id' => $request->integer('business_location_id') ?: null,
            'store_id' => $request->integer('store_id') ?: null,
            'document_type' => $data['document_type'],
            'owner_type' => $data['owner_type'] ?? null,
            'owner_id' => $data['owner_id'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'is_confidential' => $request->boolean('is_confidential'),
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('status', ['success' => 1, 'msg' => 'Document uploaded successfully.']);
    }
}
