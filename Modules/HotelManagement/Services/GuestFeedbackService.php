<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class GuestFeedbackService
{
    public function dashboard(): array
    {
        $feedback = $this->feedbackList();
        $total = count($feedback);
        $avg = 0;
        if ($total > 0) {
            $sum = array_sum(array_map(fn($r) => (float)($r->overall_rating ?? 0), $feedback));
            $avg = round($sum / max($total, 1), 2);
        }

        return [
            'total' => $total,
            'average' => $avg,
            'open' => $this->countWhere('hm_guest_feedback', 'status', 'open'),
            'resolved' => $this->countWhere('hm_guest_feedback', 'status', 'resolved'),
            'questions' => $this->questions(),
            'feedback' => $feedback,
            'notes' => [
                'Feedback is scoped by tenant database, business and business location for safe multi-business hotel operations.',
                'Low ratings can be reviewed from the same screen before the guest leaves the property.',
                'The structure is standalone inside HotelManagement and can later be bridged to CRM/Communication Hub without duplicating those modules.',
            ],
        ];
    }

    public function saveQuestion(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_guest_feedback_questions')) return;
        DB::table('hm_guest_feedback_questions')->insert([
            'business_id' => session('business.id') ?? null,
            'business_location_id' => session('business_location_id') ?? session('business.default_location_id') ?? null,
            'question' => $data['question'],
            'category' => $data['category'] ?? 'general',
            'rating_scale' => $data['rating_scale'] ?? 5,
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function saveFeedback(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_guest_feedback')) return;
        DB::table('hm_guest_feedback')->insert([
            'business_id' => session('business.id') ?? null,
            'business_location_id' => session('business_location_id') ?? session('business.default_location_id') ?? null,
            'guest_id' => $data['guest_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'folio_id' => $data['folio_id'] ?? null,
            'feedback_date' => $data['feedback_date'] ?? now()->toDateString(),
            'source' => $data['source'] ?? 'front_desk',
            'overall_rating' => $data['overall_rating'] ?? 0,
            'room_rating' => $data['room_rating'] ?? null,
            'service_rating' => $data['service_rating'] ?? null,
            'food_rating' => $data['food_rating'] ?? null,
            'cleanliness_rating' => $data['cleanliness_rating'] ?? null,
            'comments' => $data['comments'] ?? null,
            'status' => 'open',
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateStatus(int $id, array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_guest_feedback')) return;
        DB::table('hm_guest_feedback')->where('id', $id)->update([
            'status' => $data['status'] ?? 'reviewed',
            'resolution_note' => $data['resolution_note'] ?? null,
            'resolved_by' => $userId,
            'resolved_at' => in_array(($data['status'] ?? ''), ['resolved','closed']) ? now() : null,
            'updated_at' => now(),
        ]);
    }

    protected function feedbackList(): array
    {
        if (!Schema::hasTable('hm_guest_feedback')) return [];
        try { return DB::table('hm_guest_feedback')->orderByDesc('id')->limit(50)->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function questions(): array
    {
        if (!Schema::hasTable('hm_guest_feedback_questions')) return [];
        try { return DB::table('hm_guest_feedback_questions')->orderBy('display_order')->orderBy('id')->get()->toArray(); } catch (Throwable $e) { return []; }
    }

    protected function countWhere(string $table, string $column, string $value): int
    {
        if (!Schema::hasTable($table)) return 0;
        try { return DB::table($table)->where($column, $value)->count(); } catch (Throwable $e) { return 0; }
    }
}
