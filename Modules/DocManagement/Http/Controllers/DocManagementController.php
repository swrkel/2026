<?php

namespace Modules\DocManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\User;
use App\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File; // Import the File class
use App\BusinessLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\DocManagement\Entities\DocManagementCategory;
use Modules\DocManagement\Entities\DocManagementType;
use Modules\DocManagement\Entities\DocManagementSignature;
use Modules\DocManagement\Entities\DocManagementPurpose;
use Modules\DocManagement\Entities\DocManagementForwardWith;
use Modules\DocManagement\Entities\DocManagementStatus;
use Modules\DocManagement\Entities\DocManagementMandatorySignature;
use Modules\DocManagement\Entities\DocManagementUpload;
use Modules\DocManagement\Entities\DocManagementReferredTo;
use Illuminate\Support\Str; 
use App\Category;
use App\StockConversion;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Modules\Superadmin\Entities\Subscription;
 
class DocManagementController extends Controller
{
    protected function getDocAttachmentDisk(?array $attachment = null): string
    {
        $attachmentDisk = $attachment['disk'] ?? null;
        if (! empty($attachmentDisk) && config("filesystems.disks.{$attachmentDisk}")) {
            return $attachmentDisk;
        }

        $configuredDisk = env('DOC_MANAGEMENT_STORAGE_DISK');
        if (! empty($configuredDisk) && config("filesystems.disks.{$configuredDisk}")) {
            return $configuredDisk;
        }

        $defaultDisk = config('filesystems.default');
        if (! empty($defaultDisk) && config("filesystems.disks.{$defaultDisk}")) {
            return $defaultDisk;
        }

        return 'public';
    }

    protected function showBusinessLocationDropdown(int $business_id): bool
    {
        $subscription = Subscription::active_subscription($business_id);

        return ! empty($subscription->package_details['doc_management_show_business_location']);
    }

    protected function hasDocAttachmentsColumn(): bool
    {
        $model = new DocManagementUpload();
        $connection = $model->getConnection();

        return $connection->getSchemaBuilder()->hasColumn($model->getTable(), 'attachments');
    }

    protected function ensureDocAttachmentsColumn(): bool
    {
        $model = new DocManagementUpload();
        $connection = $model->getConnection();
        $table = $model->getTable();

        if ($connection->getSchemaBuilder()->hasColumn($table, 'attachments')) {
            return true;
        }

        $connectionName = $connection->getName();

        if (! empty($connectionName)) {
            Schema::connection($connectionName)->table($table, function (Blueprint $table) {
                $table->longText('attachments')->nullable()->after('image');
            });
        } else {
            Schema::table($table, function (Blueprint $table) {
                $table->longText('attachments')->nullable()->after('image');
            });
        }

        $refreshedConnection = $model->getConnection();

        return $refreshedConnection->getSchemaBuilder()->hasColumn($table, 'attachments');
    }

    protected function fillDocAttachments(DocManagementUpload $doc, array $attachments): void
    {
        $doc->image = ! empty($attachments[0]['path']) ? $attachments[0]['path'] : $doc->image;

        if (! empty($attachments) && $this->hasDocAttachmentsColumn()) {
            $doc->attachments = $attachments;
        }
    }

    protected function hasDocLocationColumns(): bool
    {
        $model = new DocManagementUpload();
        $connection = $model->getConnection();
        $table = $model->getTable();

        return $connection->getSchemaBuilder()->hasColumn($table, 'from_location')
            && $connection->getSchemaBuilder()->hasColumn($table, 'to_location');
    }

    protected function ensureDocLocationColumns(): bool
    {
        $model = new DocManagementUpload();
        $connection = $model->getConnection();
        $table = $model->getTable();

        if ($this->hasDocLocationColumns()) {
            return true;
        }

        $schema = $connection->getSchemaBuilder();
        $connectionName = $connection->getName();

        $tableCallback = function (Blueprint $table) use ($schema, $model) {
            if (! $schema->hasColumn($model->getTable(), 'from_location')) {
                $table->string('from_location', 191)->nullable()->after('location');
            }
            if (! $schema->hasColumn($model->getTable(), 'to_location')) {
                $table->string('to_location', 191)->nullable()->after('from_location');
            }
        };

        if (! empty($connectionName)) {
            Schema::connection($connectionName)->table($table, $tableCallback);
        } else {
            Schema::table($table, $tableCallback);
        }

        return $this->hasDocLocationColumns();
    }

    protected function storeAttachments(Request $request, string $inputName = 'image'): array
    {
        if (! $request->hasFile($inputName)) {
            return [];
        }

        $attachments = [];
        $business_id = $request->session()->get('user.business_id');
        $disk = $this->getDocAttachmentDisk();
        $directory = "doc_management/{$business_id}";
        $uploadedFiles = $request->file($inputName, []);

        if ($uploadedFiles instanceof UploadedFile) {
            $uploadedFiles = [$uploadedFiles];
        }

        foreach ((array) $uploadedFiles as $uploadedFile) {
            if (empty($uploadedFile)) {
                continue;
            }

            $filename = uniqid() . '_' . preg_replace('/[^A-Za-z0-9.\-_]/', '_', $uploadedFile->getClientOriginalName());
            $storedPath = Storage::disk($disk)->putFileAs($directory, $uploadedFile, $filename);

            $attachments[] = [
                'disk' => $disk,
                'path' => $storedPath,
                'name' => $uploadedFile->getClientOriginalName(),
                'mime' => $uploadedFile->getClientMimeType(),
                'extension' => strtolower($uploadedFile->getClientOriginalExtension()),
            ];
        }

        return $attachments;
    }

    protected function getDocAttachments($doc): array
    {
        $attachments = [];

        if (! empty($doc->attachments)) {
            if (is_array($doc->attachments)) {
                $attachments = $doc->attachments;
            } else {
                $decoded = json_decode($doc->attachments, true);
                if (is_array($decoded)) {
                    $attachments = $decoded;
                } elseif (is_string($decoded)) {
                    $decodedAgain = json_decode($decoded, true);
                    if (is_array($decodedAgain)) {
                        $attachments = $decodedAgain;
                    }
                }
            }
        }

        if (empty($attachments) && ! empty($doc->image)) {
            $attachments[] = [
                'path' => $doc->image,
                'name' => basename($doc->image),
                'mime' => null,
                'extension' => strtolower(pathinfo($doc->image, PATHINFO_EXTENSION)),
            ];
        }

        return collect($attachments)
            ->filter(function ($attachment) {
                return ! empty($attachment['path']);
            })
            ->values()
            ->all();
    }

    protected function attachmentUrl(?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        if (Str::startsWith($path, 'public/')) {
            return Storage::url($path);
        }

        if (Str::startsWith($path, 'public_html/')) {
            return asset(str_replace('public_html/', '', $path));
        }

        return asset($path);
    }

    protected function getAttachmentUrlFromDisk(array $attachment): string
    {
        $path = $attachment['path'] ?? null;
        if (empty($path)) {
            return '';
        }

        $disk = $this->getDocAttachmentDisk($attachment);

        try {
            return Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            return $this->attachmentUrl($path);
        }
    }

    protected function attachmentAbsolutePath(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $candidates = [];

        if (Str::startsWith($path, 'public/')) {
            $candidates[] = storage_path('app/' . $path);
        }

        if (Str::startsWith($path, 'public_html/')) {
            $candidates[] = public_path(str_replace('public_html/', '', $path));
            $candidates[] = base_path($path);
        }

        $candidates[] = storage_path('app/' . ltrim($path, '/'));
        $candidates[] = public_path(ltrim($path, '/'));
        $candidates[] = base_path($path);

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function enrichDocForDisplay($doc)
    {
        $attachment_items = $this->getDocAttachments($doc);

        foreach ($attachment_items as $index => &$attachment) {
            $attachment['url'] = ! empty($attachment['disk'])
                ? $this->getAttachmentUrlFromDisk($attachment)
                : $this->attachmentUrl($attachment['path'] ?? null);
            $attachment['download_url'] = route('doc.download', [$doc->doc_no, $index]);
            $attachment['is_image'] = in_array($attachment['extension'] ?? '', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
        }
        unset($attachment);

        $doc->attachment_items = $attachment_items;

        return $doc;
    }

    protected function getDocReferredOptions($business_id)
    {
        $activeEntries = DocManagementReferredTo::query()
            ->where('status', 'Active')
            ->orderBy('officer_name')
            ->orderBy('department')
            ->get(['officer_name', 'department']);

        $labels = $activeEntries
            ->map(function ($entry) {
                // New setting uses officer name; keep department as fallback for legacy rows.
                return trim((string) ($entry->officer_name ?: $entry->department));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $labels->combine($labels);
    }

    protected function buildReferredNames(array $selected_departments)
    {
        if (empty($selected_departments)) {
            return '';
        }

        return collect($selected_departments)
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->filter()
            ->unique()
            ->implode(', ');
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        
         $business_id = request()->session()->get('user.business_id');
        $maxId = DocManagementUpload::max('doc_no');
        $docCategories = DocManagementCategory::pluck('document_category', 'document_category');
        $docTypes = DocManagementType::pluck('type', 'id');
        $docPurpose = DocManagementPurpose::pluck('purpose_type', 'id');
        $docReferred = $this->getDocReferredOptions($business_id);
        $newId = $maxId + 1;
        $business_locations = BusinessLocation::forDropdown($business_id);
        $show_business_location_dropdown = $this->showBusinessLocationDropdown($business_id);
        $default_location = $show_business_location_dropdown ? collect($business_locations)->keys()->first() : null;
        $default_to_location = $default_location;
        $docForwardwith = DocManagementForwardWith::pluck('forwarded_with', 'forwarded_with');
        $docStatuses = DocManagementStatus::orderBy('status')->pluck('status', 'status');
        if ($docStatuses->isEmpty()) {
            $docStatuses = collect([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
                'Referred Back' => 'Referred Back',
            ]);
        }
        $logged_in_user = auth()->user();
        $default_originator = trim(
            collect([
                trim(($logged_in_user->surname ?? '') . ' ' . ($logged_in_user->first_name ?? '') . ' ' . ($logged_in_user->last_name ?? '')),
                $logged_in_user->designation ?? null,
            ])->filter()->implode(' - ')
        );
         
       if (request()->ajax()) {
         
         $route_operations = DocManagementUpload::query()->orderByDesc('created_at');
         

          if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $route_operations->whereDate('created_at', '>=', request()->start_date);
                $route_operations->whereDate('created_at', '<=', request()->end_date);
            }
            
            if (!empty(request()->type)) {
                $route_operations->where('document_type', request()->type);
            }
            
            return DataTables::of($route_operations)
                ->editColumn('created_at', function ($row) {
                    return !empty($row->created_at)
                        ? Carbon::parse($row->created_at)->format('m/d/Y h:i A')
                        : '';
                })
                ->addColumn('from_location', function ($row) {
                    return $row->from_location ?? $row->location ?? '';
                })
                ->addColumn('to_location', function ($row) {
                    return $row->to_location ?? '';
                })
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                       $html .= '<li><a href="' . route('doc.view', $row->doc_no) . '"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';
                        $html .= '<li><a href="' . route('doc.edit', $row->doc_no) . '"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>';
                        $html .= '<li><a href="' . route('doc.print', $row->doc_no) . '" target="_blank"><i class="glyphicon glyphicon-print"></i> ' . __("messages.print") . '</a></li>';
                         $html .= '<li><a href="#" class="open-modal" data-doc-no="' . $row->doc_no . '"><i class="glyphicon glyphicon-edit"></i> ' . __("Referred To") . '</a></li>';
            
 
            
            // JavaScript code to handle the click event and show the modal
            $html .= '<script>
    $(document).ready(function() {
        $(".open-modal").click(function(e) {
            e.preventDefault();
            var docNo = $(this).data("doc-no");
            $("#doc_number").val(docNo);
            $("#referredto_modal").modal("show");
        });
    });
</script>';
                        return $html;
                    }
                )
           
                ->rawColumns(['action', 'payment_status', 'method'])
                ->make(true);
        } 
    
    
        return view('docmanagement::index')
          ->with(compact('newId','docCategories','docTypes','docPurpose','docReferred' ,'business_locations','docForwardwith', 'docStatuses', 'default_location', 'default_to_location', 'default_originator', 'show_business_location_dropdown'));
    }
 public function get_upload_table() {
     
      
      if (request()->ajax()) {
           
         $route_operations=StockConversion::leftjoin('products', 'products.id', 'stock_conversions.product_convert_from')
            -> select('products.name AS productname','stock_conversions.location','stock_conversions.created_at','stock_conversions.conversion_form_no','stock_conversions.unit_convert_from','stock_conversions.unit_convert_to','stock_conversions.total_qty_convert_from','stock_conversions.product_convert_to','stock_conversions.qty_convert_to','stock_conversions.updated_at','stock_conversions.user')->get();
           
            

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $route_operations->whereDate('stock_conversions.updated_at', '>=', request()->start_date);
                $route_operations->whereDate('stock_conversions.updated_at', '<=', request()->end_date);
            }
            
            if (!empty(request()->conversion_from_no)) {
                $route_operations->where('stock_conversions.conversion_form_no', request()->conversion_from_no);
            }
            
            if (!empty(request()->product_convert_from)) {
                $route_operations->where('stock_conversions.unit_convert_from', request()->product_convert_from);
            }
            
            if (!empty(request()->unit_convert_from)) {
                $route_operations->where('air_ticket_invoices.customer', request()->unit_convert_from);
            }
            
            if (!empty(request()->product_convert_to)) {
                $route_operations->where('air_ticket_invoices.airline_agent', request()->product_convert_to);
            }
            
            if (!empty(request()->unit_convert_to)) {
                $route_operations->where('air_ticket_invoices.departure_country', request()->unit_convert_to);
            } 
            
            
            
            
            

            return DataTables::of($route_operations)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                        $html .= '<li><a data-href=""' . route('doc-management.view', ['id' => $row->doc_no]) . '"" class="btn-modal" data-container=".fleet_model"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.view") . '</a></li>';
                        $html .= '<li><a href=""' . route('doc-management.view', ['id' => $row->doc_no]) . '""  class="view_payment_modal"><i class="fa fa-edit" aria-hidden="true"></i> ' . __("Edit") . '</a></li>';
                        $html .= '<li><a href="#" data-href="#" class="delete-fleet"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        
                        return $html;
                    }
                )
           
                ->rawColumns(['action', 'payment_status', 'method'])
                ->make(true);
        } 
        return null;
    }
    /**
     * Update a document from the edit form.
     */
    public function updateDoc(Request $request, $doc_no)
    {
        try {
            $doc = DocManagementUpload::where('doc_no', $doc_no)->firstOrFail();

            $request->validate([
                'originator' => 'required|string|max:255',
                'document_type' => 'nullable',
                'purpose' => 'nullable',
                'referred_to' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:doc,docx,xls,xlsx,pdf,jpeg,jpg,png|max:' . (config('constants.document_size_limit') / 1000),
            ]);

            if ($request->hasFile('attachments')) {
                $this->ensureDocAttachmentsColumn();
            }

            DB::beginTransaction();

            $docType = DocManagementType::find($request->input('document_type'));
            if (empty($docType) && !empty($request->input('document_type'))) {
                $docType = DocManagementType::where('type', $request->input('document_type'))->first();
            }
            $docPurpose = DocManagementPurpose::find($request->input('purpose'));
            if (empty($docPurpose) && !empty($request->input('purpose'))) {
                $docPurpose = DocManagementPurpose::where('purpose_type', $request->input('purpose'))->first();
            }
            $attachments = $this->storeAttachments($request, 'attachments');
            $existingAttachments = $this->getDocAttachments($doc);
            $finalAttachments = ! empty($attachments)
                ? array_values(array_merge($existingAttachments, $attachments))
                : $existingAttachments;

            $doc->originator = $request->input('originator');
            $doc->document_type = $docType->type ?? $request->input('document_type');
            $doc->purpose = $docPurpose->purpose_type ?? $request->input('purpose');
            $doc->referred_to = $this->buildReferredNames((array) $request->input('referred_to', []));
            $doc->status = $request->input('status');
            $doc->note = $request->input('note');
            $from_location_id = $request->from_location ?? $request->location;
            $to_location_id = $request->to_location;
            $from_location_name = !empty($from_location_id)
                ? BusinessLocation::where('id', $from_location_id)->value('name')
                : null;
            $to_location_name = !empty($to_location_id)
                ? BusinessLocation::where('id', $to_location_id)->value('name')
                : null;

            // Keep legacy field in sync for backward compatibility.
            $doc->location = $from_location_name;
            if ($this->hasDocLocationColumns()) {
                $doc->from_location = $from_location_name;
                $doc->to_location = $to_location_name;
            }
            $this->fillDocAttachments($doc, $finalAttachments);
            $doc->save();

            DB::commit();

            return [
                'success' => true,
                'msg' => 'Saved Successfully',
            ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * View a document by doc_no.
     */
    public function viewDoc($doc_no)
    {
        $doc = DocManagementUpload::where('doc_no', $doc_no)->firstOrFail();
        $doc = $this->enrichDocForDisplay($doc);
        return view('docmanagement::view', compact('doc'));
    }

    /**
     * Edit a document by doc_no.
     */
    public function editDoc($doc_no)
    {
        $business_id = request()->session()->get('user.business_id');
        $doc = DocManagementUpload::where('doc_no', $doc_no)->firstOrFail();
        $doc = $this->enrichDocForDisplay($doc);
        $docTypes = DocManagementType::pluck('type', 'type');
        $docPurpose = DocManagementPurpose::pluck('purpose_type', 'purpose_type');
        $docReferred = $this->getDocReferredOptions($business_id);
        $docStatuses = DocManagementStatus::orderBy('status')->pluck('status', 'status');
        if ($docStatuses->isEmpty()) {
            $docStatuses = collect([
                'Pending' => 'Pending',
                'Approved' => 'Approved',
                'Referred Back' => 'Referred Back',
            ]);
        }
        if (! empty($doc->status) && ! $docStatuses->has($doc->status)) {
            $docStatuses = collect([$doc->status => $doc->status])->union($docStatuses);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $show_business_location_dropdown = $this->showBusinessLocationDropdown($business_id);

        // Existing rows may have legacy location only.
        $selected_from_location = array_search(
            $doc->from_location ?? $doc->location,
            $business_locations ? $business_locations->toArray() : []
        );
        $selected_to_location = array_search(
            $doc->to_location,
            $business_locations ? $business_locations->toArray() : []
        );
        $selected_referred = collect(explode(',', (string) $doc->referred_to))
            ->map(function ($value) {
                return trim($value);
            })
            ->filter()
            ->values()
            ->all();
        // Keep existing selected values visible even if they are currently inactive.
        foreach ($selected_referred as $selected_name) {
            if (! $docReferred->has($selected_name)) {
                $docReferred->put($selected_name, $selected_name);
            }
        }

        return view(
            'docmanagement::edit_doc',
            compact(
                'doc',
                'docTypes',
                'docPurpose',
                'docReferred',
                'business_locations',
                'selected_from_location',
                'selected_to_location',
                'selected_referred',
                'docStatuses',
                'show_business_location_dropdown'
            )
        );
    }

    /**
     * Print a document by doc_no.
     */
    public function printDoc($doc_no)
    {
        $doc = DocManagementUpload::where('doc_no', $doc_no)->firstOrFail();
        $doc = $this->enrichDocForDisplay($doc);
        return view('docmanagement::print_doc', compact('doc'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('docmanagement::create');
    }
  
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|array|min:1',
                'image.*' => 'required|file|mimes:doc,docx,xls,xlsx,pdf,jpeg,jpg,png|max:' . (config('constants.document_size_limit') / 1000),
            ]);

            if ($request->hasFile('image')) {
                $this->ensureDocAttachmentsColumn();
            }

            DB::beginTransaction();

            $status = $request->status;
            $orginator=  $request->orginator;
            $document_category = implode(',', (array) $request->document_category);
            $note= $request->note;
            $purpose=$request->purpose;
            $document_type=$request->document_type;
            $from_location_id = $request->from_location ?? $request->location;
            $to_location_id = $request->to_location;
            $referred_to = (array) $request->referred;
            $from_location_name = !empty($from_location_id)
                ? BusinessLocation::where('id', $from_location_id)->value('name')
                : null;
            $to_location_name = !empty($to_location_id)
                ? BusinessLocation::where('id', $to_location_id)->value('name')
                : null;
            
            $docTypes = DocManagementType::where('id', $document_type)->first();
            $docPurpose = DocManagementPurpose::where('id', $purpose)->first();
            $referred = $this->buildReferredNames($referred_to);
            $attachments = $this->storeAttachments($request, 'image');

            $upload = new DocManagementUpload();
            $upload->originator = $orginator;
            $upload->document_category = $document_category;
            $upload->document_type = $docTypes->type ?? '';
            $upload->location = $from_location_name;
            if ($this->hasDocLocationColumns()) {
                $upload->from_location = $from_location_name;
                $upload->to_location = $to_location_name;
            }
            $upload->purpose = $docPurpose->purpose_type ?? '';
            $upload->note = $note;
            $upload->referred_to = $referred;
            $upload->status = $status;
            $this->fillDocAttachments($upload, $attachments);
            $upload->save();

            DB::commit();

            return [
                'success' => true,
                'data' => $upload,
                'msg' => 'Saved Successfully'
            ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('docmanagement::show');
    }

    public function downloadAttachment($doc_no, $index)
    {
        $doc = DocManagementUpload::where('doc_no', $doc_no)->firstOrFail();
        $attachments = $this->getDocAttachments($doc);
        $attachment = $attachments[$index] ?? null;

        if (empty($attachment)) {
            abort(404);
        }

        $disk = $this->getDocAttachmentDisk($attachment);
        $path = $attachment['path'] ?? null;

        if (! empty($path) && ! empty($attachment['disk']) && Storage::disk($disk)->exists($path)) {
            $stream = Storage::disk($disk)->readStream($path);

            if ($stream === false) {
                abort(404);
            }

            $downloadName = $attachment['name'] ?? basename($path);
            $headers = [];

            if (! empty($attachment['mime'])) {
                $headers['Content-Type'] = $attachment['mime'];
            }

            return response()->streamDownload(function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, $downloadName, $headers);
        }

        $absolutePath = $this->attachmentAbsolutePath($attachment['path'] ?? null);

        if (empty($absolutePath) || !file_exists($absolutePath)) {
            abort(404);
        }

        return response()->download($absolutePath, $attachment['name'] ?? basename($absolutePath));
    }
 /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show_status()
    {
       $business_id = request()->session()->get('user.business_id');
        $maxId = DocManagementUpload::max('doc_no');
        $docTypes = DocManagementType::pluck('type', 'id');
        $docPurpose = DocManagementPurpose::pluck('purpose_type', 'id');
        $docReferred = $this->getDocReferredOptions($business_id);
        $newId = $maxId + 1;
         $business_locations = BusinessLocation::forDropdown($business_id);
         $docForwardwith = DocManagementForwardWith::pluck('forwarded_with', 'forwarded_with');
         $docStatuses = DocManagementStatus::orderBy('status')->pluck('status', 'status');
         if ($docStatuses->isEmpty()) {
             $docStatuses = collect([
                 'Pending' => 'Pending',
                 'Approved' => 'Approved',
                 'Referred Back' => 'Referred Back',
             ]);
         }
         
       if (request()->ajax()) {
           
         $route_operations=DocManagementUpload::all();
           

          if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $route_operations->whereDate('created_at', '>=', request()->start_date);
                $route_operations->whereDate('created_at', '<=', request()->end_date);
            }
            
            if (!empty(request()->type)) {
                $route_operations->where('document_type', request()->type);
            }
            
            return DataTables::of($route_operations)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                         $html .= '<li><a href="#" class="open-modal" data-doc-no="' . $row->doc_no . '"><i class="glyphicon glyphicon-print"></i> ' . __("Print") . '</a></li>';
                    
                    // JavaScript code to handle the click event and show the modal
                    $html .= '<script>
                    $(document).ready(function() {
                    $(".open-modal").click(function(e) {
                    e.preventDefault();
                    var docNo = $(this).data("doc-no");
                    $("#doc_number").val(docNo);
                    $("#print_modal").modal("show");
                    });
                    });
                    </script>';
                    return $html;
                    }
                )
           
                ->rawColumns(['action', 'payment_status', 'method'])
                ->make(true);
        } 
    //chart data
    $statusCounts = DB::table('doc_management_uploads')
        ->select('status', DB::raw('COUNT(*) as count'))
        ->groupBy('status')
        ->get();

    // Prepare the data for the charts
    $labels = $statusCounts->pluck('status');
    $counts = $statusCounts->pluck('count');

    // Generate the datasets for the charts
    $pieDataset = [
        'data' => $counts->toArray(),
        'backgroundColor' => [
            '#FF6384',
            '#36A2EB',
            '#FFCE56',
            // Add more colors if needed
        ],
    ];

    $barDataset = [
        'label' => 'Counts',
        'data' => $counts->toArray(),
        'backgroundColor' => '#36A2EB',
    ];

    // Generate the chart data as JSON
    $chartData = [
        'pie' => [
            'labels' => $labels->toArray(),
            'datasets' => [$pieDataset],
        ],
        'bar' => [
            'labels' => $labels->toArray(),
            'datasets' => [$barDataset],
        ],
    ];
    
        return view('docmanagement::show')
          ->with(compact('newId','docTypes','docPurpose','docReferred' ,'business_locations','docForwardwith','docStatuses','chartData'));
    }
    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('docmanagement::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }
/**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update_referred(Request $request )
    {
        

try {
   
 
    
            $business_id = $request->session()->get('user.business_id');
            
            DB::beginTransaction();
            $note= $request->note;
            $purpose=$request->docForwardwith;
            $referred_to = (array) $request->referred_to;
            $doc_no=$request->doc_number;
            
            $docPurpose = DocManagementForwardWith::where('forwarded_with', $purpose)->first();
            $referred = $this->buildReferredNames($referred_to);
          
$status = trim($purpose);
        
        if ($doc_no) {
                 
            
            $Data = [
             'purpose' => $docPurpose ? $docPurpose->forwarded_with : $purpose,
             'note' => $note,
             'referred_to' =>$referred,
               'status' =>$status
            ];
            
            // Update the record with matching doc_no
    $upload = DocManagementUpload::where('doc_no', $doc_no)
                ->update($Data);
            
            $output = [
            'success' => true,
            'data' => $upload,
            'msg' => __("contact.added_success")
            ];
            DB::commit();
 
       
    } 
} catch (\Exception $e) {
    Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
    $output = [
        'success' => false,
        'msg' => __("messages.something_went_wrong"),
        'error' => $e->getMessage()
    ];
}

return $output;
        
    }
    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
