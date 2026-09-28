<?php

namespace Modules\Membership\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Entities\MembershipSignature;
use Modules\Membership\Services\AuthorizedSignatureService;
use Yajra\DataTables\Facades\DataTables;

class AuthorizedSignatureController extends Controller
{
    protected AuthorizedSignatureService $service;

    public function __construct(AuthorizedSignatureService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('membership::settings.authorized_signature.create');
    }

    public function index(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $businessId = (int) request()->session()->get('user.business_id');
        $query = $this->service->datatableQuery($businessId);

        return DataTables::of($query)
            ->addColumn('date_time', function (MembershipSignature $row) {
                return optional($row->created_at)->format('Y-m-d H:i');
            })
            ->addColumn('status', function (MembershipSignature $row) {
                return $row->is_active
                    ? '<span class="label label-success">' . __('membership::lang.active') . '</span>'
                    : '<span class="label label-danger">' . __('membership::lang.inactive') . '</span>';
            })
            ->addColumn('signature_preview', function (MembershipSignature $row) {
                return $this->service->previewHtml($row->signature_path);
            })
            ->addColumn('added_by', function (MembershipSignature $row) {
                if (! $row->createdBy) {
                    return '-';
                }

                $name = trim(($row->createdBy->first_name ?? '') . ' ' . ($row->createdBy->last_name ?? ''));
                return $name !== '' ? $name : ($row->createdBy->username ?? '-');
            })
            ->addColumn('action', function (MembershipSignature $row) {
                return view('membership::settings.authorized_signature.partials.actions', compact('row'))->render();
            })
            ->rawColumns(['action', 'signature_preview', 'status'])
            ->make(true);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'signature' => 'required|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,tif,pdf,doc,docx|max:2048',
            ]);

            $this->service->create(
                (int) request()->session()->get('user.business_id'),
                (int) auth()->id(),
                $request->file('signature')
            );

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully'),
            ]);
        } catch (\Throwable $e) {
            Log::emergency('Membership authorized signature store failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function show(int $id)
    {
        $signature = $this->service->findForBusiness($id, (int) request()->session()->get('user.business_id'));
        $signature->load('createdBy:id,username,first_name,last_name');

        return view('membership::settings.authorized_signature.view', compact('signature'));
    }

    public function edit(int $id)
    {
        $signature = $this->service->findForBusiness($id, (int) request()->session()->get('user.business_id'));

        return view('membership::settings.authorized_signature.edit', compact('signature'));
    }

    public function update(Request $request, int $id)
    {
        try {
            $request->validate([
                'signature' => 'required|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,tif,pdf,doc,docx|max:2048',
            ]);

            $signature = $this->service->findForBusiness($id, (int) request()->session()->get('user.business_id'));
            $this->service->update($signature, $request->file('signature'));

            return response()->json([
                'success' => true,
                'msg' => __('messages.updated_successfully'),
            ]);
        } catch (\Throwable $e) {
            Log::emergency('Membership authorized signature update failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $signature = $this->service->findForBusiness($id, (int) request()->session()->get('user.business_id'));
            $this->service->delete($signature);

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_success'),
            ]);
        } catch (\Throwable $e) {
            Log::emergency('Membership authorized signature delete failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}
