<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Utils\SupplierContextUtil;

class SupplierUserActivityController extends Controller
{
    public function index(Request $request)
    {
        $businessId = SupplierContextUtil::businessId();
        $activities = collect();

        if (Schema::hasTable('activity_log') && Schema::hasTable('contacts')) {
            $query = DB::table('activity_log')
                ->leftJoin('contacts', function ($join) {
                    $join->on('activity_log.subject_id', '=', 'contacts.id');
                })
                ->where('contacts.business_id', $businessId)
                ->whereIn('contacts.type', ['supplier', 'both'])
                ->where(function ($q) {
                    $q->where('activity_log.subject_type', 'like', '%Contact%')
                      ->orWhere('activity_log.subject_type', 'like', '%Supplier%')
                      ->orWhereNull('activity_log.subject_type');
                });

            if (Schema::hasColumn('contacts', 'deleted_at')) {
                $query->whereNull('contacts.deleted_at');
            }

            $activities = $query->select([
                    'activity_log.id',
                    'activity_log.description',
                    'activity_log.event',
                    'activity_log.created_at',
                    'contacts.contact_id as supplier_code',
                    'contacts.name as supplier_name',
                    'contacts.supplier_business_name',
                ])
                ->orderByDesc('activity_log.created_at')
                ->limit(500)
                ->get();
        }

        return view('suppliers::user_activity.index', compact('activities'));
    }
}
