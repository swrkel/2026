@extends('layouts.app')
@section('title', 'User')

@section('content')
<link rel="stylesheet" href="{{ asset('modules/usermanagementnew/css/roles.css') }}?v=1">
<section class="content-header umn-page-heading">
    <div>
        <span class="umn-eyebrow">USER MANAGEMENT</span>
        <h1>{{ trim($user->first_name . ' ' . $user->last_name) }}</h1>
        <p>Business user details.</p>
    </div>
    <div>
        @can('users.update')
            <a class="btn btn-primary umn-primary-btn"
               href="{{ route('user-management-new.users.edit', $user->id) }}">Edit</a>
        @endcan
        <a class="btn btn-default" href="{{ route('user-management-new.users.index') }}">Back</a>
    </div>
</section>

<section class="content">
    <div class="umn-list-card">
        <div class="table-responsive">
            <table class="table umn-table">
                <tbody>
                    <tr><th style="width:220px;">Name</th><td>{{ trim($user->first_name . ' ' . $user->last_name) }}</td></tr>
                    <tr><th>Username</th><td>{{ $user->username }}</td></tr>
                    <tr><th>Email</th><td>{{ $user->email ?: '-' }}</td></tr>
                    <tr><th>Role</th><td>
                        @if ($user->umn_role)
                            <span class="umn-badge is-managed">{{ $user->umn_role }}</span>
                        @else
                            <span class="umn-badge is-legacy">No role</span>
                        @endif
                    </td></tr>
                    <tr><th>Status</th><td>
                        <span class="umn-badge {{ $user->status === 'active' ? 'is-managed' : 'is-legacy' }}">
                            {{ $user->status === 'active' ? 'Active' : 'Inactive' }}
                        </span>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
