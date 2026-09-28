<?php
namespace Modules\EzyLaw\Entities;
class LawDocumentTemplate extends LawModel
{
    protected $table='law_document_templates';
    protected $guarded=['id'];
    protected $casts=['active'=>'boolean'];
}
