@extends('helpguide::layouts.admin', [
    'title' => 'View Article',
    'heading' => 'Article Preview',
    'subheading' => 'Preview the saved English source and review the current translation states.',
])

@section('hg_content')
<style>
.hg-preview-head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px}.hg-preview-title{font-size:24px;font-weight:850;color:#0f172a;margin:0 0 6px}.hg-preview-meta{display:flex;gap:7px;flex-wrap:wrap}.hg-preview-pill{display:inline-flex;border-radius:999px;padding:5px 9px;background:#eef6ff;color:#1d4ed8;font-size:11px;font-weight:800}.hg-preview-body{font-size:14px;line-height:1.75;color:#27364a}.hg-preview-body img{max-width:100%;height:auto;border-radius:12px}.hg-preview-body table{max-width:100%;display:block;overflow:auto;border-collapse:collapse}.hg-preview-body td,.hg-preview-body th{border:1px solid #dbe7f3;padding:8px}.hg-translation-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}.hg-tr-card{border:1px solid #dbe7f3;border-radius:12px;background:#fbfdff;padding:12px}.hg-tr-code{font-size:11px;color:#64748b;text-transform:uppercase;font-weight:850}.hg-tr-title{font-weight:800;margin:3px 0 7px}.hg-tr-status{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:850}.hg-tr-ready{background:#dcfce7;color:#166534}.hg-tr-pending{background:#fef3c7;color:#92400e}.hg-tr-translating{background:#dbeafe;color:#1d4ed8}.hg-tr-failed{background:#fee2e2;color:#991b1b}
</style>

<div class="hg-card">
    <div class="hg-preview-head">
        <div>
            <div class="hg-preview-meta" style="margin-bottom:8px">
                <span class="hg-preview-pill">{{ $catalog[$article->module_key]['name'] ?? $article->module_key }}</span>
                <span class="hg-badge {{ $article->status ? 'on' : 'off' }}">{{ $article->status ? 'Published' : 'Draft' }}</span>
            </div>
            <h2 class="hg-preview-title">{{ $article->title }}</h2>
            @if($article->summary)<div class="hg-note">{{ $article->summary }}</div>@endif
        </div>
        <div class="hg-actions">
            <a class="hg-link" href="{{ route('helpguide.superadmin.articles', ['module'=>$article->module_key,'edit'=>$article->id]) }}"><i class="fa fa-pencil"></i> Edit</a>
            <a class="hg-link" href="{{ route('helpguide.superadmin.articles.list', ['module'=>$article->module_key]) }}"><i class="fa fa-list"></i> List Articles</a>
        </div>
    </div>

    <hr style="border:0;border-top:1px solid #e7eef6;margin:18px 0">
    <div class="hg-preview-body">{!! $article->content !!}</div>
</div>

<div class="hg-card">
    <h2>Translation Status</h2>
    @if($translations->isEmpty())
        <div class="hg-note">No non-English translation records exist yet.</div>
    @else
        <div class="hg-translation-grid">
            @foreach($translations as $translation)
                @php
                    $status = in_array($translation->translation_status, ['ready','pending','translating','failed']) ? $translation->translation_status : 'failed';
                    $language = $languages->get($translation->language_code);
                @endphp
                <div class="hg-tr-card">
                    <div class="hg-tr-code">{{ $language->name ?? $translation->language_code }}</div>
                    <div class="hg-tr-title">{{ $translation->title }}</div>
                    <span class="hg-tr-status hg-tr-{{ $status }}">{{ ucfirst($status) }}</span>
                    @if($translation->translation_error)
                        <div class="hg-note" style="margin-top:7px;color:#991b1b">{{ \Illuminate\Support\Str::limit($translation->translation_error, 180) }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
