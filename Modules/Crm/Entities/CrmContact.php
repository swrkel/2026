<?php

namespace Modules\Crm\Entities;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Contact;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContact extends Contact
{

    /*
     * MA-002: activity logging restored to match core.
     *
     * This model writes to `contacts`, the same table as App\Contact,
     * which logs every create and update. This copy did not - so a record
     * created or edited through the Crm module left NO AUDIT TRAIL, while the
     * identical record touched through core's screens did.
     *
     * That is the same defect already found and fixed on ContactLedger,
     * Journal and FixedAsset earlier in this project: a module copy of a core
     * model that silently lost its parent's behaviour.
     *
     * $logName is deliberately 'Contact' - the SAME value core uses, not a
     * module-specific one. The activity screens filter by log name, so a
     * different value here would file these entries somewhere nobody looks.
     *
     * Applied only because this model genuinely creates rows. The read-only
     * copies on the same tables - Airline/AirlineCustomers,
     * Suppliers/SupplierContact, Suppliers/SupplierContactGroup - are
     * deliberately left alone: logging a model that never writes adds risk and
     * produces nothing.
     */
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logName = 'Contact';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }


    /*
     * MA-002: SoftDeletes restored.
     *
     * This model is a standalone copy on the shared `contacts` table. Core's
     * App\Contact uses SoftDeletes; this copy did not. Two consequences:
     *
     *   1. ->delete() through this class was a HARD delete. The row was
     *      physically removed, while the rest of the system expects a
     *      deleted contacts row to be recoverable and excluded by scope.
     *   2. Queries through this class INCLUDED soft-deleted rows, so records
     *      deleted elsewhere could still appear in listings.
     *
     * SAFE TO APPLY NOW: `contacts` currently contains ZERO rows with
     * deleted_at set in the tenant database I was given, so adding the trait
     * cannot change the result of any existing query. It only prevents
     * future hard deletes and future stale reads.
     *
     * account_transactions was DELIBERATELY EXCLUDED from this change - it
     * has 204 soft-deleted rows, so adding the trait there WOULD change
     * results and needs to be agreed and checked first.
     */
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contacts';

    /**
     * The member that assigned to the lead.
     */
    public function leadUsers()
    {
        return $this->belongsToMany(\App\User::class, 'crm_lead_users', 'contact_id', 'user_id');
    }

    /**
     * get source for contact
     */
    public function Source()
    {
        return $this->belongsTo(\App\Category::class, 'crm_source');
    }

    /**
     * get life_stage for contact
     */
    public function lifeStage()
    {
        return $this->belongsTo(\App\Category::class, 'crm_life_stage');
    }

    /**
     * Return list of lead dropdown for a business
     *
     * @param $business_id int
     * @param $prepend_none = true (boolean)
     * @return array users
     */
    public function scopeActive($query)
    {
        return $query->where('active', '1');
    }
    public static function leadsDropdown($business_id, $prepend_none = true, $append_id = true)
    {
        $all_contacts = CrmContact::where('business_id', $business_id)
                        ->where('type', 'lead')
                        ->active();

        if ($append_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', CONCAT( COALESCE(supplier_business_name, ''), ' - ', name), CONCAT(COALESCE(supplier_business_name, ''), ' - ', name, ' (', contact_id, ')')) AS leads"),
                'id'
                );
        } else {
            $all_contacts->select('id', DB::raw('name as leads'));
        }

        $can_access_all_leads = auth()->user()->can('crm.access_all_leads');
        $can_access_own_leads = auth()->user()->can('crm.access_own_leads');

        if (! $can_access_all_leads && $can_access_own_leads) {
            $all_contacts->OnlyOwnLeads();
        }

        $leads = $all_contacts->pluck('leads', 'id');

        //Prepend none
        if ($prepend_none) {
            $leads = $leads->prepend(__('lang_v1.none'), '');
        }

        return $leads;
    }

    public static function getCustomerAndLeadsDropdown($business_id)
    {
        $leads = CrmContact::leadsDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false)->toArray();

        foreach ($customers as $key => $value) {
            $customers[$key] = $value.' ('.__('contact.customer').')';
        }
        foreach ($leads as $key => $value) {
            $customers[$key] = $value.' ('.__('crm::lang.lead').')';
        }

        return $customers;
    }

    public static function contactsDropdownForLogin($business_id, $append_contact_id = true)
    {
        $all_contacts = Contact::where('business_id', $business_id)
                        ->active();

        if ($append_contact_id) {
            $all_contacts->select(
                DB::raw("IF(contact_id IS NULL OR contact_id='', CONCAT( COALESCE(supplier_business_name, ''), ' - ', name), CONCAT(COALESCE(supplier_business_name, ''), ' - ', name, ' (', contact_id, ')')) AS contacts"),
                'id'
                );
        } else {
            $all_contacts->select('id', DB::raw('name as contacts'));
        }

        $contacts = $all_contacts->pluck('contacts', 'id');

        return $contacts;
    }

    public static function createNewLead($input, $assigned_to)
    {
        //Check Contact id
        $count = 0;
        if (! empty($input['contact_id'])) {
            $count = CrmContact::where('business_id', $input['business_id'])
                      ->where('contact_id', $input['contact_id'])
                      ->count();
        }

        if ($count == 0) {
            //Update reference count
            $commonUtil = new \App\Utils\Util;
            $ref_count = $commonUtil->setAndGetReferenceCount('contacts', $input['business_id']);

            if (empty($input['contact_id'])) {
                //Generate reference number
                $input['contact_id'] = $commonUtil->generateReferenceNumber('contacts', $ref_count, $input['business_id']);
            }

            $contact = CrmContact::create($input);

            $contact->leadUsers()->sync($assigned_to);

            //update user contact access also
            if (! empty($assigned_to)) {
                $lead_contact = Contact::find($contact->id);
                $lead_contact->userHavingAccess()->sync($assigned_to);
            }

            return $contact;
        } else {
            throw new \Exception('Error Processing Request', 1);
        }
    }

    public static function updateLead($id, $input, $assigned_to)
    {
        $business_id = auth()->user()->business_id;
        //Check Contact id
        $count = 0;
        if (! empty($input['contact_id'])) {
            $count = CrmContact::where('business_id', $business_id)
                        ->where('contact_id', $input['contact_id'])
                        ->where('id', '!=', $id)
                        ->count();
        }

        if ($count == 0) {
            $query = CrmContact::where('business_id', $business_id);

            if (! auth()->user()->can('crm.access_all_leads') && auth()->user()->can('crm.access_own_leads')) {
                $query->where(function ($qry) {
                    $qry->whereHas('leadUsers', function ($q) {
                        $q->where('user_id', auth()->user()->id);
                    })->orWhere('created_by', auth()->user()->id);
                });
            }
            $contact = $query->findOrFail($id);

            $contact->update($input);

            $contact->leadUsers()->sync($assigned_to);

            //update user contact access also
            if (! empty($assigned_to)) {
                $lead_contact = Contact::find($id);
                $lead_contact->userHavingAccess()->sync($assigned_to);
            }

            return $contact;
        } else {
            throw new \Exception('Error Processing Request', 1);
        }
    }

    public static function getContactsCountBySourceOfGivenTyps($business_id, $types = [])
    {
        $query = Contact::where('business_id', $business_id)
                    ->Active();

        if (! empty($types)) {
            $query->whereIn('type', $types);
        }

        $contacts_count_by_source = $query->select(\DB::raw('count(crm_source) as count, crm_source'))
                                    ->groupBy('crm_source')
                                    ->get()
                                    ->keyBy('crm_source');

        return $contacts_count_by_source;
    }

    /**
     * Filters only own created lead or is assigned
     */
    public function scopeOnlyOwnLeads($query)
    {
        $query->where(function ($qry) {
            $qry->whereHas('leadUsers', function ($q) {
                $q->where('user_id', auth()->user()->id);
            })->orWhere('created_by', auth()->user()->id);
        });
    }

    public function getFullNameWithBusinessAttribute()
    {
        $name_array = [];
        if (! empty($this->prefix)) {
            $name_array[] = $this->prefix;
        }
        if (! empty($this->first_name)) {
            $name_array[] = $this->first_name;
        }
        if (! empty($this->middle_name)) {
            $name_array[] = $this->middle_name;
        }
        if (! empty($this->last_name)) {
            $name_array[] = $this->last_name;
        }

        $full_name = implode(' ', $name_array);
        $business_name = ! empty($this->supplier_business_name) ? $this->supplier_business_name.', ' : '';

        return $business_name.$full_name;
    }
}
