<?php

namespace Modules\ReportsOther\Support;

use Illuminate\Http\Request;
use RuntimeException;

class CurrentScope
{
    public function __construct(private readonly Request $request)
    {
    }

    public function businessId(): int
    {
        $candidates = [
            $this->sessionValue('user.business_id'),
            optional($this->request->user())->business_id,
            $this->request->input('business_id'),
        ];

        foreach ($candidates as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        throw new RuntimeException('Reports - Other could not resolve the current business_id.');
    }

    public function locationId(): ?int
    {
        return $this->positiveInt(
            $this->request->input('location_id')
            ?? $this->sessionValue('user.location_id')
            ?? $this->sessionValue('business.location_id')
        );
    }

    public function storeId(): ?int
    {
        return $this->positiveInt(
            $this->request->input('store_id')
            ?? $this->sessionValue('user.store_id')
            ?? $this->sessionValue('business.store_id')
        );
    }

    public function userId(): ?int
    {
        return $this->positiveInt(optional($this->request->user())->id);
    }

    public function userName(): string
    {
        $user = $this->request->user();
        return trim((string) ($user->name ?? $user->username ?? $user->email ?? 'User')) ?: 'User';
    }

    public function key(): string
    {
        return sprintf('b:%d|l:%d|s:%d', $this->businessId(), $this->locationId() ?? 0, $this->storeId() ?? 0);
    }

    private function sessionValue(string $key): mixed
    {
        return $this->request->hasSession() ? $this->request->session()->get($key) : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
