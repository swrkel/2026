@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

<style>
    #f18_page_footer {
        margin-top: 30px;
        width: 100%;
        text-align: left;
        font-size: 12px;
        color: #333;
        padding: 10px 0 0 10px;
        border-top: 1px solid #eee;
    }
    @media print {
        #f18_page_footer {
            bottom: 0;
            left: 0;
            right: 0;
            margin-top: 0;
            page-break-inside: avoid;
        }
    }
</style>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.prefix_and_numbers')])
        {!! Form::open(['url' => action('\Modules\MPCS\Http\Controllers\F18FormController@storePrefixNumbers'), 'method' => 'post', 'id' => 'f18_prefix_numbers_form']) !!}
        {{ csrf_field() }}

        <div class="row">
            <!-- Opening Date -->
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('opening_date', __('mpcs::lang.opening_date') . ':') !!}
                    {!! Form::text('opening_date', now()->format('Y-m-d'), [
                        'class' => 'form-control',
                        'id' => 'f18_opening_date',
                        'required' => true,
                        'readonly' => true,
                    ]) !!}
                </div>
            </div>

            <!-- Prefix (optional) -->
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('prefix', __('mpcs::lang.prefix') . ' :') !!}
                    {!! Form::text('prefix', null, ['class' => 'form-control', 'placeholder' => __('mpcs::lang.prefix')]) !!}
                    <small class="help-block">
                        @lang('mpcs::lang.prefix') @lang('lang_v1.optional')
                    </small>
                </div>
            </div>

            <!-- Starting Number -->
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('starting_number', __('mpcs::lang.starting_number') . ':') !!}
                    {!! Form::number('starting_number', null, ['class' => 'form-control', 'required' => true, 'min' => 1]) !!}
                </div>
            </div>

            <!-- Transferred to Location (free text) -->
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('transferred_locations', __('mpcs::lang.transferred_to_location') . ':') !!}
                    {!! Form::text('transferred_locations', null, ['class' => 'form-control', 'placeholder' => __('mpcs::lang.transferred_to_location')]) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-right">
                <button type="submit" class="btn btn-primary" id="f18_prefix_save_btn">
                    @lang('mpcs::lang.save')
                </button>
            </div>
        </div>

        {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.list_opening_values')])
        <div class="table-responsive" style="overflow-x: unset !important;">
            <table class="table table-bordered table-striped" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('mpcs::lang.index')</th>
                        <th>@lang('mpcs::lang.opening_date')</th>
                        <th>@lang('mpcs::lang.prefix')</th>
                        <th>@lang('mpcs::lang.starting_number')</th>
                        <th>@lang('mpcs::lang.transferred_to_location')</th>
                        <th>@lang('mpcs::lang.added_by')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prefix_numbers as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ optional($row->opening_date)->format('Y-m-d') }}</td>
                            <td>{{ $row->prefix }}</td>
                            <td>{{ $row->starting_number }}</td>
                            <td>{{ is_array($row->transferred_locations) ? ($row->transferred_locations[0] ?? '') : ($row->transferred_locations ?? '') }}</td>
                            <td>
                                {{ optional($row->addedBy)->username ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">
                                @lang('lang_v1.no_data_available')
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent

    {{-- ── System standard report footer ── --}}
    @if (!empty($reports_footer) && !empty($reports_footer->value))
        <div id="f18_page_footer">
            {!! $reports_footer->value !!}
        </div>
    @endif
</section>
<!-- /.content -->

