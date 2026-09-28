<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Validator;
use App\CustomerStatementLogo;
use Modules\Fleet\Entities\RouteOperation;
use Yajra\DataTables\Facades\DataTables;
use App\System;
use Intervention\Image\Facades\Image;

class CustomerStatementLogoController extends Controller
{
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

            $drivers = CustomerStatementLogo::leftjoin('users','users.id','customer_statement_logos.created_by')->where('customer_statement_logos.business_id',$business_id)->select(['users.username','customer_statement_logos.*']);
            
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
                        $html .= '<li><a href="#" data-href="' . action('CustomerStatementLogoController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                        $html .= '<li><a href="#" data-href="' . action('CustomerStatementLogoController@destroy', [$row->id]) . '" class="delete_button"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                        
                        
                        return $html;
                    }
                )
                ->editColumn('logo', function ($row) {
                    $action = '';
                    

                    if (!empty($row->logo)) {
                        if (strpos($row->logo, 'jpg') || strpos($row->logo, 'jpeg') || strpos($row->logo, 'png')) {
                            $action = '<a href="#"
                            data-href="' . action("AccountController@imageModal", ["title" => "View", "url" => url($row->logo)]) . '"
                            class="btn-modal btn-xs btn btn-primary"
                            data-container=".view_modal">' . __("messages.view") . '</a>';
                        }
                    }
                    return $action;
                })
                ->editColumn('text_position', function ($row) {
                   
                    return __('vat::lang.'.$row->text_position);
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
        

        return view('customer_statement.logos.create')->with(compact(
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
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

        $validator = Validator::make($request->all(), [
            'image_name' => 'required|string|max:191',
            'alignment' => 'required|string|max:50',
            'text_position' => 'required|string|max:50',
            'attachment' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => false,
                'msg' => $validator->errors()->first(),
            ];

            return $request->ajax() ? response()->json($output) : redirect()->back()->with('status', $output)->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only([
                'image_name',
                'alignment',
                'statement_note',
                'text_position',
            ]);

            $data['business_id'] = $business_id;
            $data['created_by'] = Auth::id();
            $data['business_name'] = $request->has('business_name') ? 1 : 0;
            $data['business_address'] = $request->has('business_address') ? 1 : 0;
            $data['contact_no'] = $request->has('contact_no') ? 1 : 0;
            $data['email'] = $request->has('email') ? 1 : 0;
            $data['mobile_no'] = $request->has('mobile_no') ? 1 : 0;

            if ($request->hasFile('attachment')) {
                $upload_path = public_path('img/customer_statement_logos/' . $business_id);
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }

                $file = $request->file('attachment');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($upload_path, $filename);
                $data['logo'] = 'img/customer_statement_logos/' . $business_id . '/' . $filename;
            }

            CustomerStatementLogo::create($data);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $request->ajax() ? response()->json($output) : redirect()->back()->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $business_id = request()->session()->get('business.id');
        $driver = CustomerStatementLogo::find($id);
        
        return view('customer_statement.logos.show')->with(compact(
            'driver'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');
        $driver = CustomerStatementLogo::where('business_id', $business_id)->findOrFail($id);

        return view('customer_statement.logos.edit')->with(compact(
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
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

        $validator = Validator::make($request->all(), [
            'image_name' => 'required|string|max:191',
            'alignment' => 'required|string|max:50',
            'text_position' => 'required|string|max:50',
            'attachment' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => false,
                'msg' => $validator->errors()->first(),
            ];

            return $request->ajax() ? response()->json($output) : redirect()->back()->with('status', $output)->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only([
                'image_name',
                'alignment',
                'statement_note',
                'text_position',
            ]);

            $data['business_name'] = $request->has('business_name') ? 1 : 0;
            $data['business_address'] = $request->has('business_address') ? 1 : 0;
            $data['contact_no'] = $request->has('contact_no') ? 1 : 0;
            $data['email'] = $request->has('email') ? 1 : 0;
            $data['mobile_no'] = $request->has('mobile_no') ? 1 : 0;

            if ($request->hasFile('attachment')) {
                $upload_path = public_path('img/customer_statement_logos/' . $business_id);
                if (!file_exists($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }

                $file = $request->file('attachment');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($upload_path, $filename);
                $data['logo'] = 'img/customer_statement_logos/' . $business_id . '/' . $filename;
            }

            CustomerStatementLogo::where('business_id', $business_id)->where('id', $id)->update($data);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $request->ajax() ? response()->json($output) : redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');
            CustomerStatementLogo::where('business_id', $business_id)->where('id', $id)->delete();

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

        return request()->ajax() ? response()->json($output) : $output;
    }

}
