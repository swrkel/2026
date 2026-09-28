<?php
namespace Modules\AirlineTicketingNew\Services\Controls;

use Modules\AirlineTicketingNew\Entities\OperationalException;

class ExceptionControlService
{
    public function raise(array $data): OperationalException
    {
        return OperationalException::query()->create(array_merge($data, [
            'status' => 'open',
            'detected_at' => now(),
            'detected_by' => auth()->id(),
        ]));
    }

    public function resolve(OperationalException $exception, ?string $note = null): OperationalException
    {
        $exception->update([
            'status' => 'resolved',
            'resolution_note' => $note,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return $exception->refresh();
    }
}
