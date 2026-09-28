@extends('layouts.app')
@section('title', __('membership::lang.membership'))

@section('content')

    <style>
        /* IS1602: keep Membership Settings tab text visible after every tab switch. */
        .membership-settings-tabs .nav-tabs > li > a,
        .membership-settings-tabs .nav-tabs > li > a:focus,
        .membership-settings-tabs .nav-tabs > li > a:hover,
        .membership-settings-tabs .nav-tabs > li.active > a,
        .membership-settings-tabs .nav-tabs > li.active > a:focus,
        .membership-settings-tabs .nav-tabs > li.active > a:hover,
        .membership-settings-tabs .nav-tabs > li > a.active,
        .membership-settings-tabs .nav-tabs > li > a.active:focus,
        .membership-settings-tabs .nav-tabs > li > a.active:hover {
            color: #111827 !important;
            opacity: 1 !important;
            visibility: visible !important;
            font-weight: 600;
        }
        .membership-settings-tabs .nav-tabs > li > a span,
        .membership-settings-tabs .nav-tabs > li.active > a span {
            color: inherit !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>


    <section class="content">
        <div class="page-title-area">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <div class="breadcrumbs-area clearfix">
                        <h4 class="page-title pull-left">{{ __('membership::lang.membership') }}</h4>
                        <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                            <li><span></span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="settlement_tabs membership-settings-tabs">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item active">
                            <a class="nav-link active" data-toggle="tab" href="#business_type_tab" role="tab">
                                <span>{{ __('membership::lang.business_type') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#point_settings_tab" role="tab">
                                <span>{{ __('membership::lang.point_settings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#membership_settings_tab" role="tab">
                                <span>{{ __('membership::lang.prefix_and_starting_numbers') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#business_name_tab" role="tab">
                                <span>{{ __('membership::lang.Business_name') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#card_settings_tab" role="tab">
                                <span>{{ __('membership::lang.card_settings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#signature_tab" role="tab">
                                <span>{{ __('membership::lang.authorized_signature') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane active" id="business_type_tab" role="tabpanel">
                @include('membership::partials.bussiness_type')
            </div>
            <div class="tab-pane" id="point_settings_tab" role="tabpanel">
                @include('membership::partials.point_settings')
            </div>

            <div class="tab-pane" id="membership_settings_tab" role="tabpanel">
                @include('membership::partials.membership_settings', ['settings' => $settings ?? null])
            </div>

            <div class="tab-pane" id="business_name_tab" role="tabpanel">
                @include('membership::partials.business_name')
            </div>

            <div class="tab-pane" id="card_settings_tab" role="tabpanel">
                @include('membership::partials.card_settings')
            </div>

            <div class="tab-pane" id="signature_tab" role="tabpanel">
                @include('membership::partials.signatures')
            </div>
        </div>
    </section>

    <div class="modal fade member_modal" tabindex="-1" role="dialog" aria-labelledby="memberModalLabel"></div>
@endsection

@section('javascript')
<script>
    $(document).on('click', '#add_business_type', function () {

        let newValue = $('#business_type_add').val().trim();
        let mainInput = $('#business_type');

        if (newValue === '') {
            return;
        }

        let existingValue = mainInput.val();

        if (existingValue === '') {
            mainInput.val(newValue);
        } else {
            mainInput.val(existingValue + ', ' + newValue);
        }

        // Clear input after add
        $('#business_type_add').val('');
    });
</script>

    <script>
        $(document).ready(function() {
            var tabContainer = $('.settlement_tabs');
            var tabSelector = '.nav-tabs a[data-toggle="tab"]';
            var tabStorageKey = 'membershipActiveTab:' + window.location.pathname;
            var allowedTabIds = ['business_type_tab', 'point_settings_tab', 'membership_settings_tab', 'business_name_tab', 'card_settings_tab', 'signature_tab'];

            function getQueryParam(name) {
                try {
                    if (window.URLSearchParams) {
                        return (new URLSearchParams(window.location.search)).get(name);
                    }
                } catch (e) {}
                try {
                    var search = window.location.search || '';
                    if (search.indexOf('?') === 0) search = search.substring(1);
                    var parts = search.split('&');
                    for (var i = 0; i < parts.length; i++) {
                        var kv = parts[i].split('=');
                        if (decodeURIComponent(kv[0] || '') === name) {
                            return decodeURIComponent((kv[1] || '').replace(/\+/g, ' '));
                        }
                    }
                } catch (e) {}
                return null;
            }

            function safeSessionGet(key) {
                try { return window.sessionStorage ? sessionStorage.getItem(key) : null; } catch (e) { return null; }
            }

            function safeSessionSet(key, value) {
                try { if (window.sessionStorage) { sessionStorage.setItem(key, value); } } catch (e) {}
            }

            var activeTab = getQueryParam('tab') || safeSessionGet(tabStorageKey);

            if (activeTab && allowedTabIds.indexOf(activeTab) !== -1) {
                tabContainer.find('.nav-tabs a[href="#' + activeTab + '"]').tab('show');
            }

            tabContainer.on('shown.bs.tab', tabSelector, function(e) {
                var tabId = $(e.target).attr('href').substring(1);
                if (allowedTabIds.indexOf(tabId) !== -1) {
                    safeSessionSet(tabStorageKey, tabId);
                }
            });

            $('form').on('submit', function() {
                var activeTabPane = tabContainer.closest('.content').find('.tab-pane.active').attr('id');
                if (activeTabPane && allowedTabIds.indexOf(activeTabPane) !== -1) {
                    safeSessionSet(tabStorageKey, activeTabPane);
                }
            });
        });
    </script>
  <script>
$(document).on('change', '#signature_file', function () {
    const file = this.files[0];
    const previewContainer = $('#image_preview_container');
    const previewImage = $('#signature_preview');
    const fileInfo = $('#file_info');

    // Reset
    previewImage.hide().attr('src', '');
    fileInfo.hide().text('');

    if (!file) {
        previewContainer.hide();
        return;
    }

    const fileType = file.type;

    // ✅ IMAGE PREVIEW
    if (fileType.startsWith('image/')) {
        const objectURL = URL.createObjectURL(file);
        previewImage.attr('src', objectURL).show();
        previewContainer.show();

        previewImage.on('load', function () {
            URL.revokeObjectURL(objectURL);
        });
        return;
    }

    // ✅ PDF OR WORD (NO IMAGE PREVIEW)
    const allowedDocs = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    if (allowedDocs.includes(fileType)) {
        fileInfo.html(
            `<i class="fa fa-file"></i> <strong>${file.name}</strong><br>
             <small>${(file.size / 1024).toFixed(1)} KB</small>`
        ).show();

        previewContainer.show();
        return;
    }

    // ❌ INVALID FILE
    alert('{{ __("messages.please_select_valid_file") }}');
    $(this).val('');
    previewContainer.hide();
});
</script>


    <script src="{{ asset('js/membership/membership_settings_final.js') }}?v={{ file_exists(public_path('js/membership/membership_settings_final.js')) ? filemtime(public_path('js/membership/membership_settings_final.js')) : time() }}"></script>


<script src="{{ asset('js/membership/membership_settings_is1618.js') }}?v={{ file_exists(public_path('js/membership/membership_settings_is1618.js')) ? filemtime(public_path('js/membership/membership_settings_is1618.js')) : time() }}"></script>
@endsection
