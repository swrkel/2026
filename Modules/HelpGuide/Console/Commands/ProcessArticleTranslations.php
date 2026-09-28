<?php

namespace Modules\HelpGuide\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpGuide\Services\TranslationQueueService;

class ProcessArticleTranslations extends Command
{
    protected $signature = 'helpguide:translate-pending {--limit=1 : Maximum article-language jobs to process in this run}';

    protected $description = 'Process pending Help Guide article translations in small background batches.';

    public function handle(TranslationQueueService $queue): int
    {
        $limit = max(1, min((int) $this->option('limit'), 10));
        $stats = $queue->processBatch($limit);

        $this->line(sprintf(
            'Help Guide translations: %d processed, %d ready, %d failed.',
            $stats['processed'],
            $stats['ready'],
            $stats['failed']
        ));

        if (!empty($stats['message'])) {
            $this->comment($stats['message']);
        }

        return self::SUCCESS;
    }
}
