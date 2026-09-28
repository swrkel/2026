<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Task 8046 - a customer reference.
 *
 * A reference is either a plain reference string or a vehicle number. When it
 * is a vehicle it also carries a fuel type, which is a PRODUCT sub-category
 * sitting under the Fuel product category - not a Customers-module category.
 *
 * Kept inside Modules/Customers so the feature does not depend on Contact
 * module models, consistent with CustomerNote and the other module-owned
 * entities.
 */
class CustomerReference extends Model
{
    use SoftDeletes;

    protected $table = 'customer_qr_references';

    protected $guarded = ['id'];

    protected $casts = [
        'business_id' => 'integer',
        'customer_id' => 'integer',
        'fuel_type_id' => 'integer',
        'is_vehicle' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'reference_datetime' => 'datetime',
    ];

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeActiveOnly($query)
    {
        return $query->where('is_active', 1);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Label used on screen and inside the QR payload.
     *
     * The spec asks for the reference to be labelled "Vehicle No" when the
     * reference is a vehicle, and a plain reference otherwise.
     */
    public function referenceLabel(): string
    {
        return $this->is_vehicle ? 'Vehicle No' : 'Reference';
    }

    /**
     * Heading used at the top of the printed sheet, the PDF, the email and the
     * WhatsApp message.
     *
     * A vehicle reference is a different thing to a general customer reference
     * in the eyes of whoever receives it - the sticker goes on a windscreen,
     * not in a file - so the documents say so rather than using one generic
     * title for both.
     */
    public function documentTitle(): string
    {
        return $this->is_vehicle ? 'Customer Vehicle' : 'Customer Reference';
    }

    /**
     * Fuel type as it should be displayed.
     *
     * Only meaningful for vehicles. Falls back to the system default rather
     * than showing an empty cell, because "Not Known" is a real, selectable
     * answer in this feature and not a missing value.
     */
    public function fuelTypeLabel(): string
    {
        if (! $this->is_vehicle) {
            return '';
        }

        return $this->fuel_type_name ?: CustomerReferenceFuelType::NOT_KNOWN_LABEL;
    }
}
