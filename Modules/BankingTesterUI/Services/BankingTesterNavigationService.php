<?php

namespace Modules\BankingTesterUI\Services;

class BankingTesterNavigationService
{
    public function modules(): array
    {
        return [
            ['slug'=>'insurance','name'=>'Insurance','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/insurance','items'=>['Dashboard','Products','Policies','Premiums','Claims','Reports','Settings']],
            ['slug'=>'microfinance','name'=>'Microfinance','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/microfinance','items'=>['Groups','Members','Loan Products','Applications','Collections','Recovery','Reports','Settings']],
            ['slug'=>'core-deposits','name'=>'Core Deposits','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/core-deposits','items'=>['Savings','Current Accounts','Fixed Deposits','Transactions','Interest','Charges','Reports']],
            ['slug'=>'teller','name'=>'Teller Operations','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/teller','items'=>['Counters','Drawers','Slips','Vault Requests','EOD Balancing','Reports']],
            ['slug'=>'cheque','name'=>'Cheque Management','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/cheque','items'=>['Cheque Books','Leaves','Stop Payments','Clearing','Returned Cheques','Reports']],
            ['slug'=>'atm-debit-card','name'=>'ATM / Debit Card','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/atm-debit-card','items'=>['Products','Inventory','Issue Card','Limits','Hotlist','Transactions','Disputes','Reports']],
            ['slug'=>'internet-banking','name'=>'Internet Banking','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/internet-banking','items'=>['Customer Access','Dashboard','Transfers','Beneficiaries','Bill Payments','Security','Reports']],
            ['slug'=>'mobile-banking','name'=>'Mobile Banking','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/mobile-banking','items'=>['Registration','Devices','Transfers','Beneficiaries','Bill Payments','QR Payments','Notifications','Reports']],
            ['slug'=>'corporate-banking','name'=>'Corporate Banking','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/corporate-banking','items'=>['Corporate Customers','Signatories','Maker Checker','Bulk Payments','Payroll','Reports']],
            ['slug'=>'trade-finance','name'=>'Trade Finance','status'=>'Ready for UI check','url'=>'banking/tester-ui/module/trade-finance','items'=>['LC','Guarantees','Import Bills','Export Bills','Documents','Reports']],
            ['slug'=>'treasury-payments','name'=>'Treasury & Payments Hub','status'=>'Coming Soon - RC1','url'=>'banking/tester-ui/module/treasury-payments','items'=>['Treasury','Payments Queue','Routing','Reconciliation','Reports']],
            ['slug'=>'risk-aml-crm','name'=>'Risk / AML / CRM','status'=>'Coming Soon - RC2','url'=>'banking/tester-ui/module/risk-aml-crm','items'=>['CRM','Risk','AML','Compliance','Reports']],
        ];
    }

    public function moduleBySlug(string $slug): ?array
    {
        foreach ($this->modules() as $module) {
            if ($module['slug'] === $slug) { return $module; }
        }
        return null;
    }

    public function checklist(): array
    {
        return [
            'Sidebar Banking Suite menu is visible',
            'Dashboard opens without Laravel error',
            'All module cards are clickable',
            'Report shell opens',
            'Settings shell opens',
            'Standard toolbar is visible',
            'No broken route / 404 on tester pages',
            'Permission-restricted users can still see tester menu when allowed by admin',
            'Responsive view works on tablet',
        ];
    }
}
