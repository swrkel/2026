@extends('helpguide::layouts.standalone')
@section('title',$article->title.' - Help Guide')
@section('content')

<div style="margin-bottom:14px;display:flex;gap:14px;flex-wrap:wrap">
    @if($category)
        <a href="{{ route('helpguide.category', ['id' => $category->id, 'lang' => $selectedLanguage]) }}" class="hg-back">← {{ $category->name }}</a>
    @endif
    <a href="{{ route('helpguide.index', ['lang' => $selectedLanguage]) }}" class="hg-back">All Help</a>
</div>

<div class="hg-page-hero">
    <div>
        <div class="hg-meta" style="margin:0 0 7px;color:#2563eb">{{ $category->name ?? 'Help Desk' }}</div>
        <h1 class="hg-title">{{ $article->title }}</h1>
        <p class="hg-sub">Help article for {{ $business['name'] }}.</p>
    </div>
    @include('helpguide::partials.language')
</div>

<article class="hg-card" style="padding:26px 30px">
    <div class="hg-content">{!! $article->content !!}</div>
</article>

<div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:16px">
    @if($category)
        <a class="hg-btn secondary" href="{{ route('helpguide.category', ['id' => $category->id, 'lang' => $selectedLanguage]) }}">← More {{ $category->name }} Articles</a>
    @else
        <a class="hg-btn secondary" href="{{ route('helpguide.index', ['lang' => $selectedLanguage]) }}">← All Help</a>
    @endif
    <button class="hg-btn secondary" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">Back to top ↑</button>
</div>
@endsection
