<?php

namespace Modules\CustomerStatements\Services;

use App\Contact;
use App\CustomerStatement;
use App\CustomerStatementSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CustomerStatementNumberingService
{
    /**
     * customer_id = 0 is a module-owned configuration row. The existing table
     * has no foreign key on customer_id, so this keeps the selected numbering
     * mode without adding or altering any tenant table.
     */
    private const CONFIG_CUSTOMER_ID = 0;

    public function businessId(Request $request): int
    {
        $businessId = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional($request->user())->business_id
        );

        if ($businessId < 1) {
            throw new RuntimeException('Unable to identify the current business.');
        }

        return $businessId;
    }

    /**
     * Return the active mode and the starting number applicable to the selected
     * customer. Existing installations without a configuration row retain the
     * historical customer-wise behaviour.
     */
    public function settings(int $businessId, ?int $customerId = null): array
    {
        $config = CustomerStatementSetting::query()
            ->where('business_id', $businessId)
            ->where('customer_id', self::CONFIG_CUSTOMER_ID)
            ->orderByDesc('id')
            ->first();

        $mode = $config
            ? ((bool) $config->enable_separate_customer_statement_no ? 'customer' : 'general')
            : 'customer';

        $startingNo = 1;

        if ($mode === 'general') {
            $startingNo = max(1, (int) ($config->starting_no ?: 1));
        } elseif (! empty($customerId)) {
            $customerSetting = CustomerStatementSetting::query()
                ->where('business_id', $businessId)
                ->where('customer_id', $customerId)
                ->orderByDesc('id')
                ->first();

            $startingNo = max(1, (int) ($customerSetting->starting_no ?? 1));
        }

        return [
            'mode' => $mode,
            'enable_separate_customer_statement_no' => $mode === 'customer' ? 1 : 0,
            'customer_id' => $customerId,
            'starting_no' => $startingNo,
        ];
    }

    public function save(
        int $businessId,
        string $mode,
        int $startingNo,
        ?int $customerId = null
    ): array {
        $mode = $mode === 'general' ? 'general' : 'customer';
        $startingNo = max(1, $startingNo);

        if ($mode === 'customer') {
            $this->assertCustomerBelongsToBusiness($businessId, $customerId);
        }

        return DB::transaction(function () use ($businessId, $mode, $startingNo, $customerId): array {
            $existingConfig = CustomerStatementSetting::query()
                ->where('business_id', $businessId)
                ->where('customer_id', self::CONFIG_CUSTOMER_ID)
                ->orderByDesc('id')
                ->first();

            $configStartingNo = $mode === 'general'
                ? $startingNo
                : max(1, (int) ($existingConfig->starting_no ?? 1));

            $this->persistSingleSetting(
                $businessId,
                self::CONFIG_CUSTOMER_ID,
                $mode === 'customer',
                $configStartingNo
            );

            if ($mode === 'customer' && ! empty($customerId)) {
                $this->persistSingleSetting(
                    $businessId,
                    $customerId,
                    true,
                    $startingNo
                );
            }

            return $this->settings($businessId, $customerId);
        });
    }

    /**
     * Calculate the next number without changing the working statement storage
     * flow. General mode counts all statements in the business; customer mode
     * counts only statements belonging to the selected customer.
     */
    public function nextNumber(int $businessId, int $customerId): array
    {
        $this->assertCustomerBelongsToBusiness($businessId, $customerId);

        $settings = $this->settings($businessId, $customerId);

        $query = CustomerStatement::query()->where('business_id', $businessId);

        if ($settings['mode'] === 'customer') {
            $query->where('customer_id', $customerId);
        }

        $startingNo = max(1, (int) $settings['starting_no']);
        $highestUsed = $startingNo - 1;

        // Existing installations may contain either plain numbers or legacy
        // values such as CU-125. Use the trailing numeric sequence instead of a
        // row count so deletion/imports cannot cause a duplicate next number.
        foreach ($query->pluck('statement_no') as $statementNo) {
            $statementNo = trim((string) $statementNo);
            if (preg_match('/(\d+)$/', $statementNo, $matches) === 1) {
                $highestUsed = max($highestUsed, (int) $matches[1]);
            }
        }

        $next = max($startingNo, $highestUsed + 1);

        return [
            'statement_no' => $next,
            'mode' => $settings['mode'],
            'starting_no' => $startingNo,
        ];
    }

    /**
     * Return whether a numbering code has already been used by saved Customer
     * Statements. A General code covers the whole business. A Customer-wise
     * code covers only the configured customer.
     */
    public function settingUsage(int $businessId, int $settingId): array
    {
        $setting = CustomerStatementSetting::query()
            ->where('business_id', $businessId)
            ->where('id', $settingId)
            ->first();

        if (! $setting) {
            throw new RuntimeException('The Customer Statement numbering code was not found.');
        }

        $customerId = (int) $setting->customer_id;
        $query = CustomerStatement::query()
            ->where('business_id', $businessId);

        if ($customerId > self::CONFIG_CUSTOMER_ID) {
            $query->where('customer_id', $customerId);
        }

        $transactionCount = (int) $query->count();

        return [
            'setting_id' => (int) $setting->id,
            'customer_id' => $customerId,
            'scope' => $customerId === self::CONFIG_CUSTOMER_ID ? 'general' : 'customer',
            'transaction_count' => $transactionCount,
            'is_used' => $transactionCount > 0,
        ];
    }

    /**
     * Delete only an unused numbering code. The server-side check is mandatory
     * even though the page disables the button, because a crafted request must
     * not bypass the transaction protection.
     */
    public function deleteSetting(int $businessId, int $settingId): void
    {
        DB::transaction(function () use ($businessId, $settingId): void {
            $setting = CustomerStatementSetting::query()
                ->where('business_id', $businessId)
                ->where('id', $settingId)
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                throw new RuntimeException('The Customer Statement numbering code was not found.');
            }

            $customerId = (int) $setting->customer_id;
            $usageQuery = CustomerStatement::query()
                ->where('business_id', $businessId);

            if ($customerId > self::CONFIG_CUSTOMER_ID) {
                $usageQuery->where('customer_id', $customerId);
            }

            $transactionCount = (int) $usageQuery->count();
            if ($transactionCount > 0) {
                $scopeLabel = $customerId === self::CONFIG_CUSTOMER_ID
                    ? 'General'
                    : 'Customer-wise';

                throw new RuntimeException(
                    $scopeLabel . ' numbering code cannot be deleted because '
                    . $transactionCount . ' Customer Statement transaction(s) have been recorded.'
                );
            }

            $setting->delete();
        });
    }

    private function persistSingleSetting(
        int $businessId,
        int $customerId,
        bool $customerWise,
        int $startingNo
    ): CustomerStatementSetting {
        $rows = CustomerStatementSetting::query()
            ->where('business_id', $businessId)
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->get();

        /** @var CustomerStatementSetting $setting */
        $setting = $rows->first() ?: new CustomerStatementSetting();
        $setting->business_id = $businessId;
        $setting->customer_id = $customerId;
        $setting->enable_separate_customer_statement_no = $customerWise ? 1 : 0;
        $setting->starting_no = max(1, $startingNo);
        $setting->save();

        // The legacy save action inserted duplicates on every click. Keep the
        // newest authoritative row and remove only exact duplicate scope rows.
        $rows->slice(1)->each(function (CustomerStatementSetting $duplicate): void {
            $duplicate->delete();
        });

        return $setting;
    }

    private function assertCustomerBelongsToBusiness(int $businessId, ?int $customerId): void
    {
        if (empty($customerId)) {
            throw new RuntimeException('Please select a customer for Customer-wise numbering.');
        }

        $exists = Contact::query()
            ->where('business_id', $businessId)
            ->where('id', $customerId)
            ->whereIn('type', ['customer', 'both'])
            ->exists();

        if (! $exists) {
            throw new RuntimeException('The selected customer is not available for this business.');
        }
    }
}
