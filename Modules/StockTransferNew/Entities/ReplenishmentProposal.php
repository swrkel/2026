<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class ReplenishmentProposal extends Model
{
    protected $table = 'stn_replenishment_proposals';
    protected $guarded = ['id'];
}
