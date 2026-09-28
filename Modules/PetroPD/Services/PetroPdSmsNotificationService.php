<?php

namespace Modules\PetroPD\Services;

use App\Business;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\Utils\NotificationUtil;
use Illuminate\Support\Facades\Log;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\PetroPdNotificationTemplate;

class PetroPdSmsNotificationService
{
    protected $notificationUtil;
    protected $businessUtil;

    public function __construct(NotificationUtil $notificationUtil, BusinessUtil $businessUtil)
    {
        $this->notificationUtil = $notificationUtil;
        $this->businessUtil = $businessUtil;
    }

    public function sendPdSettlementSaved(Settlement $settlement): void
    {
        $business_id = $settlement->business_id ?: (request()->session()->get('user.business_id') ?? request()->session()->get('business.id'));
        if (empty($business_id)) {
            Log::warning('PetroPD SMS skipped: business_id missing', ['settlement_id' => $settlement->id ?? null]);
            return;
        }

        $template = PetroPdNotificationTemplate::where('business_id', $business_id)
            ->where('template_for', 'pd_settlements')
            ->where('auto_send_sms', 1)
            ->first();

        if (empty($template) || empty($template->sms_body)) {
            return;
        }

        $message = $this->replaceTags($template->sms_body, $this->buildPdSettlementData($settlement, $business_id));
        $phones = $this->resolvePhones($template, $business_id);
        if (empty($phones)) {
            Log::warning('PetroPD SMS skipped: phone numbers missing', [
                'settlement_id' => $settlement->id,
                'business_id' => $business_id,
            ]);
            return;
        }

        $business = Business::find($business_id);
        $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

        $this->notificationUtil->sendSms([
            'sms_settings' => $sms_settings,
            'mobile_number' => implode(',', $phones),
            'sms_body' => $message,
        ], 'petropd_pd_settlement');
    }

    protected function replaceTags(string $message, array $data): string
    {
        $replace = [];
        foreach ($data as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }
        return strtr($message, $replace);
    }

    protected function resolvePhones($template, $business_id): array
    {
        $raw = trim((string) ($template->phone_nos ?? ''));
        if ($raw === '') {
            $business = Business::find($business_id);
            $sms_settings = $business->sms_settings ?? [];
            if (is_string($sms_settings)) {
                $decoded = json_decode($sms_settings, true);
                $sms_settings = is_array($decoded) ? $decoded : [];
            }
            if (is_array($sms_settings)) {
                $raw = (string) ($sms_settings['msg_phone_nos'] ?? '');
            }
        }

        return array_values(array_filter(array_map('trim', explode(',', str_replace(' ', '', $raw)))));
    }

    protected function buildPdSettlementData(Settlement $settlement, $business_id): array
    {
        $settlement_no = (string) $settlement->settlement_no;
        $pump_operator = null;
        if (!empty($settlement->pump_operator_id)) {
            $pump_operator = PumpOperator::find($settlement->pump_operator_id);
        }

        $business = Business::find($business_id);
        $location = null;
        if (!empty($settlement->location_id)) {
            $location = BusinessLocation::find($settlement->location_id);
        }

        $sumByNo = function ($model, $column = 'amount') use ($business_id, $settlement_no) {
            try {
                return (float) $model::where('business_id', $business_id)
                    ->where('settlement_no', $settlement_no)
                    ->sum($column);
            } catch (\Throwable $e) {
                return 0.0;
            }
        };

        $cash = $sumByNo(SettlementCashPayment::class);
        $cards = $sumByNo(SettlementCardPayment::class);
        $credit_sales = $sumByNo(SettlementCreditSalePayment::class);
        $shortage = $sumByNo(SettlementShortagePayment::class);
        $cheques = $sumByNo(SettlementChequePayment::class);
        $cash_deposit = $sumByNo(SettlementCashDeposit::class);
        $expenses = $sumByNo(SettlementExpensePayment::class);
        $excess = $sumByNo(SettlementExcessPayment::class);
        $loans = $sumByNo(SettlementLoanPayment::class);
        $drawings = $sumByNo(SettlementDrawingPayment::class);
        $total_sale_amount = (float) ($settlement->total_sale_amount ?? $settlement->total_amount ?? ($cash + $cards + $credit_sales));

        return [
            'settlement_no' => $settlement_no,
            'settlement_date' => !empty($settlement->transaction_date) ? $settlement->transaction_date : date('Y-m-d'),
            'pump_operator_name' => $pump_operator->name ?? $pump_operator->pump_operator_name ?? '',
            'settlement_pumps' => '',
            'total_sale_amount' => number_format($total_sale_amount, 2),
            'total_cash' => number_format($cash, 2),
            'total_cards' => number_format($cards, 2),
            'total_credit_sales' => number_format($credit_sales, 2),
            'total_short' => number_format($shortage, 2),
            'total_loans' => number_format($loans, 2),
            'total_cheques' => number_format($cheques, 2),
            'cash_deposit' => number_format($cash_deposit, 2),
            'total_expenses' => number_format($expenses, 2),
            'total_excess' => number_format($excess, 2),
            'loan_payments' => number_format($loans, 2),
            'owners_drawings' => number_format($drawings, 2),
            'mechanical_meter_difference' => number_format((float) ($settlement->mechanical_meter_difference ?? 0), 2),
            'business_name' => $business->name ?? '',
            'location_name' => $location->name ?? '',
        ];
    }
}
