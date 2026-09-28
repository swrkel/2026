<?php
namespace Modules\EzyLaw\Entities;
class LawInvoiceLine extends LawModel { protected $table = 'law_invoice_lines'; protected $guarded = ['id']; protected $casts = ['qty'=>'decimal:4','unit_price'=>'decimal:4','tax_rate'=>'decimal:4','line_total'=>'decimal:4']; }
