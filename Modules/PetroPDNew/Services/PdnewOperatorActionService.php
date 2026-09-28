<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\PetroPDNew\Entities\PdnewOperatorMapping;
use Modules\PumperDashboardNew\Services\PoneNumberSequenceService;
use RuntimeException;

class PdnewOperatorActionService
{
    public function __construct(private PoneNumberSequenceService $sequences) {}

    /** @return array<string, mixed> */
    public function workspace(PdnewOperatorMapping $mapping, ?string $from = null, ?string $to = null): array
    {
        $from = $from ?: now()->startOfMonth()->toDateString();
        $to = $to ?: now()->toDateString();
        $settings = (array) $mapping->settings;
        $master = (array) data_get($settings, 'operator_master', []);
        $local = (array) data_get($settings, 'local_overrides', []);

        $ledger = $this->ledger($mapping, $from, $to);
        $commissions = $this->commissions($mapping, $from, $to);
        $recoveries = $this->recoveries($mapping, $from, $to);

        return [
            'mapping' => $mapping,
            'from' => $from,
            'to' => $to,
            'master' => $master,
            'local' => $local,
            'contact' => [
                'name' => trim((string) ($local['display_name'] ?? '')) ?: $mapping->display_name,
                'mobile' => (string) ($local['mobile'] ?? $master['mobile'] ?? ''),
                'landline' => (string) ($local['landline'] ?? $master['landline'] ?? ''),
                'cnic' => (string) ($local['cnic'] ?? $master['cnic'] ?? ''),
                'address' => (string) ($local['address'] ?? $master['address'] ?? ''),
                'dob' => (string) ($local['dob'] ?? $master['dob'] ?? ''),
                'commission_type' => (string) ($local['commission_type'] ?? $master['commission_type'] ?? 'none'),
                'commission_rate' => (float) ($local['commission_rate'] ?? $master['commission_ap'] ?? 0),
            ],
            'ledger' => $ledger,
            'current_balance' => $this->currentBalance($mapping),
            'commissions' => $commissions,
            'recoveries' => $recoveries,
            'notes' => $this->notes($mapping),
            'documents' => $this->documents($mapping),
            'location_name' => $this->locationName($mapping->location_id ? (int) $mapping->location_id : null),
        ];
    }

    public function update(PdnewOperatorMapping $mapping, array $data): PdnewOperatorMapping
    {
        return DB::transaction(function () use ($mapping, $data): PdnewOperatorMapping {
            $mapping = PdnewOperatorMapping::query()
                ->where('business_id', $mapping->business_id)
                ->whereKey($mapping->id)
                ->lockForUpdate()
                ->firstOrFail();

            $settings = (array) $mapping->settings;
            $local = (array) data_get($settings, 'local_overrides', []);
            foreach (['display_name', 'mobile', 'landline', 'cnic', 'address', 'dob', 'commission_type', 'commission_rate'] as $field) {
                if (array_key_exists($field, $data)) {
                    $local[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                }
            }
            $settings['local_overrides'] = $local;

            $mapping->display_name = trim((string) ($local['display_name'] ?? '')) ?: $mapping->display_name;
            $mapping->status = ($data['status'] ?? $mapping->status) === 'inactive' ? 'inactive' : 'active';
            $mapping->settings = $settings;
            $mapping->save();

            return $mapping->fresh();
        }, 3);
    }

    public function toggle(PdnewOperatorMapping $mapping): PdnewOperatorMapping
    {
        $mapping->status = $mapping->status === 'active' ? 'inactive' : 'active';
        $mapping->save();

        return $mapping->fresh();
    }

    public function payCommission(PdnewOperatorMapping $mapping, array $data): int
    {
        $this->requireTable('pone_excess_commissions');
        $this->requireTable('pone_operator_ledger_entries');

        return DB::transaction(function () use ($mapping, $data): int {
            $number = $this->sequences->next((int) $mapping->business_id, $mapping->location_id ? (int) $mapping->location_id : null, 'commission');
            $amount = round((float) $data['amount'], 4);
            $base = round((float) ($data['base_excess_amount'] ?? $amount), 4);
            $type = ($data['commission_type'] ?? 'fixed') === 'percentage' ? 'percentage' : 'fixed';
            $rate = round((float) ($data['commission_rate'] ?? 0), 6);

            $id = DB::table('pone_excess_commissions')->insertGetId([
                'shift_id' => null,
                'business_id' => (int) $mapping->business_id,
                'location_id' => $mapping->location_id ?: null,
                'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
                'pd_operator_id' => (int) $mapping->pone_pd_operator_id,
                'commission_number' => $number,
                'commission_date' => (string) $data['transaction_date'],
                'base_excess_amount' => $base,
                'commission_type' => $type,
                'commission_rate' => $rate,
                'commission_amount' => $amount,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'status' => 'confirmed',
                'created_by' => (int) auth()->id() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pone_operator_ledger_entries')->insert([
                'entry_key' => implode(':', [(int) $mapping->business_id, (int) $mapping->pone_operator_profile_id, 'excess_commission', $id]),
                'business_id' => (int) $mapping->business_id,
                'location_id' => $mapping->location_id ?: null,
                'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
                'pd_operator_id' => (int) $mapping->pone_pd_operator_id,
                'shift_id' => null,
                'source_type' => 'excess_commission',
                'source_id' => $id,
                'entry_at' => (string) $data['transaction_date'] . ' 12:00:00',
                'reference_no' => $number,
                'description' => 'Excess and commission payment ' . $number,
                'debit' => 0,
                'credit' => $amount,
                'status' => 'active',
                'created_by' => (int) auth()->id() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) $id;
        }, 3);
    }

    public function recoverShortage(PdnewOperatorMapping $mapping, array $data): int
    {
        $this->requireTable('pone_shortage_recoveries');
        $this->requireTable('pone_operator_ledger_entries');

        return DB::transaction(function () use ($mapping, $data): int {
            $number = $this->sequences->next((int) $mapping->business_id, $mapping->location_id ? (int) $mapping->location_id : null, 'recovery');
            $amount = round((float) $data['amount'], 4);

            $id = DB::table('pone_shortage_recoveries')->insertGetId([
                'shift_id' => null,
                'business_id' => (int) $mapping->business_id,
                'location_id' => $mapping->location_id ?: null,
                'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
                'pd_operator_id' => (int) $mapping->pone_pd_operator_id,
                'recovery_number' => $number,
                'recovery_date' => (string) $data['transaction_date'],
                'amount' => $amount,
                'payment_method' => (string) ($data['payment_method'] ?? 'cash'),
                'reference_no' => trim((string) ($data['reference_no'] ?? '')) ?: null,
                'note' => trim((string) ($data['note'] ?? '')) ?: null,
                'status' => 'confirmed',
                'created_by' => (int) auth()->id() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pone_operator_ledger_entries')->insert([
                'entry_key' => implode(':', [(int) $mapping->business_id, (int) $mapping->pone_operator_profile_id, 'shortage_recovery', $id]),
                'business_id' => (int) $mapping->business_id,
                'location_id' => $mapping->location_id ?: null,
                'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
                'pd_operator_id' => (int) $mapping->pone_pd_operator_id,
                'shift_id' => null,
                'source_type' => 'shortage_recovery',
                'source_id' => $id,
                'entry_at' => (string) $data['transaction_date'] . ' 12:00:00',
                'reference_no' => $number,
                'description' => 'Shortage recovered ' . $number,
                'debit' => 0,
                'credit' => $amount,
                'status' => 'active',
                'created_by' => (int) auth()->id() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return (int) $id;
        }, 3);
    }

    public function addNote(PdnewOperatorMapping $mapping, array $data): int
    {
        $this->requireTable('pone_operator_notes');

        return (int) DB::table('pone_operator_notes')->insertGetId([
            'business_id' => (int) $mapping->business_id,
            'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
            'shift_id' => null,
            'note_type' => (string) ($data['note_type'] ?? 'general'),
            'title' => trim((string) $data['title']),
            'body' => trim((string) $data['body']),
            'status' => 'active',
            'created_by' => (int) auth()->id() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function uploadDocument(PdnewOperatorMapping $mapping, array $data, UploadedFile $file): int
    {
        $this->requireTable('pone_operator_documents');
        $disk = 'public';
        $directory = 'pumper-dashboard-new/' . (int) $mapping->business_id . '/' . (int) $mapping->pone_operator_profile_id;
        $path = $file->store($directory, $disk);

        return (int) DB::table('pone_operator_documents')->insertGetId([
            'business_id' => (int) $mapping->business_id,
            'operator_profile_id' => (int) $mapping->pone_operator_profile_id,
            'shift_id' => null,
            'title' => trim((string) ($data['title'] ?? $file->getClientOriginalName())),
            'category' => (string) ($data['category'] ?? 'general'),
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => (int) ($file->getSize() ?: 0),
            'visibility' => 'management',
            'uploaded_by' => (int) auth()->id() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function document(PdnewOperatorMapping $mapping, int $documentId): object
    {
        $this->requireTable('pone_operator_documents');
        $document = DB::table('pone_operator_documents')
            ->where('id', $documentId)
            ->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->whereNull('deleted_at')
            ->first();
        abort_unless($document, 404);

        return $document;
    }

    private function currentBalance(PdnewOperatorMapping $mapping): float
    {
        if (! Schema::hasTable('pone_operator_ledger_entries')) return 0.0;

        return round((float) DB::table('pone_operator_ledger_entries')
            ->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->where('status', 'active')
            ->selectRaw('COALESCE(SUM(debit-credit),0) as balance')
            ->value('balance'), 4);
    }

    /** @return Collection<int, object> */
    private function ledger(PdnewOperatorMapping $mapping, string $from, string $to): Collection
    {
        if (! Schema::hasTable('pone_operator_ledger_entries')) {
            return collect();
        }

        $rows = DB::table('pone_operator_ledger_entries')
            ->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->where('status', 'active')
            ->whereDate('entry_at', '>=', $from)
            ->whereDate('entry_at', '<=', $to)
            ->orderBy('entry_at')->orderBy('id')->get();

        $balance = 0.0;
        return $rows->map(function (object $row) use (&$balance): object {
            $balance += (float) $row->debit - (float) $row->credit;
            $row->running_balance = round($balance, 4);
            return $row;
        });
    }

    private function commissions(PdnewOperatorMapping $mapping, string $from, string $to): Collection
    {
        if (! Schema::hasTable('pone_excess_commissions')) return collect();
        return DB::table('pone_excess_commissions')
            ->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->whereDate('commission_date', '>=', $from)->whereDate('commission_date', '<=', $to)
            ->orderByDesc('commission_date')->orderByDesc('id')->limit(250)->get();
    }

    private function recoveries(PdnewOperatorMapping $mapping, string $from, string $to): Collection
    {
        if (! Schema::hasTable('pone_shortage_recoveries')) return collect();
        return DB::table('pone_shortage_recoveries')
            ->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->whereDate('recovery_date', '>=', $from)->whereDate('recovery_date', '<=', $to)
            ->orderByDesc('recovery_date')->orderByDesc('id')->limit(250)->get();
    }

    private function notes(PdnewOperatorMapping $mapping): Collection
    {
        if (! Schema::hasTable('pone_operator_notes')) return collect();
        return DB::table('pone_operator_notes')->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->whereNull('deleted_at')->orderByDesc('created_at')->limit(100)->get();
    }

    private function documents(PdnewOperatorMapping $mapping): Collection
    {
        if (! Schema::hasTable('pone_operator_documents')) return collect();
        return DB::table('pone_operator_documents')->where('business_id', (int) $mapping->business_id)
            ->where('operator_profile_id', (int) $mapping->pone_operator_profile_id)
            ->whereNull('deleted_at')->orderByDesc('created_at')->limit(100)->get();
    }

    private function locationName(?int $locationId): string
    {
        if (! $locationId || ! Schema::hasTable('business_locations')) return 'All / Not assigned';
        return (string) (DB::table('business_locations')->where('id', $locationId)->value('name') ?: 'Location #' . $locationId);
    }

    private function requireTable(string $table): void
    {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException('Required Pumper Dashboard-New table is missing: ' . $table . '.');
        }
    }
}
