@extends('helpguide::layouts.standalone')
@section('title',$article->title.' - Help Guide')
@section('content')

<div style="margin-bottom:14px;display:flex;gap:14px;flex-wrap:wrap">
    <a href="{{ route('helpguide.module', ['moduleKey' => $article->module_key, 'lang' => $selectedLanguage]) }}" class="hg-back">← {{ $moduleName }}</a>
    <a href="{{ route('helpguide.index', ['lang' => $selectedLanguage]) }}" class="hg-back">All Help Modules</a>
</div>

<div class="hg-page-hero">
    <div>
        <div class="hg-meta" style="margin:0 0 7px;color:#2563eb">{{ $moduleName }}</div>
        <h1 class="hg-title">{{ $article->title }}</h1>
        @if(!empty($article->summary))
            <p class="hg-sub">{{ $article->summary }}</p>
        @endif
    </div>
    @include('helpguide::partials.language')
</div>

@if($selectedLanguage !== 'en' && empty($article->is_translated))
    <div class="hg-alert">This translation is still being prepared. The current English article is shown until the selected language is ready.</div>
@endif

<article class="hg-card" style="padding:26px 30px">
    <div class="hg-content">{!! $article->content !!}</div>
</article>

<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:16px">
    <a class="hg-btn secondary" href="{{ route('helpguide.module', ['moduleKey' => $article->module_key, 'lang' => $selectedLanguage]) }}">← More {{ $moduleName }} Articles</a>
    <button class="hg-btn secondary" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">Back to top ↑</button>
</div>
@endsection
