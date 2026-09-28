<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;

class PetroWhatsAppTemplate extends Model
{
    /**
     * Table used by the existing Petro WhatsApp template settings.
     */
    protected $table = 'petro_whatsapp_templates';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
