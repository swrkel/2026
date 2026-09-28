<?php

namespace App\Utils;

use App\AccountTransaction;
use App\Account;
use App\AccountType;
use Modules\Fleet\Entities\Fleet;
use App\Business;
use App\BusinessLocation;
use App\Utils\Util;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\Currency;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Exceptions\PurchaseSellMismatch;
use App\Http\Controllers\Ecom\ContactController;
use App\InvoiceScheme;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\StockAdjustmentLine;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\PaymentMethod;
use App\System;;

use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Entities\TankPurchaseLine;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertyBlock;
use Modules\Property\Entities\PropertySellLine;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Petro\Entities\DipReading;
use Modules\Petro\Entities\PumpOperatorCommission;
use App\Variation_store_detail;
use App\ExpenseCategory;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;

use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;

use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartnerCommission;
use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use App\SmsLog;

use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;

use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsReminderSetting;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use App\ProductVariation;
use App\Unit;
use App\Brands;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\DailyVoucher;
use App\Http\Controllers\SellController;
use Illuminate\Http\Request;

class TransactionUtil extends Util
{

    /*
     * MA-002: this class was 11,666 lines with 180 methods. Its work now lives
     * in the traits below, grouped by what each does.
     *
     * SAME CLASS AT RUNTIME. TransactionUtil keeps its name, namespace and
     * every method, so all 485 call sites across the system resolve exactly as
     * before and nothing outside app/Utils/TransactionUtil/ changed.
     */
    protected $moduleUtil;
    protected $contactUtil;
    public $petro_classes;
    public $bakery_classes;
    public $payment_transaction_types;
    public $outstanding_payment_types;

    use \App\Utils\TransactionUtil\BuildsReceipts;
    use \App\Utils\TransactionUtil\ReportsStock;
    use \App\Utils\TransactionUtil\HandlesPumpOperators;
    use \App\Utils\TransactionUtil\CalculatesTax;
    use \App\Utils\TransactionUtil\HandlesPayments;
    use \App\Utils\TransactionUtil\PostsLedger;
    use \App\Utils\TransactionUtil\HandlesTransactions;
    use \App\Utils\TransactionUtil\ReportsTotals;
    use \App\Utils\TransactionUtil\ProvidesUtilityHelpers;

    public function __construct(ModuleUtil $moduleUtil, ContactUtil $contactUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->contactUtil = $contactUtil;
        $this->petro_classes = array(
            'Modules\Petro\Entities\CurrentMeter',
            'Modules\Petro\Entities\DailyVoucher',
            'Modules\Petro\Entities\DailyCollection',
            'Modules\Petro\Entities\DailyCard',
            'Modules\Petro\Entities\CustomerPayment',
            'Modules\Petro\Entities\CustomerBillVatPrefix',
            'Modules\Petro\Entities\DailyVoucherItem',
            'Modules\Petro\Entities\IssueCustomerBillDetail',
            'Modules\Petro\Entities\IssueCustomerBill',
            'Modules\Petro\Entities\FuelTank',
            'Modules\Petro\Entities\DipResetting',
            'Modules\Petro\Entities\DipReading',
            'Modules\Superadmin\Entities\TankDipChart',
            'Modules\Superadmin\Entities\TankDipChartDetail',
            'Modules\Petro\Entities\PumperDayEntry',
            'Modules\Petro\Entities\Pump',
            'Modules\Petro\Entities\OtherSale',
            'Modules\Petro\Entities\OtherIncome',
            'Modules\Petro\Entities\OpeningMeter',
            'Modules\Petro\Entities\MeterSale',
            'Modules\Petro\Entities\MeterResetting',
            'Modules\Petro\Entities\IssueCustomerBillWithVatDetail',
            'Modules\Petro\Entities\IssueCustomerBillWithVat',
            'Modules\Petro\Entities\Settlement',
            'Modules\Petro\Entities\PumpOperatorPreAssignment',
            'Modules\Petro\Entities\PumpOperatorPayment',
            'Modules\Petro\Entities\PumpOperatorCommission',
            'Modules\Petro\Entities\PumpOperatorAssignment',
            'Modules\Petro\Entities\PumpOperator',
            'Modules\Petro\Entities\SettlementDrawingPayment',
            'Modules\Petro\Entities\SettlementCustomerLoan',
            'Modules\Petro\Entities\SettlementCreditSalePayment',
            'Modules\Petro\Entities\SettlementChequePayment',
            'Modules\Petro\Entities\SettlementCashPayment',
            'Modules\Petro\Entities\SettlementCashDeposit',
            'Modules\Petro\Entities\SettlementCardPayment',
            'Modules\Petro\Entities\UnloadStock',
            'Modules\Petro\Entities\TankTransfer',
            'Modules\Petro\Entities\TanksTransactionDetail',
            'Modules\Petro\Entities\TankSellLine',
            'Modules\Petro\Entities\TankPurchaseLine',
            'Modules\Petro\Entities\SettlementShortagePayment',
            'Modules\Petro\Entities\SettlementLoanPayment',
            'Modules\Petro\Entities\SettlementExpensePayment',
            'Modules\Petro\Entities\SettlementExcessPayment',
            'Modules\Petro\Entities\DayEnd',
            'App\Settlement',

        );

        $this->bakery_classes = array(
            'Modules/Bakery/Entities/BakeryUser',
            'Modules/Bakery/Entities/BakeryRoute',
            'Modules/Bakery/Entities/BakeryProduct',
            'Modules/Bakery/Entities/BakeryOpeningBalance',
            'Modules/Bakery/Entities/BakeryLoadingReturnProduct',
            'Modules/Bakery/Entities/BakeryLoadingReturn',
            'Modules/Bakery/Entities/BakeryLoadingProduct',
            'Modules/Bakery/Entities/BakeryLoading',
            'Modules/Bakery/Entities/BakeryInvoiceNumber',
            'Modules/Bakery/Entities/BakeryFleet',
            'Modules/Bakery/Entities/BakeryDriver'
        );



        $this->payment_transaction_types = array(
            'advance_payment' => 'Advance Payment',
            'airline_ticket' => 'Airline Ticket',
            'cheque_opening_balance' => 'Cheque Opening Balance',
            'direct_customer_loan' => 'Customer Loan',
            // 'expense' => 'Expense',
            'fleet_opening_balance' => 'Fleet Opening Balance',
            'opening_balance' => 'Opening Balance',
            // 'property_purchase' => 'Property Purchase',
            // 'purchase' => 'Purchase',
            'purchase_return' => 'Purchase Return',
            'route_operation' => 'ROute Operation',
            'security_deposit' => 'Security Deposit',
            // 'security_deposit_refund' => 'Security Deposit Refund',
            'sell' => 'Sell',
            'sell_return' => 'Sale Return',
            'settlement' => 'Settlement',
            'shipment' => 'Shipment'
        );

        $this->outstanding_payment_types = array(
            'advance_payment',
            'airline_ticket',
            'cheque_opening_balance',
            'direct_customer_loan',
            'fleet_opening_balance',
            'opening_balance', // Included to show payments against opening balances
            'purchase_return',
            'route_operation',
            'security_deposit',
            'sell',
            'settlement',
            'shipment'
        );
    }
}
