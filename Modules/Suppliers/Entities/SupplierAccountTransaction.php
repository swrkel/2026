<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * MA-002: SoftDeletes added.
 *
 * This is a standalone copy on the shared `account_transactions` table.
 * Core's App\AccountTransaction uses SoftDeletes; this copy did not, so
 * queries through it INCLUDED rows that had been deleted, and ->delete()
 * would have removed a ledger row permanently.
 *
 * ITS ONLY CONSUMER
 *     Modules/Suppliers/Utils/Financial/SupplierPaymentPostingGuard::alreadyPosted()
 *         ->where('business_id', ...)->where('ref_no', ...)
 *         ->where('type', ...)->exists()
 *
 * a duplicate-posting guard. Including deleted rows made it TOO STRICT: a
 * supplier payment that had been posted and later deleted would still match
 * the tombstone, and re-posting it would be refused with no explanation.
 *
 * WHY IT WAS SAFE TO ADD THIS NOW
 * Checked against the tenant database first. There are 204 soft-deleted rows
 * on that table, but NOT ONE of them carries a ref_no - and ref_no is exactly
 * what the guard matches on. There were zero deleted (ref_no, type) pairs
 * that did not also exist live, so the guard could not match a deleted row
 * either way. Adding the trait therefore changes no result that runs today;
 * it only closes the hole before a deleted payment WITH a ref_no ever
 * appears.
 *
 * NOTE FOR FUTURE WORK: any query through this model now excludes deleted
 * rows. If one is ever needed that must see them, use withTrashed() - the
 * same as anywhere else in the system that uses core's model.
 */
class SupplierAccountTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'account_transactions';
    protected $guarded = ['id'];
}
