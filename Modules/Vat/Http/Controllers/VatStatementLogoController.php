<?php

namespace Modules\Vat\Http\Controllers;

use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Modules\Vat\Entities\VatStatementLogo;
use Yajra\DataTables\Facades\DataTables;
use App\System;
use Intervention\Image\Facades\Image;

class VatStatementLogoController extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;

    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
        $this->transactionUtil =  $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $drivers = VatStatementLogo::leftjoin('users','users.id','vat_statement_logos.created_by')->where('vat_statement_logos.business_id',$business_id)->select(['users.username','vat_statement_logos.*']);
            
            return DataTables::of($drivers)
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
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Vat\Http\Controllers\VatStatementLogoController@edit', [$row->id]) . '" class="vat-ajax-modal-trigger" data-container=".fuel_tank_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        $html .= '<li><a href="#" data-href="' . action('\Modules\Vat\Http\Controllers\VatStatementLogoController@destroy', [$row->id]) . '" class="delete_task"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        
                        return $html;
                    }
                )
                ->editColumn('logo', function ($row) {
                    if (empty($row->logo)) {
                        return '';
                    }

                    return '<a href="#" data-href="' . action('\Modules\Vat\Http\Controllers\VatStatementLogoController@show', [$row->id]) . '" class="vat-ajax-modal-trigger btn-xs btn btn-primary" data-container=".fuel_tank_modal">' . __('messages.view') . '</a>';
                })
                ->editColumn('text_position', function ($row) {
                    return !empty($row->text_position) ? __('vat::lang.'.$row->text_position) : '';
                })
                ->addColumn('statement_date_display', function ($row) {
                    $date = Schema::hasColumn('vat_statement_logos', 'statement_date') && !empty($row->statement_date)
                        ? $row->statement_date
                        : $row->created_at;
                    return !empty($date) ? $this->vatFormatter()->format_date($date) : '';
                })
                ->editColumn('created_at', '{{@format_date($created_at)}}')
                ->removeColumn('id')
                ->rawColumns(['action','logo'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        

        return view('vat::customer_statement.logos.create')->with(compact(
            'business_id'
        ));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        try {
            $data = $request->except('_token','attachment','statement_date');
            $data['business_id'] = $business_id;
            $data['created_by'] = Auth::user()->id;
            $selected_statement_date = $request->input('statement_date') ?: now()->toDateString();
            if (Schema::hasColumn('vat_statement_logos', 'statement_date')) {
                $data['statement_date'] = $selected_statement_date;
            }
            
            //upload file
            if (!file_exists(public_path('img/fleet_logos/' . $business_id))) {
                mkdir(public_path('img/fleet_logos/' . $business_id), 0777, true);
            }
            if ($request->hasfile('attachment')) {
                $image_width = (int) System::getProperty('upload_image_width');
                $image_hieght = (int) System::getProperty('upload_image_height');
                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $file->move(public_path('img/fleet_logos/' . $business_id), $filename);
                $uploadFile = 'img/fleet_logos/' . $business_id . '/' . $filename;
                $data['logo'] = $uploadFile;
            }

            
            
            $statement_logo = VatStatementLogo::create($data);
            if (!Schema::hasColumn('vat_statement_logos', 'statement_date') && !empty($selected_statement_date)) {
                $statement_logo->created_at = Carbon::parse($selected_statement_date)->startOfDay();
                $statement_logo->save();
            }

            $output = [
                'success' => true,
                'tab' => 'fleet_logos',
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'fleet_logos',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $driver = VatStatementLogo::where('business_id', $business_id)->findOrFail($id);
        $logoUrl = $this->resolveLogoUrl($driver->logo);
        return view('vat::customer_statement.logos.show')->with(compact('driver', 'logoUrl'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $driver = VatStatementLogo::find($id);

        return view('vat::customer_statement.logos.edit')->with(compact(
            'driver'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        try {
            $data = $request->except('_token', '_method','attachment','statement_date');
            $selected_statement_date = $request->input('statement_date');
            if (Schema::hasColumn('vat_statement_logos', 'statement_date') && !empty($selected_statement_date)) {
                $data['statement_date'] = $selected_statement_date;
            }
            
            if (!file_exists(public_path('img/fleet_logos/' . $business_id))) {
                mkdir(public_path('img/fleet_logos/' . $business_id), 0777, true);
            }
            if ($request->hasfile('attachment')) {
                $image_width = (int) System::getProperty('upload_image_width');
                $image_hieght = (int) System::getProperty('upload_image_height');
                $file = $request->file('attachment');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $file->move(public_path('img/fleet_logos/' . $business_id), $filename);
                $uploadFile = 'img/fleet_logos/' . $business_id . '/' . $filename;
                $data['logo'] = $uploadFile;
            }

            
            $statement_logo = VatStatementLogo::where('business_id', $business_id)->findOrFail($id);
            $statement_logo->update($data);
            if (!Schema::hasColumn('vat_statement_logos', 'statement_date') && !empty($selected_statement_date)) {
                $statement_logo->created_at = Carbon::parse($selected_statement_date)->startOfDay();
                $statement_logo->save();
            }

            $output = [
                'success' => true,
                'tab' => 'fleet_logos',
                'msg' => __('lang_v1.success')
            ];
            
            
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'fleet_logos',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Resolve a stored logo path into a browser-safe URL.
     * Handles paths saved as public relative paths, /public paths and storage URLs.
     */
    protected function resolveLogoUrl($logoPath)
    {
        if (empty($logoPath)) {
            return null;
        }

        $logoPath = trim($logoPath);
        if (preg_match('/^https?:\/\//i', $logoPath)) {
            return $logoPath;
        }

        $cleanPath = ltrim(str_replace('\\', '/', $logoPath), '/');
        $candidates = [
            public_path($cleanPath),
            public_path('storage/' . $cleanPath),
            storage_path('app/public/' . $cleanPath),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                if (strpos($candidate, public_path()) === 0) {
                    return asset(str_replace(public_path() . DIRECTORY_SEPARATOR, '', $candidate));
                }
                return asset('storage/' . $cleanPath);
            }
        }

        return asset($cleanPath);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            VatStatementLogo::where('id', $id)->delete();

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
