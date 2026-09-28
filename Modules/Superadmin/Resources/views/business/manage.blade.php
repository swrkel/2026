@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Business')

@section('content')

<style>
 .checkbox input[type="checkbox"]:not(:checked) + label:before {
  content: "";
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid red;
  margin-right: 5px;
}


/* Background color for "danger" state */
        .bg-danger {
            background-color: #F6C8C8 !important; 
            color: #000 !important;
        }
        
         /*Background color for "success" state */
        .bg-success {
            background-color: #D1E6C0 !important; 
            color: #000 !important;
        }

</style>

<style>
    .input-icheck-red{
      accent-color: red !important;
      outline: 1px solid red !important;
      margin-right: 5px !important;
    }


</style>
<style>
/* SUPERADMIN MANAGE: section-level Select All controls */
.sa-section-select-all-row {
    background: rgba(255, 255, 255, 0.55);
    border: 1px dashed #8abf6f;
    border-radius: 6px;
    margin: 8px 15px 14px 15px;
    padding: 8px 12px;
    text-align: left;
}
.sa-section-select-all-label {
    margin: 0;
    font-weight: 700;
    color: #1f4e1f;
    cursor: pointer;
}
.sa-section-select-all-label input {
    margin-right: 8px !important;
}
.sa-section-select-all-note {
    color: #6c757d;
    font-size: 12px;
    margin-left: 10px;
    font-weight: 400;
}
</style>
<style>
    .sa-floating-save-wrap {
        position: fixed;
        top: 12px;
        right: 22px;
        z-index: 11050;
        width: 190px;
        padding: 7px;
        border-radius: 12px;
        background: rgba(255,255,255,.96);
        box-shadow: 0 10px 28px rgba(31,45,61,.20);
        border: 1px solid #dce6f2;
    }
    /* Dock Save inside the permission-search floater. This prevents the
       independent fixed button from moving/disappearing while scrolling,
       collapsing sections, or hovering near the search panel. */
    #sa_permission_explorer .sa-floating-save-wrap.sa-save-docked {
        position: static !important;
        top: auto !important;
        right: auto !important;
        z-index: auto !important;
        width: 145px;
        min-width: 145px;
        flex: 0 0 145px;
        margin: 0 0 0 8px;
        padding: 0;
        border: 0;
        border-radius: 8px;
        background: transparent;
        box-shadow: none;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
    }
    #sa_permission_explorer .sa-floating-save-wrap.sa-save-docked .sa-floating-save-btn {
        min-height: 48px;
        height: 100%;
        margin: 0;
        white-space: nowrap;
    }
    .sa-floating-save-btn {
        width: 100%;
        min-height: 44px;
        font-weight: 700;
        border-radius: 8px;
    }
    .sa-floating-save-btn.is-saving { opacity:.75; cursor:wait; }
    @media (max-width: 991px) {
        .sa-floating-save-wrap:not(.sa-save-docked) { top:auto; bottom:18px; right:16px; width:150px; }
        #sa_permission_explorer .sa-floating-save-wrap.sa-save-docked {
            width: 110px;
            min-width: 110px;
            flex-basis: 110px;
        }
    }
</style>


<style>
    .selection-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    .db-selection-card {
        background: #fff;
        border: 1px solid #d2d6de;
        border-top: 3px solid #3c8dbc;
        border-radius: 3px;
        padding: 10px;
        min-width: 250px;
        max-width: 350px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    }
    .db-selection-title {
        font-weight: bold;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        margin-bottom: 8px;
        display: block;
        font-size: 14px;
    }
    .biz-selection-item {
        margin-bottom: 8px;
        display: block;
    }
    .biz-selection-name {
        font-weight: 600;
        font-size: 13px;
        color: #3c8dbc;
        display: block;
        margin-bottom: 3px;
    }
    .loc-selection-list {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        padding-left: 5px;
    }
    .loc-selection-tag {
        background: #e7f3ff;
        border: 1px solid #cce5ff;
        color: #004085;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 10px;
        display: inline-block;
    }
    .sa-manage-save-flash {
        position: fixed;
        top: 18px;
        right: 22px;
        z-index: 2147483000;
        width: min(520px, calc(100vw - 44px));
        margin: 0;
        padding: 16px 52px 16px 18px;
        border: 0;
        border-radius: 10px;
        box-shadow: 0 14px 38px rgba(15, 23, 42, .28);
        font-size: 15px;
        font-weight: 600;
        line-height: 1.45;
    }
    .sa-manage-save-flash .close {
        position: absolute;
        top: 8px;
        right: 12px;
        font-size: 26px;
        opacity: .8;
    }
    .sa-manage-save-flash .fa {
        margin-right: 8px;
    }
    /* Let the browser skip layout/paint work for the hundreds of collapsed
       off-screen permission cards. The selected card becomes fully visible
       immediately when opened by the permission explorer. */
    #custom_permission_form .box,
    #custom_permission_form .card,
    #custom_permission_form .panel,
    #custom_permission_form .module-permission-section,
    #custom_permission_form .permission-section {
        content-visibility: auto;
        contain-intrinsic-size: 240px;
    }
    #custom_permission_form .sa-permission-section-expanded,
    #custom_permission_form .sa-manage-force-visible {
        content-visibility: visible;
    }
</style>

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'superadmin::lang.individual_company_permissions' )
        </h1>
    </section>
    
    <!-- Main content -->
    <section class="content">
    <div class="sa-floating-save-wrap" id="sa_floating_save_wrap">
        <button type="button" class="btn btn-success sa-floating-save-btn" id="sa_floating_save_btn" aria-label="Save manage permissions">
            <i class="fa fa-save"></i> @lang('superadmin::lang.save')
        </button>
    </div>
        @if(empty($subscription))
            <div class="alert alert-warning">
                @lang('superadmin::lang.please_activate_subscription')
            </div>
        @endif

        @php
            $manageStatus = session('status');
            if (is_object($manageStatus)) {
                $manageStatus = method_exists($manageStatus, 'toArray')
                    ? $manageStatus->toArray()
                    : (array) $manageStatus;
            }
            if (is_string($manageStatus) && $manageStatus !== '') {
                $manageStatus = ['success' => 1, 'msg' => $manageStatus];
            }
            if (!is_array($manageStatus)) {
                $manageStatus = [];
            }
            if (empty($manageStatus) && in_array(request('manage_status'), ['success', 'error'], true)) {
                $queryMessage = trim((string) request('manage_message', ''));
                $manageStatus = [
                    'success' => request('manage_status') === 'success' ? 1 : 0,
                    'msg' => $queryMessage !== ''
                        ? $queryMessage
                        : (request('manage_status') === 'success'
                            ? 'Manage permissions saved successfully.'
                            : 'Manage permissions could not be saved. Please try again.'),
                ];
            }
            $manageStatusSuccess = !empty($manageStatus['success']);
            $manageStatusMsg = !empty($manageStatus['msg'])
                ? (string) $manageStatus['msg']
                : ($manageStatusSuccess ? __('lang_v1.success') : __('messages.something_went_wrong'));
        @endphp
        @if(!empty($manageStatus))
            <div id="sa_manage_save_flash"
                 class="sa-manage-save-flash alert {{ $manageStatusSuccess ? 'alert-success' : 'alert-danger' }} alert-dismissible"
                 role="alert" aria-live="assertive">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="fa {{ $manageStatusSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle' }}" aria-hidden="true"></i>
                {{ $manageStatusMsg }}
            </div>
        @endif
        <div class="box">
                @can('superadmin')
                                
                <div class="box-body">
                    
                    {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\BusinessController@saveManage',
                    $business->id), 'method' => 'post', 'id' => 'custom_permission_form', 'enctype' => 'multipart/form-data',
                    'novalidate' => 'novalidate'])
                    !!}
                    {{-- This field stays at the beginning of the form. JavaScript puts every
                         non-file Manage value here and disables the original controls before
                         native submission, avoiding PHP max_input_vars truncation completely. --}}
                    <input type="hidden" name="manage_form_payload" id="manage_form_payload" value="">

                    </div>

                    
                        @include('superadmin::business.manage_sections.section_01')
                        @include('superadmin::business.manage_sections.section_02')
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                              <div class="card-header text-center">
                                <h4> @lang('superadmin::lang.enable_restaurant')</h4>
                                <hr>
                              </div>
                              <div class="card-body">
                                  <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>

                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.enable_restaurant')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('enable_restaurant', 0) !!}
                                    {!! Form::checkbox('enable_restaurant', 1, !empty($manage_module_enable['enable_restaurant'])
                                    ?
                                    true : false, ['class' => 'input-icheck-red
                                    ch_select restaurant_module']) !!}
                                </div>
                                <div class="col-md-1">
                                        @if(empty($module_activation_data['restaurant_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['restaurant_expiry_date']))
                                        @if(strtotime($module_activation_data['restaurant_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif

                                        @if(strtotime($module_activation_data['restaurant_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif

                                    @endif
                                    </div>

                                    <div class="col-md-1">
                                        {!! Form::select('restaurant_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['restaurant_interval']) ?
                                        $module_activation_data['restaurant_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}

                                    </div>

                                    <div class="col-md-1">
                                        {!! Form::text('restaurant_length', !empty($module_activation_data['restaurant_length']) ?
                                        $module_activation_data['restaurant_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>

                                    <div class="col-md-2">
                                        {!! Form::date('restaurant_activated_on', !empty($module_activation_data['restaurant_activated_on']) ?
                                        $module_activation_data['restaurant_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::date('restaurant_expiry_date', !empty($module_activation_data['restaurant_expiry_date']) ?
                                        $module_activation_data['restaurant_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                    </div>
                                    <div class="col-md-2">
                                        {!! Form::text('restaurant_price', !empty($module_activation_data['restaurant_price']) ?
                                        $module_activation_data['restaurant_price'] : null, ['class' => 'form-control']) !!}
                                    </div>

                            </div>

                            <div class="row page_permission_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;">
                                        <b>@lang('superadmin::lang.select_locations'):</b>
                                    </p>
                                </div>

                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox"
                                                class="check_all input-icheck-red"
                                                data-target=".page_permission_locations">
                                            {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>

                                <div class="clearfix"></div>

                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox(
                                                    'module_permission_location[page_permission][document_management]['.$location->id.']',
                                                    1,
                                                    !empty($module_permission_locations_value['page_permission']['document_management'])
                                                    ? array_key_exists(
                                                        $location->id,
                                                        $module_permission_locations_value['page_permission']['document_management']
                                                    )
                                                    : false,
                                                    ['class' => 'input-icheck-red ch_select']
                                                ) !!}
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <hr>

                        @include('superadmin::business.manage_sections.section_03')
                        @include('superadmin::business.manage_sections.section_04')
                        @include('superadmin::business.manage_sections.section_05')
                        @include('superadmin::business.manage_sections.section_06')
                        @include('superadmin::business.manage_sections.section_07')
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> Business Images</h4>
                            <hr>
                          </div>
                            <div class="card-body">
                                <!-- Row 1: Home Banner and Login Page Images -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('home_banner_image', __('superadmin::lang.home_banner_image') . ':') !!}
                                            {!! Form::file('home_banner_image', [
                                                'accept' => 'image/*', 
                                                'class' => 'form-control-file',
                                                'id' => 'home_banner_image'
                                            ]) !!}
                                            <small class="form-text text-muted">
                                                Recommended size: 1920x1080px (JPEG, PNG)
                                            </small>
                                            @if($business->home_banner_path)
                                                <div class="mt-2 image-preview-container">
                                                    <img src="{{ Storage::url($business->home_banner_path) }}" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 150px;">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm delete-image"
                                                            data-url="{{ route('business.images.delete', ['business' => $business->id, 'type' => 'home_banner']) }}">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('login_page_image', __('superadmin::lang.login_page_image') . ':') !!}
                                            {!! Form::file('login_page_image', [
                                                'accept' => 'image/*', 
                                                'class' => 'form-control-file',
                                                'id' => 'login_page_image'
                                            ]) !!}
                                            @if($business->login_image_path)
                                                <div class="mt-2 image-preview-container">
                                                    <img src="{{ Storage::url($business->login_image_path) }}" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 150px;">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm delete-image"
                                                            data-url="{{ route('business.images.delete', ['business' => $business->id, 'type' => 'login_image']) }}">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Row 2: Register Page Image and Site Logo -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('register_page_image', __('superadmin::lang.register_page_image') . ':') !!}
                                            {!! Form::file('register_page_image', [
                                                'accept' => 'image/*', 
                                                'class' => 'form-control-file',
                                                'id' => 'register_page_image'
                                            ]) !!}
                                            <small class="form-text text-muted">
                                                Recommended size: 1920x1080px (JPEG, PNG)
                                            </small>
                                            @if($business->register_image_path)
                                                <div class="mt-2 image-preview-container">
                                                    <img src="{{ Storage::url($business->register_image_path) }}" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 150px;">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm delete-image"
                                                            data-url="{{ route('business.images.delete', ['business' => $business->id, 'type' => 'register_image']) }}">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('site_logo', __('superadmin::lang.site_logo') . ':') !!}
                                            {!! Form::file('site_logo', [
                                                'accept' => 'image/*', 
                                                'class' => 'form-control-file',
                                                'id' => 'site_logo'
                                            ]) !!}
                                            <small class="form-text text-muted">
                                                Recommended size: 300x100px (PNG with transparent background)
                                            </small>
                                            @if($business->site_logo_path)
                                                <div class="mt-2 image-preview-container">
                                                    <img src="{{ Storage::url($business->site_logo_path) }}" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 100px; background: #f8f9fa;">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm delete-image"
                                                            data-url="{{ route('business.images.delete', ['business' => $business->id, 'type' => 'site_logo']) }}">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Row 3: Favicon -->
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            {!! Form::label('favicon', __('superadmin::lang.favicon') . ':') !!}
                                            {!! Form::file('favicon', [
                                                'accept' => 'image/x-icon,image/vnd.microsoft.icon,image/png', 
                                                'class' => 'form-control-file',
                                                'id' => 'favicon'
                                            ]) !!}
                                            <small class="form-text text-muted">
                                                Recommended size: 32x32px (ICO or PNG format)
                                            </small>
                                            @if($business->favicon_path)
                                                <div class="mt-2 image-preview-container">
                                                    <img src="{{ Storage::url($business->favicon_path) }}" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 32px; width: auto;">
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm delete-image"
                                                            data-url="{{ route('business.images.delete', ['business' => $business->id, 'type' => 'favicon']) }}">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                          
                    </div>
                 
                    
                @endcan
            </div>
        </div>
        <div class="box" style="padding-left: 50px">
            
        </div>
        <input type="hidden" name="opt_vars" id="opt_vars" value="">
        <div class="clearfix"></div>
        

        {{-- Auto-created module/page/tab sections for every current and future module. --}}
        <input type="hidden" name="auto_manage_permissions_json" id="auto_manage_permissions_json" value="{}">
        @if(!empty($auto_manage_sections) && is_array($auto_manage_sections))
            @foreach($auto_manage_sections as $autoSection)
                @php
                    $autoItems = collect($autoSection['items'] ?? [])->filter(function ($item) {
                        return !empty($item['key']);
                    })->values();
                    $autoModuleKey = $autoSection['module_key'] ?? null;
                    $autoModuleItems = $autoItems->where('type', 'module')->values();
                    $autoChildItems = $autoItems->reject(function ($item) {
                        return ($item['type'] ?? '') === 'module';
                    })->values();
                    $autoParentExplicit = !empty($autoSection['parent_explicit']);
                    if ($autoModuleItems->isEmpty() && !empty($autoModuleKey) && empty($autoSection['parent_explicit'])) {
                        $autoModuleItems = collect([[
                            'key' => $autoModuleKey,
                            'label' => ($autoSection['title'] ?? 'Module') . ' Module',
                            'type' => 'module',
                            'enabled' => !array_key_exists($autoModuleKey, (array)$manage_module_enable) || !empty($manage_module_enable[$autoModuleKey]),
                        ]]);
                    }
                @endphp
                @if($autoParentExplicit && $autoChildItems->count() > 0)
                    <div class="sa-auto-existing-module-items"
                        data-parent-candidates='@json($autoSection['parent_candidates'] ?? [])'
                        data-module-key="{{ $autoModuleKey }}"
                        style="display:none;">
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><b>Automatically Discovered Pages and Tabs</b></h5>
                                <button type="button" class="btn btn-primary btn-sm sa-auto-select-all">Select All</button>
                                <button type="button" class="btn btn-default btn-sm sa-auto-clear-all">Clear All</button>
                            </div>
                        </div>
                        <div class="row" style="margin-top:10px;">
                            @foreach($autoChildItems as $autoItem)
                                @php $autoKey = $autoItem['key']; @endphp
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label" data-search-key="{{ $autoKey }}">
                                            <input type="checkbox" value="1"
                                                data-auto-permission-key="{{ $autoKey }}"
                                                class="input-icheck-red ch_select sa-auto-child-checkbox"
                                                {{ !array_key_exists($autoKey, (array)$manage_module_enable) || !empty($manage_module_enable[$autoKey]) ? 'checked' : '' }}>
                                            {{ $autoItem['label'] ?? ucwords(str_replace('_', ' ', $autoKey)) }}
                                            @if(($autoItem['type'] ?? '') === 'tab')
                                                <small class="label label-info">Tab</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif(!$autoParentExplicit && $autoItems->count() > 0)
                    @php
                        $autoParentKey = $autoModuleItems->first()['key'] ?? ($autoModuleKey . '_module');
                        $autoSettings = (array) data_get($module_activation_data, '_auto_modules.' . $autoModuleKey, []);
                        $autoIntervalValue = $autoSettings['interval'] ?? 'Years';
                        $autoInterval = in_array($autoIntervalValue, ['Years', 'Months', 'Days'], true)
                            ? $autoIntervalValue
                            : 'Years';
                        $autoLength = max(1, (int) ($autoSettings['length'] ?? 1));
                        $autoActivatedOn = $autoSettings['activated_on'] ?? \Carbon\Carbon::today()->format('Y-m-d');
                        $autoExpiryDate = $autoSettings['expiry_date'] ?? null;
                        if (empty($autoExpiryDate)) {
                            try {
                                $autoExpiry = \Carbon\Carbon::parse($autoActivatedOn);
                                if ($autoInterval === 'Months') {
                                    $autoExpiry->addMonths($autoLength);
                                } elseif ($autoInterval === 'Days') {
                                    $autoExpiry->addDays($autoLength);
                                } else {
                                    $autoExpiry->addYears($autoLength);
                                }
                                $autoExpiryDate = $autoExpiry->format('Y-m-d');
                            } catch (\Throwable $autoExpiryException) {
                                $autoExpiryDate = \Carbon\Carbon::today()->addYear()->format('Y-m-d');
                            }
                        }
                        $autoParentEnabled = !array_key_exists($autoParentKey, (array)$manage_module_enable)
                            || !empty($manage_module_enable[$autoParentKey]);
                        $autoLocationRecord = $module_permission_locations_value[$autoParentKey] ?? null;
                        $autoSavedLocations = (array) data_get($autoLocationRecord, 'locations', []);
                    @endphp
                    <div class="card text-left bg-success sa-auto-manage-section sa-permission-search-section" data-search-text="{{ ($autoSection['title'] ?? 'Module') }} {{ collect($autoSection['items'] ?? [])->pluck('label')->implode(' ') }}" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4 class="search_label module-permission-title">{{ $autoSection['title'] ?? 'Module' }} Module</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2"><b>Module Name</b></div>
                                <div class="col-md-1"><b>Enable</b></div>
                                <div class="col-md-1"><b>Status</b></div>
                                <div class="col-md-1"><b>Interval</b></div>
                                <div class="col-md-1"><b>Interval Length</b></div>
                                <div class="col-md-2"><b>Activated on</b></div>
                                <div class="col-md-2"><b>Expiry</b></div>
                                <div class="col-md-2 text-center"><b>Module Price</b></div>
                            </div>
                            <div class="row sa-auto-module-settings-row" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <label class="search_label" data-search-key="{{ $autoParentKey }}">{{ $autoSection['title'] ?? 'Module' }} Module</label>
                                </div>
                                <div class="col-md-1">
                                    <input type="checkbox" value="1"
                                        data-auto-permission-key="{{ $autoParentKey }}"
                                        data-auto-module="{{ $autoModuleKey }}"
                                        class="input-icheck-red ch_select sa-auto-module-checkbox"
                                        {{ $autoParentEnabled ? 'checked' : '' }}>
                                </div>
                                <div class="col-md-1">
                                    @if($autoParentEnabled && strtotime($autoExpiryDate) >= time())
                                        <span class="label label-pill label-primary">Active</span>
                                    @elseif($autoParentEnabled)
                                        <span class="label label-pill label-danger">Expired</span>
                                    @else
                                        <span class="badge badge-danger">Not Set</span>
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    <select name="auto_module_settings[{{ $autoModuleKey }}][interval]"
                                        class="form-control act_interval"
                                        data-auto-module-setting="interval">
                                        @foreach(['Years', 'Months', 'Days'] as $autoIntervalOption)
                                            <option value="{{ $autoIntervalOption }}" {{ $autoInterval === $autoIntervalOption ? 'selected' : '' }}>{{ $autoIntervalOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <input type="number"
                                        name="auto_module_settings[{{ $autoModuleKey }}][length]"
                                        value="{{ $autoLength }}"
                                        min="1"
                                        class="form-control act_length"
                                        data-auto-module-setting="length">
                                </div>
                                <div class="col-md-2">
                                    <input type="date"
                                        name="auto_module_settings[{{ $autoModuleKey }}][activated_on]"
                                        value="{{ $autoActivatedOn }}"
                                        class="form-control act_on"
                                        data-auto-module-setting="activated_on">
                                </div>
                                <div class="col-md-2">
                                    <input type="date"
                                        value="{{ $autoExpiryDate }}"
                                        class="form-control act_expiry"
                                        data-auto-module-setting="expiry_date"
                                        disabled>
                                </div>
                                <div class="col-md-2">
                                    <input type="number"
                                        name="auto_module_settings[{{ $autoModuleKey }}][price]"
                                        value="{{ $autoSettings['price'] ?? 0 }}"
                                        min="0"
                                        step="0.01"
                                        class="form-control input_number"
                                        data-auto-module-setting="price">
                                </div>
                            </div>
                            @if($autoChildItems->count() > 0)
                                <hr>
                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary btn-sm sa-auto-select-all">Select All</button>
                                        <button type="button" class="btn btn-default btn-sm sa-auto-clear-all">Clear All</button>
                                    </div>
                                    @foreach($autoChildItems as $autoItem)
                                        @php $autoKey = $autoItem['key']; @endphp
                                        <div class="col-sm-3">
                                            <div class="checkbox">
                                                <label class="flex-label search_label" data-search-key="{{ $autoKey }}">
                                                    <input type="checkbox" value="1"
                                                        data-auto-permission-key="{{ $autoKey }}"
                                                        class="input-icheck-red ch_select sa-auto-child-checkbox"
                                                        {{ !array_key_exists($autoKey, (array)$manage_module_enable) || !empty($manage_module_enable[$autoKey]) ? 'checked' : '' }}>
                                                    {{ $autoItem['label'] ?? ucwords(str_replace('_', ' ', $autoKey)) }}
                                                    @if(($autoItem['type'] ?? '') === 'tab')
                                                        <small class="label label-info">Tab</small>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <hr>
                            <div class="row check_group">
                                <div class="col-md-3">
                                    <p style="padding-top:9px;"><b>@lang('superadmin::lang.select_locations'):</b></p>
                                </div>
                                <input type="hidden"
                                    name="module_permission_location[{{ $autoParentKey }}][__submitted]"
                                    value="0">
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label><input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}</label>
                                    </div>
                                </div>
                                @foreach($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox"
                                                    name="module_permission_location[{{ $autoParentKey }}][{{ $location->id }}]"
                                                    value="1"
                                                    class="input-icheck-red ch_select location_checkbox"
                                                    {{ $autoLocationRecord === null || array_key_exists($location->id, $autoSavedLocations) ? 'checked' : '' }}>
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        <script>
        (function ($) {
            function setChecked($checkboxes, checked) {
                $checkboxes.prop('checked', !!checked);
                if ($.fn.iCheck) {
                    try {
                        $checkboxes.iCheck('update');
                    } catch (ignore) {}
                }
                $checkboxes.trigger('change');
            }

            $('.sa-auto-existing-module-items').each(function () {
                var source = this;
                var candidates = [];
                try {
                    candidates = JSON.parse(source.getAttribute('data-parent-candidates') || '[]');
                } catch (ignore) {}

                var parentControl = null;
                candidates.some(function (candidate) {
                    parentControl = document.querySelector(
                        '#custom_permission_form input[name="' + String(candidate).replace(/"/g, '\\"') + '"]'
                    );
                    return !!parentControl;
                });

                var card = parentControl && parentControl.closest ? parentControl.closest('.card') : null;
                var cardBody = card ? card.querySelector('.card-body') : null;
                if (cardBody) {
                    source.style.display = '';
                    cardBody.appendChild(source);
                }
            });

            $(document).off('click.autoManageSelectAll', '.sa-auto-select-all')
                .on('click.autoManageSelectAll', '.sa-auto-select-all', function () {
                    setChecked($(this).closest('.card-body, .sa-auto-existing-module-items').find('.sa-auto-child-checkbox'), true);
                });
            $(document).off('click.autoManageClearAll', '.sa-auto-clear-all')
                .on('click.autoManageClearAll', '.sa-auto-clear-all', function () {
                    setChecked($(this).closest('.card-body, .sa-auto-existing-module-items').find('.sa-auto-child-checkbox'), false);
                });

            $('#custom_permission_form').off('submit.autoManagePermissions').on('submit.autoManagePermissions', function () {
                var permissions = {};
                $('[data-auto-permission-key]').each(function () {
                    permissions[String($(this).data('auto-permission-key'))] = $(this).prop('checked') ? 1 : 0;
                });
                $('#auto_manage_permissions_json').val(JSON.stringify(permissions));
            });
        })(jQuery);
        </script>

        <div class="clearfix"></div>
        <div class="text-right" style="padding: 15px 0 25px 0;">
            <button type="submit" class="btn btn-success"
                    id="custom_permission_btn"
                    style="width:85%; max-width:850px; font-size:16px; font-weight:600; padding:12px 20px; background:#28a745; border-color:#28a745;">
                @lang('superadmin::lang.save')
            </button>
        </div>
        <div class="clearfix"></div>
        {!! Form::close() !!}
        <div class="modal fade option_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        {{-- Independent native save controller. It does not depend on the large
             page-specific jQuery section, so save feedback always works even if
             another legacy Manage script fails. --}}
        @php
            $manageFastAssetVersion = @filemtime(public_path('js/superadmin-manage-fast.js')) ?: ($asset_v ?? '1');
        @endphp
        <script src="{{ asset('js/superadmin-manage-fast.js') }}?v={{ $manageFastAssetVersion }}"></script>

    </section>
    <!-- /.content -->

@endsection

@section('javascript')
@if(!empty($package_permission_blueprint_active))
    <script type="application/json" id="sa_package_permission_scope">@json($package_permission_scope ?? [])</script>
    <style>
        .sa-package-hidden-by-blueprint{display:none!important;}
        .sa-package-blueprint-notice{border-left:4px solid #2563eb;}
    </style>
    <script>
    (function (window, document) {
        'use strict';

        function normalise(value) {
            value = String(value || '');
            var brackets = value.match(/\[([^\]]+)\]/g);
            if (brackets && brackets.length) {
                value = brackets[brackets.length - 1].replace(/[\[\]]/g, '');
            }
            value = value.replace(/\[\]$/g, '').toLowerCase();
            value = value.replace(/[^a-z0-9_]+/g, '_').replace(/_+/g, '_');
            return value.replace(/^_+|_+$/g, '');
        }

        function permissionKey(control) {
            return normalise(
                control.getAttribute('data-auto-permission-key') ||
                control.getAttribute('name') ||
                control.getAttribute('id') ||
                ''
            );
        }

        function isTopControlSection(section) {
            var text = String(section && section.textContent || '');
            return /Annual Fee Package/i.test(text) && /Search Permissions/i.test(text);
        }

        function smallestWrapper(control) {
            return control.closest(
                '.checkbox,.form-group,.permission-item,.col-sm-3,.col-md-3,.col-sm-4,.col-md-4,.row'
            ) || control.parentElement;
        }

        function applyPackageScope() {
            var node = document.getElementById('sa_package_permission_scope');
            if (!node) { return; }

            var scope = {};
            try { scope = JSON.parse(node.textContent || '{}') || {}; } catch (error) { scope = {}; }

            var sections = document.querySelectorAll(
                '.card,.box,.panel,.module-permission-section,.permission-section,.accordion-item'
            );

            for (var s = 0; s < sections.length; s += 1) {
                var section = sections[s];
                if (!section || section.closest('#sa_permission_explorer') || isTopControlSection(section)) {
                    continue;
                }

                var controls = section.querySelectorAll('input[type="checkbox"]');
                var managed = 0;
                var visible = 0;

                for (var i = 0; i < controls.length; i += 1) {
                    var control = controls[i];
                    if (control.closest('#sa_permission_explorer') ||
                        control.classList.contains('check_all') ||
                        control.getAttribute('name') === '_token') {
                        continue;
                    }

                    var key = permissionKey(control);
                    if (!key) { continue; }

                    managed += 1;
                    var allowed = Object.prototype.hasOwnProperty.call(scope, key) && !!scope[key];
                    var wrapper = smallestWrapper(control);
                    if (wrapper) {
                        wrapper.classList.toggle('sa-package-hidden-by-blueprint', !allowed);
                    }
                    if (allowed) { visible += 1; }
                }

                if (managed > 0) {
                    section.classList.toggle('sa-package-hidden-by-blueprint', visible === 0);
                    var selectAll = section.querySelectorAll('.check_all');
                    for (var a = 0; a < selectAll.length; a += 1) {
                        var selectAllWrapper = smallestWrapper(selectAll[a]);
                        if (selectAllWrapper) {
                            selectAllWrapper.classList.toggle('sa-package-hidden-by-blueprint', visible === 0);
                        }
                    }
                }
            }

            document.documentElement.classList.add('sa-package-scope-applied');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyPackageScope);
        } else {
            applyPackageScope();
        }
    })(window, document);
    </script>
@endif
<script>
    $.fn.popover = $.fn.popover || function () {
        return this;
    };
</script>

<script>
/**
 * SUPERADMIN MANAGE - UNIVERSAL SELECT ALL
 * Adds one Select All control to every module/settings card that contains checkboxes.
 * This is frontend-only; it does not change saved field names or backend logic.
 */
$(document).ready(function () {
    var pendingSectionSyncs = new Map();

    function setManageCheckboxes($checkboxes, checked) {
        $checkboxes.prop('checked', !!checked);
        if ($.fn.iCheck) {
            try {
                $checkboxes.iCheck('update');
            } catch (ignore) {}
        }
    }

    function sectionCheckboxes($section) {
        var section = $section.get(0);
        return $section.find('input[type="checkbox"]')
            .not('.sa-section-select-all')
            .not(':disabled')
            .filter(function () {
                var input = this;
                // A nested module card owns its own controls. Excluding those
                // controls here prevents every parent card rescanning them.
                if (input.closest('.card, .box') !== section) {
                    return false;
                }
                return $(input).is(':visible') ||
                    $(input).closest('.checkbox, label, .col-md-1, .col-sm-3, .col-sm-4, .col-sm-6').length;
            });
    }

    function syncSectionSelectAll($section) {
        var $selectAll = $section.find('> .sa-section-select-all-row .sa-section-select-all, > .card-body > .sa-section-select-all-row .sa-section-select-all, > .box-body > .sa-section-select-all-row .sa-section-select-all').first();
        if (!$selectAll.length) {
            $selectAll = $section.find('.sa-section-select-all').first();
        }

        var $items = sectionCheckboxes($section);
        var total = $items.length;
        var checked = $items.filter(':checked').length;

        if (!total) {
            $selectAll.prop('checked', false).prop('indeterminate', false);
            return;
        }

        if (checked === 0) {
            $selectAll.prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $selectAll.prop('checked', true).prop('indeterminate', false);
        } else {
            $selectAll.prop('checked', false).prop('indeterminate', true);
        }
    }

    function scheduleSectionSync($section) {
        var section = $section && $section.get ? $section.get(0) : null;
        if (!section || pendingSectionSyncs.has(section)) {
            return;
        }

        var frame = window.requestAnimationFrame(function () {
            pendingSectionSyncs.delete(section);
            syncSectionSelectAll($(section));
        });
        pendingSectionSyncs.set(section, frame);
    }

    function addSelectAllToSection($section, index) {
        if ($section.data('sa-select-all-ready') || $section.find('.sa-section-select-all').length) {
            return;
        }

        var $items = sectionCheckboxes($section);
        if ($items.length < 2) {
            return;
        }

        var title = $.trim($section.find('.card-header h4, .box-header h3, .box-title, .card-header').first().text()).replace(/\s+/g, ' ');
        if (!title) {
            title = 'this section';
        }

        var selectId = 'sa_section_select_all_' + index;
        var $row = $(
            '<div class="sa-section-select-all-row">' +
                '<label class="sa-section-select-all-label" for="' + selectId + '">' +
                    '<input type="checkbox" id="' + selectId + '" class="input-icheck-red sa-section-select-all">' +
                    'Select All' +
                    '<span class="sa-section-select-all-note">Tick/untick all options in ' + title + '</span>' +
                '</label>' +
            '</div>'
        );

        var $body = $section.children('.card-body, .box-body').first();
        if ($body.length) {
            $body.prepend($row);
        } else {
            var $header = $section.children('.card-header, .box-header').first();
            if ($header.length) {
                $header.after($row);
            } else {
                $section.prepend($row);
            }
        }

        $section.data('sa-select-all-ready', true);
        scheduleSectionSync($section);
    }

    // Initialise in small animation-frame batches so a Manage page containing
    // thousands of discovered permissions never blocks the browser watchdog.
    var sections = $('.card, .box').filter(function () {
        return !$(this).closest('.modal').length;
    }).toArray();
    var sectionIndex = 0;
    function initialiseSectionBatch() {
        var end = Math.min(sectionIndex + 20, sections.length);
        for (; sectionIndex < end; sectionIndex += 1) {
            addSelectAllToSection($(sections[sectionIndex]), sectionIndex);
        }
        if (sectionIndex < sections.length) {
            window.requestAnimationFrame(initialiseSectionBatch);
        }
    }
    window.requestAnimationFrame(initialiseSectionBatch);

    // Select All toggles all options inside its own section.
    $(document).on('change ifChanged', '.sa-section-select-all', function () {
        var $selectAll = $(this);
        var $section = $selectAll.closest('.card, .box');
        var checked = $selectAll.prop('checked');

        setManageCheckboxes(sectionCheckboxes($section), checked);
        scheduleSectionSync($section);
    });

    // Keep Select All synced when users tick individual boxes.
    $(document).on('change ifChanged', '.card input[type="checkbox"], .box input[type="checkbox"]', function () {
        if ($(this).hasClass('sa-section-select-all')) {
            return;
        }
        scheduleSectionSync($(this).closest('.card, .box'));
    });
});
</script>

<script>
// ===== Daily Collection Settings - Select All =====
$(document).ready(function () {
    var $selectAll = $('.check_all_daily_collection');
    var $cbs       = $('.daily_collection_cb');

    function setDcCheckbox($el, checked) {
        $el.prop('checked', checked);
        if ($.fn.iCheck && $el.data('iCheck')) {
            $el.iCheck(checked ? 'check' : 'uncheck');
        }
    }

    function enforceDailyCollectionMode($changed) {
        if (!$changed || !$changed.length || !$changed.prop('checked')) {
            return;
        }

        if ($changed.attr('id') === 'daily_collection_sw') {
            setDcCheckbox($('#daily_collection'), false);
        } else if ($changed.attr('id') === 'daily_collection') {
            setDcCheckbox($('#daily_collection_sw'), false);
        }
    }

    // Helper: sync the Select All state based on individual checkboxes
    function syncSelectAll() {
        var total   = $cbs.length;
        var checked = $cbs.filter(':checked').length;
        if (checked === 0) {
            $selectAll.prop('checked', false).prop('indeterminate', false);
        } else if (checked === total) {
            $selectAll.prop('checked', true).prop('indeterminate', false);
        } else {
            $selectAll.prop('checked', false).prop('indeterminate', true);
        }
    }

    // Toggle all when Select All is clicked
    $selectAll.on('change', function () {
        var checked = $(this).prop('checked');
        $cbs.prop('checked', checked);
        if (checked) {
            setDcCheckbox($('#daily_collection_sw'), false);
        }
    });

    // Auto-sync Select All when any individual checkbox changes
    $cbs.on('change ifChanged', function () {
        enforceDailyCollectionMode($(this));
        syncSelectAll();
    });

    // Set initial state on page load
    syncSelectAll();
});
// ===== End Daily Collection Settings - Select All =====
</script>

<script>
$('#fuel_products_select, #fuel_products_select_main').on('change', function () {
    let selectedProducts = $(this).val() || [];
    let isMain = $(this).attr('id') === 'fuel_products_select_main';
    let container = isMain ? $('#fuel_products_qty_main') : $('#fuel_products_qty');
    let selectId = $(this).attr('id');

    // Remove qty inputs for unselected products
    container.find('.product-qty-input').each(function () {
        let productId = $(this).data('id');
        if (!selectedProducts.includes(productId.toString())) {
            $(this).remove();
        }
    });

    // Add qty inputs for newly selected products
    selectedProducts.forEach(function (productId) {
        if (container.find('.product-qty-input[data-id="' + productId + '"]').length === 0) {
            let productName = $('#' + selectId + ' option[value="' + productId + '"]').text();

            container.append(`
                <div class="col-md-4 mb-2 product-qty-input" data-id="${productId}">
                    <label>${productName}</label>
                    <input type="number"
                           name="fuel_products_qty[${productId}]"
                           class="form-control"
                           min="0"
                           value="0"
                           required>
                </div>
            `);
        }
    });
});

</script>


    <script>
        $('#currency_id_manage').select2();
    </script>
    <script>
        $('input[name="docmanagement_module"]').on('ifChecked', function () {
            $('.doc_management_settings').removeClass('hide');
        });
        $('input[name="docmanagement_module"]').on('ifUnchecked', function () {
            $('.doc_management_settings').addClass('hide');
        });

        $(document).ready(function() {
            console.log('Document is ready');
            function toggleSmsNumbers() {
                if ($('#notify_backup_restore_sms').val() === 'Yes') {
                    $('#backup_sms_numbers_container').slideDown();
                } else {
                    $('#backup_sms_numbers_container').slideUp();
                }
            }

            $('#notify_backup_restore_sms').change(toggleSmsNumbers);
            toggleSmsNumbers();
        });
        $(document).ready(function() {
            $(document).ready(function() {
                // Initialize all select2 elements
                $('.select2').select2();

                (function () {
                    var ajaxUrl = "{{ url('superadmin/business/get-locations-for-businesses') }}";
                    var savedLocations = @json(array_map('strval', $manage_module_enable['manage_business_locations'] ?? []));

                    $('#manage_businesses').select2('destroy');
                    $('#manage_business_locations').select2('destroy');

                    $('#manage_businesses').select2({ placeholder: 'Select Businesses', allowClear: true });
                    
                    if ($('#manage_databases').val() && $('#manage_databases').val().length > 0) {
                        $('#manage_businesses').prop('disabled', false);
                    }

                    $('#manage_business_locations')
                        .prop('disabled', true)
                        .select2({ placeholder: 'Select a business first', allowClear: true });

                    var manageOptionsLoaded = false;
                    var manageOptionsRequest = null;
                    function loadManageBusinessOptions(openTargetId) {
                        if (manageOptionsLoaded) {
                            if (openTargetId) {
                                window.setTimeout(function () { $('#' + openTargetId).select2('open'); }, 0);
                            }
                            return $.Deferred().resolve().promise();
                        }
                        if (manageOptionsRequest) {
                            return manageOptionsRequest;
                        }

                        var $databaseSelect = $('#manage_databases');
                        var $businessSelect = $('#manage_businesses');
                        var selectedDatabases = ($databaseSelect.val() || []).map(String);
                        var selectedBusinesses = ($businessSelect.val() || []).map(String);
                        var optionsUrl = String($databaseSelect.data('options-url') || '');

                        manageOptionsRequest = $.getJSON(optionsUrl).done(function (payload) {
                            $databaseSelect.select2('destroy').empty();
                            $.each(payload.databases || [], function (_, database) {
                                database = String(database);
                                $databaseSelect.append(new Option(database, database, selectedDatabases.indexOf(database) !== -1, selectedDatabases.indexOf(database) !== -1));
                            });
                            $databaseSelect.select2({ placeholder: 'Select Databases', allowClear: true, width: '100%' });

                            $businessSelect.select2('destroy').empty();
                            $.each(payload.businesses || {}, function (key, label) {
                                key = String(key);
                                var selected = selectedBusinesses.indexOf(key) !== -1;
                                var option = new Option(String(label), key, selected, selected);
                                $(option).attr('data-database', key.split('::')[0] || '');
                                $businessSelect.append(option);
                            });
                            $businessSelect.prop('disabled', selectedDatabases.length === 0)
                                .select2({ placeholder: 'Select Businesses', allowClear: true, width: '100%' });

                            manageOptionsLoaded = true;
                            manageOptionsRequest = null;
                            $databaseSelect.trigger('change');
                            if (openTargetId) {
                                window.setTimeout(function () { $('#' + openTargetId).select2('open'); }, 0);
                            }
                        }).fail(function () {
                            manageOptionsRequest = null;
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Unable to load business options. Please try again.');
                            }
                        });

                        return manageOptionsRequest;
                    }

                    $('#manage_databases, #manage_businesses').on('select2:opening.saManageOptions', function (event) {
                        if (manageOptionsLoaded) {
                            return;
                        }
                        event.preventDefault();
                        loadManageBusinessOptions(this.id);
                    }).on('mouseenter focus touchstart', function () {
                        if (!manageOptionsLoaded) {
                            loadManageBusinessOptions('');
                        }
                    });

                    function loadLocations(businessIds, preSelect) {
                        var $sel = $('#manage_business_locations');
                        $sel.select2('destroy');
                        $sel.empty();

                        if (!businessIds.length) {
                            $sel.prop('disabled', true)
                                .select2({ placeholder: 'Select a business first', allowClear: true });
                            updateDisplay();
                            return;
                        }

                        $.ajax({
                            url: ajaxUrl,
                            method: 'GET',
                            data: { business_ids: businessIds },
                            success: function (data) {
                                $.each(data, function (i, loc) {
                                    var selected = preSelect.indexOf(String(loc.id)) > -1;
                                    var option = new Option(loc.text, loc.id, selected, selected);
                                    $(option).attr('data-db', loc.db);
                                    $(option).attr('data-biz', loc.biz);
                                    $sel.append(option);
                                });
                                $sel.prop('disabled', false)
                                    .select2({ placeholder: 'Select Locations', allowClear: true });
                                updateDisplay();
                            },
                            error: function () {
                                $sel.prop('disabled', false)
                                    .select2({ placeholder: 'Select Locations', allowClear: true });
                            }
                        });
                    }

                    $('#manage_databases').on('change', function () {
                        var selectedDbs = $(this).val() || [];
                        var $bizSel = $('#manage_businesses');
                        
                        if (selectedDbs.length === 0) {
                            $bizSel.prop('disabled', true).val(null).trigger('change');
                            $bizSel.select2('destroy').select2({ placeholder: 'Select a database first', allowClear: true });
                        } else {
                            $bizSel.prop('disabled', false);
                            $bizSel.select2('destroy').select2({ placeholder: 'Select Businesses', allowClear: true });
                        }

                        $bizSel.find('option').each(function() {
                            var db = $(this).data('database');
                            if (selectedDbs.length === 0 || selectedDbs.indexOf(String(db)) > -1) {
                                $(this).prop('disabled', false).show();
                            } else {
                                $(this).prop('disabled', true).hide();
                                // Deselect if no longer visible
                                if ($(this).is(':selected')) {
                                    $(this).prop('selected', false);
                                }
                            }
                        });
                        $bizSel.trigger('change');
                    }).trigger('change');

                    $('#manage_businesses').on('change', function () {
                        loadLocations($(this).val() || [], savedLocations);
                    });

                    $('#manage_business_locations').on('change', function () {
                        updateDisplay();
                    });

                    function updateDisplay() {
                        var businesses = $('#manage_businesses').select2('data') || [];
                        var locations  = $('#manage_business_locations').select2('data') || [];
                        var grouped = {};

                        // 1. Initialize groupings from selected businesses
                        businesses.forEach(function (b) {
                            if (!b || typeof b.id === 'undefined') {
                                return;
                            }

                            var $opt = $('#manage_businesses').find('option[value="' + b.id + '"]');
                            var db = $opt.data('database') || 'Unknown';
                            var bizName = ((b.text || '').replace(/\[.*?\]\s*/, '') || 'Unknown').trim(); // Remove the [DB] prefix from name

                            if (!grouped[db]) grouped[db] = {};
                            if (!grouped[db][bizName]) grouped[db][bizName] = [];
                        });

                        // 2. Add locations to groupings
                        locations.forEach(function (l) {
                            if (!l || typeof l.id === 'undefined') {
                                return;
                            }

                            var $opt = $('#manage_business_locations').find('option[value="' + l.id + '"]');
                            var db = $opt.attr('data-db') || 'Unknown';
                            var bizName = $opt.attr('data-biz') || 'Unknown';

                            if (!grouped[db]) grouped[db] = {};
                            if (!grouped[db][bizName]) grouped[db][bizName] = [];
                            
                            grouped[db][bizName].push(l.text.split(' — ').pop()); // Get only the location name
                        });

                        // 3. Render HTML
                        var html = '<div class="selection-grid">';
                        Object.keys(grouped).sort().forEach(function (db) {
                            html += '<div class="db-selection-card">';
                            html += '  <span class="db-selection-title"><i class="fa fa-database"></i> ' + db + '</span>';
                            
                            Object.keys(grouped[db]).sort().forEach(function (biz) {
                                html += '<div class="biz-selection-item">';
                                html += '  <span class="biz-selection-name">' + biz + '</span>';
                                
                                if (grouped[db][biz].length > 0) {
                                    html += '<div class="loc-selection-list">';
                                    grouped[db][biz].sort().forEach(function (loc) {
                                        html += '<span class="loc-selection-tag">' + loc + '</span>';
                                    });
                                    html += '</div>';
                                } else {
                                    html += '<small class="text-muted" style="padding-left:5px;">(No Locations selected yet)</small>';
                                }
                                html += '</div>';
                            });
                            
                            html += '</div>';
                        });
                        html += '</div>';

                        if (Object.keys(grouped).length > 0) {
                            $('#selected_tag_list').html(html);
                            $('#selected_businesses_locations_display').show();
                        } else {
                            $('#selected_businesses_locations_display').hide();
                        }
                    }

                    var initialBusinessIds = $('#manage_businesses').val() || [];
                    if (initialBusinessIds.length) {
                        loadLocations(initialBusinessIds, savedLocations);
                    } else {
                        updateDisplay();
                    }
                })();

            });
    // Function to calculate expiry date
    function calculateExpiryDate(row) {
        const activateOn = row.find('.act_on').val();
        const interval = row.find('.act_interval').val();
        const length = row.find('.act_length').val();
        
        if (!activateOn || !interval || !length) {
            return;
        }
        
        const activateDate = new Date(activateOn);
        let expiryDate = new Date(activateDate);
        
        switch (interval) {
            case 'Years':
                expiryDate.setFullYear(expiryDate.getFullYear() + parseInt(length));
                break;
            case 'Months':
                expiryDate.setMonth(expiryDate.getMonth() + parseInt(length));
                break;
            case 'Days':
                expiryDate.setDate(expiryDate.getDate() + parseInt(length));
                break;
        }
        
        // Format the date as YYYY-MM-DD for the date input
        const formattedDate = expiryDate.toISOString().split('T')[0];
        row.find('.act_expiry').val(formattedDate);
    }
    
    // Initialize all rows
    $('.row').each(function() {
        const row = $(this);
        
        // Set default values if empty
        if (!row.find('.act_on').val()) {
            const today = new Date().toISOString().split('T')[0];
            row.find('.act_on').val(today);
        }
        
        if (!row.find('.act_interval').val()) {
            row.find('.act_interval').val('Years');
        }
        
        if (!row.find('.act_length').val()) {
            row.find('.act_length').val('1');
        }
        
        // Calculate expiry date initially
        calculateExpiryDate(row);
        
        // Set up event listeners
        row.find('.act_on, .act_interval, .act_length').on('change', function() {
            calculateExpiryDate(row);
        });
        
        // Disable/enable fields based on checkbox
        const checkbox = row.find('.ch_select');
        const toggleFields = () => {
            const isChecked = checkbox.is(':checked');
            row.find('.act_on, .act_interval, .act_length').prop('disabled', !isChecked);
            
            // Expiry date is always disabled (readonly)
            row.find('.act_expiry').prop('disabled', true);
        };
        
        // Initialize and set up change listener
        toggleFields();
        checkbox.on('change', toggleFields);
    });
});
        $(document).ready(function() {
          
            $('.check_all').change(function() {
                var isChecked = $(this).prop('checked');
                $(this).closest('.row').find('.ch_select').prop('checked', isChecked);
            });
        });
        
        
        $('#test_sms_btn').click( function() {
        var test_number = $('#test_number').val();
        if (test_number.trim() == '') {
            toastr.error('{{__("lang_v1.test_number_is_required")}}');
            $('#test_number').focus();

            return false;
        }

        var data = {
            url: $('#sms_settings_url').val(),
            send_to_param_name: $('#send_to_param_name').val(),
            msg_param_name: $('#msg_param_name').val(),
            request_method: $('#request_method').val(),
            param_1: $('#sms_settings_param_key1').val(),
            param_2: $('#sms_settings_param_key2').val(),
            param_3: $('#sms_settings_param_key3').val(),
            param_4: $('#sms_settings_param_key4').val(),
            param_5: $('#sms_settings_param_key5').val(),
            param_6: $('#sms_settings_param_key6').val(),
            param_7: $('#sms_settings_param_key7').val(),
            param_8: $('#sms_settings_param_key8').val(),
            param_9: $('#sms_settings_param_key9').val(),
            param_10: $('#sms_settings_param_key10').val(),

            param_val_1: $('#sms_settings_param_val1').val(),
            param_val_2: $('#sms_settings_param_val2').val(),
            param_val_3: $('#sms_settings_param_val3').val(),
            param_val_4: $('#sms_settings_param_val4').val(),
            param_val_5: $('#sms_settings_param_val5').val(),
            param_val_6: $('#sms_settings_param_val6').val(),
            param_val_7: $('#sms_settings_param_val7').val(),
            param_val_8: $('#sms_settings_param_val8').val(),
            param_val_9: $('#sms_settings_param_val9').val(),
            param_val_10: $('#sms_settings_param_val10').val(),
            test_number: test_number
        };

        $.ajax({
            method: 'post',
            data: data,
            url: "{{ action('BusinessController@testSmsConfiguration') }}",
            dataType: 'json',
            success: function(result) {
                if (result.success == true) {
                    swal({
                        text: result.msg,
                        icon: 'success'
                    });
                } else {
                    swal({
                        text: result.msg,
                        icon: 'error'
                    });
                }
            },
        });
    });
       

        let  opt_val_array = [];

        $('#custom_permission_btn').on('click.prepareManageSave', function(){
            if(Array.isArray(opt_val_array) && opt_val_array.length){
                $('#opt_vars').val(JSON.stringify(opt_val_array));
            }
        });
        @php
            $manage_module_enable = (array) $manage_module_enable;
        @endphp
        @if(is_array($manage_module_enable) && empty($manage_module_enable['access_sms_settings']))
        $('.sms_setting_div').addClass('hide');
        @endif

        
        $('#sms_settings_checkbox').change(function() {
            if ($(this).is(':checked')) {
                $('div.sms_setting_div').removeClass('hide');
            } else {
                $('div.sms_setting_div').addClass('hide');
            }
        });
    

        $('#customer_interest_deduct_option').on('ifChecked', function(event){
            $('div.customer_interest_deduct_option').removeClass('hide');
        });
        $('#customer_interest_deduct_option').on('ifUnchecked', function(event){
            $('div.customer_interest_deduct_option').addClass('hide');
        });

        @foreach ($module_permission_locations as $module)
        $('.{{$module}}').on('ifChecked', function(event){
            $('.{{$module}}_locations').removeClass('hide');
        });
        $('.{{$module}}').on('ifUnChecked', function(event){
            $('.{{$module}}_locations').addClass('hide');
        });
        @endforeach
        $(document).ready(function(){
            // Initialize module permission location visibility based on current checkbox state
            @foreach ($module_permission_locations as $module)
            if ($('.{{$module}}').is(':checked')) {
                $('.{{$module}}_locations').removeClass('hide');
            }
            @endforeach

            let sale_date = @if(!empty($sale_import_date)) '{{$sale_import_date}}' @else new Date() @endif;
            $('#sale_import_date').datepicker("setDate", sale_date);

            let purchase_date = @if(!empty($purchase_import_date)) {{$purchase_import_date}} @else new Date() @endif;
            $('#purchase_import_date').datepicker("setDate", purchase_date);

        })
        
        $(".act_interval, .act_on, .act_length").on("input change", function() {
            var interval = $(this).closest('.row').find('.act_interval').val();
            var length = $(this).closest('.row').find('.act_length').val();
            var act_on = $(this).closest('.row').find('.act_on').val();
            
            if(interval && length && act_on){
                $(this).closest('.row').find('.act_expiry').prop("disabled", false);
                
                switch(interval){
                    case "Years":
                        var expiry = addYears(new Date(act_on),length).toISOString().substr(0, 10);
                        
                        console.log(expiry);
                    
                        $(this).closest('.row').find('.act_expiry').val(expiry);
                        
                        break;
                    case "Months" : 
                        var expiry = addMonths(new Date(act_on),length).toISOString().substr(0, 10);
                        
                        $(this).closest('.row').find('.act_expiry').val(expiry);
                        
                        break;
                    case "Days":
                        
                        var expiry = addDays(act_on,length).toISOString().substr(0, 10);
                        
                        $(this).closest('.row').find('.act_expiry').val(expiry);
                        
                        break;
                }
                
                 $(this).closest('.row').find('.act_expiry').prop("disabled", true);
            }
        });
        
        function addDays(date, days) {
          var result = new Date(date);
          result.setDate(result.getDate() + +days);
          return result;
        }
        
        
        function addMonths(date, months) {
            var d = date.getDate();
            date.setMonth(date.getMonth() + +months);
            if (date.getDate() != d) {
              date.setDate(0);
            }
            return date;
        }
        
        function addYears(date, months) {
            var currentDate = new Date(date);
            currentDate.setFullYear(currentDate.getFullYear() + +months);
            var d = currentDate;
            
            return d;
        }
        
        $(document).on('click', '.add-row', function() {
            /*
             * IS2339: each location owns its own row indexes. The previous
             * duplicate #current_id element always read the first location's
             * value, so a new row could reuse an existing index and get
             * overwritten in the POST. That is why newly-added methods did not
             * reliably reach the related payment pages.
             */
            var $tbody = $(this).closest('tbody');
            var new_id = -1;
            $tbody.find('input[name*="[name]["]').each(function() {
                var match = String(this.name || '').match(/\[name\]\[(\d+)\]$/);
                if (match) {
                    new_id = Math.max(new_id, parseInt(match[1], 10));
                }
            });
            new_id += 1;

            var location_id = $(this).data('id');
            var account_options = "";
            $.each(<?php echo json_encode($payment_account_groups ?? $account_groups); ?>, function(key, value) {
               account_options += '<option value="' + key + '">' + value + '</option>';
            });
            
            var newRow = `
              <tr>
                <td class="text-center"><input type="text" name="default_payment_accounts[`+location_id+`][name][`+new_id+`]" class="form-control" required></td>
                <td class="text-center">
                  <select class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_enabled][`+new_id+`]" required>
                        <option value="">Select one</option>
                        <option value="0">Not Active </option>
                        <option value = "1" >Active </option>
                  </select>
                </td>
                <td>
                    <input type="hidden" name="default_payment_accounts[`+location_id+`][is_purchase_enabled][`+new_id+`]" value="0">
                    <input class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_purchase_enabled][`+new_id+`]" type="checkbox" value="1">
                </td>
                
                <td>
                    <input type="hidden" name="default_payment_accounts[`+location_id+`][is_sale_enabled][`+new_id+`]" value="0">
                    <input class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_sale_enabled][`+new_id+`]" type="checkbox" value="1">
                </td>
                
                <td>
                    <input type="hidden" name="default_payment_accounts[`+location_id+`][is_expense_enabled][`+new_id+`]" value="0">
                    <input class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_expense_enabled][`+new_id+`]" type="checkbox" value="1">
                </td>
                
                <td>
                    <input type="hidden" name="default_payment_accounts[`+location_id+`][is_purchase_return_enabled][`+new_id+`]" value="0">
                    <input class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_purchase_return_enabled][`+new_id+`]" type="checkbox" value="1">
                </td>
                
                <td>
                    <input type="hidden" name="default_payment_accounts[`+location_id+`][is_sale_return_enabled][`+new_id+`]" value="0">
                    <input class="form-control input-sm" name="default_payment_accounts[`+location_id+`][is_sale_return_enabled][`+new_id+`]" type="checkbox" value="1">
                </td>
                
                <td class="text-center">
                  <select class="form-control input-sm select2" name="default_payment_accounts[`+location_id+`][account][`+new_id+`]">
                    <option value="">Please select</option>
                    `+account_options+`
                  </select>
                </td>
                <td>
                  <button type="button" class="btn btn-danger remove-row"> - </button>
                  <input type="hidden" name="default_payment_accounts[`+location_id+`][is_custom][`+new_id+`]" value="1">
                </td>
              </tr>
            `;
            $(this).closest('tr').after(newRow);
            $(".select2").select2()
          });
        
          // Remove row
          $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
          });
          
          $(document).on('click','#add_amount',function(){
              var item_element = `
                <div class="form-group col-sm-4 added-amount">
                    <div class="input-group">
                      {!! Form::text('reminder_phone[]', null, ['class' => 'form-control', 'required','placeholder' => __(
                        'superadmin::lang.phone_no')]) !!}
                      <span  class="input-group-addon bg-danger remove_amount"> - </span>
                    </div>
                  </div>
              `;
              
              $("#amounts_row").append(item_element);
          });
          
          $(document).on('click', '.remove_amount', function () {
            $(this).closest('.added-amount').remove();
        });

        
    </script>
    
    <script>
   if (document.querySelector('.ns-font-color-picker')) {
    const pickr = Pickr.create({
      el: '.ns-font-color-picker',
      theme: 'classic', // or 'monolith', or 'nano'

      swatches: [
          'rgba(244, 67, 54, 1)',
          'rgba(233, 30, 99, 0.95)',
          'rgba(156, 39, 176, 0.9)',
          'rgba(103, 58, 183, 0.85)',
          'rgba(63, 81, 181, 0.8)',
          'rgba(33, 150, 243, 0.75)',
          'rgba(3, 169, 244, 0.7)',
          'rgba(0, 188, 212, 0.7)',
          'rgba(0, 150, 136, 0.75)',
          'rgba(76, 175, 80, 0.8)',
          'rgba(139, 195, 74, 0.85)',
          'rgba(205, 220, 57, 0.9)',
          'rgba(255, 235, 59, 0.95)',
          'rgba(255, 193, 7, 1)'
      ],

      components: {
          preview: true,
          opacity: true,
          hue: true,
          interaction: {
              hex: true,
              input: true,
              clear: true,
              save: true,
              useAsButton: false,
          }
      }
    }).on('save', (color) => {
        $('#ns_font_color').val(color.toHEXA().toString());
    });
   }
  
  if (document.querySelector('.ns-background-color-picker')) {
    const pickr2 = Pickr.create({
      el: '.ns-background-color-picker',
      theme: 'classic', // or 'monolith', or 'nano'

      swatches: [
          'rgba(244, 67, 54, 1)',
          'rgba(233, 30, 99, 0.95)',
          'rgba(156, 39, 176, 0.9)',
          'rgba(103, 58, 183, 0.85)',
          'rgba(63, 81, 181, 0.8)',
          'rgba(33, 150, 243, 0.75)',
          'rgba(3, 169, 244, 0.7)',
          'rgba(0, 188, 212, 0.7)',
          'rgba(0, 150, 136, 0.75)',
          'rgba(76, 175, 80, 0.8)',
          'rgba(139, 195, 74, 0.85)',
          'rgba(205, 220, 57, 0.9)',
          'rgba(255, 235, 59, 0.95)',
          'rgba(255, 193, 7, 1)'
      ],

      components: {
          preview: true,
          opacity: true,
          hue: true,
          interaction: {
              hex: true,
              input: true,
              clear: true,
              save: true,
              useAsButton: false,
          }
      }
    }).on('save', (color) => {
        $('#ns_background_color').val(color.toHEXA().toString());
    });
  }
</script>

<script>
    $('#vat_registered').on('change', function() {
        $('#vat_number_section').toggle($(this).val() === 'yes');
    });
</script>

<script>
$(document).ready(function () {

    function toggleBackupSmsField() {
        if ($('#notify_backup_restore_sms').val() === 'Yes') {
            $('#backup_sms_numbers_container').slideDown();
        } else {
            $('#backup_sms_numbers_container').slideUp();
            $('#backup_sms_numbers').val('');
        }
    }

    // On load
    toggleBackupSmsField();

    // On change
    $('#notify_backup_restore_sms').on('change', function () {
        toggleBackupSmsField();
    });

});
</script>


<script>
    // Add this to your JavaScript file
    $(document).on('click', '.confirm-delete', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Your image has been deleted.',
                            'success'
                        ).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    });
</script>
<script>
$(document).ready(function() {
    // Image deletion handler
    $('.delete-image').click(function() {
        var deleteButton = $(this);
        var deleteUrl = deleteButton.data('url');
        
        swal({
            title: "@lang('messages.are_you_sure')",
            text: "@lang('messages.you_wont_be_able_to_revert_this')",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: deleteUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        if (response.success) {
                            deleteButton.closest('.image-preview-container').remove();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    }
                });
            }
        });
    });
    
    // Preview image before upload
    $('input[type="file"]').change(function(e) {
        var file = e.target.files[0];
        var reader = new FileReader();
        var previewContainer = $(this).siblings('.image-preview-container');
        
        if (previewContainer.length === 0) {
            previewContainer = $('<div class="mt-2 image-preview-container"></div>');
            $(this).after(previewContainer);
        } else {
            previewContainer.empty();
        }
        
        reader.onload = function(event) {
            var img = $('<img class="img-thumbnail" style="max-height: 150px;">');
            img.attr('src', event.target.result);
            previewContainer.prepend(img);
        }
        
        reader.readAsDataURL(file);
    });
});
</script>
<script>
$(function() {
    // helper to set the target checkbox state in a way that works with iCheck or plain checkboxes
    function setCheckbox($el, checked) {
        if ($.fn.iCheck && $el.hasClass('iCheck-helper') === false && $el.closest('.icheckbox_square, .icheckbox_flat, .icheckbox_minimal').length) {
            // if the markup was enhanced by iCheck, use iCheck API
            try {
                if (checked) {
                    $el.iCheck('check');
                } else {
                    $el.iCheck('uncheck');
                }
            } catch (e) {
                // fallback to prop
                $el.prop('checked', !!checked).trigger('change');
            }
        } else if ($.fn.iCheck && $el.data('iCheck')) {
            // another possible detection: stored data
            if (checked) $el.iCheck('check'); else $el.iCheck('uncheck');
        } else {
            // no iCheck — use native prop
            $el.prop('checked', !!checked).trigger('change');
        }
    }

    // toggle logic: when MPCS is checked => ensure do_not_show_delete_button is checked
    function syncDeleteCheckbox(mpcsChecked) {
        var $deleteCb = $('#do_not_show_delete_button');
        if (mpcsChecked) {
            setCheckbox($deleteCb, true);
        } else {
            // leave it as user chooses OR uncheck it if you want strict behavior:
            // setCheckbox($deleteCb, false);
        }
    }

    var $mpcs = $('#mpcs_module');
    var $doNotShow = $('#do_not_show_delete_button');

    // Run on load according to current saved state
    if ($mpcs.length) {
        // If iCheck is present we should check its current state using its API or DOM prop
        var checkedOnLoad = $mpcs.prop('checked') || ($.fn.iCheck && $mpcs.parent().hasClass('checked'));
        syncDeleteCheckbox(checkedOnLoad);
    }

    // Attach event handlers. Support iCheck's events first, then fallback to native change.
    if ($.fn.iCheck) {
        // If inputs have been initialized by iCheck, use its events.
        $mpcs.on('ifChanged', function() {
            syncDeleteCheckbox($(this).prop('checked'));
        });
        // In case the inputs are initialized later, also listen to native change as backup:
        $mpcs.on('change', function() {
            syncDeleteCheckbox($(this).prop('checked'));
        });
    } else {
        // plain checkbox — native event works
        $mpcs.on('change', function() {
            syncDeleteCheckbox($(this).prop('checked'));
        });
    }
});
</script>


<script>
$(document).ready(function() {
    @if(!empty($manageStatus))
        if (typeof swal === 'function') {
            swal({
                title: '{{ $manageStatusSuccess ? 'Saved' : 'Not Saved' }}',
                text: @json($manageStatusMsg),
                icon: '{{ $manageStatusSuccess ? 'success' : 'error' }}',
                buttons: {
                    confirm: {
                        text: 'OK',
                        value: true,
                        visible: true,
                        className: 'btn {{ $manageStatusSuccess ? 'btn-success' : 'btn-danger' }}',
                        closeModal: true
                    }
                }
            });
        }
    @endif
});
</script>




<script>
/*
    MA-002: a type-and-filter box INSIDE EVERY SECTION of the Manage page.

    One box per section, and each one searches ONLY its own section - so
    filtering "Membership Module" never disturbs what is shown under "Petro
    General" or anywhere else.

    THE BOXES ARE ADDED BY SCRIPT, not written into the section files. Every
    .card on this page gets one, so a NEW MODULE OR SECTION IS COVERED THE
    MOMENT IT IS ADDED - nothing to register and nothing that can fall behind.
    That was the condition asked for.

    There are 65 sections and 541 settings on this page today.
*/
(function () {
    function buildSectionFilters() {
        $('.card').each(function () {
            var $card = $(this);

            // One box per card, however many times this runs.
            if ($card.data('manage-filter-ready')) {
                return;
            }

            var $body = $card.children('.card-body').first();

            if (!$body.length) {
                return;
            }

            // Only sections that actually contain settings.
            var $controls = $body.find('input[type="checkbox"], input[type="text"], input[type="number"], select');

            if ($controls.length < 3) {
                return;
            }

            $card.data('manage-filter-ready', true);

            var $box = $(
                '<div class="manage-section-filter" style="padding: 0 15px 10px 15px;">' +
                    '<input type="text" class="form-control input-sm manage-section-search"' +
                    ' autocomplete="off" placeholder="Type to filter this section...">' +
                    '<span class="help-block manage-section-search-status" style="margin:4px 0 0 0;"></span>' +
                '</div>'
            );

            $body.before($box);

            /*
                Each control's own column is its block. Sections use col-md-4,
                col-md-2, col-sm-3 and others, so the nearest column is found
                per control rather than assuming one class.
            */
            var blocks = [];

            $controls.each(function () {
                var $col = $(this).closest('[class*="col-"]');

                if ($col.length && $.inArray($col[0], blocks) === -1) {
                    blocks.push($col[0]);
                }
            });

            var $blocks = $(blocks);

            $blocks.each(function () {
                $(this).data('manage-text', ($(this).text() || '').toLowerCase().replace(/\s+/g, ' '));
            });

            var $input = $box.find('.manage-section-search');
            var $status = $box.find('.manage-section-search-status');
            var timer = null;

            function applyFilter() {
                var term = $.trim(($input.val() || '').toLowerCase());

                if (term === '') {
                    $blocks.show();
                    $status.text('');
                    return;
                }

                var shown = 0;

                $blocks.each(function () {
                    var match = ($(this).data('manage-text') || '').indexOf(term) !== -1;
                    $(this).toggle(match);

                    if (match) {
                        shown++;
                    }
                });

                $status.text(shown === 0
                    ? 'Nothing in this section matches.'
                    : shown + (shown === 1 ? ' setting' : ' settings'));
            }

            $input.on('input', function () {
                window.clearTimeout(timer);
                timer = window.setTimeout(applyFilter, 150);
            });

            // Enter must never submit the Manage form from a filter box.
            $input.on('keydown', function (event) {
                if (event.which === 13) {
                    event.preventDefault();
                    applyFilter();
                }
            });
        });
    }

    $(buildSectionFilters);

    // Sections revealed later - a tab opening, a module toggled on.
    $(document).on('shown.bs.tab shown.bs.collapse', function () {
        buildSectionFilters();
    });
}());
</script>

{{--
 | ---------------------------------------------------------------------
 | Confirm before switching a module OFF.
 | ---------------------------------------------------------------------
 |
 | WHY THIS EXISTS
 |
 | On 12 Aug a Manage save disabled three modules for a live business in a
 | single click: the Pump Operator Dashboard, Contacts and Realize Cheque.
 | Nobody involved could tell it had happened.
 |
 |   - The Super Admin saw a normal "saved" message. The page gives no
 |     indication of what changed, so an accidentally unticked box looks
 |     exactly like a deliberate one.
 |   - The business users found out by hitting a red error mid-task that
 |     named an internal setting key ("Blocked by the setting:
 |     realize_cheque"), which means nothing to them.
 |   - Pumper Dashboard users simply could not log in - their business had
 |     vanished from the selector.
 |
 | This lists every module the save is about to switch off and asks the
 | Super Admin to confirm. Turning a module ON is never questioned; only
 | losing access needs a second look.
 |
 | HOW IT WORKS
 |
 | Purely client side. It records each checkbox state when the page loads,
 | compares on submit, and asks only if something went from on to off. No
 | extra queries, no controller change, so it cannot affect what is saved -
 | it either lets the submit through untouched, or stops it.
 |
 | The label comes from the row's own text, so new modules are covered
 | automatically without editing this list.
 --}}


@endsection


<script>
    (function () {
        function calculateSuppliersExpiryDate() {
            var activatedOn = document.getElementById('suppliers_activated_on');
            var interval = document.getElementById('suppliers_interval');
            var length = document.getElementById('suppliers_length');
            var expiry = document.getElementById('suppliers_expiry_date');
            if (!activatedOn || !interval || !length || !expiry || !activatedOn.value) {
                return;
            }
            var dt = new Date(activatedOn.value + 'T00:00:00');
            var len = parseInt(length.value || '1', 10);
            len = len > 0 ? len : 1;
            if (interval.value === 'Months') {
                dt.setMonth(dt.getMonth() + len);
            } else if (interval.value === 'Days') {
                dt.setDate(dt.getDate() + len);
            } else {
                dt.setFullYear(dt.getFullYear() + len);
            }
            var yyyy = dt.getFullYear();
            var mm = String(dt.getMonth() + 1).padStart(2, '0');
            var dd = String(dt.getDate()).padStart(2, '0');
            expiry.value = yyyy + '-' + mm + '-' + dd;
        }
        document.addEventListener('DOMContentLoaded', function () {
            ['suppliers_activated_on', 'suppliers_interval', 'suppliers_length', 'suppliers_module_checkbox'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('change', calculateSuppliersExpiryDate);
                    el.addEventListener('keyup', calculateSuppliersExpiryDate);
                }
            });
            calculateSuppliersExpiryDate();
        });
    })();
</script>
