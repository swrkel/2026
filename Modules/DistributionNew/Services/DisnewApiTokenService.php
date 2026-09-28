<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Str;
use Modules\DistributionNew\Models\DisnewApiToken;

class DisnewApiTokenService
{
    public function issue(array $payload): array
    {
        $plain = Str::random(80);
        $token = DisnewApiToken::create($payload + [
            'token_hash' => hash('sha256', $plain),
            'abilities_json' => json_encode($payload['abilities'] ?? ['distributionnew:mobile']),
        ]);
        return ['plain_token' => $plain, 'token' => $token];
    }
}
