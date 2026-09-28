<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;

/**
 * The SW change log.
 *
 * A closed shift can be edited, and so can a settlement - a genuine mistake
 * should be correctable rather than frozen. But a figure someone has agreed
 * must not be able to change SILENTLY.
 *
 * Everything here answers one question: why is this different from what I
 * signed off?
 */
class LogService
{
    /**
     * Record a change.
     *
     * @param  array  $before  the record as it was, or [] when created
     * @param  array  $after   the record as it now is, or [] when deleted
     */
    public function record(
        string $documentType,
        ?int $documentId,
        ?string $documentNo,
        string $action,
        array $before = [],
        array $after = [],
        array $context = []
    ): void {
        try {
            $changes = $this->diff($before, $after);

            /*
             | A save that alters no field is not an event. Logging it would
             | bury the entries that matter under ones that do not.
            */
            if ($action === 'updated' && empty($changes)) {
                return;
            }

            $status = $context['document_status'] ?? null;

            DB::table('sw_logs')->insert([
                'business_id' => $context['business_id']
                    ?? (int) (session('business.id') ?: session('user.business_id') ?: 0),
                'location_id' => $context['location_id'] ?? null,

                'document_type' => $documentType,
                'document_id' => $documentId,
                'document_no' => $documentNo,

                'record_type' => $context['record_type'] ?? null,
                'record_id' => $context['record_id'] ?? null,

                'action' => $action,
                'changes' => $changes ? json_encode($changes) : null,
                'reason' => $context['reason'] ?? null,
                'note' => $context['note'] ?? null,

                'document_status' => $status,
                'after_closure' => (int) ($context['after_closure'] ?? $this->isAfterClosure($status)),

                'user_id' => auth()->id(),
                'username' => optional(auth()->user())->username,
                'ip_address' => request()->ip(),

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            /*
             | A failing log must never break the work it describes.
             |
             | Losing one entry is bad; refusing a legitimate correction because
             | the log could not be written is worse.
            */
            \Illuminate\Support\Facades\Log::warning('SW log could not be written.', [
                'document' => $documentType . ' ' . $documentNo,
                'action' => $action,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Which fields differ, and what they were.
     */
    protected function diff(array $before, array $after): array
    {
        if (empty($before) && empty($after)) {
            return [];
        }

        $changes = [];
        $fields = array_unique(array_merge(array_keys($before), array_keys($after)));

        foreach ($fields as $field) {
            if (in_array($field, ['updated_at', 'created_at', 'updated_by'], true)) {
                continue;
            }

            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($this->same($old, $new)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'from' => $this->readable($old),
                'to' => $this->readable($new),
            ];
        }

        return $changes;
    }

    /**
     * Compared as numbers where both are numeric.
     *
     * A decimal read back from the database is '500.0000' where the form sent
     * '500'. Reporting that as a change would fill the log with edits nobody
     * made, and a log full of noise is a log nobody reads.
     */
    protected function same($a, $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }

        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.00005;
        }

        return (string) $a === (string) $b;
    }

    protected function readable($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Was the document already closed when this happened?
     *
     * That is the distinction the log exists for: an edit to an open shift is
     * ordinary work, the same edit to a closed one is what someone will come
     * looking for.
     */
    protected function isAfterClosure(?string $status): bool
    {
        return in_array($status, ['Closed', 'Settled'], true);
    }
}
