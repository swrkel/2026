<?php
namespace Modules\EzyLaw\Entities;
class LawPayment extends LawModel { protected $table = 'law_payments'; protected $guarded = ['id']; protected $casts = ['payment_date'=>'date','amount'=>'decimal:4']; }
