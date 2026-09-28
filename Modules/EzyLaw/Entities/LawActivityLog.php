<?php
namespace Modules\EzyLaw\Entities;
class LawActivityLog extends LawModel { protected $table = 'law_activity_logs'; protected $guarded = ['id']; public $timestamps = false; protected $casts = ['metadata_json'=>'array','created_at'=>'datetime']; }
