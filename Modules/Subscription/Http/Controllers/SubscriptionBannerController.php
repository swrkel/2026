<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Subscription\Entities\SubscriptionBanner;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionBannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $userId = auth()->id();

            $banners = SubscriptionBanner::with('user')
                ->select('subscription_banners.*')
                // Only banners created by current user
                ->when(!auth()->user()->can('superadmin'), function ($query) use ($userId) {
                    $query->where('created_by', $userId);
                });

            return DataTables::of($banners)
                ->addColumn('action', function ($row) {
                    $action = '';

                    $action .= '<button type="button" class="btn btn-xs btn-primary edit-banner"
                    data-href="' . action([self::class, 'edit'], $row->id) . '"
                    title="' . __('messages.edit') . '">
                    <i class="fa fa-edit"></i>
                </button>';

                    $action .= '&nbsp;<button type="button" class="btn btn-xs btn-danger delete-banner"
                    data-href="' . action([self::class, 'destroy'], $row->id) . '"
                    title="' . __('messages.delete') . '">
                    <i class="fa fa-trash"></i>
                </button>';

                    return $action;
                })
                ->addColumn('logo', function ($row) {
                    if ($this->isImage($row->file_path)) {
                        $tenant = session('tenant.name', 'tenant1');
                        $url    = route('tenant.storage', ['tenant' => $tenant, 'path' => $row->file_path]);

                        return '<img src="' . $url . '" class="img-thumbnail" style="max-height:50px;">';
                    }
                })
                ->addColumn('width', fn($row) => $row->width ? $row->width . ' px' : '-')
                ->addColumn('height', fn($row) => $row->height ? $row->height . ' px' : '-')
                ->editColumn('created_by', fn($row) => $row->user ? $row->user->username : __('messages.not_available'))
                ->editColumn('date', fn($row) => !empty($row->created_at) ? $row->created_at->format('Y-m-d H:i:s') : '')
                ->rawColumns(['action', 'logo'])
                ->make(true);
        }

        return view('subscription::settings.tabs.banners');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('subscription::settings.modals.banner_form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        try {
            $request->validate([
                'file_path' => 'required|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:5120', // 5MB max
            ]);

            // Upload file
            $path = $request->file('file_path')->store('subscription/banners', 'public');

            // Create banner record
            SubscriptionBanner::create([
                'business_id' => $business_id,
                'file_path'   => $path,
                'width'       => $request->banner_width,
                'height'      => $request->banner_height,
                'created_by'  => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'msg'     => __('messages.saved_successfully'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'msg'     => __('messages.validation_error'),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Banner upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => __('messages.something_went_wrong') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $banner = SubscriptionBanner::findOrFail($id);

            // Check if user has permission to edit
            if (auth()->id() != $banner->created_by && ! auth()->user()->can('superadmin')) {
                abort(403, 'Unauthorized action.');
            }

            return view('subscription::settings.modals.banner_form', compact('banner'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, __('messages.not_found'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'file_path' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx|max:5120',
            ]);

            $banner = SubscriptionBanner::findOrFail($id);

            // Check if user has permission to update
            if (auth()->id() != $banner->created_by && ! auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.unauthorized_action'),
                ], 403);
            }

            $data = [];

            // Update file if provided
            if ($request->hasFile('file_path')) {
                // Delete old file
                if (Storage::disk('public')->exists(str_replace('storage/', '', $banner->file_path))) {
                    Storage::disk('public')->delete(str_replace('storage/', '', $banner->file_path));
                }

                // Upload new file
                $path              = $request->file('file_path')->store('subscription/banners', 'public');
                $data['file_path'] = $path;
            }

            // Update banner
            $banner->update($data);

            return response()->json([
                'success' => true,
                'msg'     => __('messages.updated_successfully'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'msg'     => __('messages.validation_error'),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'msg'     => __('messages.not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Banner update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $banner = SubscriptionBanner::findOrFail($id);

            // Check if user has permission to delete
            if (auth()->id() != $banner->created_by && ! auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.unauthorized_action'),
                ], 403);
            }

            if (Storage::disk('public')->exists($banner->file_path)) {
                Storage::disk('public')->delete($banner->file_path);
            }

            // Delete record from database
            $banner->delete();

            return response()->json([
                'success' => true,
                'msg'     => __('messages.deleted_successfully'),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'msg'     => __('messages.not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Banner delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Check if file is an image
     */
    private function isImage($filePath)
    {
        $extension       = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

        return in_array($extension, $imageExtensions);
    }

    /**
     * Serve tenant-specific banner files dynamically
     *
     * @param string $tenant
     * @param string $path
     * @return \Illuminate\Http\Response
     */
    public function serveTenantFile($tenant, $path)
    {

        $fullPath = storage_path("app/public/" . ltrim($path, '/'));

        Log::info('full path of the image: ' . $fullPath);

        if (!file_exists($fullPath)) {
            abort(404, "File not found: " . $fullPath);
        }

        return response()->file($fullPath);
    }
}
