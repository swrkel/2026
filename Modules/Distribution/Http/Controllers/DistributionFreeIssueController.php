<?php

namespace Modules\Distribution\Http\Controllers;

use \Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use \Modules\Distribution\Entities\Core\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Distribution\Entities\DistributionFreeIssue;
use Modules\Distribution\Entities\DistributionFreeIssueLog;
use Yajra\DataTables\Facades\DataTables;

class DistributionFreeIssueController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $business_id = session('user.business_id');

            $issues = DistributionFreeIssue::with([
                'product:id,name',
                'category:id,name',
                'subcategory:id,name',
                'unit:id,actual_name',
                'createdBy:id,username',
                'updatedBy:id,username',
            ])
                ->where('business_id', $business_id)
                ->whereNotNull('form_no')
                ->whereNotNull('date_time')
                ->select([
                    'id',
                    'form_no',
                    'date_time',
                    'date_since',
                    'date_till',
                    'product_name',
                    'product_category',
                    'product_subcategory',
                    'unit_id',
                    'free_products',
                    'qty_from',
                    'qty_till',
                    'free_qty',
                    'qty_type',
                    'is_free',
                    'is_free_bottles',
                    'status',
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at'
                ])
                ->latest();

            if (!empty($request->products)) {
                $issues->whereIn('product_name', (array) $request->products);
            }
            if (!empty($request->categories)) {
                $issues->whereIn('product_category', (array) $request->categories);
            }
            if (!empty($request->subcategories)) {
                $issues->whereIn('product_subcategory', (array) $request->subcategories);
            }

            return DataTables::eloquent($issues)
                ->addColumn('action', function ($row) {
                    return '
                    <button type="button" class="btn btn-xs btn-primary btn-modal"
                        data-href="' . route('free-issues.edit', $row->id) . '"
                        data-container=".view_modal">
                        <i class="fa fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-xs btn-info view-logs" data-id="' . $row->id . '">View Logs</button>
                    <label class="switch">
                        <input type="checkbox" class="toggle-status" data-id="' . $row->id . '" ' . ($row->status ? 'checked' : '') . '>
                        <span class="slider round"></span>
                    </label>
                    <button class="btn btn-xs btn-warning user-logs" data-id="' . $row->id . '">User Details</button>
                ';
                })
                ->addColumn('product_name_text', fn($row) => $row->product?->name ?? '-')
                ->addColumn('product_category_text', fn($row) => $row->category?->name ?? '-')
                ->addColumn('product_subcategory_text', fn($row) => $row->subcategory?->name ?? '-')
                ->addColumn('unit_name', fn($row) => $row->unit?->actual_name ?? '-')
                ->addColumn('qty_type_text', fn($row) => $row->qty_type === 'single' ? 'Single Qty' : 'Qty Range')
                ->editColumn('date_time', fn($row) => $row->date_time
                    ? \Carbon\Carbon::parse($row->date_time)->format('Y-m-d h:i A') : '-')
                ->editColumn('date_since', fn($row) => $row->date_since
                    ? \Carbon\Carbon::parse($row->date_since)->format('Y-m-d h:i A') : '-')
                ->editColumn('date_till', fn($row) => $row->date_till
                    ? \Carbon\Carbon::parse($row->date_till)->format('Y-m-d h:i A') : '-')
                ->editColumn('form_no', fn($row) => $row->form_no ?? '-')
                ->editColumn('created_at', fn($row) => $row->created_at->format('Y-m-d h:i A'))
                ->rawColumns(['action'])
                ->make(true);
        }

        // Non-AJAX: just return the view skeleton — NO heavy queries here
        return view('distribution::settings.free_issue.index', [
            'products'   => \Modules\Distribution\Entities\Core\Product::where('business_id', session('user.business_id'))
                ->select('id', 'name')->orderBy('name')->pluck('name', 'id'),
            'categories' => Category::forDropdown(session('user.business_id'), 'product'),
        ]);
    }

    // public function index(Request $request)
    // {
    //     if ($request->ajax()) {
    //         $issues = DistributionFreeIssue::with(['product', 'category', 'subcategory', 'unit', 'createdBy', 'updatedBy'])
    //             ->select('*')
    //             ->latest()
    //             ->get();

    //         return DataTables::of($issues)
    //             ->addColumn('action', function ($row) {
    //                 $editBtn = '<button type="button" class="btn btn-xs btn-primary btn-modal" 
    //                     data-href="' . route('free-issues.edit', $row->id) . '" 
    //                     data-container=".view_modal">
    //                     <i class="fa fa-edit"></i> Edit
    //                 </button>';

    //                 return $editBtn . '
    //                     <button class="btn btn-xs btn-info view-logs" data-id="'.$row->id.'">View Logs</button>
    //                     <label class="switch">
    //                         <input type="checkbox" class="toggle-status" data-id="'.$row->id.'" '.($row->status ? 'checked' : '').'>
    //                         <span class="slider round"></span>
    //                     </label>
    //                     <button class="btn btn-xs btn-warning user-logs" data-id="'.$row->id.'">User Details</button>
    //                 ';
    //             })
    //             ->addColumn('product_name_text', function ($row) {
    //                 return $row->product ? $row->product->name : '-';
    //             })
    //             ->addColumn('product_category_text', function ($row) {
    //                 return $row->category ? $row->category->name : '-';
    //             })
    //             ->addColumn('product_subcategory_text', function ($row) {
    //                 return $row->subcategory ? $row->subcategory->name : '-';
    //             })
    //             ->addColumn('unit_name', function ($row) {
    //                 return $row->unit ? $row->unit->actual_name : '-';
    //             })
    //             ->addColumn('qty_type_text', function ($row) {
    //                 return $row->qty_type === 'single' ? 'Single Qty' : 'Qty Range';
    //             })
    //             ->editColumn('date_time', fn ($row) => $row->date_time ? \Carbon\Carbon::parse($row->date_time)->format('Y-m-d h:i A') : ($row->created_at ? $row->created_at->format('Y-m-d h:i A') : '-'))
    //             ->editColumn('form_no', fn ($row) => $row->form_no ?? '-')
    //             ->editColumn('date_since', fn ($row) => $row->date_since ? \Carbon\Carbon::parse($row->date_since)->format('Y-m-d h:i A') : '-')
    //             ->editColumn('date_till', fn ($row) => $row->date_till ? \Carbon\Carbon::parse($row->date_till)->format('Y-m-d h:i A') : '-')
    //             ->editColumn('created_at', fn ($row) => $row->created_at->format('Y-m-d h:i A'))
    //             ->rawColumns(['action'])
    //             ->make(true);
    //     }

    //     return view('distribution::settings.free_issue.index', [
    //         'products'   => Product::pluck('name', 'id'),
    //         'categories' => Category::pluck('name', 'id'),
    //     ]);
    // }

    public function create()
    {
        abort_unless(auth()->user()->can('add_free_issues'), 403);

        $business_id = session('user.business_id');

        $lastFormNo = DistributionFreeIssue::where('business_id', $business_id)
            ->max('form_no') ?? 0;
        $nextFormNo = $lastFormNo + 1;

        return view('distribution::settings.free_issue.create', [
            'products'   => Product::where('business_id', $business_id)->pluck('name', 'id'),
            'categories' => Category::forDropdown($business_id, 'product'),
            'form_no'    => $nextFormNo,
            'units'      => \Modules\Distribution\Entities\Core\Unit::where('business_id', $business_id)->pluck('actual_name', 'id'),
        ]);
    }

    public function addDraft(Request $request)
    {
        $draft   = session()->get('free_issue_draft', []);
        $draft[] = $request->all();
        session(['free_issue_draft' => $draft]);

        return response()->json($draft);
    }


    public function store(Request $request)
{
    try {
        \Log::info('=== FREE ISSUE STORE START ===');
        \Log::info('All input:', $request->all());

        $business_id = session('user.business_id');
        $user_id = auth()->id();
        $ip_address = $request->ip();
        $user_agent = $request->userAgent();

        // ============================================
        // GET ALL INPUTS FIRST (CRITICAL FIX)
        // ============================================
        
        $cats = $request->input('cat', []);
        $subs = $request->input('sub', []);
        $prods = $request->input('prod', []);
        $unit_ids = $request->input('unit_ids', []);  // FIXED: Moved before validation
        $qty_froms = $request->input('qty_froms', []);
        $qty_tills = $request->input('qty_tills', []);
        $qty_frees = $request->input('qty_frees', []);
        $free_products = $request->input('free_products', []);
        $qty_types = $request->input('qty_types', []);
        $date_sinces = $request->input('date_sinces', []);
        $date_tills = $request->input('date_tills', []);
        $date_time = $request->input('date_time');
        $is_frees = $request->input('is_frees', []);
        $is_free_bottles = $request->input('is_free_bottles_list', []);

        // ============================================
        // VALIDATION SECTION
        // ============================================
        
        // Validation 1: Check if any rows exist
        if (empty($prods)) {
            $errorMsg = 'Please add at least one product row before saving.';
            \Log::warning('Store validation failed: ' . $errorMsg);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->back()->withErrors(['msg' => $errorMsg]);
        }
        
        // Validation 2: Check each row for required data
        $validationErrors = [];
        
        for ($i = 0; $i < count($prods); $i++) {
            $rowNum = $i + 1;
            
            // Check product
            if (empty($prods[$i])) {
                $validationErrors[] = "Row {$rowNum}: Product is required.";
            }
            
            // FIXED: Check unit - now $unit_ids is defined
            if (empty($unit_ids[$i] ?? null)) {
                $validationErrors[] = "Row {$rowNum}: Unit is required. Please select a unit for the product.";
            }
            
            // Check free products
            $freeProdsValue = $free_products[$i] ?? '[]';
            if (is_string($freeProdsValue)) {
                $freeProdsArray = json_decode($freeProdsValue, true);
            } else {
                $freeProdsArray = $freeProdsValue;
            }
            if (empty($freeProdsArray)) {
                $validationErrors[] = "Row {$rowNum}: At least one free product is required.";
            }
            
            // Check dates
            if (empty($date_sinces[$i] ?? null)) {
                $validationErrors[] = "Row {$rowNum}: Date Since is required.";
            }
            if (empty($date_tills[$i] ?? null)) {
                $validationErrors[] = "Row {$rowNum}: Date Till is required.";
            }
            
            // Check free qty
            $freeQty = $qty_frees[$i] ?? null;
            if (empty($freeQty) || floatval($freeQty) <= 0) {
                $validationErrors[] = "Row {$rowNum}: Free Qty must be greater than 0.";
            }
            
            // Check qty based on type
            $qtyType = $qty_types[$i] ?? 'single';
            $qtyFrom = $qty_froms[$i] ?? null;
            $qtyTill = $qty_tills[$i] ?? null;
            
            if ($qtyType === 'single') {
                if (empty($qtyFrom) || floatval($qtyFrom) <= 0) {
                    $validationErrors[] = "Row {$rowNum}: For Every Qty must be greater than 0.";
                }
            } else {
                if (empty($qtyFrom) || floatval($qtyFrom) <= 0) {
                    $validationErrors[] = "Row {$rowNum}: Qty From must be greater than 0.";
                }
                if (empty($qtyTill) || floatval($qtyTill) <= 0) {
                    $validationErrors[] = "Row {$rowNum}: Qty Till must be greater than 0.";
                }
                if (!empty($qtyFrom) && !empty($qtyTill) && floatval($qtyFrom) > floatval($qtyTill)) {
                    $validationErrors[] = "Row {$rowNum}: Qty From cannot be greater than Qty Till.";
                }
            }
        }
        
        // If validation errors exist, return them
        if (!empty($validationErrors)) {
            $errorMsg = implode(' ', $validationErrors);
            \Log::warning('Store validation failed: ' . $errorMsg);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMsg, 'errors' => $validationErrors], 422);
            }
            return redirect()->back()->withErrors(['msg' => $errorMsg]);
        }
        
        // ============================================
        // PROCESSING SECTION
        // ============================================

        $created_issue_ids = [];

        // Get the next form number
        $lastFormNo = DistributionFreeIssue::where('business_id', $business_id)->max('form_no') ?? 0;
        $form_no = $lastFormNo + 1;

        // Create each free issue record
        for ($i = 0; $i < count($prods); $i++) {
            $freeProds = $free_products[$i] ?? '[]';
            if (is_string($freeProds)) {
                $freeProds = json_decode($freeProds, true);
            }
            if (!is_array($freeProds)) {
                $freeProds = [];
            }

            $qtyType = $qty_types[$i] ?? 'range';
            $qtyTill = ($qtyType === 'single') ? null : ($qty_tills[$i] ?? null);

            $issue = DistributionFreeIssue::create([
                'business_id' => $business_id,
                'form_no' => $form_no,
                'date_time' => $date_time,
                'date_since' => $date_sinces[$i] ?? null,
                'date_till' => $date_tills[$i] ?? null,
                'product_name' => $prods[$i] ?? null,
                'product_category' => $cats[$i] ?? null,
                'product_subcategory' => $subs[$i] ?? null,
                'unit_id' => $unit_ids[$i] ?? null,
                'free_products' => $freeProds,
                'qty_from' => $qty_froms[$i] ?? null,
                'qty_till' => $qtyTill,
                'free_qty' => $qty_frees[$i] ?? null,
                'qty_type' => $qtyType,
                'is_free' => $is_frees[$i] ?? 0,
                'is_free_bottles' => $is_free_bottles[$i] ?? 0,
                'status' => 1,
                'created_by' => $user_id,
            ]);

            if ($issue && $issue->id) {
                $created_issue_ids[] = $issue->id;
                \Log::info("Created free issue with ID: {$issue->id}, form_no: {$form_no}");
            } else {
                \Log::error("Failed to create free issue at index {$i}");
            }
        }

        // Create log for the main issue
        if (!empty($created_issue_ids)) {
            $main_issue_id = $created_issue_ids[0];
            \Log::info("Creating log for main issue ID: {$main_issue_id}");

            $log = DistributionFreeIssueLog::create([
                'free_issue_id' => $main_issue_id,
                'user_id' => $user_id,
                'action' => 'created',
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
            ]);

            if ($log && $log->id) {
                \Log::info("Log created successfully with ID: {$log->id}, free_issue_id: {$log->free_issue_id}");
            } else {
                \Log::error("Failed to create log. Log object: " . json_encode($log));
            }
        } else {
            \Log::error("No free issues were created successfully!");
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to create free issues. Please try again.'], 500);
            }
            return redirect()->back()->withErrors(['msg' => 'Failed to create free issues. Please try again.']);
        }

        // ============================================
        // SUCCESS RESPONSE
        // ============================================
        
        $successMsg = 'Free Issues saved successfully! Form No: ' . $form_no;
        \Log::info('Free Issue store completed successfully. Form No: ' . $form_no);
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'form_no' => $form_no,
                'redirect' => route('distribution.settings.tab', ['tab' => 'free_issue'])  // Redirect to settings with free_issue tab
            ]);
        }
        
return redirect()->route('distribution.settings.tab', ['tab' => 'free_issue'])->with('success', $successMsg);
        
    } catch (\Exception $e) {
        \Log::error('FreeIssue store critical error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        $errorMsg = 'An error occurred while saving: ' . $e->getMessage();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $errorMsg], 500);
        }
        
        return redirect()->back()->withErrors(['msg' => $errorMsg])->withInput();
    }
}

    public function edit($id)
    {
        abort_unless(auth()->user()->can('add_free_issues'), 403);
        $business_id = session('user.business_id');

        $freeIssue = DistributionFreeIssue::with('unit')->findOrFail($id);

        if ($freeIssue->form_no) {
            $freeIssues = DistributionFreeIssue::where('business_id', $business_id)
                ->where('form_no', $freeIssue->form_no)
                ->with(['unit', 'category', 'subcategory'])
                ->get();
        } else {
            $freeIssues = collect([$freeIssue]);
        }

        $categories    = Category::forDropdown($business_id, 'product');
        $sub_categories = Category::subCategoryforDropdown($business_id, false);

        return view('distribution::settings.free_issue.edit_actual', [
            'freeIssues'     => $freeIssues,
            'mainIssue'      => $freeIssue,
            'products'       => Product::where('business_id', $business_id)->pluck('name', 'id'),
            'categories'     => $categories,
            'sub_categories' => $sub_categories,
            'units'          => \Modules\Distribution\Entities\Core\Unit::where('business_id', $business_id)->pluck('actual_name', 'id'),
            'auto_date_time' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $business_id = session('user.business_id');
            $user_id     = auth()->id();

            $cats            = $request->input('cat', []);
            $subs            = $request->input('sub', []);
            $prods           = $request->input('prod', []);
            $unit_ids        = $request->input('unit_ids', []);
            $qty_froms       = $request->input('qty_froms', []);
            $qty_tills       = $request->input('qty_tills', []);
            $qty_frees       = $request->input('qty_frees', []);
            $free_products   = $request->input('free_products', []);
            $qty_types       = $request->input('qty_types', []);
            $date_sinces     = $request->input('date_sinces', []);
            $date_tills      = $request->input('date_tills', []);
            $date_time       = $request->input('date_time');
            $is_frees        = $request->input('is_frees', []);
            $is_free_bottles = $request->input('is_free_bottles_list', []);

            if (empty($cats)) {
                $msg = 'No rows to save. Please add at least one rule.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'msg' => $msg], 422);
                }
                return redirect()->back()->withErrors(['msg' => $msg]);
            }

            $mainIssue = DistributionFreeIssue::findOrFail($id);
            $oldData   = $mainIssue->toArray();
            $form_no   = $mainIssue->form_no;

            if ($form_no) {
                DistributionFreeIssue::where('business_id', $business_id)
                    ->where('form_no', $form_no)
                    ->delete();
            } else {
                $mainIssue->delete();
                $lastFormNo = DistributionFreeIssue::where('business_id', $business_id)->max('form_no') ?? 0;
                $form_no    = $lastFormNo + 1;
            }

            $last_issue_id = null;

            for ($i = 0; $i < count($cats); $i++) {
                $freeProds = $free_products[$i] ?? '[]';
                if (is_string($freeProds)) {
                    $freeProds = json_decode($freeProds, true);
                }
                if (!is_array($freeProds)) {
                    $freeProds = [];
                }

                $qtyType = $qty_types[$i] ?? 'range';
                $qtyTill = ($qtyType === 'single') ? null : ($qty_tills[$i] ?? null);

                $issue = DistributionFreeIssue::create([
                    'business_id'         => $business_id,
                    'form_no'             => $form_no,
                    'date_time'           => $date_time,
                    'date_since'          => $date_sinces[$i] ?? null,
                    'date_till'           => $date_tills[$i] ?? null,
                    'product_name'        => $prods[$i] ?? null,
                    'product_category'    => $cats[$i] ?? null,
                    'product_subcategory' => $subs[$i] ?? null,
                    'unit_id'             => $unit_ids[$i] ?? null,
                    'free_products'       => $freeProds,
                    'qty_from'            => $qty_froms[$i] ?? null,
                    'qty_till'            => $qtyTill,
                    'free_qty'            => $qty_frees[$i] ?? null,
                    'qty_type'            => $qtyType,
                    'is_free'             => $is_frees[$i] ?? 0,
                    'is_free_bottles'     => $is_free_bottles[$i] ?? 0,
                    'status'              => 1,
                    'created_by'          => $user_id,
                    'updated_by'          => $user_id,
                ]);

                $last_issue_id = $issue->id;
            }

            if ($last_issue_id) {
                DistributionFreeIssueLog::create([
                    'free_issue_id' => $last_issue_id,
                    'user_id'       => $user_id,
                    'action'        => 'updated',
                    'ip_address'    => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                    'old_data'      => json_encode($oldData),
                    'new_data'      => json_encode($request->except(['_token', '_method'])),
                ]);
            }

            $output = ['success' => true, 'msg' => __('Free Issues updated successfully!')];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output);
            }

            return redirect()->back()->with(['status' => $output, 'page' => 'free_issue']);
        } catch (\Throwable $e) {
            \Log::error('FreeIssue update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

            $output = ['success' => false, 'msg' => 'Error updating Free Issue: ' . $e->getMessage()];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output, 500);
            }

            return redirect()->back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function toggleStatus($id)
    {
        $issue = DistributionFreeIssue::findOrFail($id);
        $oldStatus = $issue->status;
        $issue->status = !$issue->status;
        $issue->updated_by = auth()->id();
        $issue->save();

        DistributionFreeIssueLog::create([
            'free_issue_id' => $id,
            'user_id'       => auth()->id(),
            'action'        => $issue->status ? 'enabled' : 'disabled',
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'old_data'      => json_encode(['status' => $oldStatus]),
            'new_data'      => json_encode(['status' => $issue->status]),
        ]);

        return response()->json(['success' => true]);
    }

    public function logs($id)
    {
        $logs = DistributionFreeIssueLog::where('free_issue_id', $id)
            ->with('user:id,username,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function getSubCategories($ids)
    {
        return Category::whereIn('parent_id', explode(',', $ids))->pluck('name', 'id');
    }

    public function getProducts(Request $request)
    {
        $subcategories = $request->get('subcategories', []);

        if (empty($subcategories)) {
            return response()->json([]);
        }

        return Product::whereIn('sub_category_id', $subcategories)
            ->pluck('name', 'id');
    }

    public function getProductUnit($id)
    {
        $business_id = session('user.business_id');

        $units = \Modules\Distribution\Entities\Core\Unit::where('business_id', $business_id)
            ->pluck('actual_name', 'id')
            ->toArray();

        return response()->json(['units' => $units]);
    }

    public function getUserDetails($id)
    {
        $issue = DistributionFreeIssue::with(['createdBy', 'updatedBy'])->findOrFail($id);

        return response()->json([
            'id' => $issue->id,
            'form_no' => $issue->form_no,
            'created_at' => $issue->created_at,
            'updated_at' => $issue->updated_at,
            'status' => $issue->status,
            'created_by_user' => $issue->createdBy ? [
                'id' => $issue->createdBy->id,
                'username' => $issue->createdBy->username,
                'email' => $issue->createdBy->email,
            ] : null,
            'updated_by_user' => $issue->updatedBy ? [
                'id' => $issue->updatedBy->id,
                'username' => $issue->updatedBy->username,
                'email' => $issue->updatedBy->email,
            ] : null,
        ]);
    }
}
