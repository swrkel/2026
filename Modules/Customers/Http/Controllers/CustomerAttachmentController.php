<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Customers\Services\CustomerActivityService;
use Modules\Customers\Services\CustomerService;

class CustomerAttachmentController extends Controller
{
    protected $customerService;
    protected $activityService;

    public function __construct(CustomerService $customerService, CustomerActivityService $activityService)
    {
        $this->customerService = $customerService;
        $this->activityService = $activityService;
    }

    public function store($id, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');

        abort_if(!Schema::hasTable('customer_attachments'), 404);

        $customer = $this->customerService->customerQuery($businessId)->where('id', $id)->first();
        abort_if(empty($customer), 404);

        Validator::make($request->all(), [
            'title' => 'nullable|string|max:191',
            'attachment' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt',
        ])->validate();

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = 'customer_' . $id . '_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $relativeDirectory = 'uploads/customers/' . $businessId . '/' . $id;
        $absoluteDirectory = public_path($relativeDirectory);

        if (!File::exists($absoluteDirectory)) {
            File::makeDirectory($absoluteDirectory, 0755, true);
        }

        $file->move($absoluteDirectory, $safeName);

        $branchColumn = $this->customerService->branchColumn();
        $locationId = $branchColumn ? data_get($customer, $branchColumn) : null;

        DB::table('customer_attachments')->insert([
            'business_id' => $businessId,
            'customer_id' => $id,
            'location_id' => $locationId,
            'title' => $request->input('title'),
            'file_name' => $originalName,
            'file_path' => $relativeDirectory . '/' . $safeName,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->activityService->log(
            $businessId,
            $id,
            'attachment_added',
            'Customer attachment added: ' . $originalName,
            $userId,
            $locationId,
            [],
            ['file_name' => $originalName]
        );

        return redirect()->route('customers.show', $id)->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.attachment_uploaded_successfully')
        ]);
    }

    public function download($id, $attachmentId, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');

        abort_if(!Schema::hasTable('customer_attachments'), 404);

        $customer = $this->customerService->customerQuery($businessId)->where('id', $id)->first();
        abort_if(empty($customer), 404);

        $attachment = DB::table('customer_attachments')
            ->where('business_id', $businessId)
            ->where('customer_id', $id)
            ->where('id', $attachmentId)
            ->first();

        abort_if(empty($attachment), 404);

        $path = public_path($attachment->file_path);
        abort_if(!File::exists($path), 404);

        return Response::download($path, $attachment->file_name);
    }

    public function destroy($id, $attachmentId, Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $userId = $request->session()->get('user.id');

        abort_if(!Schema::hasTable('customer_attachments'), 404);

        $customer = $this->customerService->customerQuery($businessId)->where('id', $id)->first();
        abort_if(empty($customer), 404);

        $attachment = DB::table('customer_attachments')
            ->where('business_id', $businessId)
            ->where('customer_id', $id)
            ->where('id', $attachmentId)
            ->first();

        abort_if(empty($attachment), 404);

        $path = public_path($attachment->file_path);
        if (File::exists($path)) {
            File::delete($path);
        }

        DB::table('customer_attachments')
            ->where('business_id', $businessId)
            ->where('customer_id', $id)
            ->where('id', $attachmentId)
            ->delete();

        $branchColumn = $this->customerService->branchColumn();
        $locationId = $branchColumn ? data_get($customer, $branchColumn) : null;

        $this->activityService->log(
            $businessId,
            $id,
            'attachment_deleted',
            'Customer attachment deleted: ' . $attachment->file_name,
            $userId,
            $locationId,
            (array) $attachment,
            []
        );

        return redirect()->route('customers.show', $id)->with('status', [
            'success' => 1,
            'msg' => __('customers::lang.attachment_deleted_successfully')
        ]);
    }
}
