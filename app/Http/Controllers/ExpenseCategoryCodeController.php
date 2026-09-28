<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountType;
use App\ExpenseCategoryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use App\Contact;
use App\System;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;


class ExpenseCategoryCodeController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                if (!Schema::hasTable('expense_categories_codes')) {
                    return response()->json([
                        'draw' => (int) request()->input('draw', 0),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ], 200);
                }

                $expense_category = ExpenseCategoryCode::leftJoin('users', 'users.id', '=', 'expense_categories_codes.created_by')
                    ->where('expense_categories_codes.business_id', $business_id)
                    ->select([
                        'expense_categories_codes.id',
                        'expense_categories_codes.date',
                        'expense_categories_codes.prefix',
                        'expense_categories_codes.starting_no',
                        'expense_categories_codes.created_at',
                        'users.username as username'
                    ]);

                return Datatables::of($expense_category)
                    ->addColumn(
                        'action',
                        '<button data-href="{{action(\'ExpenseCategoryCodeController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>'
                    )
                    ->editColumn('date', function ($row) {
                        $date = !empty($row->date) ? $row->date : (!empty($row->created_at) ? $row->created_at : null);
                        return !empty($date) ? $this->commonUtil->format_date($date) : '';
                    })
                    ->filterColumn('username', function ($query, $keyword) {
                        $query->where('users.username', 'like', '%' . $keyword . '%');
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Throwable $e) {
                Log::emergency('Expense Category Code DataTable failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

                return response()->json([
                    'draw' => (int) request()->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Expense Settings could not be loaded. Please check the log.'
                ], 200);
            }
        }

        $business_id = request()->session()->get('user.business_id');
        $show_payment_note_in_print = (int) System::getProperty('expense_show_payment_note_in_print_' . $business_id) === 1;
        $show_expense_note_in_print = (int) System::getProperty('expense_show_expense_note_in_print_' . $business_id) === 1;

        return view('expense_category_code.index', compact('show_payment_note_in_print', 'show_expense_note_in_print'));
    }

    public function saveNoteSettings(Request $request)
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $show_payment_note_in_print = $request->has('show_payment_note_in_print') ? 1 : 0;
            $show_expense_note_in_print = $request->has('show_expense_note_in_print') ? 1 : 0;

            System::updateOrCreate(
                ['key' => 'expense_show_payment_note_in_print_' . $business_id],
                ['value' => $show_payment_note_in_print]
            );
            System::updateOrCreate(
                ['key' => 'expense_show_expense_note_in_print_' . $business_id],
                ['value' => $show_expense_note_in_print]
            );

            $output = [
                'success' => true,
                'msg' => __('expense.updated_success'),
                'tab' => 'note_settings',
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
                'tab' => 'note_settings',
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        // S277: this page is opened in a modal from Expense Settings > Add.
        // Return only the modal body view and do not redirect, so the shared .btn-modal handler can display it.
        return view('expense_category_code.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }


        try {

            $input = $request->only(['prefix', 'starting_no']);
            $input['date'] = date('Y-m-d H:i');
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = auth()->user()->id;


            $expense_category = ExpenseCategoryCode::updateOrCreate(['business_id' => $input['business_id'] ],$input);
            $output = [
                'success' => true,
                'msg' => __("expense.added_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $id = (int) $id;
        abort(404, "Expense category code show is not implemented for ID: {$id}");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $expense_category_code = ExpenseCategoryCode::findOrFail($id);
            return view('expense_category_code.edit',compact('expense_category_code'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

         try {
                $input = $request->only(['prefix', 'starting_no']);
                $business_id = $request->session()->get('user.business_id');

                $expense_category = ExpenseCategoryCode::where('business_id', $business_id)->findOrFail($id);
                $expense_category->prefix = $input['prefix'];
                $expense_category->starting_no = $input['starting_no'];
                $expense_category->save();

                $output = [
                    'success' => true,
                    'msg' => __("expense.updated_success")
                ];
            } catch (\Exception $e) {
                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return Redirect::back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Gate::forUser(auth()->user())->check('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
                $business_id = request()->session()->get('user.business_id');

                $expense_category = ExpenseCategoryCode::where('business_id', $business_id)->findOrFail($id);
                $expense_category->delete();

                $output = [
                    'success' => true,
                    'msg' => __("expense.deleted_success")
                ];
            } catch (\Exception $e) {
                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return Redirect::back()->with('status', $output);
    }
}

