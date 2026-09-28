<?php

namespace Modules\VehicleService\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\VehicleService\Entities\VehicleServiceJob;
use Modules\VehicleService\Services\VehicleServiceCalculator;

class VehicleServiceController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $jobs = VehicleServiceJob::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($request->filled('vehicle_no'), fn ($q) => $q->where('vehicle_no', 'like', '%' . $request->vehicle_no . '%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25);

        return view('vehicleservice::service_jobs.index', compact('jobs'));
    }

    public function create()
    {
        $job = new VehicleServiceJob([
            'transaction_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        return view('vehicleservice::service_jobs.create', compact('job'));
    }

    public function store(Request $request, VehicleServiceCalculator $calculator)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'vehicle_no' => 'required|string|max:191',
            'lines' => 'required|array|min:1',
        ]);

        $calculated = $calculator->calculateLines($request->input('lines', []));
        if (empty($calculated['lines'])) {
            return back()->withInput()->withErrors(['lines' => 'Please add at least one valid product/service line.']);
        }

        DB::transaction(function () use ($request, $calculated) {
            $job = VehicleServiceJob::create([
                'business_id' => $this->businessId(),
                'location_id' => $this->locationId(),
                'job_no' => $this->nextJobNo(),
                'transaction_date' => $request->transaction_date,
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'customer_mobile' => $request->customer_mobile,
                'vehicle_no' => strtoupper(trim($request->vehicle_no)),
                'vehicle_make' => $request->vehicle_make,
                'vehicle_model' => $request->vehicle_model,
                'meter_reading' => $request->meter_reading,
                'service_notes' => $request->service_notes,
                'subtotal' => $calculated['subtotal'],
                'discount_total' => $calculated['discount_total'],
                'tax_total' => $calculated['tax_total'],
                'total_amount' => $calculated['total_amount'],
                'paid_amount' => $this->num($request->paid_amount),
                'balance_amount' => $calculated['total_amount'] - $this->num($request->paid_amount),
                'payment_type' => $request->payment_type,
                'status' => $request->status ?: 'draft',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            foreach ($calculated['lines'] as $line) {
                $job->lines()->create($line);
            }
        });

        return redirect()->route('vehicleservice.jobs.index')->with('status', ['success' => 1, 'msg' => 'Vehicle service bill saved successfully.']);
    }

    public function show($id)
    {
        $job = VehicleServiceJob::with('lines')->findOrFail($id);
        $this->authorizeBusiness($job);
        return view('vehicleservice::service_jobs.show', compact('job'));
    }

    public function productSearch(Request $request)
    {
        $term = trim((string)$request->get('q', ''));
        $businessId = $this->businessId();

        $products = Product::query()
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->where(function ($q) use ($term) {
                $q->where('products.name', 'like', '%' . $term . '%')
                  ->orWhere('products.sku', 'like', '%' . $term . '%');
            })
            ->when($businessId, fn ($q) => $q->where('products.business_id', $businessId))
            ->where(function ($q) {
                $q->whereNull('products.not_for_selling')->orWhere('products.not_for_selling', 0);
            })
            ->select('products.id as product_id', 'products.name', 'products.sku', 'variations.id as variation_id', 'variations.default_sell_price')
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->product_id,
                    'variation_id' => $p->variation_id,
                    'text' => trim($p->name . (!empty($p->sku) ? ' (' . $p->sku . ')' : '')),
                    'unit_price' => (float)($p->default_sell_price ?? 0),
                ];
            });

        return response()->json(['results' => $products]);
    }

    private function nextJobNo(): string
    {
        $prefix = 'VS-' . now()->format('ymd') . '-';
        $count = VehicleServiceJob::where('job_no', 'like', $prefix . '%')->count() + 1;
        return $prefix . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    private function businessId()
    {
        return session('business.id') ?? session('user.business_id') ?? optional(Auth::user())->business_id;
    }

    private function locationId()
    {
        return session('business_location_id') ?? session('location_id');
    }

    private function num($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }
        return round((float)$value, 4);
    }

    private function authorizeBusiness(VehicleServiceJob $job): void
    {
        $businessId = $this->businessId();
        abort_if($businessId && $job->business_id && (int)$job->business_id !== (int)$businessId, 403);
    }
}
