<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Collection;

/**
 * Customers-owned timeline service.
 *
 * Combines notes, activities and attachments into a single chronological feed.
 */
class CustomerTimelineService
{
    protected $auditService;

    public function __construct(CustomerAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function timeline(int $businessId, int $customerId): Collection
    {
        return $this->timelineFromCollections(
            $this->auditService->notes($businessId, $customerId),
            $this->auditService->activities($businessId, $customerId),
            $this->auditService->attachments($businessId, $customerId)
        );
    }

    /**
     * Build the timeline from already loaded collections. The Audit popup uses
     * this method so customer activities are not queried twice in one request.
     */
    public function timelineFromCollections($notes, $activities, $attachments): Collection
    {
        $items = collect();

        foreach ($notes as $note) {
            $items->push([
                'type' => 'note',
                'title' => 'Note',
                'description' => $note->note ?? '',
                'created_at' => $note->created_at,
                'created_by' => $note->created_by ?? null,
                'source' => $note,
            ]);
        }

        foreach ($activities as $activity) {
            $items->push([
                'type' => 'activity',
                'title' => $activity->event ?? 'Activity',
                'description' => $this->activityDescription($activity),
                'created_at' => $activity->created_at,
                'created_by' => $activity->created_by ?? null,
                'source' => $activity,
            ]);
        }

        foreach ($attachments as $attachment) {
            $items->push([
                'type' => 'attachment',
                'title' => $attachment->filename ?? 'Attachment',
                'description' => $attachment->path ?? '',
                'created_at' => $attachment->created_at,
                'created_by' => $attachment->created_by ?? null,
                'source' => $attachment,
            ]);
        }

        return $items->sortByDesc('created_at')->values();
    }

    protected function activityDescription($activity): string
    {
        $properties = $activity->properties ?? [];

        if (is_array($properties) && ! empty($properties['name'])) {
            return (string) $properties['name'];
        }

        return '';
    }

    public function status(): array
    {
        return $this->auditService->tableStatus();
    }
}
