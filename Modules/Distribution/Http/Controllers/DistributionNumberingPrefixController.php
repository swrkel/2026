<?php
namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Distribution\Entities\DistributionNumberingPrefix;
use Illuminate\Support\Arr;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class DistributionNumberingPrefixController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $prefixes = DistributionNumberingPrefix::leftJoin('users', 'distribution_prefix_settings.created_by', '=', 'users.id')
                ->where('distribution_prefix_settings.business_id', $business_id)
                ->select(
                    'distribution_prefix_settings.*',
                    'users.username as added_by_user'
                );

            return DataTables::of($prefixes)
                ->editColumn('numbering_type', function ($row) {
                    $type = strtolower($row->numbering_type ?? '');
                    $labels = [
                        'sales_invoice'       => 'Sales Invoice',
                        'sales_order'         => 'Sales Orders',
                        'daily_summary_sheet' => 'Daily Summary Sheet',
                        'loading_sheet'       => 'Loading Sheet',
                        'loading sheet'       => 'Loading Sheet',
                        'stock_transfer'      => 'Stock Transfer',
                        'vat_distribution_invoice' => 'VAT Distribution Invoice',
                    ];

                    return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
                })
                ->addColumn('action', function ($row) {
                    return '
                    <button class="btn btn-xs btn-primary edit_prefix" data-id="' . $row->id . '">Edit</button>
                    <button class="btn btn-xs btn-danger delete_prefix" data-id="' . $row->id . '">Delete</button>
                ';
                })
                ->editColumn('added_by_user', function ($row) {
                    return $row->added_by_user ?? '—';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('distribution::settings.prefix.index');
    }

    public function create()
    {
        return view('distribution::settings.prefix.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'numbering_type' => 'required|string',
            'prefix' => 'nullable|string|max:10',
            'starting_no' => 'required|numeric|min:0',
        ]);
        try {
            try {
                DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type VARCHAR(191) NOT NULL");
            } catch (\Exception $e) {
                // Ignore SQLite or already modified column errors
            }

            $business_id = request()->session()->get('user.business_id');
            $numberingType = strtolower($request->input('numbering_type'));
            if ($numberingType === 'loading sheet') {
                $numberingType = 'loading_sheet';
            }
            $startingNo = $request->input('starting_no');
            $prefixValue = $request->input('prefix');
            $userId = Auth::id();

            // Use updateOrCreate to respect the unique (business_id, numbering_type) constraint.
            // If a prefix for this type already exists it will be updated, not duplicated.
            DistributionNumberingPrefix::updateOrCreate(
                [
                    'business_id'    => $business_id,
                    'numbering_type' => $numberingType,
                ],
                [
                    'prefix'      => $prefixValue,
                    'starting_no' => $startingNo,
                    'current_no'  => $startingNo,
                    'created_by'  => $userId,
                ]
            );

            $message = 'Prefix saved successfully!';

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'msg' => $message
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Prefix store error: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Error saving prefix setting: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error saving prefix setting.')->withInput();
        }
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        
        $prefix = DistributionNumberingPrefix::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        // Normalize variations of loading sheet to the correct key
        $type = strtolower($prefix->numbering_type);
        if ($type === 'loading sheet' || $type === 'loading_sheet') {
            $prefix->numbering_type = 'loading_sheet';
        }

        return view('distribution::settings.prefix.edit', compact('prefix'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numbering_type' => 'required|string',
            'prefix' => 'nullable|string|max:10',
            'starting_no' => 'required|numeric|min:0',
        ]);

        try {
            try {
                DB::statement("ALTER TABLE distribution_prefix_settings MODIFY COLUMN numbering_type VARCHAR(191) NOT NULL");
            } catch (\Exception $e) {
                // Ignore SQLite or already modified column errors
            }

            $business_id = request()->session()->get('user.business_id');
            $prefix = DistributionNumberingPrefix::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $numberingType = strtolower($request->input('numbering_type'));
            if ($numberingType === 'loading sheet') {
                $numberingType = 'loading_sheet';
            }

            $prefix->numbering_type = $numberingType;
            $prefix->prefix = $request->input('prefix');
            $prefix->starting_no = $request->input('starting_no');
            
            // Only update current_no if it's less than the new starting_no
            if ($prefix->current_no < $request->input('starting_no')) {
                $prefix->current_no = $request->input('starting_no');
            }
            
            $prefix->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'msg' => 'Prefix updated successfully!'
                ]);
            }

            return redirect()->back()->with('success', 'Prefix updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Error: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $prefix = DistributionNumberingPrefix::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            $prefix->delete();

            return response()->json([
                'success' => true,
                'msg' => 'Prefix deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
