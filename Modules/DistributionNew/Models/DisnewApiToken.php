<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewApiToken extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_api_tokens';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'user_id',
        'device_id',
        'token_hash',
        'abilities_json',
        'last_used_at',
        'expires_at',
        'revoked_at'
    ];
}
