<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class GuestCrmController extends Controller
{
    use HotelTenantContext;

    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $status = $request->get('status');

        $guests = collect();
        if ($this->tableExists('hm_guests')) {
            $query = $this->scopedQuery('hm_guests');
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    foreach (['guest_code', 'guest_name', 'mobile', 'email', 'id_no'] as $column) {
                        if (Schema::hasColumn('hm_guests', $column)) {
                            $q->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }
                });
            }
            if ($status && Schema::hasColumn('hm_guests', 'status')) {
                $query->where('status', $status);
            }
            $guests = $query->latest('id')->limit(150)->get();
        }

        $guestIds = $guests->pluck('id')->filter()->all();
        $preferences = collect();
        $notes = collect();
        $stayStats = collect();

        if (!empty($guestIds) && $this->tableExists('hm_guest_preferences')) {
            $preferences = $this->scopedQuery('hm_guest_preferences')
                ->whereIn('guest_id', $guestIds)
                ->latest('id')
                ->get()
                ->groupBy('guest_id');
        }

        if (!empty($guestIds) && $this->tableExists('hm_guest_notes')) {
            $notes = $this->scopedQuery('hm_guest_notes')
                ->whereIn('guest_id', $guestIds)
                ->latest('id')
                ->limit(300)
                ->get()
                ->groupBy('guest_id');
        }

        if (!empty($guestIds) && $this->tableExists('hm_reservations')) {
            $stayStats = $this->scopedQuery('hm_reservations')
                ->select('guest_id', DB::raw('COUNT(*) as reservation_count'), DB::raw('MAX(check_in_date) as last_stay_date'), DB::raw('SUM(COALESCE(estimated_total,0)) as lifetime_value'))
                ->whereIn('guest_id', $guestIds)
                ->groupBy('guest_id')
                ->get()
                ->keyBy('guest_id');
        }

        return view('hotelmanagement::crm.index', compact('guests', 'preferences', 'notes', 'stayStats', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedGuest($request);
        if (empty($data['guest_code'])) {
            $data['guest_code'] = $this->nextCode('hm_guests', 'guest_code', 'GST');
        }
        $data['status'] = $data['status'] ?: 'active';
        $data = $this->onlyExistingColumns('hm_guests', $this->withScope($data));
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_guests')->insertGetId($data);
        $this->audit('created', 'hm_guests', $id, $data);
        return back()->with('status', 'Guest profile saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validatedGuest($request);
        $data['status'] = $data['status'] ?: 'active';
        $data = $this->onlyExistingColumns('hm_guests', $data);
        $this->updateScopedRow('hm_guests', (int) $id, $data);
        $this->audit('updated', 'hm_guests', (int) $id, $data);
        return back()->with('status', 'Guest profile updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_guests', (int) $id);
        $this->audit('deleted', 'hm_guests', (int) $id);
        return back()->with('status', 'Guest profile deleted successfully.');
    }

    public function storePreference(Request $request, $guestId)
    {
        $this->ensureGuestIsScoped((int) $guestId);
        $data = $request->validate([
            'preference_type' => 'required|string|max:100',
            'preference_value' => 'nullable|string|max:191',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['guest_id'] = (int) $guestId;
        $data = $this->onlyExistingColumns('hm_guest_preferences', $this->withScope($data));
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_guest_preferences')->insertGetId($data);
        $this->audit('created', 'hm_guest_preferences', $id, $data);
        return back()->with('status', 'Guest preference saved successfully.');
    }

    public function deletePreference($guestId, $id)
    {
        $this->ensureGuestIsScoped((int) $guestId);
        $this->deleteScopedRow('hm_guest_preferences', (int) $id);
        $this->audit('deleted', 'hm_guest_preferences', (int) $id);
        return back()->with('status', 'Guest preference removed successfully.');
    }

    public function storeNote(Request $request, $guestId)
    {
        $this->ensureGuestIsScoped((int) $guestId);
        $data = $request->validate([
            'note_type' => 'nullable|string|max:100',
            'note' => 'required|string|max:2000',
        ]);
        $data['guest_id'] = (int) $guestId;
        $data['note_type'] = $data['note_type'] ?: 'general';
        $data['created_by'] = Auth::id();
        $data = $this->onlyExistingColumns('hm_guest_notes', $this->withScope($data));
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_guest_notes')->insertGetId($data);
        $this->audit('created', 'hm_guest_notes', $id, $data);
        return back()->with('status', 'Guest note saved successfully.');
    }

    public function deleteNote($guestId, $id)
    {
        $this->ensureGuestIsScoped((int) $guestId);
        $this->deleteScopedRow('hm_guest_notes', (int) $id);
        $this->audit('deleted', 'hm_guest_notes', (int) $id);
        return back()->with('status', 'Guest note removed successfully.');
    }

    protected function validatedGuest(Request $request): array
    {
        return $request->validate([
            'guest_code' => 'nullable|string|max:50',
            'guest_name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'id_no' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'vip_level' => 'nullable|string|max:50',
            'marketing_consent' => 'nullable|boolean',
            'status' => 'nullable|string|max:30',
        ]);
    }

    protected function onlyExistingColumns(string $table, array $data): array
    {
        return array_filter($data, function ($key) use ($table) {
            return Schema::hasColumn($table, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function ensureGuestIsScoped(int $guestId): void
    {
        abort_unless($this->tableExists('hm_guests') && $this->scopedQuery('hm_guests')->where('id', $guestId)->exists(), 404);
    }
}
