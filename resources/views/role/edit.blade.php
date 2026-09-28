@extends('layouts.app')
@section('title', __('role.edit_role'))

@section('content')
    <link rel="stylesheet" href="{{ asset('css/role-permission-editor.css') }}?v=059">

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang( 'role.edit_role' )</h1>
    </section>
    <!-- Main content -->
    <section class="content pos-tab-container">
        @component('components.widget', ['class' => 'box-primary role-page-editor-box'])
            {!! Form::open(['url' => action('RoleController@update', [$role->id]), 'method' => 'PUT', 'id' => 'role_form', 'data-role-permission-editor' => 'edit']) !!}
            <input type="hidden" name="permissions_payload" id="permissions_payload">
            <div class="role-name-card role-stable-card" id="role_stable_header">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group role-name-form-group">
                            {!! Form::label('name', __('user.role_name') . ':*') !!}
                            {!! Form::text('name', $display_role_name, ['class' => 'form-control', 'required', 'autofocus', 'placeholder' => __('user.role_name')]) !!}
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="role-name-guidance">
                            <i class="fa fa-info-circle"></i>
                            <span>The Role Name, assigned permissions, and every available permission option remain visible when this page opens.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="role-permission-search-card">
                @include('layouts.partials.search_settings', [
                    'permission_search_inline' => true,
                    'permission_search_context' => 'role'
                ])
            </div>

            @include('role.partials.assigned_permissions_summary', [
                'permissions' => $role_permission_details,
                'summaryTitle' => 'Assigned Permissions',
                'summaryHelp' => 'All permissions currently assigned to this role are visible below. You may search, add, or remove permissions and save the changes.'
            ])

            <div id="role_permissions_workspace" class="role-permissions-workspace">
            <div id="role_unlisted_permissions_controls" class="role-unlisted-permissions-controls" style="display:none;"></div>

            {{-- All permission sections now come from resources/views/role/_sections.php
                 and resources/views/role/sections/. This page and role/edit.blade.php
                 render the SAME partials, so the two can no longer drift apart - which
                 is what produced every finding in the MA-002 review.

                 The driver applies both required gates: Manage (package_details) and
                 Manage Side Bar. See _driver.blade.php for the detail. --}}
            @include('role._driver', ['mode' => 'edit'])

            </div><!-- /#role_permissions_workspace -->

            <div class="row role-editor-submit-row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
                </div>
            </div>

            {!! Form::close() !!}
        @endcomponent
    </section>
    <!-- /.content -->
@endsection

@section('javascript')
<script src="{{ asset('js/role-permission-editor.js') }}?v=059"></script>
@endsection
