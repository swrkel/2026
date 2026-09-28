<?php

namespace Modules\HelpGuide\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\HelpGuide\Services\LanguageService;
use Modules\HelpGuide\Services\ModuleAssignmentService;
use Modules\HelpGuide\Services\TenantDatabaseService;
use Modules\HelpGuide\Services\TranslationService;
use Modules\HelpGuide\Services\VisibilityService;

class GuideController extends Controller
{
    public function __construct(
        private TenantDatabaseService $databases,
        private ModuleAssignmentService $assignments,
        private VisibilityService $visibility,
        private LanguageService $languages,
        private TranslationService $translator
    ) {
    }

    public function index(Request $request)
    {
        $context = $this->guideContext();
        $lang = $this->selectedLanguage($request);
        $central = $this->databases->centralConnection();

        $articles = collect();
        if (!empty($context['visibleKeys']) && Schema::connection($central)->hasTable('hg_articles')) {
            $articles = DB::connection($central)->table('hg_articles')
                ->select('id', 'module_key', 'title', 'slug', 'summary', 'sort_order', 'status', 'created_at', 'updated_at')
                ->whereIn('module_key', $context['visibleKeys'])
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();
            $articles = $this->localizeArticles($articles, $lang, false);
        }

        $counts = $articles->groupBy('module_key')->map->count();
        $modules = [];
        foreach ($context['catalog'] as $key => $meta) {
            if (empty($context['visible'][$key])) {
                continue;
            }
            $modules[] = [
                'key' => $key,
                'name' => $meta['name'],
                'assigned' => !empty($context['assigned'][$key]),
                'article_count' => (int) ($counts[$key] ?? 0),
            ];
        }
        usort($modules, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        // The original Help Desk dashboard stores knowledge-base content in the
        // tenant tables (helpguide_categories / articles / article_translations).
        // Keep that content visible in the redesigned Help Guide as well. This
        // closes the gap where an administrator could create a category/article
        // successfully in Help Desk but the user-facing /help-guide directory
        // only read the newer central hg_articles table.
        $legacy = $this->legacyHelpdeskContent($context['connection'], $lang);

        return view('helpguide::guide.index', [
            'modules' => $modules,
            'articles' => $articles,
            'catalog' => $context['catalog'],
            'business' => $context['business'],
            'helpdeskCategories' => $legacy['categories'],
            'helpdeskArticles' => $legacy['articles'],
        ] + $this->languageViewData($request));
    }

    public function module(Request $request, string $moduleKey)
    {
        $context = $this->guideContext();
        abort_unless(isset($context['catalog'][$moduleKey]), 404);
        abort_unless(!empty($context['visible'][$moduleKey]), 403, 'This Help Guide module is not enabled for this business.');

        $central = $this->databases->centralConnection();
        $articles = collect();
        $lang = $this->selectedLanguage($request);

        if (Schema::connection($central)->hasTable('hg_articles')) {
            $articles = DB::connection($central)->table('hg_articles')
                ->select('id', 'module_key', 'title', 'slug', 'summary', 'sort_order', 'status', 'created_at', 'updated_at')
                ->where('module_key', $moduleKey)
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();
            $articles = $this->localizeArticles($articles, $lang, false);
        }

        return view('helpguide::guide.module', [
            'moduleKey' => $moduleKey,
            'moduleName' => $context['catalog'][$moduleKey]['name'],
            'articles' => $articles,
            'business' => $context['business'],
        ] + $this->languageViewData($request));
    }

    public function article(Request $request, int $id)
    {
        $context = $this->guideContext();
        $central = $this->databases->centralConnection();

        abort_unless(Schema::connection($central)->hasTable('hg_articles'), 404);

        $article = DB::connection($central)->table('hg_articles')
            ->where('id', $id)
            ->where('status', 1)
            ->first();

        abort_unless($article, 404, 'Help article not found.');
        abort_unless(!empty($context['visible'][$article->module_key]), 403, 'This Help Guide module is not enabled for this business.');

        $lang = $this->selectedLanguage($request);
        $localized = $this->localizeArticles(collect([$article]), $lang)->first();

        return view('helpguide::guide.article', [
            'article' => $localized,
            'moduleName' => $context['catalog'][$article->module_key]['name'] ?? $article->module_key,
            'business' => $context['business'],
        ] + $this->languageViewData($request));
    }

    public function category(Request $request, int $id)
    {
        $context = $this->guideContext();
        $lang = $this->selectedLanguage($request);
        $legacy = $this->legacyHelpdeskContent($context['connection'], $lang);

        $category = $legacy['categories']->firstWhere('id', $id);
        abort_unless($category, 404, 'Help category not found.');

        $articles = $legacy['articles']
            ->where('category_id', $id)
            ->values();

        return view('helpguide::guide.category', [
            'category' => $category,
            'articles' => $articles,
            'business' => $context['business'],
        ] + $this->languageViewData($request));
    }

    public function helpdeskArticle(Request $request, int $id)
    {
        $context = $this->guideContext();
        $lang = $this->selectedLanguage($request);
        $legacy = $this->legacyHelpdeskContent($context['connection'], $lang);

        $article = $legacy['articles']->firstWhere('id', $id);
        abort_unless($article, 404, 'Help article not found.');

        $category = $legacy['categories']->firstWhere('id', (int) $article->category_id);

        return view('helpguide::guide.helpdesk_article', [
            'article' => $article,
            'category' => $category,
            'business' => $context['business'],
        ] + $this->languageViewData($request));
    }

    /**
     * Live, business-scoped search for the top Help Guide search bar.
     * Only published articles from modules enabled for the current business are
     * ever returned.
     */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 1) {
            return response()->json(['results' => []]);
        }

        $context = $this->guideContext();
        $lang = $this->selectedLanguage($request);
        $needle = mb_strtolower($term);
        $results = [];

        foreach ($context['catalog'] as $key => $meta) {
            if (empty($context['visible'][$key])) {
                continue;
            }
            if (str_contains(mb_strtolower((string) $meta['name']), $needle)) {
                $results[] = [
                    'type' => 'Module',
                    'title' => (string) $meta['name'],
                    'summary' => 'Browse help articles in this module',
                    'url' => route('helpguide.module', ['moduleKey' => $key, 'lang' => $lang]),
                ];
            }
        }

        $central = $this->databases->centralConnection();
        if (!empty($context['visibleKeys']) && Schema::connection($central)->hasTable('hg_articles')) {
            $like = '%' . $term . '%';
            $query = DB::connection($central)->table('hg_articles as a')
                ->select('a.*')
                ->whereIn('a.module_key', $context['visibleKeys'])
                ->where('a.status', 1);

            if ($lang !== 'en' && Schema::connection($central)->hasTable('hg_article_translations')) {
                $query->leftJoin('hg_article_translations as tr', function ($join) use ($lang) {
                    $join->on('tr.article_id', '=', 'a.id')
                        ->where('tr.language_code', '=', $lang)
                        ->where('tr.translation_status', '=', 'ready');
                });

                $query->where(function ($q) use ($like) {
                    $q->where('a.title', 'like', $like)
                        ->orWhere('a.summary', 'like', $like)
                        ->orWhere('a.content', 'like', $like)
                        ->orWhere('tr.title', 'like', $like)
                        ->orWhere('tr.summary', 'like', $like)
                        ->orWhere('tr.content', 'like', $like);
                });
            } else {
                $query->where(function ($q) use ($like) {
                    $q->where('a.title', 'like', $like)
                        ->orWhere('a.summary', 'like', $like)
                        ->orWhere('a.content', 'like', $like);
                });
            }

            $articles = $query->orderBy('a.sort_order')->orderBy('a.title')->limit(40)->get();
            $articles = $this->localizeArticles($articles, $lang);

            foreach ($articles as $article) {
                $haystack = mb_strtolower(
                    (string) $article->title . ' ' .
                    (string) ($article->summary ?? '') . ' ' .
                    strip_tags((string) ($article->content ?? '')) . ' ' .
                    (string) ($context['catalog'][$article->module_key]['name'] ?? $article->module_key)
                );

                if (!str_contains($haystack, $needle)) {
                    continue;
                }

                $summary = trim((string) ($article->summary ?? ''));
                if ($summary === '') {
                    $summary = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $article->content))), 120);
                }

                $results[] = [
                    'type' => 'Article',
                    'title' => (string) $article->title,
                    'summary' => $summary,
                    'module' => (string) ($context['catalog'][$article->module_key]['name'] ?? $article->module_key),
                    'url' => route('helpguide.article', ['id' => $article->id, 'lang' => $lang]),
                ];
            }
        }

        // Search the original Help Desk knowledge base too. These records are
        // tenant-local, so they remain isolated from other tenant databases.
        $legacy = $this->legacyHelpdeskContent($context['connection'], $lang);
        foreach ($legacy['categories'] as $category) {
            if (str_contains(mb_strtolower((string) $category->name), $needle)) {
                $results[] = [
                    'type' => 'Category',
                    'title' => (string) $category->name,
                    'summary' => ((int) $category->article_count) . ' published article' . ((int) $category->article_count === 1 ? '' : 's'),
                    'url' => route('helpguide.category', ['id' => $category->id, 'lang' => $lang]),
                ];
            }
        }

        foreach ($legacy['articles'] as $article) {
            $haystack = mb_strtolower((string) $article->title . ' ' . strip_tags((string) $article->content) . ' ' . (string) ($article->category_name ?? ''));
            if (!str_contains($haystack, $needle)) {
                continue;
            }
            $results[] = [
                'type' => 'Article',
                'title' => (string) $article->title,
                'summary' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $article->content))), 120),
                'module' => (string) ($article->category_name ?? 'Help Desk'),
                'url' => route('helpguide.helpdesk.article', ['id' => $article->id, 'lang' => $lang]),
            ];
        }

        return response()->json(['results' => array_slice($results, 0, 20)]);
    }

    /**
     * Read the original Help Desk knowledge base from the active tenant
     * connection and localize it with article_translations when available.
     * The English/source article is always a safe fallback, so a missing
     * translation can never make a published article disappear.
     */
    private function legacyHelpdeskContent(string $connection, string $lang): array
    {
        $empty = ['categories' => collect(), 'articles' => collect()];
        if (!Schema::connection($connection)->hasTable('helpguide_categories')
            || !Schema::connection($connection)->hasTable('articles')) {
            return $empty;
        }

        $articleQuery = DB::connection($connection)->table('articles');
        if (Schema::connection($connection)->hasColumn('articles', 'published')) {
            $articleQuery->where('published', 1);
        }
        if (Schema::connection($connection)->hasColumn('articles', 'deleted_at')) {
            $articleQuery->whereNull('deleted_at');
        }

        $articles = $articleQuery
            ->orderByDesc('id')
            ->get();

        if ($articles->isEmpty()) {
            $categories = DB::connection($connection)->table('helpguide_categories')
                ->when(Schema::connection($connection)->hasColumn('helpguide_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->orderBy('name')
                ->get()
                ->map(function ($category) {
                    $category->article_count = 0;
                    return $category;
                });
            return ['categories' => $categories, 'articles' => collect()];
        }

        $translations = collect();
        if ($lang !== 'en' && Schema::connection($connection)->hasTable('article_translations')) {
            $translationQuery = DB::connection($connection)->table('article_translations')
                ->whereIn('article_id', $articles->pluck('id'));
            if (Schema::connection($connection)->hasColumn('article_translations', 'language')) {
                $translationQuery->where('language', $lang);
            }
            $translations = $translationQuery->get()->keyBy('article_id');
        }

        $categories = DB::connection($connection)->table('helpguide_categories')
            ->when(Schema::connection($connection)->hasColumn('helpguide_categories', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy(Schema::connection($connection)->hasColumn('helpguide_categories', 'category_order') ? 'category_order' : 'name')
            ->orderBy('name')
            ->get();

        $categoryNames = $categories->pluck('name', 'id');
        foreach ($articles as $article) {
            $translation = $translations->get($article->id);
            if ($translation) {
                if (isset($translation->title) && trim((string) $translation->title) !== '') {
                    $article->title = $translation->title;
                }
                if (isset($translation->content) && trim((string) $translation->content) !== '') {
                    $article->content = $translation->content;
                }
            }
            $article->category_name = (string) ($categoryNames[(int) ($article->category_id ?? 0)] ?? 'Help Desk');
        }

        $counts = $articles->groupBy(fn ($article) => (int) ($article->category_id ?? 0))->map->count();
        $categories = $categories->map(function ($category) use ($counts) {
            $category->article_count = (int) ($counts[(int) $category->id] ?? 0);
            return $category;
        })->filter(function ($category) {
            // A category containing a published article must always be visible.
            // For an empty category, respect its Active flag when that column exists.
            if ((int) $category->article_count > 0) {
                return true;
            }
            return !isset($category->active) || (bool) $category->active;
        })->values();

        return ['categories' => $categories, 'articles' => $articles->values()];
    }

    private function localizeArticles(Collection $articles, string $lang, bool $verifyHash = true): Collection
    {
        if ($articles->isEmpty() || $lang === 'en') {
            return $articles;
        }

        $central = $this->databases->centralConnection();
        if (!Schema::connection($central)->hasTable('hg_article_translations')) {
            return $articles;
        }

        $translationColumns = ['article_id', 'title', 'summary', 'source_hash', 'translation_status'];
        if ($verifyHash) {
            $translationColumns[] = 'content';
        }

        $translations = DB::connection($central)->table('hg_article_translations')
            ->select($translationColumns)
            ->whereIn('article_id', $articles->pluck('id'))
            ->where('language_code', $lang)
            ->get()
            ->keyBy('article_id');

        foreach ($articles as $article) {
            $translation = $translations->get($article->id);
            $hash = $verifyHash ? $this->translator->sourceHash($article) : '';

            if ($translation
                && (string) $translation->translation_status === 'ready'
                && (!$verifyHash || hash_equals((string) $translation->source_hash, $hash))) {
                $article->title = $translation->title;
                $article->summary = $translation->summary;
                if ($verifyHash) {
                    $article->content = $translation->content;
                }
                $article->is_translated = true;
            } else {
                $article->is_translated = false;
            }
        }

        return $articles;
    }

    private function selectedLanguage(Request $request): string
    {
        $requested = (string) $request->query('lang', '');
        if ($requested !== '' && $this->languages->isActive($requested)) {
            return $requested;
        }
        return $this->languages->defaultCodeForUser((int) auth()->id());
    }

    private function languageViewData(Request $request): array
    {
        return [
            'languages' => $this->languages->active(),
            'selectedLanguage' => $this->selectedLanguage($request),
            'defaultLanguage' => $this->languages->defaultCodeForUser((int) auth()->id()),
        ];
    }

    private function guideContext(): array
    {
        [$tenantId, $database, $business, $connection] = $this->context();
        $assigned = $this->assignments->assignment($connection, (int) $business['id']);
        $visible = $this->visibility->resolve($tenantId, $database, $business, $assigned);
        $catalog = $this->assignments->catalog();
        $visibleKeys = array_values(array_keys(array_filter($visible)));

        return compact('tenantId', 'database', 'business', 'connection', 'assigned', 'visible', 'catalog', 'visibleKeys');
    }

    private function context(): array
    {
        $businessId = (int) (
            session('user.business_id')
            ?: data_get(session('business'), 'id')
            ?: data_get(auth()->user(), 'business_id')
        );
        abort_if($businessId <= 0, 403, 'Business context is unavailable.');

        $connection = DB::getDefaultConnection();
        if (!Schema::connection($connection)->hasTable('business')) {
            $connection = config('database.connections.mysql_tenant.database') ? 'mysql_tenant' : $connection;
        }

        $columns = ['id', 'name'];
        if (Schema::connection($connection)->hasColumn('business', 'global_uid')) {
            $columns[] = 'global_uid';
        }

        $row = DB::connection($connection)->table('business')
            ->select($columns)
            ->where('id', $businessId)
            ->first();
        abort_unless($row, 404, 'Business not found.');

        $business = [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'uid' => (string) ($row->global_uid ?? ''),
        ];

        $host = request()->getHost();
        $central = $this->databases->centralConnection();
        $tenantId = '';
        if (Schema::connection($central)->hasTable('domains')) {
            $tenantId = (string) (DB::connection($central)->table('domains')->where('domain', $host)->value('tenant_id') ?? '');
        }
        if ($tenantId === '' && function_exists('tenant') && tenant()) {
            $tenantId = (string) tenant()->getTenantKey();
        }

        $database = (string) DB::connection($connection)->getDatabaseName();
        return [$tenantId, $database, $business, $connection];
    }
}
