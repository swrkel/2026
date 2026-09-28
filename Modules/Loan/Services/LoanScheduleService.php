<?php

namespace Modules\Loan\Services;

use Carbon\Carbon;

use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanRepaymentSchedule;

class LoanScheduleService
{
    /*
    |--------------------------------------------------------------------------
    | Generate Enterprise Schedules
    |--------------------------------------------------------------------------
    */

    public function generateSchedules($loan)
    {
        /*
        |--------------------------------------------------------------------------
        | Clear Existing Schedules
        |--------------------------------------------------------------------------
        */

        LoanRepaymentSchedule::where(
            'loan_id',
            $loan->id
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | Loan Values
        |--------------------------------------------------------------------------
        */

        $principal =
            (float) $loan->principal_amount;

        $interest_rate =
            (float) $loan->interest_rate;

        $installments =
            (int) $loan->installment_count;

        $interest_method =
            $loan->interest_type ?? 'flat';

        $repayment_frequency =
            $loan->installment_frequency ?? 'monthly';

        $grace_period =
            (int) ($loan->grace_period ?? 0);

        $disbursement_date =
            Carbon::parse(
                $loan->disbursement_date
            );

        /*
        |--------------------------------------------------------------------------
        | Validation Protection
        |--------------------------------------------------------------------------
        */

        if (
            $principal <= 0 ||
            $installments <= 0
        ) {

            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Generate Schedule By Interest Method
        |--------------------------------------------------------------------------
        */

        switch ($interest_method) {

            case 'reducing':

                $this->generateReducingBalanceSchedule(
                    $loan,
                    $principal,
                    $interest_rate,
                    $installments,
                    $repayment_frequency,
                    $grace_period,
                    $disbursement_date
                );

                break;

            case 'compound':

                $this->generateCompoundInterestSchedule(
                    $loan,
                    $principal,
                    $interest_rate,
                    $installments,
                    $repayment_frequency,
                    $grace_period,
                    $disbursement_date
                );

                break;

            default:

                $this->generateFlatInterestSchedule(
                    $loan,
                    $principal,
                    $interest_rate,
                    $installments,
                    $repayment_frequency,
                    $grace_period,
                    $disbursement_date
                );

                break;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | FLAT INTEREST
    |--------------------------------------------------------------------------
    */

    private function generateFlatInterestSchedule(
        $loan,
        $principal,
        $interest_rate,
        $installments,
        $repayment_frequency,
        $grace_period,
        $disbursement_date
    ) {

        $total_interest =
            ($principal * $interest_rate / 100);

        $total_payable =
            $principal + $total_interest;

        $installment_amount =
            $total_payable / $installments;

        $principal_per_installment =
            $principal / $installments;

        $interest_per_installment =
            $total_interest / $installments;

        $remaining_balance =
            $principal;

        for ($i = 1; $i <= $installments; $i++) {

            $due_date =
                $this->calculateDueDate(
                    $disbursement_date,
                    $i,
                    $repayment_frequency,
                    $grace_period
                );

            $remaining_balance =
                $remaining_balance -
                $principal_per_installment;

            LoanRepaymentSchedule::create([

                'business_id' =>
                    $loan->business_id,

                'loan_id' =>
                    $loan->id,

                'installment_no' =>
                    $i,

                'due_date' =>
                    $due_date,

                'principal_amount' =>
                    round(
                        $principal_per_installment,
                        2
                    ),

                'interest_amount' =>
                    round(
                        $interest_per_installment,
                        2
                    ),

                'penalty_amount' =>
                    0,

                'installment_amount' =>
                    round(
                        $installment_amount,
                        2
                    ),

                'paid_amount' =>
                    0,

                'balance_amount' =>
                    round(
                        $remaining_balance,
                        2
                    ),

                'status' =>
                    'pending'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | REDUCING BALANCE EMI
    |--------------------------------------------------------------------------
    */

    private function generateReducingBalanceSchedule(
        $loan,
        $principal,
        $interest_rate,
        $installments,
        $repayment_frequency,
        $grace_period,
        $disbursement_date
    ) {

        /*
        |--------------------------------------------------------------------------
        | Monthly Interest Rate
        |--------------------------------------------------------------------------
        */

        $monthly_rate =
            ($interest_rate / 100) / 12;

        /*
        |--------------------------------------------------------------------------
        | EMI Formula
        |--------------------------------------------------------------------------
        */

        if ($monthly_rate > 0) {

            $emi =
                (
                    $principal *
                    $monthly_rate *
                    pow(
                        1 + $monthly_rate,
                        $installments
                    )
                ) /
                (
                    pow(
                        1 + $monthly_rate,
                        $installments
                    ) - 1
                );

        } else {

            $emi =
                $principal / $installments;
        }

        $remaining_balance =
            $principal;

        for ($i = 1; $i <= $installments; $i++) {

            $interest_component =
                $remaining_balance *
                $monthly_rate;

            $principal_component =
                $emi -
                $interest_component;

            $remaining_balance =
                $remaining_balance -
                $principal_component;

            if ($remaining_balance < 0) {

                $remaining_balance = 0;
            }

            $due_date =
                $this->calculateDueDate(
                    $disbursement_date,
                    $i,
                    $repayment_frequency,
                    $grace_period
                );

            LoanRepaymentSchedule::create([

                'business_id' =>
                    $loan->business_id,

                'loan_id' =>
                    $loan->id,

                'installment_no' =>
                    $i,

                'due_date' =>
                    $due_date,

                'principal_amount' =>
                    round(
                        $principal_component,
                        2
                    ),

                'interest_amount' =>
                    round(
                        $interest_component,
                        2
                    ),

                'penalty_amount' =>
                    0,

                'installment_amount' =>
                    round(
                        $emi,
                        2
                    ),

                'paid_amount' =>
                    0,

                'balance_amount' =>
                    round(
                        $remaining_balance,
                        2
                    ),

                'status' =>
                    'pending'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | COMPOUND INTEREST
    |--------------------------------------------------------------------------
    */

    private function generateCompoundInterestSchedule(
        $loan,
        $principal,
        $interest_rate,
        $installments,
        $repayment_frequency,
        $grace_period,
        $disbursement_date
    ) {

        $periodic_rate =
            ($interest_rate / 100);

        $future_value =
            $principal *
            pow(
                (1 + $periodic_rate),
                $installments
            );

        $installment_amount =
            $future_value / $installments;

        $remaining_balance =
            $future_value;

        for ($i = 1; $i <= $installments; $i++) {

            $interest_component =
                $remaining_balance *
                $periodic_rate;

            $principal_component =
                $installment_amount -
                $interest_component;

            $remaining_balance =
                $remaining_balance -
                $installment_amount;

            if ($remaining_balance < 0) {

                $remaining_balance = 0;
            }

            $due_date =
                $this->calculateDueDate(
                    $disbursement_date,
                    $i,
                    $repayment_frequency,
                    $grace_period
                );

            LoanRepaymentSchedule::create([

                'business_id' =>
                    $loan->business_id,

                'loan_id' =>
                    $loan->id,

                'installment_no' =>
                    $i,

                'due_date' =>
                    $due_date,

                'principal_amount' =>
                    round(
                        $principal_component,
                        2
                    ),

                'interest_amount' =>
                    round(
                        $interest_component,
                        2
                    ),

                'penalty_amount' =>
                    0,

                'installment_amount' =>
                    round(
                        $installment_amount,
                        2
                    ),

                'paid_amount' =>
                    0,

                'balance_amount' =>
                    round(
                        $remaining_balance,
                        2
                    ),

                'status' =>
                    'pending'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ENTERPRISE DUE DATE ENGINE
    |--------------------------------------------------------------------------
    */

    private function calculateDueDate(
        $disbursement_date,
        $installment_no,
        $repayment_frequency,
        $grace_period = 0
    ) {

        $date =
            $disbursement_date
                ->copy()
                ->addDays($grace_period);

        switch ($repayment_frequency) {

            case 'daily':

                return $date
                    ->addDays($installment_no);

            case 'weekly':

                return $date
                    ->addWeeks($installment_no);

            case 'biweekly':

                return $date
                    ->addDays(
                        $installment_no * 14
                    );

            case 'quarterly':

                return $date
                    ->addMonths(
                        $installment_no * 3
                    );

            case 'annually':

                return $date
                    ->addYears($installment_no);

            default:

                return $date
                    ->addMonths($installment_no);
        }
    }
}