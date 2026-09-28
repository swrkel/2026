<?php

namespace App\Services\Documents;

use App\Business;
use App\BusinessLocation;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Resolves the mandatory metadata shared by every print, PDF, email,
 * WhatsApp message, report and future document delivery channel.
 *
 * Controllers may override any value by passing:
 * business_id, business_name, location_id, business_location,
 * page_title, date_range and page_no.
 */
class GlobalDocumentMetadata
{
    /**
     * @param  array<string,mixed>  $overrides
     * @return array<string,string|int|null>
     */
    public function resolve(array $overrides = [])
    {
        $requestContext = [];

        try {
            if (app()->bound('request')) {
                $requestContext = (array) request()->attributes->get('global_document_context', []);
            }
        } catch (\Throwable $exception) {
            $requestContext = [];
        }

        $overrides = array_merge($requestContext, $overrides);
        $businessId = $this->resolveBusinessId($overrides);

        return [
            'business_id' => $businessId,
            'business_name' => $this->resolveBusinessName($businessId, $overrides),
            'location_id' => $this->resolveLocationId($overrides),
            'business_location' => $this->resolveLocationName($businessId, $overrides),
            'page_title' => $this->resolvePageTitle($overrides),
            'date_range' => $this->resolveDateRange($overrides),
            'page_no' => $overrides['page_no'] ?? 1,
        ];
    }

    /**
     * Save explicit metadata on the current request for PDF/email/share code
     * that runs later during the same request.
     *
     * @param  array<string,mixed>  $context
     * @return void
     */
    public function setRequestContext(array $context)
    {
        try {
            if (app()->bound('request')) {
                $existing = (array) request()->attributes->get('global_document_context', []);
                request()->attributes->set('global_document_context', array_merge($existing, $context));
            }
        } catch (\Throwable $exception) {
            // Metadata must never stop the requested business operation.
        }
    }

    /** @return int|null */
    private function resolveBusinessId(array $overrides)
    {
        $candidates = [
            Arr::get($overrides, 'business_id'),
            data_get($overrides, 'business.id'),
            data_get($overrides, 'business_details.id'),
            data_get($overrides, 'transaction.business_id'),
            data_get($overrides, 'invoice.business_id'),
            data_get($overrides, 'header.business_id'),
            data_get($overrides, 'data.business_id'),
            $this->requestInput(['business_id']),
            $this->sessionValue('user.business_id'),
            $this->sessionValue('business.id'),
        ];

        try {
            if (auth()->check()) {
                $candidates[] = auth()->user()->business_id ?? null;
            }
        } catch (\Throwable $exception) {
            // Ignore missing authentication context (CLI/queue/public pages).
        }

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }

    private function resolveBusinessName($businessId, array $overrides)
    {
        $explicit = $this->firstFilled([
            Arr::get($overrides, 'business_name'),
            data_get($overrides, 'business.name'),
            data_get($overrides, 'business_details.name'),
            data_get($overrides, 'transaction.business.name'),
            data_get($overrides, 'invoice.business.name'),
            data_get($overrides, 'header.business.name'),
            data_get($overrides, 'data.business.name'),
            $this->requestInput(['business_name']),
        ]);

        if ($explicit !== null) {
            return $this->plain($explicit);
        }

        try {
            $sessionBusiness = session()->get('business');
            $sessionName = is_object($sessionBusiness)
                ? ($sessionBusiness->name ?? null)
                : data_get($sessionBusiness, 'name');

            if ($this->filled($sessionName)) {
                return $this->plain($sessionName);
            }
        } catch (\Throwable $exception) {
            // Continue to database fallback.
        }

        if ($businessId) {
            try {
                $name = Business::query()->whereKey($businessId)->value('name');
                if ($this->filled($name)) {
                    return $this->plain($name);
                }
            } catch (\Throwable $exception) {
                // Tenant connection may not be available in early boot/queue.
            }
        }

        return 'Not Available';
    }

    /** @return int|string|null */
    private function resolveLocationId(array $overrides)
    {
        $candidate = $this->firstFilled([
            Arr::get($overrides, 'location_id'),
            Arr::get($overrides, 'business_location_id'),
            data_get($overrides, 'location.id'),
            data_get($overrides, 'business_location.id'),
            data_get($overrides, 'transaction.location_id'),
            data_get($overrides, 'invoice.location_id'),
            data_get($overrides, 'header.location_id'),
            data_get($overrides, 'data.location_id'),
            $this->requestInput([
                'business_location_id',
                'location_id',
                'selected_location_id',
                'location',
            ]),
            $this->sessionValue('business_location_id'),
            $this->sessionValue('location_id'),
            $this->sessionValue('business.location_id'),
            $this->sessionValue('user.business_location_id'),
            $this->sessionValue('user.location_id'),
        ], true);

        if (is_array($candidate)) {
            return implode(',', array_filter(array_map('strval', $candidate)));
        }

        return $candidate;
    }

    private function resolveLocationName($businessId, array $overrides)
    {
        $explicit = $this->firstFilled([
            Arr::get($overrides, 'business_location'),
            Arr::get($overrides, 'location_name'),
            data_get($overrides, 'business_location.name'),
            data_get($overrides, 'location.name'),
            data_get($overrides, 'transaction.business_location.name'),
            data_get($overrides, 'transaction.location.name'),
            data_get($overrides, 'invoice.location.name'),
            data_get($overrides, 'header.location.name'),
            data_get($overrides, 'data.location.name'),
            $this->requestInput(['business_location', 'location_name']),
        ]);

        if ($explicit !== null) {
            return $this->plain($explicit);
        }

        $locationId = $this->resolveLocationId($overrides);
        if ($this->meansAll($locationId)) {
            return 'All Locations';
        }

        $ids = is_array($locationId)
            ? $locationId
            : preg_split('/\s*,\s*/', (string) $locationId, -1, PREG_SPLIT_NO_EMPTY);
        $ids = array_values(array_filter(array_map('intval', (array) $ids)));

        if ($ids) {
            try {
                $query = BusinessLocation::query()->whereIn('id', $ids);
                if ($businessId) {
                    $query->where('business_id', $businessId);
                }
                $names = $query->pluck('name')->filter()->values()->all();
                if ($names) {
                    return implode(', ', array_map([$this, 'plain'], $names));
                }
            } catch (\Throwable $exception) {
                // Continue to safe fallback.
            }
        }

        if ($businessId) {
            try {
                $locations = BusinessLocation::query()
                    ->where('business_id', $businessId)
                    ->limit(2)
                    ->pluck('name');

                if ($locations->count() === 1) {
                    return $this->plain($locations->first());
                }
            } catch (\Throwable $exception) {
                // Continue to safe fallback.
            }
        }

        return 'All Locations';
    }

    private function resolvePageTitle(array $overrides)
    {
        $title = $this->firstFilled([
            Arr::get($overrides, 'page_title'),
            Arr::get($overrides, 'document_title'),
            Arr::get($overrides, 'report_title'),
            Arr::get($overrides, 'title'),
            $this->requestInput(['page_title', 'document_title', 'report_title', 'title']),
        ]);

        if ($title !== null) {
            return $this->plain($title);
        }

        try {
            $route = request()->route();
            $routeName = $route ? $route->getName() : null;
            if ($this->filled($routeName)) {
                return Str::headline(str_replace(['.', '-', '_'], ' ', (string) $routeName));
            }

            $path = trim((string) request()->path(), '/');
            if ($path !== '') {
                return Str::headline(str_replace(['/', '-', '_'], ' ', $path));
            }
        } catch (\Throwable $exception) {
            // No HTTP request (queue/CLI).
        }

        return 'Document';
    }

    private function resolveDateRange(array $overrides)
    {
        $explicit = $this->firstFilled([
            Arr::get($overrides, 'date_range'),
            Arr::get($overrides, 'selected_date_range'),
            $this->requestInput([
                'date_range',
                'selected_date_range',
                'report_date_range',
                'transaction_date_range',
            ]),
        ]);

        if ($explicit !== null) {
            return $this->plain($explicit);
        }

        $pairs = [
            ['start_date', 'end_date'],
            ['date_from', 'date_to'],
            ['from_date', 'to_date'],
            ['period_start', 'period_end'],
            ['start', 'end'],
        ];

        foreach ($pairs as $pair) {
            $start = Arr::get($overrides, $pair[0], $this->requestInput([$pair[0]]));
            $end = Arr::get($overrides, $pair[1], $this->requestInput([$pair[1]]));

            if ($this->filled($start) || $this->filled($end)) {
                $startText = $this->formatDate($start ?: $end);
                $endText = $this->formatDate($end ?: $start);

                return $startText === $endText ? $startText : $startText . ' - ' . $endText;
            }
        }

        $single = $this->firstFilled([
            Arr::get($overrides, 'selected_date'),
            Arr::get($overrides, 'date'),
            data_get($overrides, 'transaction.transaction_date'),
            data_get($overrides, 'invoice.invoice_date'),
            data_get($overrides, 'invoice.transaction_date'),
            data_get($overrides, 'header.transaction_date'),
            data_get($overrides, 'data.transaction_date'),
            $this->requestInput(['selected_date', 'transaction_date', 'date']),
        ]);

        return $single !== null ? $this->formatDate($single) : 'All Dates';
    }

    /** @param array<int,string> $keys */
    private function requestInput(array $keys)
    {
        try {
            if (! app()->bound('request')) {
                return null;
            }

            foreach ($keys as $key) {
                $value = request()->input($key);
                if ($this->filled($value) || $value === 0 || $value === '0') {
                    return $value;
                }
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    private function sessionValue($key)
    {
        try {
            return session()->get($key);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function formatDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'All Dates';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $exception) {
            return $this->plain($value);
        }
    }

    private function meansAll($value)
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['all', 'all locations', '*'], true);
    }

    private function firstFilled(array $values, $allowZero = false)
    {
        foreach ($values as $value) {
            if ($this->filled($value) || ($allowZero && ($value === 0 || $value === '0'))) {
                return $value;
            }
        }

        return null;
    }

    private function filled($value)
    {
        if (is_array($value)) {
            return count(array_filter($value, static fn ($item) => $item !== null && $item !== '')) > 0;
        }

        return $value !== null && trim((string) $value) !== '';
    }

    private function plain($value)
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}
