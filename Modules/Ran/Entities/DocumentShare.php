<?php
namespace Modules\Ran\Entities;
class DocumentShare extends RanModel {
 protected $table='ran_document_shares';
 protected $casts=['selected_sections'=>'array','expires_at'=>'datetime','last_viewed_at'=>'datetime','is_active'=>'boolean'];

}
