<?php

namespace Modules\Chequer\Http\Controllers\Chequer;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Chequer\Entities\Chequer\ChequeTemplate;
use Modules\Chequer\Entities\System;
use Modules\Chequer\Entities\Account;
use Modules\Chequer\Entities\User;
use Yajra\DataTables\Facades\DataTables;
use Modules\Chequer\Entities\Chequer\ChequerBankAccount;
use Modules\Chequer\Utils\ChequerModuleUtil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class ChequeTemplateController extends Controller
{
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ChequerModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');

        if (request()->ajax()) {
            try {
                if (!$business_id) {
                    return response()->json([
                        'draw' => intval(request()->get('draw')),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => []
                    ]);
                }

                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                }

                $templates = ChequeTemplate::query()
                    ->leftJoin('users', 'cheque_templates.created_by', '=', 'users.id')
                    ->where('cheque_templates.business_id', $business_id)
                    ->select('cheque_templates.*', 'users.username as creator_username')
                    ->with(['chequer_bank_accounts.account']);

                return Datatables::of($templates)
                    ->addColumn('username', function ($row) {
                        return $row->creator_username ?: '-';
                    })
                    ->addColumn('account_name', function ($row) {
                        $account_names = [];

                        foreach ($row->chequer_bank_accounts as $chequer_bank_account) {
                            if (!empty($chequer_bank_account->account) && !empty($chequer_bank_account->account->name)) {
                                $account_names[] = $chequer_bank_account->account->name;
                            }
                        }

                        return !empty($account_names) ? implode(', ', array_unique($account_names)) : '-';
                    })
                    ->addColumn('action', function ($row) {
                        $add_account_url = url('get-templates-link-bank', [$row->id]);
                        $edit_url = action('Chequer\ChequeTemplateController@edit', [$row->id]);
                        $delete_url = action('Chequer\ChequeTemplateController@destroy', [$row->id]);

                        return '<div class="btn-group cheque-template-actions">'
                            . '<button type="button" class="btn btn-primary btn-sm dropdown-toggle cheque-action-parent" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-cog"></i> Action <span class="caret"></span></button>'
                            . '<ul class="dropdown-menu dropdown-menu-right cheque-action-menu" role="menu">'
                            . '<li><a href="javascript:void(0);" data-href="' . $add_account_url . '" class="link_bank cheque-action-child add-account" data-container=".template_link_bank_model"><i class="fa fa-plus"></i> Add Account</a></li>'
                            . '<li><a href="' . $edit_url . '" class="cheque-action-child edit-template"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>'
                            . '<li><a href="javascript:void(0);" data-href="' . $delete_url . '" class="delete_employee cheque-action-child delete-template"><i class="glyphicon glyphicon-trash"></i> Delete</a></li>'
                            . '</ul></div>';
                    })
                    ->editColumn('created_date', function ($row) {
                        return !empty($row->created_date) ? date('Y-m-d', strtotime($row->created_date)) : '-';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::emergency('Cheque template list failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

                return response()->json([
                    'draw' => intval(request()->get('draw')),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Unable to load cheque templates. Please check laravel.log for details.'
                ], 200);
            }
        }

        return view('chequer/templates/index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $templates = ChequeTemplate::where('business_id', $business_id)->get();
        return view('chequer/templates/create')->with(compact('templates'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id') ?: $request->session()->get('business.id');
            $temp_id = $request->temp_id;
            $data['business_id'] = $business_id;
            $data['template_name'] = $request->template_name;
            $data['template_size'] = $request->template_size;
            $data['seprator'] = $request->seprator;
            $data['words2'] = $request->words2;
            $data['words3'] = $request->words3;
            $data['pay_name'] = $request->pay_name;
            $data['date_pos'] = $request->date_pos;
            $data['date_format'] = $request->date_format;
            $data['amount'] = $request->amount;
            $data['amount_in_w1'] = $request->amount_in_words1;
            $data['amount_in_w2'] = $request->amount_in_words2;
            $data['amount_in_w3'] = $request->amount_in_words3;
            $data['template_cross'] = $request->cross;
            $data['pay_only'] = $request->pay_only;
            $data['not_negotiable'] = $request->negotiable;
            $data['signature_stamp'] = $request->signature_stamp;
            $data['is_dublecross'] = $request->is_dublecross;
            $data['dublecross'] = $request->dublecross;
            $data['strikeBearer'] = $request->strikeBearer;
            $data['is_strikeBearer'] = $request->is_strikeBearer;
            $data['is_stamp'] = $request->is_stamp;
            $data['d1'] = $request->d1;
            $data['d2'] = $request->d2;
            $data['m1'] = $request->m1;
            $data['m2'] = $request->m2;
            $data['y1'] = $request->y1;
            $data['y2'] = $request->y2;
            $data['y3'] = $request->y3;
            $data['y4'] = $request->y4;
            $data['ds1'] = $request->ds1;
            $data['ds2'] = $request->ds2;
            $data['signature_stamp_area'] = $request->signature_stamp_area;
            $data['created_by'] = Auth::user()->id;
            $data['created_date'] = date('Y-m-d H:i:s', time());
            if ($temp_id) {
                if ($request->image_url) {
                    $data['image_url'] = $request->image_url;
                }
                ChequeTemplate::where('id', $temp_id)->update($data);
            } else {
                $data['image_url'] = $request->image_url;
                ChequeTemplate::create($data);
            }

            $output = [
                'success' => 1,
                'msg' => __('chequer::cheque.template_add_succuss')
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function linkbank($id)
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $temp_id = $id;
        $get_bankacount = Account::where('business_id', $business_id)
            ->where('is_need_cheque', 'Y')
            ->where('is_closed', 0)
            ->orderBy('name')
            ->get();
        $getLinkBankAcounts = ChequerBankAccount::with('account')->where('is_visible', 1)->where('cheque_templete_id', $temp_id)->get();
        return view('chequer/templates/linkbank')->with(compact(
            'get_bankacount','temp_id','getLinkBankAcounts'
        ));;
    }
    public function addbank(Request $request)
    {
        if (request()->ajax()) {
             try {
            $data=[];
            $data['business_id']=request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
            $data['account_id'] = (int) $request->accountId;
            $data['is_visible'] = 1;
            $data['cheque_templete_id'] = (int) $request->tempId;
            $data['cheque_temp_regdate'] = $request->regDate ?: date('Y-m-d');

            $account = Account::where('business_id', $data['business_id'])
                ->where('is_need_cheque', 'Y')
                ->where('is_closed', 0)
                ->find($data['account_id']);
            if (empty($account)) {
                return response('Invalid account or account is not enabled for Chequer Module.', 422);
            }

            $record = ChequerBankAccount::firstOrNew([
                'business_id' => $data['business_id'],
                'cheque_templete_id' => $data['cheque_templete_id'],
                'account_id' => $data['account_id'],
            ]);
            $record->is_visible = 1;
            $record->cheque_temp_regdate = $data['cheque_temp_regdate'];
            $record->save();
            $getLinkBankAcounts = ChequerBankAccount::where('is_visible', 1)->where('cheque_templete_id', $data['cheque_templete_id'])->get();
            $tbodyHtml = '';
            $index=1;
            foreach($getLinkBankAcounts as $row)
            {
                $tbodyHtml.="<tr>";
                $tbodyHtml.="<td>".$index."</td>";
                $tbodyHtml.="<td>".$row->cheque_temp_regdate."</td>";
                $tbodyHtml.="<td>".(!empty($row->account) ? e($row->account->name) : '-')."</td>";
                $tbodyHtml.="<td>";
                $tbodyHtml.="<button data-url=".url('get-templates-delete-bank', [$row->id])." class='btn btn-sm btn-danger delLinkBank'><i class='fa fa-trash'></button>";
                $tbodyHtml.="</td>";
                $tbodyHtml.="</tr>";
                $index++;
            }
            echo $tbodyHtml;
             } catch (\Exception $e) {
                 Log::info('Request Data:', $request->all());
                 Log::info('Business ID:', ['business_id' => request()->session()->get('business.id')]);
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
        }
        }
    }
    public function delbank($id)
    {
        $chequeBankAccount  = ChequerBankAccount::find($id);
        try {
            ChequerBankAccount::where('id', $id)->delete();
            $getLinkBankAcounts = ChequerBankAccount::where('is_visible', 1)->where('cheque_templete_id', $chequeBankAccount->cheque_templete_id)->get();
            $tbodyHtml = '';
            $index=1;
            foreach($getLinkBankAcounts as $row)
            {
                $tbodyHtml.="<tr>";
                $tbodyHtml.="<td>".$index."</td>";
                $tbodyHtml.="<td>".$row->cheque_temp_regdate."</td>";
                $tbodyHtml.="<td>".(!empty($row->account) ? e($row->account->name) : '-')."</td>";
                $tbodyHtml.="<td>";
                $tbodyHtml.="<button data-url=".url('get-templates-delete-bank', [$row->id])." class='btn btn-sm btn-danger delLinkBank'><i class='fa fa-trash'></button>";
                $tbodyHtml.="</td>";
                $tbodyHtml.="</tr>";
                $index++;
            }
            return $tbodyHtml;
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return "File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage();
        }
        
        
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
        $templates = ChequeTemplate::where('business_id', $business_id)->get();
        return view('chequer/templates/create')->with(compact('templates', 'id'));
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
        //to update template store method is use
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id') ?: request()->session()->get('business.id');
            $template = ChequeTemplate::where('business_id', $business_id)->findOrFail($id);
            ChequerBankAccount::where('business_id', $business_id)->where('cheque_templete_id', $id)->delete();
            $template->delete();
            $output = [
                'success' => true,
                'msg' => __("cheque.template_delete_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return  $output;
    }

    public function getTemplateValues()
    {
        $id = request()->id;
        $values = ChequeTemplate::where('id', $id)->first();
        echo json_encode($values);
    }

    public function uploadImageFile(Request $request)
    {

        //upload prfile image
        if (!file_exists('./public/chequer/uploads')) {
            mkdir('./public/chequer/uploads', 0777, true);
        }
        try {
            if ($request->hasfile('userfile')) {

                $validate = Validator::make(
                    $request->all(),
                    [
                        'userfile' => 'mimes:jpeg,png,bmp|max:4096',
                    ]
                );
                $filename = null;

                if ($validate->fails()) {
                    $output = [
                        'success' => 0,
                        'msg' => 'Only jpeg, png, bmp are allowed.'
                    ];

                    return  $output;
                }

                if ($request->hasfile('userfile')) {
                    $file = $request->file('userfile');
                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '.' . $extension;
                    $file->move('public/chequer/uploads', $filename);
                }
            }
            $output = [
                'success' => 1,
                'msg' => __("cheque.file_upload_success"),
                'filename' => $filename
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }
}
