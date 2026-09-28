<?php

namespace Modules\Chequer\Http\Controllers\Chequer;

use Modules\Chequer\Entities\Chequer\ChequerStamp;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yajra\DataTables\DataTables;
use Modules\Chequer\Utils\ChequerModuleUtil;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChequerStampController extends Controller
{
    protected $moduleUtil;
    protected $stampTable = 'chequer_stamps';

    public function __construct(ChequerModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    protected function businessId()
    {
        return request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: request()->session()->get('business_id');
    }

    protected function tableReady()
    {
        return Schema::hasTable($this->stampTable);
    }

    protected function hasColumn($column)
    {
        return $this->tableReady() && Schema::hasColumn($this->stampTable, $column);
    }

    public function index()
    {
        $business_id = $this->businessId();

        if (request()->ajax()) {
            if (!$this->tableReady()) {
                return response()->json([
                    'draw' => (int) request('draw'),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Missing table: '.$this->stampTable,
                ]);
            }

            try {
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                }
            } catch (\Throwable $e) {
                // Do not break the DataTable because of subscription helper errors in legacy Cheque Writing.
            }

            $stamps = ChequerStamp::query()->select($this->stampTable.'.*');
            if (!empty($business_id) && $this->hasColumn('business_id')) {
                $stamps->where($this->stampTable.'.business_id', $business_id);
            }
            $stamps->orderBy($this->stampTable.'.id', 'desc');

            return Datatables::of($stamps)
                ->addColumn('action', function ($row) {
                    $editUrl = action('Chequer\\ChequerStampController@edit', [$row->id], false);
                    $deleteUrl = action('Chequer\\ChequerStampController@destroy', [$row->id], false);
                    return '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                        <ul class="dropdown-menu dropdown-menu-right" role="menu">
                            <li><a href="#" data-href="'.$editUrl.'" class="btn-modal" data-container=".edit_modal"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                            <li><a href="#" data-href="'.$deleteUrl.'" class="delete_stamps"><i class="glyphicon glyphicon-trash" style="color:brown;"></i> Delete</a></li>
                        </ul></div>';
                })
                ->editColumn('stamp_image', function ($row) {
                    $path = trim((string)($row->stamp_image ?? ''), '/');
                    $image_url = $path !== '' ? url('/chequer/' . $path) : '';
                    return $image_url ? "<img height='50' width='50' src='" . e($image_url) . "' />" : '';
                })
                ->editColumn('active', function ($row) {
                    return (int)($row->stamp_status ?? 0) === 1 ? 'Yes' : 'No';
                })
                ->editColumn('updated_at', function ($row) {
                    $date = $row->updated_at ?? $row->stamp_entrydt ?? null;
                    return !empty($date) ? date('Y-m-d', strtotime($date)) : '';
                })
                ->rawColumns(['action', 'stamp_image', 'active'])
                ->make(true);
        }

        return view('chequer/stamps/index');
    }

    public function create()
    {
        return view('chequer/stamps/create');
    }

    public function store(Request $request)
    {
        $business_id = $this->businessId();
        try {
            $validate = Validator::make($request->all(), [
                'stamp_name' => 'required',
                'upload_stamp' => 'required|mimes:jpg,jpeg,png,bmp|max:4096',
            ]);

            if ($validate->fails()) {
                return $this->redirectToStamps(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
            }

            if (!$this->tableReady()) {
                return $this->redirectToStamps(['success' => 0, 'msg' => 'Missing table: '.$this->stampTable]);
            }

            $uploadFileFicon = null;
            $dir = public_path('chequer/stamps');
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if ($request->hasFile('upload_stamp')) {
                $file = $request->file('upload_stamp');
                $filename = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName());
                $file->move($dir, $filename);
                $uploadFileFicon = 'stamps/'.$filename;
            }

            $data = [
                'stamp_name' => $request->stamp_name,
                'stamp_image' => $uploadFileFicon,
                'stamp_entrydt' => date('Y-m-d'),
                'stamp_status' => $request->has('active') ? 1 : 0,
            ];
            if ($this->hasColumn('business_id')) { $data['business_id'] = $business_id; }
            if ($this->hasColumn('created_at')) { $data['created_at'] = now(); }
            if ($this->hasColumn('updated_at')) { $data['updated_at'] = now(); }

            DB::table($this->stampTable)->insert($data);

            return $this->redirectToStamps(['success' => 1, 'msg' => __('cheque.stamp_add_success')]);
        } catch (\Exception $e) {
            Log::emergency('Cheque stamp save failed. File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            return $this->redirectToStamps(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function edit($id)
    {
        $stamp = ChequerStamp::where('id', $id)->firstOrFail();
        return view('chequer/stamps/edit')->with(compact('stamp'));
    }

    public function update(Request $request, $id)
    {
        try {
            $validate = Validator::make($request->all(), [
                'stamp_name' => 'required',
                'upload_stamp' => 'nullable|mimes:jpg,jpeg,png,bmp|max:4096',
            ]);
            if ($validate->fails()) {
                return $this->redirectToStamps(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
            }

            $data = [
                'stamp_name' => $request->stamp_name,
                'stamp_status' => $request->has('active') ? 1 : 0,
            ];
            if ($this->hasColumn('updated_at')) { $data['updated_at'] = now(); }

            if ($request->hasFile('upload_stamp')) {
                $dir = public_path('chequer/stamps');
                if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
                $file = $request->file('upload_stamp');
                $filename = time().'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $file->getClientOriginalName());
                $file->move($dir, $filename);
                $data['stamp_image'] = 'stamps/'.$filename;
            }

            ChequerStamp::where('id', $id)->update($data);
            return $this->redirectToStamps(['success' => 1, 'msg' => __('cheque.stamp_add_success')]);
        } catch (\Exception $e) {
            Log::emergency('Cheque stamp update failed. File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            return $this->redirectToStamps(['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function destroy($id)
    {
        try {
            ChequerStamp::where('id', $id)->delete();
            return ['success' => true, 'msg' => __('cheque.stamp_delete_success')];
        } catch (\Exception $e) {
            Log::emergency('Cheque stamp delete failed. File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    protected function redirectToStamps(array $output)
    {
        return redirect(action('Chequer\\ChequerStampController@index', [], false))->with('status', $output);
    }
}
