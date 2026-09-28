<?php
namespace Modules\AirlineTicketingNew\Entities;
class CrmInteraction extends BaseAirlineTicketingModel {
    protected $table='atn_crm_interactions';
    protected $guarded=['id'];
    protected $casts=['interaction_at'=>'datetime','follow_up_at'=>'datetime'];
}
