<?php

namespace Modules\RiceMill\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\RiceMill\Models\Dispatch;
use Modules\RiceMill\Models\Setting;
use Modules\RiceMill\Notifications\SalesInvoiceApprovalRequired;

/**
 * Sends the Sales Invoice approval request only when the business setting is
 * enabled and only to users who currently hold rice_mill.dispatch.approve.
 */
class SalesApprovalNotificationService
{
    public function __construct(
        private PermissionAccessService $permissions,
        private BusinessPrecisionService $precision
    ) {
    }

    public function enabled(int $businessId): bool
    {
        $row = Setting::forBusiness($businessId)->select(['settings'])->first();
        $settings = (array) optional($row)->settings;

        return (bool) ($settings['auto_notify_sales_invoice_approval'] ?? false);
    }

    /**
     * @return array{enabled:bool,sent:int,reason:?string}
     */
    public function notifyDraft(int $businessId, Dispatch $dispatch, int $createdBy): array
    {
        if (! $this->enabled($businessId)) {
            return ['enabled' => false, 'sent' => 0, 'reason' => null];
        }

        if (! class_exists(\App\User::class) || ! Schema::hasTable('users') || ! Schema::hasTable('notifications')) {
            Log::warning('Rice Mill sales approval notification skipped: host notification tables/model unavailable.', [
                'business_id' => $businessId,
                'dispatch_id' => $dispatch->id,
            ]);
            return ['enabled' => true, 'sent' => 0, 'reason' => 'notification_system_unavailable'];
        }

        $recipients = \App\User::query()
            ->where('business_id', $businessId)
            ->get()
            ->filter(fn ($user) => $this->permissions->allows($user, 'rice_mill.dispatch.approve'));

        if ($recipients->isEmpty()) {
            return ['enabled' => true, 'sent' => 0, 'reason' => 'no_permitted_users'];
        }

        $customer = DB::table('contacts')
            ->where('business_id', $businessId)
            ->where('id', (int) $dispatch->customer_id)
            ->value('name');
        $customer = trim((string) $customer) !== '' ? trim((string) $customer) : ('Customer #' . $dispatch->customer_id);

        $currencyPrecision = (int) ($this->precision->forBusiness($businessId)['currency'] ?? 4);
        $amount = number_format((float) $dispatch->net_total, $currencyPrecision, '.', ',');
        try {
            $url = route('rice-mill.dispatch.show', [(int) $dispatch->id], false);
        } catch (\Throwable $e) {
            $url = '/rice-mill/dispatch/' . (int) $dispatch->id;
        }
        $message = 'Sales Invoice ' . $dispatch->dispatch_no . ' for ' . $customer . ' (' . $amount . ') is waiting for approval.';

        $payload = [
            'title' => 'Sales Invoice Approval Required',
            'message' => $message,
            // Keep common host notification keys as well, so existing navbar /
            // message renderers can display the same notification without a
            // Rice Mill core-layout modification.
            'msg' => $message,
            'url' => $url,
            'action_url' => $url,
            'icon' => 'fa fa-file-text-o',
            'module' => 'rice_mill',
            'type' => 'sales_invoice_approval_required',
            'business_id' => $businessId,
            'dispatch_id' => (int) $dispatch->id,
            'invoice_no' => (string) $dispatch->dispatch_no,
            'customer_id' => (int) $dispatch->customer_id,
            'customer_name' => $customer,
            'net_total' => (float) $dispatch->net_total,
            'permission' => 'rice_mill.dispatch.approve',
            'created_by' => $createdBy,
        ];

        $sent = 0;
        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new SalesInvoiceApprovalRequired($payload));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Rice Mill sales approval notification failed for a permitted user.', [
                    'business_id' => $businessId,
                    'dispatch_id' => $dispatch->id,
                    'user_id' => $recipient->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'enabled' => true,
            'sent' => $sent,
            'reason' => $sent > 0 ? null : 'delivery_failed',
        ];
    }

    /**
     * Once one permitted user approves the invoice, clear the pending approval
     * alert for every recipient so other approvers are not left with a stale
     * "waiting for approval" message.
     */
    public function markResolved(int $businessId, int $dispatchId): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        try {
            $ids = DB::table('notifications')
                ->where('type', SalesInvoiceApprovalRequired::class)
                ->whereNull('read_at')
                ->get(['id','data'])
                ->filter(function ($row) use ($businessId, $dispatchId) {
                    $data = json_decode((string) $row->data, true);
                    return is_array($data)
                        && (int) ($data['business_id'] ?? 0) === $businessId
                        && (int) ($data['dispatch_id'] ?? 0) === $dispatchId;
                })
                ->pluck('id')
                ->all();

            if ($ids) {
                DB::table('notifications')->whereIn('id', $ids)->update([
                    'read_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Rice Mill sales approval notifications could not be marked resolved.', [
                'business_id' => $businessId,
                'dispatch_id' => $dispatchId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
