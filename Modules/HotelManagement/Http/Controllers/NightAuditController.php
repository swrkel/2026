<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class NightAuditController extends Controller
{
    use HotelTenantContext;

    public function index(Request $request)
    {
        $auditDate = $request->get('audit_date', date('Y-m-d'));
        $audits = $this->tableExists('hm_night_audits')
            ? $this->scopedQuery('hm_night_audits')->orderByDesc('audit_date')->orderByDesc('id')->limit(60)->get()
            : collect();
        $preview = $this->buildPreview($auditDate);
        $latest = $audits->first();
        $lines = collect();
        if ($latest && $this->tableExists('hm_night_audit_lines')) {
            $lines = DB::table('hm_night_audit_lines')->where('night_audit_id', $latest->id)->orderBy('section')->orderBy('line_label')->get();
        }
        return view('hotelmanagement::night_audit.index', compact('auditDate','audits','preview','latest','lines'));
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'audit_date' => 'required|date',
            'note' => 'nullable|string',
            'close_open_day' => 'nullable|boolean',
        ]);
        if (!$this->tableExists('hm_night_audits')) {
            return back()->withErrors(['audit' => 'Night Audit SQL has not been executed yet. Please run HOTELMGT_013_SQL.sql in the tenant database.']);
        }
        $auditDate = $data['audit_date'];
        $exists = $this->scopedQuery('hm_night_audits')->where('audit_date', $auditDate)->whereIn('status', ['posted','closed'])->first();
        if ($exists) {
            return back()->withErrors(['audit' => 'Night audit is already posted/closed for '.$auditDate.'. Reopen first if correction is required.']);
        }
        $preview = $this->buildPreview($auditDate);
        DB::beginTransaction();
        try {
            $id = DB::table('hm_night_audits')->insertGetId($this->withScope([
                'audit_no' => $this->nextCode('hm_night_audits','audit_no','NA'),
                'audit_date' => $auditDate,
                'room_revenue' => $preview['room_revenue'],
                'pos_revenue' => $preview['pos_revenue'],
                'room_service_revenue' => $preview['room_service_revenue'],
                'banquet_revenue' => $preview['banquet_revenue'],
                'conference_revenue' => $preview['conference_revenue'],
                'total_revenue' => $preview['total_revenue'],
                'cash_collected' => $preview['cash_collected'],
                'card_collected' => $preview['card_collected'],
                'other_collected' => $preview['other_collected'],
                'total_collected' => $preview['total_collected'],
                'open_folio_balance' => $preview['open_folio_balance'],
                'arrivals_count' => $preview['arrivals_count'],
                'departures_count' => $preview['departures_count'],
                'occupied_rooms' => $preview['occupied_rooms'],
                'vacant_rooms' => $preview['vacant_rooms'],
                'out_of_service_rooms' => $preview['out_of_service_rooms'],
                'status' => $request->boolean('close_open_day') ? 'closed' : 'posted',
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
            if ($this->tableExists('hm_night_audit_lines')) {
                foreach ($preview['lines'] as $line) {
                    DB::table('hm_night_audit_lines')->insert($this->withScope([
                        'night_audit_id' => $id,
                        'audit_date' => $auditDate,
                        'section' => $line['section'],
                        'line_label' => $line['label'],
                        'line_value' => $line['value'],
                        'amount' => $line['amount'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]));
                }
            }
            $this->audit('posted', 'hm_night_audits', $id, ['audit_date' => $auditDate, 'total_revenue' => $preview['total_revenue']]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['audit' => 'Night audit could not be posted: '.$e->getMessage()])->withInput();
        }
        return back()->with('status', 'Night Audit posted successfully for '.$auditDate.'.');
    }

    public function reopen($id)
    {
        $this->updateScopedRow('hm_night_audits', (int)$id, ['status' => 'reopened', 'reopened_by' => auth()->id(), 'reopened_at' => now()]);
        $this->audit('reopened', 'hm_night_audits', (int)$id);
        return back()->with('status', 'Night Audit reopened for correction.');
    }

    public function close($id)
    {
        $this->updateScopedRow('hm_night_audits', (int)$id, ['status' => 'closed', 'closed_by' => auth()->id(), 'closed_at' => now()]);
        $this->audit('closed', 'hm_night_audits', (int)$id);
        return back()->with('status', 'Night Audit closed successfully.');
    }

    private function buildPreview(string $date): array
    {
        $roomRevenue = $this->sumFolioLines($date, ['room','room_charge','accommodation']);
        $posRevenue = $this->sumFolioLines($date, ['pos','restaurant','bar','mini_bar']);
        $roomServiceRevenue = $this->sumFolioLines($date, ['room_service','in_room_dining']);
        $banquetRevenue = $this->sumSimpleTable('hm_banquet_events', $date, 'event_date', 'total_amount', ['completed','confirmed']);
        $conferenceRevenue = $this->sumSimpleTable('hm_conference_bookings', $date, 'booking_date', 'total_amount', ['completed','confirmed']);
        $cash = $this->sumPayments($date, ['cash']);
        $card = $this->sumPayments($date, ['card','visa','master','credit_card','debit_card']);
        $totalCollected = $this->sumPayments($date, null);
        $other = max(0, $totalCollected - $cash - $card);
        $totalRevenue = $roomRevenue + $posRevenue + $roomServiceRevenue + $banquetRevenue + $conferenceRevenue;
        $openBalance = $this->tableExists('hm_folios') ? (float)$this->scopedQuery('hm_folios')->where('status','open')->sum('balance') : 0;
        $arrivals = $this->countDate('hm_reservations', 'arrival_date', $date);
        $departures = $this->countDate('hm_reservations', 'departure_date', $date);
        $occupied = $this->countRoomsByStatus(['occupied','in_house']);
        $vacant = $this->countRoomsByStatus(['available','vacant','clean']);
        $ooo = $this->countRoomsByStatus(['out_of_service','maintenance']);
        return [
            'room_revenue'=>$roomRevenue,'pos_revenue'=>$posRevenue,'room_service_revenue'=>$roomServiceRevenue,
            'banquet_revenue'=>$banquetRevenue,'conference_revenue'=>$conferenceRevenue,'total_revenue'=>$totalRevenue,
            'cash_collected'=>$cash,'card_collected'=>$card,'other_collected'=>$other,'total_collected'=>$totalCollected,
            'open_folio_balance'=>$openBalance,'arrivals_count'=>$arrivals,'departures_count'=>$departures,
            'occupied_rooms'=>$occupied,'vacant_rooms'=>$vacant,'out_of_service_rooms'=>$ooo,
            'lines'=>[
                ['section'=>'Revenue','label'=>'Room Revenue','value'=>$arrivals.' arrivals / '.$departures.' departures','amount'=>$roomRevenue],
                ['section'=>'Revenue','label'=>'Hotel POS Revenue','value'=>'Folio POS charges','amount'=>$posRevenue],
                ['section'=>'Revenue','label'=>'Room Service Revenue','value'=>'In-room dining charges','amount'=>$roomServiceRevenue],
                ['section'=>'Revenue','label'=>'Banquet Revenue','value'=>'Confirmed/completed events','amount'=>$banquetRevenue],
                ['section'=>'Revenue','label'=>'Conference Revenue','value'=>'Confirmed/completed meetings','amount'=>$conferenceRevenue],
                ['section'=>'Collections','label'=>'Cash Collection','value'=>'Guest payment method cash','amount'=>$cash],
                ['section'=>'Collections','label'=>'Card Collection','value'=>'Card/debit/credit card methods','amount'=>$card],
                ['section'=>'Collections','label'=>'Other Collection','value'=>'Other guest payments','amount'=>$other],
                ['section'=>'Control','label'=>'Open Folio Balance','value'=>'All open folios','amount'=>$openBalance],
                ['section'=>'Room Status','label'=>'Occupied Rooms','value'=>(string)$occupied,'amount'=>0],
                ['section'=>'Room Status','label'=>'Vacant Rooms','value'=>(string)$vacant,'amount'=>0],
                ['section'=>'Room Status','label'=>'Out of Service Rooms','value'=>(string)$ooo,'amount'=>0],
            ]
        ];
    }

    private function sumFolioLines(string $date, array $types): float
    {
        if (!$this->tableExists('hm_folio_lines')) return 0;
        $q = $this->scopedQuery('hm_folio_lines')->whereDate('charge_date', $date);
        $q->where(function($sub) use ($types) { foreach($types as $type) { $sub->orWhere('charge_type', 'like', '%'.$type.'%'); } });
        return (float)$q->sum('amount');
    }

    private function sumPayments(string $date, ?array $methods): float
    {
        if (!$this->tableExists('hm_guest_payments')) return 0;
        $q = $this->scopedQuery('hm_guest_payments')->whereDate('payment_date', $date);
        if ($methods) { $q->where(function($sub) use ($methods) { foreach($methods as $m) { $sub->orWhere('payment_method', 'like', '%'.$m.'%'); } }); }
        return (float)$q->sum('amount');
    }

    private function sumSimpleTable(string $table, string $date, string $dateColumn, string $amountColumn, array $statuses): float
    {
        if (!$this->tableExists($table)) return 0;
        $q = $this->scopedQuery($table)->whereDate($dateColumn, $date);
        if (count($statuses)) $q->whereIn('status', $statuses);
        return (float)$q->sum($amountColumn);
    }

    private function countDate(string $table, string $column, string $date): int
    {
        if (!$this->tableExists($table)) return 0;
        return (int)$this->scopedQuery($table)->whereDate($column, $date)->count();
    }

    private function countRoomsByStatus(array $statuses): int
    {
        if (!$this->tableExists('hm_rooms')) return 0;
        return (int)$this->scopedQuery('hm_rooms')->whereIn('status', $statuses)->count();
    }
}
