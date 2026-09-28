<?php

namespace Modules\MyHealthMembers\Services\Notifications;

use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthNotification;
use Modules\MyHealthMembers\Entities\MyHealthNotificationTemplate;

class MyHealthNotificationService
{
    public function dashboard(): array
    {
        return [
            'queued' => MyHealthNotification::where('status', 'queued')->count(),
            'sent_today' => MyHealthNotification::whereDate('sent_at', now()->toDateString())->count(),
            'failed' => MyHealthNotification::where('status', 'failed')->count(),
            'templates' => MyHealthNotificationTemplate::count(),
        ];
    }

    public function list(array $filters = [])
    {
        $query = MyHealthNotification::with('member')->latest('id');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (!empty($filters['member'])) {
            $keyword = $filters['member'];
            $query->whereHas('member', function ($memberQuery) use ($keyword) {
                $memberQuery->where('member_code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate(25);
    }

    public function queueForMember(int $memberId, string $channel, string $subject, string $message, ?string $purpose = null): MyHealthNotification
    {
        $member = MyHealthMember::findOrFail($memberId);

        return DB::transaction(function () use ($member, $channel, $subject, $message, $purpose) {
            return MyHealthNotification::create([
                'member_id' => $member->id,
                'member_code' => $member->member_code ?? null,
                'channel' => $channel,
                'purpose' => $purpose ?: 'manual',
                'recipient' => $channel === 'sms' ? ($member->mobile ?? null) : ($member->email ?? null),
                'subject' => $subject,
                'message' => $message,
                'status' => 'queued',
                'scheduled_at' => now(),
                'payload' => [],
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function markSent(MyHealthNotification $notification): MyHealthNotification
    {
        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        return $notification;
    }

    public function markFailed(MyHealthNotification $notification, ?string $reason = null): MyHealthNotification
    {
        $notification->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
        ]);

        return $notification;
    }
}
