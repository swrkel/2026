<?php

namespace Modules\PumperDashboard\Services;

use App\Business;
use App\Contact;
use App\NotificationTemplate;
use App\Product;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * LA-1169 #5: SMS when a credit sale is added from the Pumper Dashboard.
 *
 * The customer record carries a "Credit Notification" setting
 * (contacts.credit_notification) with these values:
 *
 *     'pumper_dashboard' -> notify as soon as the pumper records the credit sale
 *     'settlement'       -> notify when the PD settlement is saved
 *     'customer_bill'    -> notify at billing (handled elsewhere)
 *     '' / null          -> do not notify
 *
 * The settlement branch already existed in PetroPD. The pumper_dashboard branch
 * did not exist anywhere - this module had no SMS code of any kind - so nothing
 * was ever sent for those customers.
 *
 * The message is built from the same 'credit_sale' notification template and the
 * same placeholders the settlement branch uses, so a customer receives the same
 * wording whichever setting they are on.
 *
 * NOTHING HERE IS ALLOWED TO BREAK A SALE. Every failure is caught and logged.
 * A credit sale must still save even if the SMS gateway is down or misconfigured.
 */
class CreditSaleSmsNotifier
{
    public function __construct(
        private readonly BusinessUtil $businessUtil,
        private readonly ProductUtil $productUtil
    ) {
    }

    /**
     * Send the "credit sale added" SMS for one credit sale row, when the customer
     * is set to be notified from the Pumper Dashboard.
     *
     * @param  object  $creditSalePayment  the saved settlement_credit_sale_payments row
     */
    public function notifyForPumperCreditSale(int $businessId, $creditSalePayment): void
    {
        try {
            if (empty($creditSalePayment) || empty($creditSalePayment->customer_id)) {
                return;
            }

            $contact = Contact::find($creditSalePayment->customer_id);

            if (empty($contact)) {
                return;
            }

            // Only customers configured for Pumper Dashboard notification.
            if ((string) $contact->credit_notification !== 'pumper_dashboard') {
                return;
            }

            /*
             * MA-002 (S-622) added contacts.need_to_send_sms, defaulting to 1.
             * Honour it here so switching a customer off actually stops the SMS.
             * Guarded with hasColumn so an install without the migration still
             * behaves as before.
             */
            if (Schema::hasColumn('contacts', 'need_to_send_sms')
                && (int) ($contact->need_to_send_sms ?? 1) !== 1) {
                return;
            }

            if (empty($contact->mobile) && empty($contact->alternate_number)) {
                return;
            }

            $business = Business::find($businessId);

            if (empty($business)) {
                return;
            }

            $smsSettings = empty($business->sms_settings)
                ? $this->businessUtil->defaultSmsSettings()
                : $business->sms_settings;

            if (empty($smsSettings)) {
                return;
            }

            $template = NotificationTemplate::where('business_id', $businessId)
                ->where('template_for', 'credit_sale')
                ->first();

            if (empty($template) || empty($template->sms_body)) {
                // No template configured - nothing meaningful to send.
                return;
            }

            $message = $this->buildMessage($template->sms_body, $business, $contact, $creditSalePayment);

            $this->dispatch($smsSettings, $contact, $message);
        } catch (\Throwable $e) {
            // Never let a notification failure roll back or block the sale.
            Log::warning('LA-1169: pumper dashboard credit sale SMS failed', [
                'business_id' => $businessId,
                'credit_sale_id' => $creditSalePayment->id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fill the template placeholders. Same set the settlement branch uses.
     */
    private function buildMessage(string $body, $business, $contact, $creditSalePayment): string
    {
        $finalTotal = (float) ($creditSalePayment->amount ?? 0)
            - (float) ($creditSalePayment->total_discount ?? 0);

        $replacements = [
            '{business_name}' => $business->name,
            '{contact_name}' => $contact->name,
            '{total_amount}' => $this->productUtil->num_f($finalTotal),
            // Nothing is paid at the moment a credit sale is recorded.
            '{paid_amount}' => $this->productUtil->num_f(0),
            '{due_amount}' => $this->productUtil->num_f($finalTotal),
            '{invoice_number}' => $creditSalePayment->bill_number
                ?? $creditSalePayment->order_number
                ?? '',
            '{transaction_date}' => $creditSalePayment->order_date ?? date('Y-m-d'),
            '{customer_reference}' => $creditSalePayment->customer_reference ?? '',
            '{vehicle_no}' => $creditSalePayment->customer_reference ?? '',
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $body);

        // The settlement branch appends the product and quantity - match it.
        $product = ! empty($creditSalePayment->product_id)
            ? Product::find($creditSalePayment->product_id)
            : null;

        if (! empty($product)) {
            $message .= PHP_EOL
                . 'Product Sold: ' . ucfirst($product->name) . PHP_EOL
                . 'Quantity: ' . $this->productUtil->num_f($creditSalePayment->qty ?? 0);
        }

        return $message;
    }

    /**
     * Send to the customer's mobile, and to the alternate number when set.
     */
    private function dispatch(array $smsSettings, $contact, string $message): void
    {
        if (! empty($contact->mobile)) {
            $this->businessUtil->sendSms([
                'sms_settings' => $smsSettings,
                'mobile_number' => $contact->mobile,
                'sms_body' => $message,
            ], $contact, 'credit_sale');
        }

        if (! empty($contact->alternate_number)) {
            $this->businessUtil->sendSms([
                'sms_settings' => $smsSettings,
                'mobile_number' => $contact->alternate_number,
                'sms_body' => $message,
            ], $contact, 'credit_sale');
        }
    }
}
