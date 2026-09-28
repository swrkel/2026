@extends('pos::layouts.app', ['title' => __('pos::messages.registers')])
@section('pos_content')
<div class="pos-toolbar-card box box-solid">
    <div class="box-body pos-toolbar">
        <div class="pos-toolbar-left">
            <input type="text" class="form-control pos-instant-search" data-target="#pos-register-table" placeholder="{{ __('pos::messages.search') }}">
        </div>
        <div class="pos-toolbar-right">
            <a href="{{ route('pos.shifts.open_form') }}" class="btn btn-success"><i class="fa fa-play"></i> {{ __('pos::messages.open_shift') }}</a>
            <a href="{{ route('pos.registers.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> {{ __('pos::messages.add_register') }}</a>
            <button class="btn btn-default pos-export" data-export="csv" data-table="#pos-register-table">CSV</button>
            <button class="btn btn-default pos-print" data-table="#pos-register-table"><i class="fa fa-print"></i> {{ __('pos::messages.print') }}</button>
        </div>
    </div>
</div>
<div class="box box-primary pos-table-card">
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped pos-standard-table" id="pos-register-table">
            <thead><tr><th>{{ __('pos::messages.action') }}</th><th>{{ __('pos::messages.code') }}</th><th>{{ __('pos::messages.name') }}</th><th>{{ __('pos::messages.opening_balance') }}</th><th>{{ __('pos::messages.status') }}</th><th>{{ __('pos::messages.note') }}</th></tr></thead>
            <tbody>
            @forelse($registers as $register)
                <tr>
                    <td class="no-print">
                        <div class="btn-group">
                            <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">{{ __('pos::messages.action') }} <span class="caret"></span></button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="{{ route('pos.registers.show', $register->id) }}"><i class="fa fa-eye"></i> {{ __('pos::messages.view') }}</a></li>
                                <li><a href="{{ route('pos.registers.edit', $register->id) }}"><i class="fa fa-edit"></i> {{ __('pos::messages.edit') }}</a></li>
                            </ul>
                        </div>
                    </td>
                    <td>{{ $register->code ?? '-' }}</td>
                    <td>{{ $register->name ?? '-' }}</td>
                    <td class="text-right pos-money">{{ number_format((float)($register->opening_balance ?? 0), session('business.currency_precision', 2)) }}</td>
                    <td>{!! !empty($register->is_active) ? '<span class="label label-success">'.__('pos::messages.active').'</span>' : '<span class="label label-default">'.__('pos::messages.inactive').'</span>' !!}</td>
                    <td><button type="button" class="btn btn-xs btn-info pos-note-btn" data-note="{{ e($register->note ?? '') }}">{{ __('pos::messages.note') }}</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">{{ __('pos::messages.no_records_found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($registers, 'links')) {{ $registers->links() }} @endif
    </div>
</div>
@endsection
