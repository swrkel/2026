<?php

namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TranslationQueueService
{
    public function __construct(
        private TenantDatabaseService $databases,
        private LanguageService $languages,
        private TranslationService $translator
    ) {
    }

    private function c(): string
    {
        return $this->databases->centralConnection();
    }

    public function ready(): bool
    {
        return Schema::connection($this->c())->hasTable('hg_translation_queue')
            && Schema::connection($this->c())->hasTable('hg_article_translations')
            && Schema::connection($this->c())->hasTable('hg_articles');
    }

    /**
     * Queue one job per active non-English language.
     *
     * Article saving must never wait for a translation provider. This method only
     * writes small central-database rows and returns. Existing ready translations
     * are retained when the English source hash has not changed.
     */
    public function queueArticle(int $articleId, bool $force = false): array
    {
        $article = DB::connection($this->c())->table('hg_articles')->where('id', $articleId)->first();
        if (!$article) {
            return [];
        }

        $hash = $this->translator->sourceHash($article);
        $queued = [];
        $queueReady = $this->ready();

        if (!Schema::connection($this->c())->hasTable('hg_article_translations')) {
            Log::warning('Help Guide translation table is unavailable; article saved without translation queue.', [
                'article_id' => $articleId,
            ]);
            return [];
        }

        foreach ($this->languages->active() as $language) {
            $code = (string) $language->code;
            if ($code === 'en') {
                continue;
            }

            $existing = DB::connection($this->c())
                ->table('hg_article_translations')
                ->where('article_id', $articleId)
                ->where('language_code', $code)
                ->first();

            $isCurrent = $existing
                && (string) $existing->translation_status === 'ready'
                && hash_equals((string) $existing->source_hash, $hash);

            if (!$force && $isCurrent) {
                continue;
            }

            // Keep the old translated text in the row while it is stale. The
            // reader checks source_hash + status and falls back to English until
            // the refreshed translation is ready, so users never see mixed data.
            DB::connection($this->c())->table('hg_article_translations')->updateOrInsert(
                ['article_id' => $articleId, 'language_code' => $code],
                [
                    'title' => $existing->title ?? (string) $article->title,
                    'summary' => $existing->summary ?? $article->summary,
                    'content' => $existing->content ?? $article->content,
                    'source_hash' => $hash,
                    'translation_status' => 'pending',
                    'translation_error' => null,
                    'translated_at' => $existing->translated_at ?? null,
                    'updated_at' => now(),
                    'created_at' => $existing->created_at ?? now(),
                ]
            );

            if ($queueReady) {
                DB::connection($this->c())->table('hg_translation_queue')->updateOrInsert(
                    ['article_id' => $articleId, 'language_code' => $code],
                    [
                        'source_hash' => $hash,
                        'status' => 'pending',
                        'attempts' => 0,
                        'available_at' => now(),
                        'locked_at' => null,
                        'last_error' => null,
                        'completed_at' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $queued[$code] = true;
            } else {
                $queued[$code] = false;
            }
        }

        return $queued;
    }

    /**
     * Process a bounded number of jobs. Designed for Laravel Scheduler/cron.
     * One queue row = one article + one language, so a slow provider can never
     * turn one web request into dozens of remote calls.
     */
    public function processBatch(int $limit = 1): array
    {
        if (!$this->ready()) {
            return ['processed' => 0, 'ready' => 0, 'failed' => 0, 'message' => 'Translation queue tables are not installed.'];
        }

        $limit = max(1, min($limit, 10));
        $this->releaseStaleLocks();
        $this->backfillPendingQueue();

        $stats = ['processed' => 0, 'ready' => 0, 'failed' => 0, 'message' => ''];

        for ($i = 0; $i < $limit; $i++) {
            $job = $this->claimNext();
            if (!$job) {
                break;
            }

            $stats['processed']++;
            $ok = $this->processClaimed($job);
            $stats[$ok ? 'ready' : 'failed']++;
        }

        return $stats;
    }


    /**
     * Upgrade-safe recovery: if older code marked a translation pending before
     * hg_translation_queue existed, seed the missing queue row automatically.
     */
    private function backfillPendingQueue(): void
    {
        $connection = DB::connection($this->c());
        $rows = $connection->table('hg_article_translations as tr')
            ->join('hg_articles as a', 'a.id', '=', 'tr.article_id')
            ->leftJoin('hg_translation_queue as q', function ($join) {
                $join->on('q.article_id', '=', 'tr.article_id')
                    ->on('q.language_code', '=', 'tr.language_code');
            })
            ->whereIn('tr.translation_status', ['pending', 'failed'])
            ->whereNull('q.id')
            ->select('tr.article_id', 'tr.language_code', 'a.title', 'a.summary', 'a.content')
            ->orderBy('tr.updated_at')
            ->limit(50)
            ->get();

        foreach ($rows as $row) {
            $hash = $this->translator->sourceHash($row);
            $connection->table('hg_translation_queue')->updateOrInsert(
                ['article_id' => (int) $row->article_id, 'language_code' => (string) $row->language_code],
                [
                'source_hash' => $hash,
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
                'locked_at' => null,
                'completed_at' => null,
                'last_error' => null,
                'created_at' => now(),
                'updated_at' => now(),
                ]
            );
        }
    }

    private function claimNext(): ?object
    {
        $connection = DB::connection($this->c());

        return $connection->transaction(function () use ($connection) {
            $job = $connection->table('hg_translation_queue')
                ->whereIn('status', ['pending', 'failed'])
                ->where(function ($q) {
                    $q->whereNull('available_at')->orWhere('available_at', '<=', now());
                })
                ->orderBy('available_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$job) {
                return null;
            }

            $updated = $connection->table('hg_translation_queue')
                ->where('id', $job->id)
                ->whereIn('status', ['pending', 'failed'])
                ->update([
                    'status' => 'processing',
                    'attempts' => (int) $job->attempts + 1,
                    'locked_at' => now(),
                    'updated_at' => now(),
                ]);

            if (!$updated) {
                return null;
            }

            $job->attempts = (int) $job->attempts + 1;
            $job->status = 'processing';
            return $job;
        });
    }

    private function processClaimed(object $job): bool
    {
        $article = DB::connection($this->c())->table('hg_articles')->where('id', $job->article_id)->first();

        if (!$article) {
            DB::connection($this->c())->table('hg_translation_queue')->where('id', $job->id)->update([
                'status' => 'completed',
                'last_error' => 'Article no longer exists.',
                'completed_at' => now(),
                'locked_at' => null,
                'updated_at' => now(),
            ]);
            return true;
        }

        $currentHash = $this->translator->sourceHash($article);
        if (!hash_equals((string) $job->source_hash, $currentHash)) {
            // English changed again while this job was waiting. Requeue with the
            // new source hash instead of publishing a stale translation.
            DB::connection($this->c())->table('hg_translation_queue')->where('id', $job->id)->update([
                'source_hash' => $currentHash,
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => now(),
                'locked_at' => null,
                'last_error' => null,
                'updated_at' => now(),
            ]);
            return false;
        }

        DB::connection($this->c())->table('hg_article_translations')
            ->where('article_id', $article->id)
            ->where('language_code', $job->language_code)
            ->update(['translation_status' => 'translating', 'updated_at' => now()]);

        $ok = $this->translator->translateArticle($article, (string) $job->language_code);

        if ($ok) {
            DB::connection($this->c())->table('hg_translation_queue')->where('id', $job->id)->update([
                'status' => 'completed',
                'available_at' => null,
                'locked_at' => null,
                'last_error' => null,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            return true;
        }

        $translation = DB::connection($this->c())->table('hg_article_translations')
            ->where('article_id', $article->id)
            ->where('language_code', $job->language_code)
            ->first();

        $maxAttempts = max(1, (int) config('helpguide.translation.max_attempts', 4));
        $attempts = (int) $job->attempts;
        $retry = $attempts < $maxAttempts;

        DB::connection($this->c())->table('hg_translation_queue')->where('id', $job->id)->update([
            'status' => $retry ? 'failed' : 'dead',
            'available_at' => $retry ? now()->addMinutes($this->backoffMinutes($attempts)) : null,
            'locked_at' => null,
            'last_error' => mb_substr((string) ($translation->translation_error ?? 'Translation failed.'), 0, 2000),
            'updated_at' => now(),
        ]);

        return false;
    }

    private function releaseStaleLocks(): void
    {
        $minutes = max(5, (int) config('helpguide.translation.stale_lock_minutes', 20));
        DB::connection($this->c())->table('hg_translation_queue')
            ->where('status', 'processing')
            ->whereNotNull('locked_at')
            ->where('locked_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => 'failed',
                'available_at' => now(),
                'locked_at' => null,
                'last_error' => 'Recovered a stale translation worker lock.',
                'updated_at' => now(),
            ]);
    }

    private function backoffMinutes(int $attempt): int
    {
        $steps = (array) config('helpguide.translation.backoff_minutes', [1, 5, 15, 60]);
        $index = max(0, min($attempt - 1, count($steps) - 1));
        return max(1, (int) ($steps[$index] ?? 15));
    }

    public function statusForArticles($articleIds): array
    {
        $ids = collect($articleIds)->map(fn ($id) => (int) $id)->filter()->values();
        if ($ids->isEmpty() || !Schema::connection($this->c())->hasTable('hg_article_translations')) {
            return [];
        }

        $rows = DB::connection($this->c())->table('hg_article_translations')
            ->select('article_id', 'translation_status', DB::raw('COUNT(*) as total'))
            ->whereIn('article_id', $ids)
            ->groupBy('article_id', 'translation_status')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $id = (int) $row->article_id;
            $out[$id] ??= ['ready' => 0, 'pending' => 0, 'translating' => 0, 'failed' => 0, 'total' => 0];
            $status = (string) $row->translation_status;
            $count = (int) $row->total;
            if (array_key_exists($status, $out[$id])) {
                $out[$id][$status] += $count;
            } elseif ($status !== 'ready') {
                $out[$id]['failed'] += $count;
            }
            $out[$id]['total'] += $count;
        }

        return $out;
    }
}
