@extends('helpguide::layouts.standalone')
@section('title',$moduleName.' - Help Guide')
@section('content')

<div style="margin-bottom:14px">
    <a href="{{ route('helpguide.index', ['lang' => $selectedLanguage]) }}" class="hg-back">← All Help Modules</a>
</div>

<div class="hg-page-hero">
    <div>
        <h1 class="hg-title">{{ $moduleName }}</h1>
        <p class="hg-sub">Help Guide for {{ $business['name'] }}. Choose an article below or type in the search bar above.</p>
    </div>
    @include('helpguide::partials.language')
</div>

@if($articles->isEmpty())
    <div class="hg-card hg-empty">No published help articles are available for this module yet.</div>
@else
    <div class="hg-grid">
        @foreach($articles as $article)
            @php
                $preview = $article->summary ?: 'Open this article to read the full guidance.';
                $searchText = strtolower($article->title.' '.$preview);
            @endphp
            <a class="hg-card hg-article-card" data-hg-filter-group="module-articles"
               data-hg-filter-text="{{ $searchText }}"
               href="{{ route('helpguide.article', ['id' => $article->id, 'lang' => $selectedLanguage]) }}">
                <div class="hg-article-bullet">→</div>
                <div>
                    <h3>{{ $article->title }}</h3>
                    <div class="hg-muted">{{ $preview }}</div>
                    <div class="hg-meta">Read article</div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="hg-card hg-empty" data-hg-filter-empty="module-articles" style="display:none;margin-top:14px">No article matches your search.</div>
@endif
@endsection
