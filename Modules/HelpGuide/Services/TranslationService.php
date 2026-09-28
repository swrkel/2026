<?php

namespace Modules\HelpGuide\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class TranslationService
{
    public function __construct(
        private TenantDatabaseService $databases,
        private LanguageService $languages
    ) {
    }

    private function c(): string
    {
        return $this->databases->centralConnection();
    }

    public function sourceHash(object $article): string
    {
        return hash('sha256',
            (string) $article->title . "\n" .
            (string) ($article->summary ?? '') . "\n" .
            (string) ($article->content ?? '')
        );
    }

    /**
     * Kept for compatibility with older buttons/commands. New article saves do
     * NOT call this method; TranslationQueueService schedules work instead.
     */
    public function translateAll(int $articleId): array
    {
        $article = DB::connection($this->c())->table('hg_articles')->where('id', $articleId)->first();
        if (!$article) {
            return [];
        }

        $out = [];
        foreach ($this->languages->active() as $language) {
            $code = (string) $language->code;
            if ($code === 'en') {
                continue;
            }
            $out[$code] = $this->translateArticle($article, $code);
        }
        return $out;
    }

    public function translateArticle(object $article, string $target): bool
    {
        if (!Schema::connection($this->c())->hasTable('hg_article_translations')) {
            return false;
        }

        $hash = $this->sourceHash($article);

        try {
            $existing = DB::connection($this->c())->table('hg_article_translations')
                ->where('article_id', $article->id)
                ->where('language_code', $target)
                ->first();

            if ($existing) {
                DB::connection($this->c())->table('hg_article_translations')
                    ->where('id', $existing->id)
                    ->update([
                        'source_hash' => $hash,
                        'translation_status' => 'translating',
                        'translation_error' => null,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::connection($this->c())->table('hg_article_translations')->insert([
                    'article_id' => $article->id,
                    'language_code' => $target,
                    'title' => (string) $article->title,
                    'summary' => $article->summary,
                    'content' => $article->content,
                    'source_hash' => $hash,
                    'translation_status' => 'translating',
                    'translation_error' => null,
                    'translated_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $title = $this->translateText((string) $article->title, $target);
            $summary = $article->summary !== null && trim((string) $article->summary) !== ''
                ? $this->translateText((string) $article->summary, $target)
                : null;
            $content = $article->content !== null && trim(strip_tags((string) $article->content)) !== ''
                ? $this->translateHtml((string) $article->content, $target)
                : $article->content;

            DB::connection($this->c())->table('hg_article_translations')->updateOrInsert(
                ['article_id' => $article->id, 'language_code' => $target],
                [
                    'title' => $title,
                    'summary' => $summary,
                    'content' => $content,
                    'source_hash' => $hash,
                    'translation_status' => 'ready',
                    'translation_error' => null,
                    'translated_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return true;
        } catch (\Throwable $e) {
            report($e);

            $old = DB::connection($this->c())->table('hg_article_translations')
                ->where('article_id', $article->id)
                ->where('language_code', $target)
                ->first();

            DB::connection($this->c())->table('hg_article_translations')->updateOrInsert(
                ['article_id' => $article->id, 'language_code' => $target],
                [
                    'title' => $old->title ?? (string) $article->title,
                    'summary' => $old->summary ?? $article->summary,
                    'content' => $old->content ?? $article->content,
                    'source_hash' => $hash,
                    'translation_status' => 'failed',
                    'translation_error' => mb_substr($e->getMessage(), 0, 1000),
                    'updated_at' => now(),
                    'created_at' => $old->created_at ?? now(),
                ]
            );

            return false;
        }
    }

    /**
     * Google and LibreTranslate accept HTML. For those providers we submit
     * several reasonably-sized top-level HTML fragments instead of making one
     * network request for every text node. The MyMemory fallback remains text
     * based, because its public endpoint does not reliably preserve markup.
     */
    private function translateHtml(string $html, string $target): string
    {
        $provider = strtolower((string) config('helpguide.translation.provider', 'mymemory'));

        if (in_array($provider, ['google', 'libretranslate'], true)) {
            return $this->translateHtmlChunks($html, $target);
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<div id="hg-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $cache = [];

        foreach ($xpath->query('//*[@id="hg-root"]//text()[normalize-space(.)!=""]') as $node) {
            $source = (string) $node->nodeValue;
            $key = trim($source);
            if ($key === '') {
                continue;
            }
            if (!array_key_exists($key, $cache)) {
                $cache[$key] = $this->translateText($source, $target);
            }
            $node->nodeValue = $cache[$key];
        }

        $root = $dom->getElementById('hg-root');
        if (!$root) {
            return $this->translateText(strip_tags($html), $target);
        }

        $out = '';
        foreach ($root->childNodes as $node) {
            $out .= $dom->saveHTML($node);
        }
        return $out;
    }

    private function translateHtmlChunks(string $html, string $target): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<div id="hg-root">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementById('hg-root');
        if (!$root) {
            return $this->translateText(strip_tags($html), $target);
        }

        $max = max(800, (int) config('helpguide.translation.html_chunk_chars', 3500));
        $chunks = [];
        $buffer = '';

        foreach ($root->childNodes as $node) {
            $fragment = $dom->saveHTML($node);
            if ($fragment === false) {
                continue;
            }

            if (mb_strlen($fragment) > $max) {
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                    $buffer = '';
                }
                // Very large single block: safely fall back to text-node mode for
                // only that block rather than sending an oversized API request.
                $chunks[] = ['large' => $fragment];
                continue;
            }

            if ($buffer !== '' && mb_strlen($buffer . $fragment) > $max) {
                $chunks[] = $buffer;
                $buffer = $fragment;
            } else {
                $buffer .= $fragment;
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        $out = '';
        foreach ($chunks as $chunk) {
            if (is_array($chunk)) {
                $out .= $this->translateHtmlWithTextNodes((string) $chunk['large'], $target);
            } else {
                $out .= $this->translatePayload((string) $chunk, $target, 'html');
            }
        }

        return $out;
    }

    private function translateHtmlWithTextNodes(string $html, string $target): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<div id="hg-root-large">' . $html . '</div>';
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//*[@id="hg-root-large"]//text()[normalize-space(.)!=""]') as $node) {
            $node->nodeValue = $this->translateText((string) $node->nodeValue, $target);
        }

        $root = $dom->getElementById('hg-root-large');
        if (!$root) {
            return $this->translateText(strip_tags($html), $target);
        }

        $out = '';
        foreach ($root->childNodes as $node) {
            $out .= $dom->saveHTML($node);
        }
        return $out;
    }

    private function translateText(string $text, string $target): string
    {
        if (trim($text) === '' || $target === 'en') {
            return $text;
        }

        $maxChars = max(200, (int) config('helpguide.translation.text_chunk_chars', 430));
        if (mb_strlen($text) > $maxChars) {
            $parts = preg_split('/(?<=[.!?。！？])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
            $chunks = [];
            $buffer = '';

            foreach ($parts as $part) {
                if ($buffer !== '' && mb_strlen($buffer . ' ' . $part) > $maxChars) {
                    $chunks[] = $buffer;
                    $buffer = $part;
                } else {
                    $buffer .= ($buffer === '' ? '' : ' ') . $part;
                }
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
            }

            if (count($chunks) > 1) {
                return implode(' ', array_map(fn ($value) => $this->translateText($value, $target), $chunks));
            }
        }

        return $this->translatePayload($text, $target, 'text');
    }

    private function translatePayload(string $text, string $target, string $format): string
    {
        $provider = strtolower((string) config('helpguide.translation.provider', 'mymemory'));
        $timeout = max(5, min((int) config('helpguide.translation.timeout', 20), 60));

        if ($provider === 'google') {
            $key = (string) config('helpguide.translation.google_api_key');
            if ($key === '') {
                throw new \RuntimeException('Google Translate API key is not configured.');
            }

            $response = Http::timeout($timeout)->asForm()->post(
                'https://translation.googleapis.com/language/translate/v2?key=' . urlencode($key),
                [
                    'q' => $text,
                    'source' => 'en',
                    'target' => $target,
                    'format' => $format === 'html' ? 'html' : 'text',
                ]
            );
            $response->throw();

            $value = (string) data_get($response->json(), 'data.translations.0.translatedText');
            if ($value === '') {
                throw new \RuntimeException('Google Translate returned an empty translation.');
            }
            return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if ($provider === 'libretranslate') {
            $url = rtrim((string) config('helpguide.translation.libretranslate_url'), '/');
            if ($url === '') {
                throw new \RuntimeException('LibreTranslate URL is not configured.');
            }

            $payload = [
                'q' => $text,
                'source' => 'en',
                'target' => $target,
                'format' => $format === 'html' ? 'html' : 'text',
            ];
            $apiKey = (string) config('helpguide.translation.libretranslate_api_key');
            if ($apiKey !== '') {
                $payload['api_key'] = $apiKey;
            }

            $response = Http::timeout($timeout)->post($url . '/translate', $payload);
            $response->throw();

            $value = (string) $response->json('translatedText');
            if ($value === '') {
                throw new \RuntimeException('LibreTranslate returned an empty translation.');
            }
            return $value;
        }

        if ($format === 'html') {
            // MyMemory is text-only in this module. The caller should have used
            // the text-node path, but guard against accidental markup submission.
            $text = strip_tags($text);
        }

        $response = Http::timeout($timeout)->get('https://api.mymemory.translated.net/get', [
            'q' => $text,
            'langpair' => 'en|' . $target,
        ]);
        $response->throw();

        $value = (string) data_get($response->json(), 'responseData.translatedText');
        if ($value === '') {
            throw new \RuntimeException('Translation provider returned an empty translation.');
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
