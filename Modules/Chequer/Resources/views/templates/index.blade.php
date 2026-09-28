@extends('chequer::layouts.app')
@section('title','Cheque Templates')
@section('chequer_content')
<div class="cheq-top">
    <div>
        <div class="cheq-title">Templates</div>
        <div class="cheq-sub">Create, copy, position, preview and test-print cheque layouts.</div>
    </div>
</div>
@include('chequer::partials.list_toolbar', ['searchPlaceholder' => 'Search template, bank, status ...', 'createUrl' => url('/chequer-module/templates/create'), 'createLabel' => 'Add Template'])
<div class="cheq-card">
    <div class="cheq-table-wrap">
        <table class="cheq-table">
            <thead>
                <tr>
                    <th style="width:27%">Template<br>Name</th>
                    <th style="width:22%">Bank</th>
                    <th class="w-small">Paper<br>Width</th>
                    <th class="w-small">Paper<br>Height</th>
                    <th class="w-small">Status</th>
                    <th class="w-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $rowMap = json_decode($row->field_map ?? '{}', true) ?: [];
                        $isDefault = !empty($rowMap['is_default']);
                    @endphp
                    <tr>
                        <td>
                            <b>{{ $row->template_name }}</b>
                            @if($isDefault)<span class="cheq-default-pill">Default</span>@endif
                        </td>
                        <td>{{ $row->bank_name ?? '-' }}</td>
                        <td class="amount">{{ $row->paper_width }} in</td>
                        <td class="amount">{{ $row->paper_height }} in</td>
                        <td><span class="cheq-badge cheq-status-{{ $row->status ?? 'active' }}">{{ ucfirst($row->status) }}</span></td>
                        <td class="cheq-actions">
                            <div class="dropdown cheq-action-menu">
                                <button class="cheq-btn blue dropdown-toggle" type="button" data-toggle="dropdown">Action <span class="caret"></span></button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="{{ url('/chequer-module/templates/'.$row->id.'/edit') }}"><i class="fa fa-edit"></i> Edit Designer</a></li>
                                    <li><a target="_blank" href="{{ url('/chequer-module/templates/'.$row->id.'/test-print') }}"><i class="fa fa-print"></i> Test Print</a></li>
                                    <li role="separator" class="divider"></li>
                                    <li>
                                        <form method="post" action="{{ url('/chequer-module/templates/'.$row->id) }}" onsubmit="return confirm('Delete this template?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="cheq-delete-link"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No templates found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows,'links'))<div class="cheq-pagination">{{ $rows->links() }}</div>@endif
</div>
<style>
.cheq-default-pill{display:inline-block;margin-left:8px;padding:3px 7px;border-radius:999px;background:#dcfce7;color:#166534;font-size:11px;font-weight:900}.cheq-action-menu .dropdown-menu{min-width:180px;padding:7px}.cheq-action-menu .dropdown-menu a,.cheq-delete-link{display:block;width:100%;padding:9px 11px;border:0;background:transparent;color:#334155;text-align:left;font-weight:700}.cheq-action-menu .dropdown-menu a:hover,.cheq-delete-link:hover{background:#f1f5f9;text-decoration:none}.cheq-delete-link{color:#b91c1c}
</style>
@endsection
