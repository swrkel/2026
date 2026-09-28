<?php

namespace Modules\Vat\Entities;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Separation step 3 (see document 5-18, "Suggested sequence").
 *
 * A VAT-owned model over the SHARED `contacts` table.
 *
 *
 * NAMING - READ THIS FIRST
 *
 * This is NOT VatContact. Modules\Vat\Entities\VatContact already exists and
 * maps to `vat_contacts`, a separate VAT-only contact list used by
 * VatContactController. The two are different data sets:
 *
 *     VatContact     -> vat_contacts  (VAT module's own contact records)
 *     SharedContact  -> contacts      (the system-wide contact master)
 *
 *
 * WHY THIS EXISTS
 *
 * Thirteen controllers imported App\Contact. When the Contact module is retired
 * in favour of Customers, code bound to that class breaks - but the TABLE is not
 * going anywhere, because Customers uses the same `contacts` table. So the
 * dependency to remove is on the core CLASS, not on the data. Category B in the
 * inventory: shared data wrapped, never duplicated.
 *
 * Adoption is one line per controller:
 *
 *     use App\Contact;
 *   → use Modules\Vat\Entities\SharedContact as Contact;
 *
 *
 * THE DROPDOWN HELPERS ARE A TRANSCRIPTION, NOT A REWRITE
 *
 * These decide WHICH parties appear in invoice dropdowns and how they are
 * labelled, so they are copied from App\Contact deliberately, signature for
 * signature. An earlier draft of this file was written from inference and got
 * four things wrong, every one of which would have shipped a silent defect:
 *
 *   1. contactDropdown()'s SECOND parameter is $exclude_default, not
 *      $prepend_none. The module calls it as
 *      contactDropdown($business_id, false, true, true, 'customer'), so
 *      guessing the order inverted "prepend none" and injected an unwanted
 *      "All" entry.
 *   2. Active contacts are filtered on `active` = 1. The guess used
 *      `contact_status`, a different column - so every dropdown would have been
 *      unfiltered or empty depending on the schema.
 *   3. $append_id defaults to TRUE and appends the contact_id to the label:
 *      "ACME Ltd (C-0042)". The guess appended supplier_business_name instead,
 *      changing every label on every screen.
 *   4. contactDropdown() with a type filters where('type', $type) EXACTLY,
 *      while customersDropdown() uses whereIn('type', ['customer','both']).
 *      They genuinely differ; a contact of type 'both' appears in one and not
 *      the other.
 *
 * These helpers return an Eloquent Collection from pluck(), as core does - not
 * an array - because callers chain Collection methods onto the result.
 *
 * If core's contact model changes, this must be revisited. That is the standing
 * cost of separation, recorded here so the next person sees it.
 */
class SharedContact extends Model
{
    use SoftDeletes;

    protected $table = 'contacts';

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];

    /**
     * Core sets a default customer_group_id from
     * App\Interfaces\CommonConstants::GENERAL_CUSTOMER_GROUP via $attributes.
     *
     * That value is applied here through the constructor rather than a literal,
     * because hard-coding a number guessed from context would put new contacts
     * in the wrong group. If the interface is absent the default is simply not
     * applied, which is the same behaviour as omitting the column.
     */
    public function __construct(array $attributes = [])
    {
        if (interface_exists(\App\Interfaces\CommonConstants::class)
            && defined(\App\Interfaces\CommonConstants::class . '::GENERAL_CUSTOMER_GROUP')) {
            $this->attributes['customer_group_id'] = \App\Interfaces\CommonConstants::GENERAL_CUSTOMER_GROUP;
        }

        parent::__construct($attributes);
    }

    public function scopeActive($query)
    {
        return $query->where('active', '1');
    }

    public function scopeOnlySuppliers($query)
    {
        return $query->whereIn('contacts.type', ['supplier', 'both']);
    }

    public function scopeOnlyCustomers($query)
    {
        return $query->whereIn('contacts.type', ['customer', 'both']);
    }

    public function scopeOnlyActive($query)
    {
        return $query->where('contacts.active', 1);
    }

    /**
     * Contacts for a select list.
     *
     * Transcribed from App\Contact::contactDropdown(). Note the parameter order:
     * $exclude_default comes SECOND.
     */
    public static function contactDropdown($business_id, $exclude_default = false, $prepend_none = true, $append_id = true, $type = null)
    {
        $query = static::where('business_id', $business_id)
            ->where('active', 1);

        if ($exclude_default) {
            $query->where('is_default', 0);
        }

        // Exact type match here, unlike customersDropdown() below which also
        // accepts 'both'. This difference is in core and is preserved.
        if (!empty($type) && $type == 'supplier') {
            $query->where('type', 'supplier');
        }
        if (!empty($type) && $type == 'customer') {
            $query->where('type', 'customer');
        }

        if ($append_id) {
            $query->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"),
                'id'
            );
        } else {
            $query->select(
                'id',
                DB::raw("IF (supplier_business_name IS not null, CONCAT(name, ' (', supplier_business_name, ')'), name) as supplier")
            );
        }

        $contacts = $query->pluck('supplier', 'id');

        if ($prepend_none) {
            $contacts = $contacts->prepend(__('lang_v1.none'), '');
        }

        return $contacts;
    }

    /**
     * Suppliers for a select list.
     *
     * Transcribed from App\Contact::suppliersDropdown().
     */
    public static function suppliersDropdown($business_id, $prepend_none = true, $append_id = true)
    {
        $all_contacts = static::where('business_id', $business_id)
            ->whereIn('type', ['supplier', 'both'])
            ->where('active', 1);

        if ($append_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"),
                'id'
            );
        } else {
            $all_contacts->select(
                'id',
                DB::raw("CONCAT(name, ' (', supplier_business_name, ')') as supplier")
            );
        }

        $suppliers = $all_contacts->pluck('supplier', 'id');

        if ($prepend_none) {
            $suppliers = $suppliers->prepend(__('lang_v1.none'), '');
        }

        return $suppliers;
    }

    /**
     * Customers for a select list.
     *
     * Transcribed from App\Contact::customersDropdown(). The label format here
     * differs from the two helpers above - name and contact_id only, with no
     * supplier_business_name - which is core's behaviour, not an oversight.
     */
    public static function customersDropdown($business_id, $prepend_none = true, $append_id = true)
    {
        $all_contacts = static::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1);

        if ($append_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' (', contact_id, ')')) AS customer"),
                'id'
            );
        } else {
            $all_contacts->select('id', DB::raw("name as customer"));
        }

        $customers = $all_contacts->pluck('customer', 'id');

        if ($prepend_none) {
            $customers = $customers->prepend(__('lang_v1.none'), '');
        }

        return $customers;
    }

    /**
     * Payees for a select list.
     *
     * Transcribed from App\Contact::payeeDropdown(). Not called by the VAT
     * module today, but included so the alias stays a complete drop-in if a
     * screen starts using it.
     */
    public static function payeeDropdown($business_id, $exclude_default = false, $prepend_none = true, $append_id = true)
    {
        $query = static::where('business_id', $business_id)
            ->where('active', 1);

        if ($exclude_default) {
            $query->where('is_default', 0);
        }

        $query->where('is_payee', 1);

        if ($append_id) {
            $query->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', name, CONCAT(name, ' - ', COALESCE(supplier_business_name, ''), '(', contact_id, ')')) AS supplier"),
                'id'
            );
        } else {
            $query->select(
                'id',
                DB::raw("IF (supplier_business_name IS not null, CONCAT(name, ' (', supplier_business_name, ')'), name) as supplier")
            );
        }

        $contacts = $query->pluck('supplier', 'id');

        if ($prepend_none) {
            $contacts = $contacts->prepend(__('lang_v1.none'), '');
        }

        return $contacts;
    }

    /**
     * Contact type list. Transcribed from App\Contact::typeDropdown().
     */
    public static function typeDropdown($prepend_all = false)
    {
        $types = [];

        if ($prepend_all) {
            $types[''] = __('lang_v1.all');
        }

        $types['customer'] = __('report.customer');
        $types['supplier'] = __('report.supplier');
        $types['both'] = __('lang_v1.both_supplier_customer');

        return $types;
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }
}
