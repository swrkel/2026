<?php

namespace Modules\DocManagement\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\User;
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
use Modules\DocManagement\Entities\DocManagementLogo;
use Modules\DocManagement\Entities\DocManagementReferredTo;
use Modules\DocManagement\Entities\DocManagementReferredToStatusHistory;
use Modules\HR\Entities\Department;
use Modules\Essentials\Entities\HrmDepartment;
use Modules\Essentials\Entities\HrmDesignation;
use App\Category;
use Carbon\Carbon;
use App\Models\Designation;

class DocManagementSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $username = user::pluck('username', 'username');
        $signatureLevel = DocManagementMandatorySignature::all()->pluck('signature_level','signature_level');
        $designations = Department::where('business_id', $business_id)
            ->orderBy('department')
            ->pluck('department','department');
        $referred_departments = $designations
            ->merge(
                HrmDepartment::where('business_id', $business_id)
                    ->orderBy('name')
                    ->pluck('name', 'name')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->combine(
                $designations
                    ->merge(
                        HrmDepartment::where('business_id', $business_id)
                            ->orderBy('name')
                            ->pluck('name', 'name')
                    )
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
            );
        $user_designations = HrmDesignation::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'name');

        return view('docmanagement::doc_settings.settings_index')
          ->with(compact('business_locations','username','designations','signatureLevel', 'user_designations', 'referred_departments'));
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
    public function store_category(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $categoryType = trim($request->categoryType);

            if (empty($categoryType)) {
                return [
                    'success' => false,
                    'msg' => 'Document Category is required'
                ];
            }

            // ✅ Get logged-in username correctly
            $user = auth()->user()->username;

            // ✅ Strict duplicate check (case insensitive)
            $exists = DocManagementCategory::whereRaw(
                'LOWER(document_category) = ?',
                [mb_strtolower($categoryType)]
            )->exists();

            if ($exists) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => 'Document Category already exists'
                ];
            }

            $category = DocManagementCategory::create([
                'document_category' => $categoryType,
                'user' => $user
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $category,
                'msg' => 'Document Category added successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e);

            return [
                'success' => false,
                'msg' => 'Something went wrong',
                'error' => $e->getMessage()
            ];
        }
    }

    public function store_type(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $documentType = trim($request->documentType);

            if (empty($documentType)) {
                return [
                    'success' => false,
                    'msg' => 'Document Type is required'
                ];
            }

            // duplicate prevention
            $exists = DocManagementType
                ::whereRaw('LOWER(TRIM(type)) = ?', [mb_strtolower($documentType)])
                ->exists();

            if ($exists) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => 'Document Type already exists'
                ];
            }

            $username = User::where('id', auth()->id())->pluck('username')->first();

            $type = DocManagementType::create([
                'business_id' => $business_id,
                'type' => $documentType,
                'user' => $username // FIXED
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $type,
                'msg' => 'Document Type added successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e);

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }
    public function store_purpose(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $purpose = trim($request->purpose);

            if (empty($purpose)) {
                return [
                    'success' => false,
                    'msg' => 'Document Purpose is required'
                ];
            }

            $username = User::where('id', auth()->id())->pluck('username')->first();

            // duplicate prevention
            $exists = DocManagementPurpose::where('user', $username)
                ->whereRaw('LOWER(TRIM(purpose_type)) = ?', [mb_strtolower($purpose)])
                ->exists();

            if ($exists) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => 'Document Purpose already exists'
                ];
            }

            $purpose = DocManagementPurpose::create([
                // 'business_id' => $business_id,
                'purpose_type' => $purpose,
                'user' => $username // FIXED
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $purpose,
                'msg' => 'Document Purpose added successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e);

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }

    public function store_forwardwith(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $username = auth()->user()->username ?? auth()->user()->first_name ?? 'System';
            DB::beginTransaction();

            $document_forwardwith = trim((string) $request->fowardwith);

            if (empty($document_forwardwith)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                    'error' => 'Forwarded with is required',
                ];
            }

            $existing_forwardwith = DocManagementForwardWith::whereRaw(
                'LOWER(forwarded_with) = ?',
                [mb_strtolower($document_forwardwith)]
            )->first();

            if (!empty($existing_forwardwith)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'data' => $existing_forwardwith,
                    'msg' => 'Forwarded with already exists'
                ];
            }

            $document_forwardwiths = DocManagementForwardWith::create([
                'forwarded_with' => $document_forwardwith,
                'user' => $username,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $document_forwardwiths,
                'msg' => __('contact.added_success')
            ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error($e);

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }

    public function store_mandatorySignature(Request $request)
    {
        if (!auth()->user()->can('supplier.create') && !auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {

            $business_id = $request->session()->get('user.business_id');

            $business_id = $request->session()->get('user.business_id');
            $username = User::where('id', $business_id)->pluck('username')->first();
            DB::beginTransaction();
            $number =  $request->number;
            $mandatory =  $request->mandatory;
            $user = $username;

            if ($mandatory) {


                $Data = [
                    'no_of_mandatory' => $number,
                    'signature_level' => $mandatory,
                    'user' => $user
                ];

                $mandatory = DocManagementMandatorySignature::create($Data);
                $output = [
                    'success' => true,
                    'data' => $mandatory,
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

    public function store_signatures(Request $request)
    {


        try {

            $business_id = $request->session()->get('user.business_id');

            $business_id = $request->session()->get('user.business_id');
            $username = User::where('id', $business_id)->pluck('username')->first();
            DB::beginTransaction();
            $date =  $request->date;
            $location =  $request->location;
            $user =  $request->user;
            $designations =  $request->designations;
            $signature_level =  $request->signature_level;
            $image =  $request->image;
            $path = '';

            if ($request->hasFile('image')) {
                $imageFiles = $request->file('image');

                foreach ($imageFiles as $imageFile) {
                    // Define the directory where you want to store the image files
                    $directory = 'public_html/Modules/DocManagement/Resources/assets/images';

                    // Generate a unique filename for each uploaded image
                    $fileName = uniqid() . '.' . $imageFile->getClientOriginalExtension();

                    // Store the image file in the specified directory
                    $path = $imageFile->storeAs($directory, $fileName);

                    // You can also get the file name, extension, etc.
                    $originalFileName = $imageFile->getClientOriginalName();
                    $extension = $imageFile->getClientOriginalExtension();
                }
            }

            if ($signature_level) {


                $Data = [
                    'date' => $date,
                    'location' => $location,
                    'user' => $user,
                    'designations' => $designations,

                    'upload_signature' => $path,
                    'signature_levels' => $signature_level
                ];

                $type = DocManagementSignature::create($Data);
                $output = [
                    'success' => true,
                    'data' => $type,
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

    public function store_logo(Request $request)
    {


        try {

            $business_id = $request->session()->get('user.business_id');

            $business_id = $request->session()->get('user.business_id');
            $username = User::where('id', $business_id)->pluck('username')->first();
            DB::beginTransaction();

            $location =  $request->location;
            $user =  $request->user;
            $image =  $request->image;
            $enable_disable_buttons = $request->enable_disable_buttons;
            $path = '';
            $toggle = 0;
            if ($enable_disable_buttons == 'enabled') {
                $toggle = 1;
            }


            if ($request->hasFile('image')) {
                $imageFiles = $request->file('image');

                foreach ($imageFiles as $imageFile) {
                    // Define the directory where you want to store the image files
                    // $directory = 'public_html/images';
                    $directory = 'public_html/Modules/DocManagement/Resources/assets/images';
                    // Generate a unique filename for each uploaded image
                    $fileName = uniqid() . '.' . $imageFile->getClientOriginalExtension();

                    // Store the image file in the specified directory
                    $path = $imageFile->storeAs($directory, $fileName);

                    // You can also get the file name, extension, etc.
                    $originalFileName = $imageFile->getClientOriginalName();
                    $extension = $imageFile->getClientOriginalExtension();
                }
            }

            if ($image) {


                $Data = [
                    'upload_logo' => $path,
                    'position' => $location,
                    'enable_button' => $toggle,
                    'user' => $user

                ];

                $type = DocManagementLogo::create($Data);
                $output = [
                    'success' => true,
                    'data' => $type,
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

    public function store_doc_status(Request $request)
    {
        try {
            DB::beginTransaction();
            $status_name = trim((string) $request->status_name);
            $user = auth()->user()->username ?? auth()->user()->first_name ?? 'System';

            if ($status_name === '') {
                return [
                    'success' => false,
                    'msg' => 'Status is required'
                ];
            }

            $existing_status = DocManagementStatus::whereRaw('LOWER(status) = ?', [mb_strtolower($status_name)])->first();
            if (!empty($existing_status)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'data' => $existing_status,
                    'msg' => 'Doc Status already exists'
                ];
            }

            $status = DocManagementStatus::create([
                'date_time' => now(),
                'status' => $status_name,
                'user' => $user,
            ]);

            DB::commit();

            return [
                'success' => true,
                'data' => $status,
                'msg' => __('contact.added_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }

    public function store_referred_to(Request $request)
    {
        try {
            DB::beginTransaction();

            $department = trim((string) $request->department);
            $designation = trim((string) $request->designation);
            $officer_name = trim((string) $request->officer_name);
            $status = trim((string) $request->status) ?: 'Active';
            $date_time = now();
            if (! empty($request->date_time)) {
                try {
                    $date_time = Carbon::createFromFormat('m/d/Y h:i A', $request->date_time);
                } catch (\Throwable $e) {
                    $date_time = now();
                }
            }
            $username = auth()->user()->username ?? auth()->user()->first_name ?? 'System';

            if ($department && $designation && $officer_name) {
                $existing = DocManagementReferredTo::whereRaw('LOWER(department) = ?', [mb_strtolower($department)])
                    ->whereRaw('LOWER(designation) = ?', [mb_strtolower($designation)])
                    ->whereRaw('LOWER(officer_name) = ?', [mb_strtolower($officer_name)])
                    ->first();
                if ($existing) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'data' => $existing,
                        'msg' => 'Referred to already exists'
                    ];
                }

                $referred_to = DocManagementReferredTo::create([
                    'date_time' => $date_time,
                    'department' => $department,
                    'designation' => $designation,
                    'officer_name' => $officer_name,
                    'status' => $status,
                    'user' => $username,
                ]);

                DB::commit();

                return [
                    'success' => true,
                    'data' => $referred_to,
                    'msg' => __("contact.added_success")
                ];
            }

            DB::rollBack();
            return [
                'success' => false,
                'msg' => 'Department, Designation and Officer Name are required'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }
    /***
     * Fetching document category types
     * 
     * 
     */
    public function doc_category_gets()
    {
        $categoryType = DocManagementCategory::select('document_category', DB::raw('MAX(id) as id'), DB::raw('MAX(user) as user'), DB::raw('MAX(created_at) as created_at'))
            ->groupBy('document_category')
            ->orderByDesc(DB::raw('MAX(created_at)'))
            ->get();
        return  $categoryType;
    }

    public function store_department(Request $request)
    {
        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('user.business_id');
            $department_name = trim($request->department);
            $description = trim($request->description);

            Log::info("Storing department: " . $department_name . " for business_id: " . $business_id);

            if (empty($department_name)) {
                return [
                    'success' => false,
                    'msg' => 'Department is required'
                ];
            }

            // Strong duplicate prevention
            $exists = Department::where('business_id', $business_id)
                ->whereRaw('LOWER(TRIM(department)) = ?', [mb_strtolower($department_name)])
                ->exists();

            if ($exists) {
                DB::rollBack();
                return [
                    'success' => false,
                    'msg' => 'Department already exists'
                ];
            }

            $department = Department::create([
                'business_id' => $business_id,
                'department' => $department_name,
                'description' => $description,
                'user_id' => auth()->id() // NEW FIELD
            ]);

            HrmDepartment::firstOrCreate(
                [
                    'business_id' => $business_id,
                    'name' => $department_name,
                ],
                [
                    'description' => $description,
                    'created_by' => auth()->id(),
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'data' => $department,
                'msg' => __('hr::lang.department_add_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error($e);

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }

    public function doc_department_gets()
    {
        $business_id = request()->session()->get('user.business_id');

        return Department::where('business_id', $business_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'department' => $item->department,
                    'description' => $item->description,
                    'added_by' => optional($item->user)->username ?? 'System',
                    'user' => optional($item->user)->username ?? 'System',
                    'created_at' => !empty($item->created_at) ? Carbon::parse($item->created_at)->format('m/d/Y h:i A') : '',
                ];
            });
    }
    public function store_designation(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $department_name = trim((string) $request->input('department_id'));
            $designation_name = trim((string) $request->input('designation_name'));
            $description = trim((string) $request->input('description'));

            if ($department_name === '' || $designation_name === '') {
                return [
                    'success' => false,
                    'msg' => 'Department and Designation are required'
                ];
            }

            $department = HrmDepartment::firstOrCreate(
                [
                    'business_id' => $business_id,
                    'name' => $department_name,
                ],
                [
                    'created_by' => auth()->id(),
                ]
            );

            $existing_designation = HrmDesignation::where('business_id', $business_id)
                ->where('department_id', $department->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($designation_name)])
                ->first();

            if (!empty($existing_designation)) {
                return [
                    'success' => false,
                    'data' => $existing_designation,
                    'msg' => 'Designation already exists'
                ];
            }

            $designation = HrmDesignation::create([
                'business_id' => $business_id,
                'department_id' => $department->id,
                'name' => $designation_name,
                'description' => $description,
                'created_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'data' => $designation,
                'msg' => __('lang_v1.added_success')
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }
    public function doc_designation_gets()
    {
        $business_id = request()->session()->get('user.business_id');

        return HrmDesignation::where('hrm_designations.business_id', $business_id)
            ->leftJoin('hrm_departments as d', 'd.id', '=', 'hrm_designations.department_id')
            ->leftJoin('users as u', 'u.id', '=', 'hrm_designations.created_by')
            ->orderByDesc('hrm_designations.created_at')
            ->get([
                'hrm_designations.id',
                'hrm_designations.name',
                'hrm_designations.description',
                'hrm_designations.created_at',
                'd.name as department',
                'u.username as user',
            ])
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'department' => $item->department,
                    'designation' => $item->name,
                    'name' => $item->name,
                    'description' => $item->description,
                    'added_by' => $item->user ?? 'System',
                    'user' => $item->user ?? 'System',
                    'created_at' => !empty($item->created_at) ? Carbon::parse($item->created_at)->format('m/d/Y h:i A') : '',
                ];
            });
    }

    public function doc_type_gets()
    {
        $business_id = request()->session()->get('user.business_id');

        return DocManagementType::
            // ->where('business_id', $business_id)
            latest()
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,

                    'created_at' => $item->created_at
                        ? \Carbon\Carbon::parse($item->created_at)->format('M d, Y h:i A')
                        : '',

                    'added_by' => $item->user ?? 'System'
                ];
            });
    }
    public function doc_purpose_gets()
    {
        //  dd("test");
        return DocManagementPurpose::latest()
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,

                    'created_at' => $item->created_at
                        ? \Carbon\Carbon::parse($item->created_at)->format('M d, Y h:i A')
                        : '',

                    'user' => optional($item->user) ?? 'System'
                ];
            });

        // return  $purpose;
    }
    public function doc_forwardwith_get()
    {
        return DocManagementForwardWith::orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'forwarded_with' => $item->forwarded_with,
                    'user' => $item->user,
                    'created_at' => !empty($item->created_at) ? Carbon::parse($item->created_at)->format('m/d/Y h:i A') : '',
                ];
            });
    }
    public function doc_status_gets()
    {
        return DocManagementStatus::orderByDesc('date_time')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'status' => $item->status,
                    'user' => $item->user,
                    'date_time' => !empty($item->date_time) ? Carbon::parse($item->date_time)->format('m/d/Y h:i A') : '',
                ];
            });
    }
    public function doc_mandatorysignature_gets()
    {
        //  dd("test");
        $mandatory = DocManagementMandatorySignature::all();

        return  $mandatory;
    }
    public function doc_upload_gets()
    {

        $upload = DocManagementSignature::all();
        return  $upload;
    }
    public function doc_uploadlogo_gets()
    {

        $uploadlogo = DocManagementLogo::all();
        return  $uploadlogo;
    }
    public function doc_referred_to_gets(Request $request)
    {
        $query = DocManagementReferredTo::query();
        $hasDesignation = Schema::hasColumn('doc_management_referred_tos', 'designation');
        $hasOfficerName = Schema::hasColumn('doc_management_referred_tos', 'officer_name');

        if ($request->filled('department')) {
            $query->where('department', trim((string) $request->department));
        }

        if ($request->filled('status')) {
            $query->where('status', trim((string) $request->status));
        }

        if ($hasDesignation && $request->filled('designation')) {
            $query->where('designation', trim((string) $request->designation));
        }

        $referred_to = $query->orderByDesc('date_time')->get()->map(function ($item) use ($hasDesignation, $hasOfficerName) {
            return [
                'id' => $item->id,
                'date_time' => !empty($item->date_time) ? Carbon::parse($item->date_time)->format('m/d/Y h:i A') : '',
                'department' => $item->department,
                'designation' => $hasDesignation ? $item->designation : '',
                'officer_name' => $hasOfficerName ? $item->officer_name : '',
                'status' => $item->status,
                'user' => $item->user,
            ];
        });
        return $referred_to;
    }
    public function update_referred_to_status(Request $request)
    {
        try {
            DB::beginTransaction();

            $referred_to = DocManagementReferredTo::findOrFail($request->id);
            $old_status = $referred_to->status;
            $new_status = trim((string) $request->status);
            $changed_by = auth()->user()->username ?? auth()->user()->first_name ?? 'System';

            $referred_to->update([
                'status' => $new_status,
            ]);

            DocManagementReferredToStatusHistory::create([
                'doc_management_referred_to_id' => $referred_to->id,
                'date_time' => now(),
                'changed_by' => $changed_by,
                'status_from' => $old_status,
                'status_to' => $new_status,
            ]);

            DB::commit();

            return [
                'success' => true,
                'msg' => 'Status changed successfully'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return [
                'success' => false,
                'msg' => __("messages.something_went_wrong"),
                'error' => $e->getMessage()
            ];
        }
    }
    public function doc_referred_to_status_history_gets(Request $request)
    {
        $history = DocManagementReferredToStatusHistory::where('doc_management_referred_to_id', $request->id)
            ->orderByDesc('date_time')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'date_time' => !empty($item->date_time) ? Carbon::parse($item->date_time)->format('m/d/Y h:i A') : '',
                    'status_from' => $item->status_from,
                    'status_to' => $item->status_to,
                    'changed_by' => $item->changed_by,
                ];
            });

        return $history;
    }

    public function getDesignationsByDepartment(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $department = trim((string) $request->department);

        $query = HrmDesignation::where('business_id', $business_id)->orderBy('name');

        if ($department !== '') {
            $department_id = HrmDepartment::where('business_id', $business_id)
                ->where('name', $department)
                ->value('id');

            if (!empty($department_id)) {
                $query->where('department_id', $department_id);
            } else {
                return HrmDesignation::where('business_id', $business_id)
                    ->orderBy('name')
                    ->pluck('name')
                    ->values();
            }
        }

        return $query->pluck('name')->values();
    }
    public function document_purpose_gets()
    {

        $purpose = DocManagementPurpose::all();
        return  $purpose;
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
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
