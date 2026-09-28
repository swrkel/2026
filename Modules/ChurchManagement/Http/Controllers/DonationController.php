<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Donations — tithes, offerings and gifts.
 */
class DonationController extends Controller
{
    use ChurchTenantContext;

    public const PAYMENT_METHODS = [
        'cash'     => 'Cash',
        'cheque'   => 'Cheque',
        'bank'     => 'Bank Transfer',
        'card'     => 'Card',
        'online'   => 'Online',
        'other'    => 'Other',
    ];

    public function index(Request $request)
    {
        $installed = $this->tableExists('donations');

        $donations = collect();
        $members = collect();
        $types = collect();
        $totals = ['period' => 0.0, 'month' => 0.0, 'count' => 0];

        if ($installed) {
            $query = $this->scopedQuery('donations');

            /*
             | Date range defaults to the current month.
             |
             | A donations list with no range would grow without limit and, more
             | to the point, a treasurer almost always wants a period rather
             | than everything ever given.
             */
            $from = $request->input('from') ?: now()->startOfMonth()->format('Y-m-d');
            $to   = $request->input('to') ?: now()->endOfMonth()->format('Y-m-d');

            $query->whereBetween('donation_date', [$from, $to]);

            if ($memberId = $request->input('member_id')) {
                $query->where('member_id', (int) $memberId);
            }

            if ($typeId = $request->input('donation_type_id')) {
                $query->where('donation_type_id', (int) $typeId);
            }

            if ($search = trim((string) $request->input('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('donor_name', 'like', '%' . $search . '%')
                        ->orWhere('receipt_no', 'like', '%' . $search . '%')
                        ->orWhere('reference_no', 'like', '%' . $search . '%');
                });
            }

            /*
             | The period total is taken from a CLONE of the filtered query,
             | before pagination. Summing the paginated rows would total only
             | the current page, which is a subtly wrong number that looks
             | plausible.
             */
            $totals['period'] = (float) (clone $query)->sum('amount');
            $totals['count']  = (int) (clone $query)->count();

            $donations = $query->orderBy('donation_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(25)
                ->withQueryString();

            $totals['month'] = (float) $this->scopedQuery('donations')
                ->whereBetween('donation_date', [
                    now()->startOfMonth()->format('Y-m-d'),
                    now()->endOfMonth()->format('Y-m-d'),
                ])->sum('amount');

            $members = $this->tableExists('members')
                ? $this->scopedQuery('members')->orderBy('full_name')->pluck('full_name', 'id')
                : collect();

            $types = $this->donationTypes();

            $request->merge(['from' => $from, 'to' => $to]);
        }

        return view('churchmanagement::donations.index', [
            'installed' => $installed,
            'donations' => $donations,
            'members'   => $members,
            'types'     => $types,
            'methods'   => self::PAYMENT_METHODS,
            'totals'    => $totals,
            'locations' => $this->locationOptions(),
            'filters'   => $request->only(['from', 'to', 'member_id', 'donation_type_id', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->tableExists('donations')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $data = $this->validated($request);

        try {
            $data = $this->withScope($data);

            $data['receipt_no'] = $this->nextCode('donations', 'receipt_no', 'RCT');
            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table($this->table('donations'))->insert($data);

            return back()->with('chc_success', 'Donation recorded successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: donation create failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to record this donation.');
        }
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validated($request);

        try {
            $data['updated_by'] = Auth::id();

            if (! $this->updateScopedRow('donations', $id, $data)) {
                return back()->with('chc_error', 'That donation was not found for this business.');
            }

            return back()->with('chc_success', 'Donation updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: donation update failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to update this donation.');
        }
    }

    public function destroy(int $id)
    {
        try {
            if (! $this->deleteScopedRow('donations', $id)) {
                return back()->with('chc_error', 'That donation was not found for this business.');
            }

            return back()->with('chc_success', 'Donation removed.');
        } catch (\Throwable $e) {
            Log::error('Church Management: donation delete failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to remove this donation.');
        }
    }

    /**
     * Add a donation type from the same page, so a treasurer is not sent to a
     * settings screen mid-entry to create "Harvest Offering".
     */
    public function storeType(Request $request)
    {
        if (! $this->tableExists('donation_types')) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $data = $this->withScope($data);
            unset($data['business_location_id']); // types are business-wide

            $data['is_active']  = 1;
            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table($this->table('donation_types'))->insert($data);

            return back()->with('chc_success', 'Donation type added.');
        } catch (\Throwable $e) {
            Log::error('Church Management: donation type create failed', ['message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to add this donation type.');
        }
    }

    protected function donationTypes()
    {
        if (! $this->tableExists('donation_types')) {
            return collect();
        }

        return $this->scopedQuery('donation_types')
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'member_id'            => 'nullable|integer',
            'donor_name'           => 'nullable|string|max:191',
            'donation_type_id'     => 'nullable|integer',
            'amount'               => 'required|numeric|min:0',
            'donation_date'        => 'required|date',
            'payment_method'       => 'nullable|in:' . implode(',', array_keys(self::PAYMENT_METHODS)),
            'reference_no'         => 'nullable|string|max:100',
            'business_location_id' => 'nullable|integer',
            'notes'                => 'nullable|string|max:2000',
        ], [
            'amount.required'        => 'Enter the amount given.',
            'amount.numeric'         => 'The amount must be a number.',
            'donation_date.required' => 'Enter the date of the donation.',
        ]);

        foreach (['member_id', 'donation_type_id', 'business_location_id'] as $key) {
            if (empty($data[$key])) {
                $data[$key] = null;
            }
        }

        /*
         | A donation always has a giver of some sort. Where no member is
         | chosen and no name was typed, it is recorded as Anonymous rather than
         | as a blank, so a printed list never has an empty donor column.
         */
        $data['donor_name'] = trim((string) ($data['donor_name'] ?? ''));
        if ($data['member_id'] === null && $data['donor_name'] === '') {
            $data['donor_name'] = 'Anonymous';
        }

        return $data;
    }
}
