@extends('enterpriseframework::layout')

@section('content')
<div class="efw-page">
    <div class="efw-header">
        <h1>Enterprise Framework Administration</h1>
        <p>Shared reporting settings, menu groups, widgets, and platform defaults.</p>
    </div>

    <div class="efw-grid">
        <div class="efw-card">
            <h3>Menu Groups</h3>
            <ul>
                @foreach($menu_groups as $key => $label)
                    <li><strong>{{ $label }}</strong> <small>{{ $key }}</small></li>
                @endforeach
            </ul>
        </div>
        <div class="efw-card">
            <h3>Default Settings</h3>
            <pre>{{ json_encode($settings, JSON_PRETTY_PRINT) }}</pre>
        </div>
        <div class="efw-card">
            <h3>Widget Library</h3>
            <ul>
                @foreach($widgets as $key => $widget)
                    <li>{{ $widget['label'] ?? $key }} <small>{{ $widget['category'] ?? '' }}</small></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
