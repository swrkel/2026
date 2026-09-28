<?php

namespace Modules\HelpGuide\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\HelpGuide\Services\LanguageService;
use Modules\HelpGuide\Services\ModuleAssignmentService;
use Modules\HelpGuide\Services\TenantDatabaseService;
use Modules\HelpGuide\Services\TranslationQueueService;

class ArticleAdminController extends Controller
{
    public function __construct(
        private TenantDatabaseService $databases,
        private ModuleAssignmentService $assignments,
        private TranslationQueueService $translationQueue,
        private LanguageService $languages
    ) {
    }

    private function authz(): void
    {
        if (!auth()->check() || !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function c(): string
    {
        return $this->databases->centralConnection();
    }

    public function upload(Request $request)
    {
        $this->authz();

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $allowed = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'svg' => ['image/svg+xml'],
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
            'ogg' => ['video/ogg', 'application/ogg'],
        ];

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (!isset($allowed[$extension]) || !in_array($mime, $allowed[$extension], true)) {
            return response()->json([
                'success' => false,
                'msg' => 'That file type is not allowed. Use JPG, PNG, GIF, WEBP, SVG, MP4, WEBM or OGG.',
            ], 422);
        }

        $directory = public_path('uploads/helpguide');
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return response()->json([
                'success' => false,
                'msg' => 'The upload folder could not be created. Check permissions on public/uploads.',
            ], 500);
        }

        $name = date('Ymd_His') . '_' . Str::random(12) . '.' . $extension;

        try {
            $file->move($directory, $name);
        } catch (\Throwable $e) {
            Log::error('Help Guide upload failed', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'msg' => 'The file could not be saved: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'url' => asset('uploads/helpguide/' . $name),
            'type' => str_starts_with($mime, 'video/') ? 'video' : 'image',
        ]);
    }

    public function index(Request $request)
    {
        $this->authz();

        $module = (string) $request->query('module', '');
        $catalog = $this->assignments->catalog();
        $edit = null;

        if ($request->filled('edit')) {
            $edit = DB::connection($this->c())
                ->table('hg_articles')
                ->where('id', (int) $request->query('edit'))
                ->first();
        }

        return view('helpguide::admin.articles', [
            'catalog' => $catalog,
            'module' => $module,
            'edit' => $edit,
            'languages' => $this->languages->active(),
        ]);
    }

    public function listArticles(Request $request)
    {
        $this->authz();

        $module = (string) $request->query('module', '');
        $catalog = $this->assignments->catalog();
        $query = DB::connection($this->c())->table('hg_articles');

        if ($module !== '' && isset($catalog[$module])) {
            $query->where('module_key', $module);
        }

        $articles = $query
            ->orderBy('module_key')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $translationStatus = $this->translationQueue->statusForArticles($articles->pluck('id'));

        return view('helpguide::admin.article_list', [
            'catalog' => $catalog,
            'module' => $module,
            'articles' => $articles,
            'languages' => $this->languages->active(),
            'translationStatus' => $translationStatus,
            'queueReady' => $this->translationQueue->ready(),
            'edit' => null,
        ]);
    }

    public function view(int $id)
    {
        $this->authz();

        $article = DB::connection($this->c())->table('hg_articles')->where('id', $id)->first();
        abort_unless($article, 404, 'Help Guide article not found.');

        $translations = collect();
        if (Schema::connection($this->c())->hasTable('hg_article_translations')) {
            $translations = DB::connection($this->c())
                ->table('hg_article_translations')
                ->where('article_id', $id)
                ->orderBy('language_code')
                ->get();
        }

        return view('helpguide::admin.article_view', [
            'article' => $article,
            'translations' => $translations,
            'catalog' => $this->assignments->catalog(),
            'languages' => $this->languages->active()->keyBy('code'),
        ]);
    }

    public function save(Request $request)
    {
        $this->authz();
        $catalog = $this->assignments->catalog();

        $data = $request->validate([
            'id' => 'nullable|integer',
            'module_key' => 'required|string',
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:191',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        abort_unless(isset($catalog[$data['module_key']]), 422, 'Invalid module.');

        $id = (int) ($data['id'] ?? 0);
        $slug = Str::slug($data['slug'] ?: $data['title']);
        if ($slug === '') {
            $slug = 'article-' . time();
        }

        $payload = [
            'module_key' => $data['module_key'],
            'title' => trim($data['title']),
            'slug' => $slug,
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $request->boolean('status'),
            'updated_at' => now(),
        ];

        $duplicate = DB::connection($this->c())
            ->table('hg_articles')
            ->where('module_key', $payload['module_key'])
            ->where('slug', $slug);
        if ($id) {
            $duplicate->where('id', '!=', $id);
        }
        abort_if($duplicate->exists(), 422, 'An article with this slug already exists for the selected module.');

        DB::connection($this->c())->transaction(function () use (&$id, $payload): void {
            if ($id) {
                DB::connection($this->c())->table('hg_articles')->where('id', $id)->update($payload);
            } else {
                $insert = $payload;
                $insert['created_at'] = now();
                $id = (int) DB::connection($this->c())->table('hg_articles')->insertGetId($insert);
            }
        });

        // No remote translation request is allowed inside article save. Enqueue
        // tiny database jobs only, so the browser gets an immediate response.
        $queued = $this->translationQueue->queueArticle($id);
        $pending = count(array_filter($queued));

        $message = 'Article saved successfully.';
        if ($pending > 0) {
            $message .= ' ' . $pending . ' translation' . ($pending === 1 ? '' : 's') . ' queued for background processing.';
        } elseif (!$this->translationQueue->ready() && $this->languages->active()->where('code', '!=', 'en')->isNotEmpty()) {
            $message .= ' Translation queue table is not installed yet; run the HelpGuide migration.';
        }

        return redirect()
            ->route('helpguide.superadmin.articles.list', ['module' => $payload['module_key']])
            ->with('hg_success', $message);
    }

    public function delete(int $id)
    {
        $this->authz();

        DB::connection($this->c())->transaction(function () use ($id): void {
            if (Schema::connection($this->c())->hasTable('hg_translation_queue')) {
                DB::connection($this->c())->table('hg_translation_queue')->where('article_id', $id)->delete();
            }
            if (Schema::connection($this->c())->hasTable('hg_article_translations')) {
                DB::connection($this->c())->table('hg_article_translations')->where('article_id', $id)->delete();
            }
            DB::connection($this->c())->table('hg_articles')->where('id', $id)->delete();
        });

        return back()->with('hg_success', 'Article deleted.');
    }

    public function retry(int $id)
    {
        $this->authz();
        $queued = $this->translationQueue->queueArticle($id, true);
        $count = count(array_filter($queued));

        return back()->with(
            'hg_success',
            $count > 0
                ? $count . ' translation' . ($count === 1 ? '' : 's') . ' queued for background retry.'
                : 'No translation jobs were queued.'
        );
    }
}
