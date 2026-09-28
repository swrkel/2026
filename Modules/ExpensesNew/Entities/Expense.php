<?php

namespace Modules\ExpensesNew\Entities;

class Expense extends BaseModel
{
    protected $table = 'expnew_expenses';

    protected $fillable = [
        'business_id',
        'location_id',
        'expense_no',
        'expense_date',
        'category_id',
        // MA-002 (S-621 #2): the category-driven fields. $fillable is a
        // WHITELIST - without these three the values would be silently
        // dropped on save, with no error to show for it.
        'vat_category_id',
        'sub_category_id',
        'employee_id',
        'payee_id',
        'expense_account_id',
        'accounting_module',
        'total_amount',
        'paid_amount',
        'due_amount',
        'balance_amount',
        'payment_status',
        'payment_method',
        'reference_no',
        'cheque_no',
        'bank_account_id',
        'card_no',
        'notes',
        // MA-002 (Issue 3): Applicable Tax (none|vat) and VAT Invoice (Yes/No).
        'applicable_tax',
        'vat_invoice',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'decimal:4',
        'paid_amount' => 'decimal:4',
        'due_amount' => 'decimal:4',
        'balance_amount' => 'decimal:4',
        'vat_invoice' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function payee()
    {
        return $this->belongsTo(Payee::class, 'payee_id');
    }

    public function account()
    {
        return $this->belongsTo(ExpenseAccount::class, 'expense_account_id');
    }

    public function payments()
    {
        return $this->hasMany(ExpensePayment::class, 'expense_id');
    }

    public function attachments()
    {
        return $this->hasMany(ExpenseAttachment::class, 'expense_id');
    }
}
