@extends('layouts.app')

@section('title', __('bankingui::messages.permission_matrix'))

@section('content')
<div class="container-fluid banking-ui-permission-matrix">
    <h3>{{ __('bankingui::messages.permission_matrix') }}</h3>
    <p class="text-muted">Tester role visibility and permission planning matrix.</p>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Menu</th>
                    <th>Required Permission</th>
                    @foreach($roles as $role => $rolePermissions)
                        <th>{{ ucwords(str_replace('_', ' ', $role)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($menuItems as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td><code>{{ $item['permission'] }}</code></td>
                        @foreach($roles as $role => $rolePermissions)
                            <td class="text-center">{{ in_array('banking.*', $rolePermissions) || in_array($item['permission'], $rolePermissions) ? 'Yes' : 'Review' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
