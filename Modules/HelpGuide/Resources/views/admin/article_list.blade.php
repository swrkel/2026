@extends('helpguide::layouts.admin', [
    'title' => 'List Articles',
    'heading' => 'Help Guide Articles',
    'subheading' => 'Manage published guidance, preview articles, and monitor background translations.',
])

@section('hg_content')
<style>
.hg-list-toolbar{display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}
.hg-list-tools{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.hg-list-tools .hg-filter{min-width:280px}
.hg-translation-mini{display:flex;gap:5px;align-items:center;flex-wrap:wrap}.hg-mini-badge{display:inline-flex;align-items:center;gap:4px;border-radius:999px;padding:4px 8px;font-size:10px;font-weight:800}.hg-mini-ready{background:#dcfce7;color:#166534}.hg-mini-pending{background:#fef3c7;color:#92400e}.hg-mini-working{background:#dbeafe;color:#1d4ed8}.hg-mini-failed{background:#fee2e2;color:#991b1b}.hg-list-meta{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:5px}.hg-list-slug{color:#94a3b8;font-size:11px}.hg-queue-note{border:1px solid #cfe0f3;background:linear-gradient(135deg,#f8fbff,#eef6ff);border-radius:13px;padding:12px 14px;margin-bottom:15px;color:#475569;font-size:12px;line-height:1.5}.hg-queue-note strong{color:#1d4ed8}
@media(max-width:850px){.hg-list-tools{width:100%}.hg-list-tools .hg-filter{min-width:100%;width:100%}}
</style>

<div class="hg-card">
    <div class="hg-list-toolbar">
        <div>
            <h2 style="margin:0 0 4px">Articles</h2>
            <div class="hg-note">Published articles appear in the business Help Guide when their module is enabled.</div>
        </div>
        <a href="{{ route('helpguide.superadmin.articles') }}" class="hg-btn"><i class="fa fa-plus"></i> Create Article</a>
    </div>

    <div class="hg-queue-note">
        <strong>Background translation:</strong>
        article saves return immediately. Each active language is queued separately and processed by the Laravel scheduler.
        @if(!$queueReady)
            <span style="color:#b45309;font-weight:800"> The translation queue table is not installed yet; run the latest HelpGuide migration.</span>
        @endif
    </div>

    <div class="hg-list-toolbar" style="margin-bottom:13px">
        <div class="hg-list-tools">
            <div class="hg-filter">
                <i class="fa fa-search"></i>
                <input type="text" id="hg-article-filter" placeholder="Type to filter articles…" autocomplete="off">
            </div>

            <form method="get" style="margin:0;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="text" class="hg-module-search" data-target="hg-module-filter"
                       placeholder="Type to filter modules…" autocomplete="off"
                       style="width:190px">
                <select name="module" id="hg-module-filter" onchange="this.form.submit()" style="min-width:210px">
                    <option value="">All Modules</option>
                    @foreach($catalog as $key => $meta)
                        <option value="{{ $key }}" @selected($module === $key)>{{ $meta['name'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div style="overflow:auto;border:1px solid #e6eef7;border-radius:14px">
        <table class="hg-table" id="hg-article-table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Article</th>
                    <th>Publish</th>
                    <th>Translations</th>
                    <th class="hg-actions-cell">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($articles as $article)
                @php
                    $ts = $translationStatus[$article->id] ?? ['ready'=>0,'pending'=>0,'translating'=>0,'failed'=>0,'total'=>0];
                    $moduleName = $catalog[$article->module_key]['name'] ?? $article->module_key;
                @endphp
                <tr>
                    <td><strong>{{ $moduleName }}</strong></td>
                    <td>
                        <strong>{{ $article->title }}</strong>
                        <div class="hg-list-meta">
                            <span class="hg-list-slug">/{{ $article->slug }}</span>
                            <span class="hg-note">Sort: {{ $article->sort_order }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="hg-badge {{ $article->status ? 'on' : 'off' }}">{{ $article->status ? 'Published' : 'Draft' }}</span>
                    </td>
                    <td>
                        @if(($ts['total'] ?? 0) === 0)
                            <span class="hg-note">English only</span>
                        @else
                            <div class="hg-translation-mini">
                                @if($ts['ready'])<span class="hg-mini-badge hg-mini-ready">✓ {{ $ts['ready'] }} ready</span>@endif
                                @if($ts['translating'])<span class="hg-mini-badge hg-mini-working">↻ {{ $ts['translating'] }} translating</span>@endif
                                @if($ts['pending'])<span class="hg-mini-badge hg-mini-pending">◷ {{ $ts['pending'] }} pending</span>@endif
                                @if($ts['failed'])<span class="hg-mini-badge hg-mini-failed">! {{ $ts['failed'] }} failed</span>@endif
                            </div>
                        @endif
                    </td>
                    <td class="hg-actions-cell">
                        <div class="hg-actions">
                            <a class="hg-link" href="{{ route('helpguide.superadmin.articles.view', $article->id) }}" target="_blank"><i class="fa fa-eye"></i> View</a>
                            <a class="hg-link" href="{{ route('helpguide.superadmin.articles', ['module' => $article->module_key, 'edit' => $article->id]) }}"><i class="fa fa-pencil"></i> Edit</a>
                            <form method="post" action="{{ route('helpguide.superadmin.articles.retry', $article->id) }}">
                                @csrf<button class="hg-link" type="submit"><i class="fa fa-refresh"></i> Queue Translation</button>
                            </form>
                            <form method="post" action="{{ route('helpguide.superadmin.articles.delete', $article->id) }}" onsubmit="return confirm('Delete this Help Guide article?')">
                                @csrf<button class="hg-link hg-danger" type="submit"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr id="hg-empty-row"><td colspan="5" style="text-align:center;padding:42px;color:#69758b">No articles yet. Click <strong>Create Article</strong> to add the first one.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div id="hg-no-match" style="display:none;text-align:center;padding:28px;color:#69758b">No article matches that filter.</div>
    </div>
</div>
@endsection

@push('hg_scripts')
<script>
$(function () {
    $('#hg-article-filter').on('input', function () {
        var q = $.trim($(this).val()).toLowerCase();
        var shown = 0;
        $('#hg-article-table tbody tr').each(function () {
            if (this.id === 'hg-empty-row') { return; }
            var hit = q === '' || $(this).text().toLowerCase().indexOf(q) !== -1;
            $(this).toggle(hit);
            if (hit) { shown++; }
        });
        $('#hg-no-match').toggle(q !== '' && shown === 0);
    });

    $('.hg-module-search').each(function () {
        var $search = $(this), $select = $('#' + $search.data('target'));
        if (!$select.length) { return; }
        var options = $select.find('option').map(function () { return {value:this.value,text:$(this).text()}; }).get();
        $search.on('input', function () {
            var term = $.trim($search.val()).toLowerCase(), selected = $select.val();
            $select.empty();
            $.each(options, function (_, opt) {
                if (term === '' || opt.text.toLowerCase().indexOf(term) !== -1 || opt.value === selected) {
                    $select.append($('<option>', {value:opt.value,text:opt.text}));
                }
            });
            $select.val(selected).attr('size', term === '' ? 1 : Math.min(8, $select.find('option').length));
        });
        $select.on('change blur', function () { $select.attr('size', 1); });
    });
});
</script>
@endpush
