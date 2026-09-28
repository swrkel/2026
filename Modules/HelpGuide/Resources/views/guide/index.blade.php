@extends('helpguide::layouts.standalone')
@section('title','Help Guide')
@section('content')

<div class="hg-page-hero">
    <div>
        <h1 class="hg-title">Help Guide</h1>
        <p class="hg-sub">{{ $business['name'] }} — browse the modules available to your business or search directly for an article.</p>
    </div>
    @include('helpguide::partials.language')
</div>

@if(isset($helpdeskCategories) && $helpdeskCategories->isNotEmpty())
    <h2 class="hg-section-title">Help Desk Categories</h2>
    <p class="hg-section-sub">Categories and published articles created in the Help Desk appear here automatically.</p>
    <div class="hg-grid" id="hg-helpdesk-category-grid">
        @foreach($helpdeskCategories as $category)
            <a class="hg-card hg-module" data-hg-filter-group="helpdesk-categories"
               data-hg-filter-text="{{ strtolower($category->name) }}"
               href="{{ route('helpguide.category', ['id' => $category->id, 'lang' => $selectedLanguage]) }}">
                <span class="hg-count">{{ (int) $category->article_count }} {{ (int) $category->article_count === 1 ? 'article' : 'articles' }}</span>
                <div class="hg-icon"><i class="fa fa-folder-open"></i></div>
                <h3>{{ $category->name }}</h3>
                <div class="hg-muted">Open this Help Desk category and browse its published guidance.</div>
            </a>
        @endforeach
    </div>
    <div class="hg-card hg-empty" data-hg-filter-empty="helpdesk-categories" style="display:none;margin-top:14px">No Help Desk category matches your search.</div>
@endif

@if(isset($helpdeskArticles) && $helpdeskArticles->isNotEmpty())
    <h2 class="hg-section-title">Latest Help Desk Articles</h2>
    <p class="hg-section-sub">Published articles created from Help Desk are available immediately; a missing translation falls back to the saved source article.</p>
    <div class="hg-grid" id="hg-helpdesk-article-directory">
        @foreach($helpdeskArticles as $article)
            @php
                $preview = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($article->content ?? '')))), 135);
                $searchText = strtolower(($article->category_name ?? 'Help Desk').' '.($article->title ?? '').' '.$preview);
            @endphp
            <a class="hg-card hg-article-card" data-hg-filter-group="helpdesk-articles"
               data-hg-filter-text="{{ $searchText }}"
               href="{{ route('helpguide.helpdesk.article', ['id' => $article->id, 'lang' => $selectedLanguage]) }}">
                <div class="hg-article-bullet">→</div>
                <div>
                    <h3>{{ $article->title }}</h3>
                    <div class="hg-muted">{{ $preview ?: 'Open this article to read the full guidance.' }}</div>
                    <div class="hg-meta">{{ $article->category_name ?? 'Help Desk' }}</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="hg-card hg-empty" data-hg-filter-empty="helpdesk-articles" style="display:none;margin-top:14px">No Help Desk article matches your search.</div>
@endif

@if(empty($modules))
    <div class="hg-card hg-empty">No Help Guide modules are enabled for this business.</div>
@else
    <h2 class="hg-section-title">Help by Module</h2>
    <p class="hg-section-sub">Choose a module to see its published guidance.</p>
    <div class="hg-grid" id="hg-module-grid">
        @foreach($modules as $module)
            <a class="hg-card hg-module" data-hg-filter-group="modules"
               data-hg-filter-text="{{ strtolower($module['name']) }}"
               href="{{ route('helpguide.module', ['moduleKey' => $module['key'], 'lang' => $selectedLanguage]) }}">
                <span class="hg-count">{{ $module['article_count'] }} {{ $module['article_count'] === 1 ? 'article' : 'articles' }}</span>
                <div class="hg-icon">?</div>
                <h3>{{ $module['name'] }}</h3>
                <div class="hg-muted">Open help, instructions and guidance for this module.</div>
                @if(!$module['assigned'])
                    <div style="margin-top:10px"><span class="hg-badge">Help-only access</span></div>
                @endif
            </a>
        @endforeach
    </div>
    <div class="hg-card hg-empty" data-hg-filter-empty="modules" style="display:none;margin-top:14px">No module matches your search.</div>

    <h2 class="hg-section-title">Published Help Articles</h2>
    <p class="hg-section-sub">Saved and published articles appear here automatically when their module is enabled for this business.</p>

    @if($articles->isEmpty())
        <div class="hg-card hg-empty">No published help articles are available yet.</div>
    @else
        <div class="hg-grid" id="hg-article-directory">
            @foreach($articles as $article)
                @php
                    $moduleName = $catalog[$article->module_key]['name'] ?? $article->module_key;
                    $searchText = strtolower($moduleName.' '.$article->title.' '.($article->summary ?? ''));
                @endphp
                <a class="hg-card hg-article-card" data-hg-filter-group="articles"
                   data-hg-filter-text="{{ $searchText }}"
                   href="{{ route('helpguide.article', ['id' => $article->id, 'lang' => $selectedLanguage]) }}">
                    <div class="hg-article-bullet">→</div>
                    <div>
                        <h3>{{ $article->title }}</h3>
                        <div class="hg-muted">{{ $article->summary ?: 'Open this article to read the full guidance.' }}</div>
                        <div class="hg-meta">{{ $moduleName }}</div>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="hg-card hg-empty" data-hg-filter-empty="articles" style="display:none;margin-top:14px">No published article matches your search.</div>
    @endif
@endif
@endsection
