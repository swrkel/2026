<?php

namespace Modules\DigitalBusinessCards\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardView extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('digital-business-cards.table_prefix', 'dbc_').'card_views';
    }

    public function getConnectionName(): ?string
    {
        return config('digital-business-cards.connection') ?: parent::getConnectionName();
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}
