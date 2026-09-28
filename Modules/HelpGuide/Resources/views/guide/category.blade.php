@extends('helpguide::layouts.standalone')
@section('title',$category->name.' - Help Guide')
@section('content')

<div style="margin-bottom:14px">
    <a href="{{ route('helpguide.index', ['lang' => $selectedLanguage]) }}" class="hg-back">← All Help</a>
</div>

<div class="hg-page-hero">
    <div>
        <div class="hg-meta" style="margin:0 0 7px;color:#2563eb">Help Desk Category</div>
        <h1 class="hg-title">{{ $category->name }}</h1>
        <p class="hg-sub">{{ $business['name'] }} — {{ $articles->count() }} published {{ $articles->count() === 1 ? 'article' : 'articles' }} in this category.</p>
    </div>
    @include('helpguide::partials.language')
</div>

@if($articles->isEmpty())
    <div class="hg-card hg-empty">No published articles are available in this category yet.</div>
@else
    <div class="hg-grid">
        @foreach($articles as $article)
            @php
                $preview = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($article->content ?? '')))), 140);
                $searchText = strtolower(($article->title ?? '').' '.$preview);
            @endphp
            <a class="hg-card hg-article-card" data-hg-filter-group="category-articles"
               data-hg-filter-text="{{ $searchText }}"
               href="{{ route('helpguide.helpdesk.article', ['id' => $article->id, 'lang' => $selectedLanguage]) }}">
                <div class="hg-article-bullet">→</div>
                <div>
                    <h3>{{ $article->title }}</h3>
                    <div class="hg-muted">{{ $preview ?: 'Open this article to read the full guidance.' }}</div>
                    <div class="hg-meta">Read article</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="hg-card hg-empty" data-hg-filter-empty="category-articles" style="display:none;margin-top:14px">No article matches your search.</div>
@endif
@endsection
