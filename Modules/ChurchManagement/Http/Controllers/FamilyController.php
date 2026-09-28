<?php

namespace Modules\ChurchManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ChurchManagement\Http\Controllers\Concerns\ChurchTenantContext;

/**
 * Families — households that members belong to.
 */
class FamilyController extends Controller
{
    use ChurchTenantContext;

    public function index(Request $request)
    {
        $installed = $this->moduleInstalled();

        $families = collect();
        $memberOptions = collect();

        if ($installed) {
            $query = $this->scopedQuery('families');

            if ($search = trim((string) $request->input('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('family_name', 'like', '%' . $search . '%')
                        ->orWhere('family_code', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            }

            $families = $query->orderBy('family_name')->paginate(25)->withQueryString();

            /*
             | Member counts for the whole page in ONE query, keyed by family.
             |
             | Counting inside the row loop would issue a query per family - the
             | classic N+1, and on a congregation of any size the page would
             | slow down as it grew.
             */
            $familyIds = collect($families->items())->pluck('id')->all();

            $counts = empty($familyIds) ? collect() : $this->scopedQuery('members')
                ->whereIn('family_id', $familyIds)
                ->select('family_id', DB::raw('COUNT(*) as member_count'))
                ->groupBy('family_id')
                ->pluck('member_count', 'family_id');

            foreach ($families as $family) {
                $family->member_count = (int) ($counts[$family->id] ?? 0);
            }

            $memberOptions = $this->scopedQuery('members')
                ->orderBy('full_name')
                ->pluck('full_name', 'id');
        }

        return view('churchmanagement::families.index', [
            'installed'     => $installed,
            'families'      => $families,
            'memberOptions' => $memberOptions,
            'filters'       => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if (! $this->moduleInstalled()) {
            return back()->with('chc_error', 'Church Management tables are not installed in this database yet.');
        }

        try {
            $data = $this->withScope($data);

            $data['family_code'] = $this->nextCode(
                'families',
                'family_code',
                (string) config('churchmanagement.family_code_prefix', 'FM')
            );

            $data['created_by'] = Auth::id();
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table($this->table('families'))->insert($data);

            return back()->with('chc_success', 'Family added successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: family create failed', ['message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to save this family. Please try again.');
        }
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validated($request);

        try {
            $data['updated_by'] = Auth::id();

            $updated = $this->updateScopedRow('families', $id, $data);

            if (! $updated) {
                return back()->with('chc_error', 'That family was not found for this business.');
            }

            return back()->with('chc_success', 'Family updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Church Management: family update failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()->with('chc_error', 'Unable to update this family.');
        }
    }

    public function destroy(int $id)
    {
        try {
            /*
             | Members are detached rather than deleted with the family.
             |
             | Deleting a household must not delete the people in it. Their
             | family_id is cleared so they remain on the roll, unattached, and
             | can be moved to another family.
             */
            if ($this->tableExists('members')) {
                $this->scopedQuery('members')
                    ->where('family_id', $id)
                    ->update(['family_id' => null, 'updated_at' => now()]);
            }

            $deleted = $this->deleteScopedRow('families', $id);

            if (! $deleted) {
                return back()->with('chc_error', 'That family was not found for this business.');
            }

            return back()->with('chc_success', 'Family removed. Its members are still on the roll, without a family.');
        } catch (\Throwable $e) {
            Log::error('Church Management: family delete failed', ['id' => $id, 'message' => $e->getMessage()]);

            return back()->with('chc_error', 'Unable to remove this family.');
        }
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'family_name'    => 'required|string|max:191',
            'head_member_id' => 'nullable|integer',
            'phone'          => 'nullable|string|max:50',
            'email'          => 'nullable|email|max:191',
            'address'        => 'nullable|string|max:1000',
            'city'           => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:2000',
        ], [
            'family_name.required' => 'A family name is required.',
            'email.email'          => 'Enter a valid email address.',
        ]);

        $data['family_name'] = trim($data['family_name']);

        if (empty($data['head_member_id'])) {
            $data['head_member_id'] = null;
        }

        return $data;
    }
}
