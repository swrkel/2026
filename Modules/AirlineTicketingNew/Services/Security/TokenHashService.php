<?php
namespace Modules\AirlineTicketingNew\Services\Security;

class TokenHashService
{
    public function hash(string $token): string
    {
        return hash('sha256',$token);
    }

    public function verify(string $token,string $hash): bool
    {
        return hash_equals($hash,$this->hash($token));
    }
}
