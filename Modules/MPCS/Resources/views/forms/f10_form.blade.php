@extends('layouts.app')
@section('title', __('mpcs::lang.F10_form'))

@section('content')

@php
    $f10_opening_date_value = '';
    if (!empty($opening_numbers) && !empty($opening_numbers->opening_date)) {
        try {
            $f10_opening_date_value = \Carbon\Carbon::parse($opening_numbers->opening_date)->format(session('business.date_format', 'm/d/Y'));
        } catch (\Exception $e) {
            $f10_opening_date_value = $opening_numbers->opening_date;
        }
    }
@endphp

<section class="content">
    <div class="settlement_tabs" data-mpcs-tabs>
        <ul class="nav nav-tabs">
            <li class="active">
                <a href="#f10_receipt_tab" data-toggle="tab"><strong>F 10 Receipt</strong></a>
            </li>
            <li>
                <a href="#f10_opening_numbers_tab" data-toggle="tab"> <strong>F 10 - Opening Numbers</strong></a>
            </li>
            <li>
                <a href="#f10_list_receipts_tab" data-toggle="tab"> <strong>List F 10 Receipts</strong></a>
            </li>
            <li>
                <a href="#f10_upload_signatures_tab" data-toggle="tab"> <strong>Upload Signatures</strong></a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- F10 Receipt Tab -->
            <div class="tab-pane active" id="f10_receipt_tab">

                {{-- LOAD RECEIPT PARTIAL --}}
                @include('mpcs::forms.partials.f10_form', [
                    'business_locations' => $business_locations ?? [],
                    'location' => $location ?? null,
                    'settings' => $settings ?? null
                ])

            </div>


            <!-- F10 Opening Numbers Tab -->
            <div class="tab-pane" id="f10_opening_numbers_tab">

                <!-- Opening Numbers Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h3 class="box-title">Opening Numbers</h3>
                            </div>
                            <div class="box-body">
                                {!! Form::open(['url' => '/mpcs/F10/opening-numbers', 'method' => 'post']) !!}
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Opening Date: *</label>
                                            <input type="text" name="opening_date"
                                                class="form-control date-picker"
                                                value="{{ $f10_opening_date_value }}"
                                                {{ $opening_numbers ? 'readonly' : 'required' }}
                                                placeholder="Opening Date">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>F 10 Number: *</label>
                                            <input type="text" name="f10_number"
                                                class="form-control"
                                                value="{{ $opening_numbers ? $opening_numbers->f10_number : '' }}"
                                                {{ $opening_numbers ? 'readonly' : 'required' }}
                                                placeholder="F 10 Number">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Document No: *</label>
                                            <input type="text" name="document_no"
                                                class="form-control"
                                                value="{{ $opening_numbers ? $opening_numbers->document_no : '' }}"
                                                {{ $opening_numbers ? 'readonly' : 'required' }}
                                                placeholder="Document No">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Currency Prefix: *</label>
                                            <input type="text" name="currency_prefix"
                                                class="form-control"
                                                value="{{ $opening_numbers ? $opening_numbers->currency_prefix : '' }}"
                                                {{ $opening_numbers ? 'readonly' : 'required' }}
                                                placeholder="e.g. LKR">
                                        </div>
                                    </div>
                                </div>

                                @if(!$opening_numbers)
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fa fa-save"></i> Save Opening Numbers
                                        </button>
                                    </div>
                                </div>
                                @else
                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <p class="text-muted">
                                            <i class="fa fa-lock"></i>
                                            <em>Opening numbers are locked and cannot be edited through the UI.</em>
                                        </p>
                                    </div>
                                </div>
                                @endif

                                {!! Form::close() !!}
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Managers Section -->
                <div class="row" style="margin-top:20px;">
                    <div class="col-md-12">
                        <div class="box box-solid">
                            <div class="box-header with-border">
                                <h3 class="box-title">Manager Names</h3>
                            </div>
                            <div class="box-body">

                                {!! Form::open(['url' => '/mpcs/F10/managers', 'method' => 'post']) !!}
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Manager Name: *</label>
                                            <input type="text" name="manager_name" class="form-control" required placeholder="Enter Manager Name">
                                        </div>
                                    </div>
                                    <div class="col-md-2" style="margin-top:24px;">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-plus"></i> Add Manager
                                        </button>
                                    </div>
                                </div>
                                {!! Form::close() !!}

                                <hr>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="f10_managers_table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Manager Name</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($managers as $i => $manager)
                                            <tr>
                                                <td>{{ $i + 1 }}</td>
                                                <td>{{ $manager->manager_name }}</td>
                                                <td>
                                                    @if($manager->status == 'Active')
                                                        <span class="label bg-green">Active</span>
                                                    @else
                                                        <span class="label bg-red">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {!! Form::open([
                                                        'url' => '/mpcs/F10/managers/toggle/' . $manager->id,
                                                        'method' => 'post',
                                                        'style' => 'display:inline;'
                                                    ]) !!}
                                                    <button type="submit" class="btn btn-xs {{ $manager->status == 'Active' ? 'btn-danger' : 'btn-success' }}">
                                                        Mark as {{ $manager->status == 'Active' ? 'Inactive' : 'Active' }}
                                                    </button>
                                                    {!! Form::close() !!}
                                                </td>
                                            </tr>
                                            @endforeach

                                            @if($managers->isEmpty())
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    No managers added yet.
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
             <div class="tab-pane" id="f10_list_receipts_tab">

                @include('mpcs::forms.partials.f10_list_receipts', [
                    'business_locations' => $business_locations ?? [],
                    'location' => $location ?? null,
                    'settings' => $settings ?? null,
                    'managers' => $managers ?? [],
                    'cashiers' => $cashiers ?? [],
                    'f10_numbers' => $f10_numbers ?? [],
                    'opening_numbers' => $opening_numbers ?? null
                ])

            </div>

            <!-- Upload Signatures Tab -->
            <div class="tab-pane" id="f10_upload_signatures_tab">
                
                @include('mpcs::forms.partials.upload_signatures', [
                    'business_locations' => $business_locations ?? [],
                    'location' => $location ?? null,
                    'settings' => $settings ?? null
                ])

            </div>

        </div>
    </div>
</section>
@endsection


@section('javascript')
@include('mpcs::partials.safe_tabs')

<script type="text/javascript">
$(document).ready(function() {

    function activateF10Tab(targetId) {
        if (!targetId || !document.getElementById(targetId)) {
            return;
        }

        $('.settlement_tabs .nav-tabs li').removeClass('active');
        $('.settlement_tabs .nav-tabs a').each(function() {
            if ($(this).attr('href') === '#' + targetId) {
                $(this).closest('li').addClass('active');
            }
        });
        $('.settlement_tabs .tab-pane').removeClass('active in').hide();
        $('#' + targetId).addClass('active in').show();

        if (targetId === 'f10_list_receipts_tab' && $.fn.DataTable.isDataTable('#f10_form_table')) {
            $('#f10_form_table').DataTable().columns.adjust();
        }
    }

    $(document).on('click', '.settlement_tabs .nav-tabs a[data-toggle="tab"]', function(event) {
        var targetId = String($(this).attr('href') || '').replace(/^#/, '');
        if (!targetId || !document.getElementById(targetId)) {
            return;
        }
        event.preventDefault();
        activateF10Tab(targetId);
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, document.title, '#' + targetId);
        }
    });

    var initialF10Tab = @json(session('active_f10_tab')) || String(window.location.hash || '').replace(/^#/, '');
    if (initialF10Tab) {
        activateF10Tab(initialF10Tab);
    }

    if ($('#f10_date_range').length) {

        $('#f10_date_range').daterangepicker(dateRangeSettings, function(start, end) {

            $('#f10_date_range').val(
                start.format(moment_date_format) + ' ~ ' +
                end.format(moment_date_format)
            );

            f10_form_table.ajax.reload();
        });

        $('#f10_date_range').on('cancel.daterangepicker', function() {
            $('#f10_date_range').val('');
            f10_form_table.ajax.reload();
        });

    }

    var f10_form_table = $('#f10_form_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/mpcs/get-form-f10-list',
            data: function(d) {

                d.location_id = $('#f10_location_id').val();

                if ($('#f10_date_range').val()) {

                    d.start_date = $('#f10_date_range')
                        .data('daterangepicker')
                        .startDate
                        .format('YYYY-MM-DD');

                    d.end_date = $('#f10_date_range')
                        .data('daterangepicker')
                        .endDate
                        .format('YYYY-MM-DD');
                }

            }
        },
        columns: [
            { data: 'form_date', name: 'form_date' },
            { data: 'form_no', name: 'form_no' },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'added_by', name: 'users.username' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    $('#f10_location_id').change(function() {
        f10_form_table.ajax.reload();
    });

    $('.date-picker').datepicker({
        autoclose: true,
        format: datepicker_date_format
    });

});
</script>
@endsection
