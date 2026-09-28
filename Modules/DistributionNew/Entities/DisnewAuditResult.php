<?php
namespace Modules\DistributionNew\Entities;
use Illuminate\Database\Eloquent\Model;
class DisnewAuditResult extends Model{protected $table='disnew_audit_results';protected $guarded=[];protected $casts=['payload'=>'array'];}
