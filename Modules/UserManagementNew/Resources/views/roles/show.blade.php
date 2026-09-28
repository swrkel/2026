@extends('layouts.app')
@section('title', 'View Role')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=1">
@php
    $permissionService = app(\Modules\UserManagementNew\Services\RolePermissionService::class);
    $roleName = preg_replace('/#' . preg_quote((string) $businessId, '/') . '$/', '', $role->name);
@endphp
<section class="content-header umn-page-heading">
    <div><span class="umn-eyebrow">ROLE DETAILS</span><h1>{{ $roleName }}</h1><p>Effective permissions for this business role.</p></div>
    <div>
        @can('roles.update')<a class="btn btn-primary" href="{{ route('user-management-new.roles.edit', $role->id) }}"><i class="fa fa-pencil"></i> Edit</a>@endcan
        <a class="btn btn-default" href="{{ route('user-management-new.roles.index') }}">Back</a>
    </div>
</section>
<section class="content">
    @foreach ($sections as $section)
        @php
            $moduleAllowed = isset($selected[$permissionService->modulePermission($section['module_key'], 'view')]);
        @endphp
        <article class="umn-module-card">
            <header class="umn-module-card__header is-static">
                <span class="umn-module-icon"><i class="fa fa-cubes"></i></span>
                <span><strong>{{ $section['title'] }}</strong><small>{{ $moduleAllowed ? 'Module access enabled' : 'Module access disabled' }}</small></span>
                <span class="umn-badge {{ $moduleAllowed ? 'is-managed' : 'is-legacy' }}">{{ $moduleAllowed ? 'Allowed' : 'Blocked' }}</span>
            </header>
            <div class="umn-module-card__body">
                <div class="umn-right-grid">
                    @foreach ($rights as $rightKey => $right)
                        @php $allowed = isset($selected[$permissionService->modulePermission($section['module_key'], $rightKey)]); @endphp
                        <div class="umn-permission-tile is-readonly {{ $allowed ? 'is-allowed' : 'is-blocked' }}"><span><i class="fa {{ $right['icon'] }}"></i><strong>{{ $right['label'] }}</strong></span><i class="fa {{ $allowed ? 'fa-check-circle' : 'fa-times-circle' }}"></i></div>
                    @endforeach
                </div>
                <div class="umn-page-grid umn-show-pages">
                    @foreach ($section['items'] as $item)
                        @php $allowed = isset($selected[$permissionService->pagePermission($item['key'])]); @endphp
                        <div class="umn-page-tile is-readonly {{ $allowed ? 'is-allowed' : 'is-blocked' }}"><span class="umn-page-type">{{ strtoupper($item['type']) }}</span><span>{{ $item['label'] }}</span><i class="fa {{ $allowed ? 'fa-check' : 'fa-times' }}"></i></div>
                    @endforeach
                </div>
            </div>
        </article>
    @endforeach
</section>
@endsection
