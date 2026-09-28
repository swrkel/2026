@extends('helpguide::layouts.admin', [
    'title' => $edit ? 'Edit Article' : 'Create Article',
    'heading' => $edit ? 'Edit Help Article' : 'Create Help Article',
    'subheading' => 'Save the English source immediately. Other active languages are translated automatically in the background.',
])

@section('hg_content')
<style>
.hg-editor-toolbar{display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px}.hg-editor-columns{display:grid;grid-template-columns:minmax(280px,32%) minmax(0,68%);gap:20px;align-items:start}.hg-editor-side{background:linear-gradient(180deg,#fbfdff,#f8fbff);border:1px solid #e0ebf6;border-radius:15px;padding:16px}.hg-editor-main{min-width:0}.hg-editor-main .note-editor{width:100%!important;max-width:100%!important;min-height:500px}.hg-editor-main .note-editable{min-height:420px;font-size:14px;line-height:1.65}.hg-module-search{width:100%;margin-bottom:6px}.hg-translation-info{display:flex;gap:12px;align-items:flex-start;border:1px solid #bfdbfe;background:#eff6ff;border-radius:13px;padding:13px 14px;margin-bottom:16px;color:#334155;font-size:12px;line-height:1.55}.hg-translation-info .hg-info-icon{width:32px;height:32px;min-width:32px;border-radius:10px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900}.hg-form-actions{display:flex;gap:9px;align-items:center;justify-content:flex-end;flex-wrap:wrap;margin-top:16px}.hg-form-actions .hg-btn{min-width:145px;justify-content:center}.hg-summary-help{margin-top:5px}.hg-publish-toggle{display:flex!important;align-items:center;gap:8px;padding:9px 10px;background:#fff;border:1px solid #dbe7f3;border-radius:10px;font-weight:700!important}.hg-publish-toggle input{width:auto!important;min-height:auto!important;margin:0!important}.hg-field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:920px){.hg-editor-columns{grid-template-columns:1fr}.hg-editor-main .note-editor{min-height:420px}.hg-field-row{grid-template-columns:1fr}}
</style>

<div class="hg-editor-toolbar">
    <a href="{{ route('helpguide.superadmin.articles.list', ['module' => $edit->module_key ?? $module]) }}" class="hg-link"><i class="fa fa-list"></i> List Articles</a>
    @if($edit)
        <a href="{{ route('helpguide.superadmin.articles.view', $edit->id) }}" target="_blank" class="hg-link"><i class="fa fa-eye"></i> Preview Current Article</a>
    @endif
</div>

<div class="hg-translation-info">
    <div class="hg-info-icon">↻</div>
    <div><strong>Fast save + background translation.</strong> Saving this page never waits for an external translation service. Each active non-English language is queued separately and retried automatically if the provider is temporarily unavailable.</div>
</div>

<div class="hg-card" style="padding:20px">
    <form method="post" action="{{ route('helpguide.superadmin.articles.save') }}" id="hg-article-form">
        @csrf
        @if($edit)<input type="hidden" name="id" value="{{ $edit->id }}">@endif

        <div class="hg-editor-columns">
            <aside class="hg-editor-side">
                <div class="hg-field">
                    <label>Module</label>
                    <input type="text" class="hg-module-search" data-target="hg-module-key" placeholder="Type to filter modules…" autocomplete="off">
                    <select name="module_key" id="hg-module-key" required size="1">
                        @foreach($catalog as $key => $meta)
                            <option value="{{ $key }}" @selected(old('module_key', $edit->module_key ?? $module) === $key)>{{ $meta['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="hg-field">
                    <label>English Title</label>
                    <input name="title" required maxlength="191" value="{{ old('title', $edit->title ?? '') }}" placeholder="Clear, task-focused article title">
                </div>

                <div class="hg-field">
                    <label>Slug <span class="hg-note">(optional)</span></label>
                    <input name="slug" maxlength="191" value="{{ old('slug', $edit->slug ?? '') }}" placeholder="Generated from title if blank">
                </div>

                <div class="hg-field">
                    <label>English Summary</label>
                    <textarea name="summary" rows="4" placeholder="One short sentence describing what this article helps the user do.">{{ old('summary', $edit->summary ?? '') }}</textarea>
                    <div class="hg-note hg-summary-help">Shown in Help Guide search results and article cards.</div>
                </div>

                <div class="hg-field-row">
                    <div class="hg-field">
                        <label>Sort Order</label>
                        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $edit->sort_order ?? 0) }}">
                    </div>
                    <div class="hg-field">
                        <label>Status</label>
                        <label class="hg-publish-toggle">
                            <input type="checkbox" name="status" value="1" @checked(old('status', $edit->status ?? 1))>
                            Published
                        </label>
                    </div>
                </div>
            </aside>

            <section class="hg-editor-main">
                <div class="hg-field" style="margin-bottom:0">
                    <label>English Content</label>
                    <textarea id="hg-content-editor" name="content">{{ old('content', $edit->content ?? '') }}</textarea>
                    <div class="hg-note" style="margin-top:7px">Use headings, short paragraphs, numbered steps and screenshots where they make the task easier to follow.</div>
                </div>
            </section>
        </div>

        @if($errors->any())
            <div class="hg-alert" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;margin-top:16px"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div>
        @endif

        <div class="hg-form-actions">
            @if($edit)
                <a class="hg-link" href="{{ route('helpguide.superadmin.articles.list', ['module' => $edit->module_key]) }}">Cancel</a>
            @endif
            <button class="hg-btn" type="submit" id="hg-save-article"><i class="fa fa-save"></i> {{ $edit ? 'Update Article' : 'Save Article' }}</button>
        </div>
    </form>
</div>
@endsection

@push('hg_scripts')
<script>
$(function () {
    var $editor = $('#hg-content-editor');

    $editor.summernote({
        height: 500,
        placeholder: 'Write the English help article here…',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onInit: function () {
                $editor.closest('.hg-editor-main').find('.note-editor').css({width:'100%',maxWidth:'100%'});
            },
            onImageUpload: function (files) {
                var editor = $(this);
                Array.prototype.forEach.call(files, function (file) {
                    var data = new FormData();
                    data.append('file', file);
                    data.append('_token', $('meta[name="csrf-token"]').attr('content'));
                    $.ajax({
                        url: '{{ route('helpguide.superadmin.articles.upload') }}',
                        method: 'POST',
                        data: data,
                        contentType: false,
                        processData: false
                    }).done(function (res) {
                        if (res && res.url) {
                            editor.summernote('insertImage', res.url);
                        } else {
                            alert('The image could not be uploaded.');
                        }
                    }).fail(function (xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.msg ? xhr.responseJSON.msg : 'The image could not be uploaded.';
                        alert(msg);
                    });
                });
            }
        }
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

    $('#hg-article-form').on('submit', function () {
        $('#hg-save-article').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving…');
    });
});
</script>
@endpush
