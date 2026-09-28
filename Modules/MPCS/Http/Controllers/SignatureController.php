<?php

namespace Modules\MPCS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsSignature;
use App\User;
use Modules\MPCS\Entities\HrmDesignation;
use App\Business;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class SignatureController extends Controller
{
    /**
     * Display a listing of signatures.
     */
    public function list(Request $request)
    {
        try {
            if ($request->ajax()) {
                $business_id = request()->session()->get('user.business_id');
                
                // Simple test query first
                \Log::info('Business ID: ' . $business_id);
                \Log::info('Testing signature query...');
                
                $signatures = MpcsSignature::where('mpcs_signatures.business_id', $business_id)
                    ->leftJoin('users', 'mpcs_signatures.user_id', '=', 'users.id')
                    ->leftJoin('hrm_designations', 'mpcs_signatures.designation_id', '=', 'hrm_designations.id')
                    ->select(
                        'mpcs_signatures.id',
                        'mpcs_signatures.signature_datetime',
                        'mpcs_signatures.signature_path',
                        'mpcs_signatures.signature_type',
                        'mpcs_signatures.created_at',
                        'users.first_name',
                        'users.last_name',
                        'users.username',
                        'hrm_designations.name as designation_name'
                    )
                    ->orderBy('mpcs_signatures.created_at', 'desc');

                \Log::info('Query built successfully');

                return Datatables::of($signatures)
                    ->addColumn('datetime', function ($row) {
                        try {
                            if ($row->signature_datetime) {
                                // Handle both string and Carbon instances
                                if (is_string($row->signature_datetime)) {
                                    // If it's a string like "0000-00-00 00:00:00", use created_at instead
                                    if ($row->signature_datetime === '0000-00-00 00:00:00' || $row->signature_datetime === '0000-00-00 00:00:00.000000') {
                                        return $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '';
                                    }
                                    return $row->signature_datetime;
                                } elseif (method_exists($row->signature_datetime, 'format')) {
                                    return $row->signature_datetime->format('Y-m-d H:i:s');
                                }
                                return $row->signature_datetime;
                            }
                            return '';
                        } catch (\Exception $e) {
                            \Log::error('DateTime formatting error: ' . $e->getMessage());
                            return $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '';
                        }
                    })
                    ->addColumn('user_name', function ($row) {
                        return ($row->first_name ?? '') . ' ' . ($row->last_name ?? '') . ' (' . ($row->username ?? '') . ')';
                    })
                    ->addColumn('signature_preview', function ($row) {
                        if ($row->signature_path) {
                            return asset('uploads/mpcs/signatures/' . $row->signature_path);
                        }
                        return null;
                    })
                    ->addColumn('signature_type', function ($row) {
                        return $row->signature_type ?? '';
                    })
                    ->make(true);
            }
        } catch (\Exception $e) {
            \Log::error('Signature list error: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            if ($request->ajax()) {
                return response()->json([
                    'error' => 'Error loading signatures: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        }
    }

    /**
     * Store a newly uploaded signature.
     */
    public function upload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'designation_id' => 'required|exists:hrm_designations,id',
                'signature_file' => 'required|file|mimes:pdf,jpeg,jpg,png|max:5120',
                'datetime' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ]);
            }

            $business_id = request()->session()->get('user.business_id');
            $file = $request->file('signature_file');
            
            // Create uploads directory if it doesn't exist
            $uploadPath = public_path('uploads/mpcs/signatures');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Move file to public directory
            $file->move($uploadPath, $filename);

            // Get file type
            $fileType = $file->getClientOriginalExtension();
            if ($fileType === 'jpg' || $fileType === 'jpeg') {
                $fileType = 'jpeg';
            } elseif ($fileType === 'png') {
                $fileType = 'png';
            } else {
                $fileType = 'pdf';
            }

            // Save signature record
            $signature = MpcsSignature::create([
                'business_id' => $business_id,
                'user_id' => $request->user_id,
                'designation_id' => $request->designation_id,
                'signature_path' => $filename,
                'signature_type' => $fileType,
                'signature_datetime' => now(), // Use current timestamp instead of request datetime
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Signature uploaded successfully!',
                'data' => $signature
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading signature: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified signature.
     */
    public function delete($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            
            $signature = MpcsSignature::where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (!$signature) {
                return response()->json([
                    'success' => false,
                    'message' => 'Signature not found'
                ]);
            }

            // Delete file from storage
            if ($signature->signature_path) {
                $filePath = public_path('uploads/mpcs/signatures/' . $signature->signature_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Delete database record
            $signature->delete();

            return response()->json([
                'success' => true,
                'message' => 'Signature deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting signature: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get users for dropdown.
     */
    public function getUsers(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            
            $users = User::where('business_id', $business_id)
                ->where('is_customer', false)
                ->where('is_cmmsn_agnt', false)
                ->where('is_pump_operator', false)
                ->where('is_property_user', false)
                ->select('id', 'first_name', 'last_name', 'username', 'designation')
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching users: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get designations for dropdown.
     */
    public function getDesignations(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            
            $designations = HrmDesignation::where('hrm_designations.business_id', $business_id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $designations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching designations: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get user designation.
     */
    public function getUserDesignation(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $business_id = request()->session()->get('user.business_id');
            
            $user = User::where('business_id', $business_id)
                ->where('id', $request->user_id)
                ->select('id', 'first_name', 'last_name', 'designation')
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ]);
            }

            $designation = null;
            if ($user->designation) {
                // Check if this is a designation ID or a text designation
                if (is_numeric($user->designation)) {
                    // If it's numeric, get from hrm_designations table
                    $designationRecord = HrmDesignation::where('business_id', $business_id)
                        ->where('id', $user->designation)
                        ->first();
                    
                    if ($designationRecord) {
                        $designation = [
                            'id' => $designationRecord->id,
                            'name' => $designationRecord->name
                        ];
                    }
                } else {
                    // If it's text, use it directly and find/create matching designation
                    $designationRecord = HrmDesignation::where('business_id', $business_id)
                        ->where('name', $user->designation)
                        ->first();
                    
                    if ($designationRecord) {
                        $designation = [
                            'id' => $designationRecord->id,
                            'name' => $designationRecord->name
                        ];
                    } else {
                        // Create new designation if it doesn't exist
                        $newDesignation = HrmDesignation::create([
                            'business_id' => $business_id,
                            'name' => $user->designation,
                            'created_by' => auth()->user()->id
                        ]);
                        
                        $designation = [
                            'id' => $newDesignation->id,
                            'name' => $newDesignation->name
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $designation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user designation: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add new user.
     */
    public function addUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|max:255|unique:users,username',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ]);
            }

            $business_id = request()->session()->get('user.business_id');

            $user = User::create([
                'business_id' => $business_id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User added successfully!',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Add new designation.
     */
    public function addDesignation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validator->errors()->all())
                ]);
            }

            $business_id = request()->session()->get('user.business_id');

            $designation = HrmDesignation::create([
                'business_id' => $business_id,
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Designation added successfully!',
                'data' => $designation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding designation: ' . $e->getMessage()
            ]);
        }
    }
}
