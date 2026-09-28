<?php
namespace Modules\EzyLaw\Entities;
class LawIntegrationQueue extends LawModel { protected $table = 'law_integration_queue'; protected $guarded = ['id']; protected $casts = ['payload_json'=>'array','processed_at'=>'datetime']; }
