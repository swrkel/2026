@extends('audit::layout')
@section('audit-title','Audit Rules')
@section('audit-content')
@php
    $ruleModules = collect($rules)->pluck('module')->filter()->unique()->sort()->values();
    $totalRules = count($rules);
    $enabledRules = collect($rules)->filter(function ($rule) use ($settings) {
        $setting = $settings->get($rule['code']);
        return !$setting || (bool) $setting->is_enabled;
    })->count();
@endphp

<div class="audit-rule-summary">
    <div class="audit-rule-stat">
        <span>Total Rules</span>
        <strong>{{ $totalRules }}</strong>
    </div>
    <div class="audit-rule-stat">
        <span>Enabled Rules</span>
        <strong>{{ $enabledRules }}</strong>
    </div>
    <div class="audit-rule-stat">
        <span>Modules Covered</span>
        <strong>{{ $ruleModules->count() }}</strong>
    </div>
</div>

<div class="audit-card audit-rules-page">
    <div class="audit-rules-heading-row">
        <div>
            <div class="audit-card-title">Module-wise automated checks</div>
            <div class="audit-note">Review the checks used by Audit. Disable only a rule you intentionally do not want to run.</div>
        </div>
        <div class="audit-rule-toolbar">
            <input type="search" class="audit-input audit-rule-search" placeholder="Search rule, module or description">
            <select class="audit-input audit-rule-module-filter" aria-label="Filter rules by module">
                <option value="">All Modules</option>
                @foreach($ruleModules as $module)
                    <option value="{{ $module }}">{{ $module }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="audit-rule-table-wrap">
        <table class="audit-table audit-rule-table">
            <colgroup>
                <col class="audit-rule-col-module">
                <col class="audit-rule-col-rule">
                <col class="audit-rule-col-description">
                <col class="audit-rule-col-severity">
                <col class="audit-rule-col-setting">
            </colgroup>
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Rule</th>
                    <th>Description</th>
                    <th>Default Severity</th>
                    <th>Setting</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rules as $r)
                    @php($s = $settings->get($r['code']))
                    @php($enabled = !$s || (bool) $s->is_enabled)
                    <tr class="audit-rule-row"
                        data-module="{{ strtolower($r['module']) }}"
                        data-search="{{ strtolower($r['module'].' '.$r['code'].' '.$r['title'].' '.$r['description'].' '.$r['severity']) }}">
                        <td data-label="Module">
                            <span class="audit-module-chip">{{ $r['module'] }}</span>
                        </td>
                        <td data-label="Rule">
                            <div class="audit-rule-code">{{ $r['code'] }}</div>
                            <div class="audit-rule-name">{{ $r['title'] }}</div>
                        </td>
                        <td data-label="Description">
                            <div class="audit-rule-description">{{ $r['description'] }}</div>
                        </td>
                        <td data-label="Default Severity">
                            <span class="audit-badge {{ $r['severity'] }}">{{ ucfirst($r['severity']) }}</span>
                        </td>
                        <td data-label="Setting">
                            <form method="post" action="{{ route('audit.rules.update') }}" class="audit-rule-setting-form">
                                @csrf
                                <input type="hidden" name="rule_code" value="{{ $r['code'] }}">
                                <input type="hidden" name="is_enabled" value="0">
                                <label class="audit-switch">
                                    <input class="audit-rule-toggle" type="checkbox" name="is_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                                    <span class="audit-switch-track" aria-hidden="true"><span class="audit-switch-thumb"></span></span>
                                    <span class="audit-switch-text">{{ $enabled ? 'Enabled' : 'Disabled' }}</span>
                                </label>
                                <button class="audit-btn primary small" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                <tr class="audit-rule-empty" style="display:none;">
                    <td colspan="5">No rules match the selected filters.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
