@extends('layouts.app')

@section('title', __('development::lang.completed_development'))


@section('content')
    <style>
        /* Filter Section Styles */
        .filter-section {
            margin-bottom: 20px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
        }

        .filter-section .box-header {
            border-bottom: 1px solid #e0e0e0;
            margin: -15px -15px 15px -15px;
            padding: 10px 15px;
            background-color: #f5f5f5;
        }

        .filter-section .box-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .filter-section .form-group {
            margin-bottom: 15px;
        }

        .filter-section label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
        }

        .filter-section .select2-container {
            width: 100% !important;
        }

        .filter-actions {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        /* Make sure date range picker is above modals */
        .daterangepicker {
            z-index: 9999 !important;
        }
    </style>

    <div class="content-wrapper">
        <section class="content-header">
            <h1> @lang('development::lang.developments') <small>@lang('development::lang.completed_documents')</small></h1>
        </section>

        <section class="content" style="padding-top: 10px;">
            <div class="box">
                <div class="box-body">
                    @component('components.filters', ['title' => __('report.filters')])
                        <!-- Filters Section -->
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-md-12">
                                <div class="box box-solid">
                                    <div class="box-body">
                                        <div class="row">
                                            <!-- Date Range -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <!-- D 81 Added some code here-->
                                                    {!! Form::label('development_list_filter_date_range', __('report.date_range') . ':') !!}
                                                    {!! Form::text(
                                                        'development_list_filter_date_range',
                                                        @format_date('first day of January this year') . ' ~ ' . @format_date('last day of December this year'),
                                                        ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly'],
                                                    ) !!}
                                                </div>
                                            </div>

                                            <!-- Document Number -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.doc_no')</label>
                                                    <select class="form-control select2" id="doc_no_filter"
                                                        style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        @foreach ($documentNumbers as $docNo)
                                                            <option value="{{ $docNo }}">{{ $docNo }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Username -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.created_by')</label>
                                                    <select class="form-control select2" id="username_filter"
                                                        style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        @foreach ($usernames as $username)
                                                            <option value="{{ $username->id }}">{{ $username->username }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Module -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.module')</label>
                                                    <select class="form-control select2" id="module_filter"
                                                        style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        @foreach ($modules as $module)
                                                            <option value="{{ $module->id }}">{{ $module->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <!-- Type -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.type')</label>
                                                    <select class="form-control" id="type_filter" style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        <option value="Task">@lang('development::lang.type_task')</option>
                                                        <option value="Issue">@lang('development::lang.type_issue')</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Task Heading -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.task_heading')</label>
                                                    <select class="form-control select2" id="task_heading_filter"
                                                        style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        @foreach ($taskHeadings as $heading)
                                                            <option value="{{ $heading }}">{{ $heading }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Related Document Number -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.related_doc_no')</label>
                                                    <select class="form-control select2" id="related_doc_no_filter"
                                                        style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        <option value="no_document">No Document Number</option>
                                                        @foreach ($relatedDocNos as $docNo)
                                                            <option value="{{ is_object($docNo) ? $docNo->doc_no : $docNo }}">
                                                                {{ is_object($docNo) ? $docNo->doc_no : $docNo }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Status -->
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>@lang('development::lang.status')</label>
                                                    <select class="form-control" id="status_filter" style="width: 100%;">
                                                        <option value="">@lang('messages.all')</option>
                                                        <option value="Pending">@lang('development::lang.status_pending')</option>
                                                        <option value="Not Completed">@lang('development::lang.status_not_completed')</option>
                                                        <option value="Completed">@lang('development::lang.status_completed')</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 text-right">
                                                {{-- <button type="button" class="btn btn-primary" id="apply_filters">
                                                    <i class="fa fa-filter"></i> @lang('development::lang.apply_filters')
                                                </button> --}}
                                                <button type="button" class="btn btn-default" id="reset_filters">
                                                    <i class="fa fa-undo"></i> @lang('development::lang.reset')
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endcomponent
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="development_table">
                            <colgroup>
                                <col width="9%">
                                <col width="10%">
                                <col width="10%">
                                <col width="30%">
                                <col width="8.5%">
                                <col width="10%">
                                <col width="15%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>@lang('development::lang.doc_no')</th>
                                    <th>@lang('development::lang.datetime')</th>
                                    <th>@lang('development::lang.module')</th>
                                    <th>@lang('development::lang.task_heading')</th>
                                    <th>@lang('development::lang.related_doc_no')</th>
                                    <th>@lang('development::lang.priority')</th>
                                    <th>@lang('development::lang.status')</th>
                                    <th>@lang('development::lang.created_by')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('javascript')
    <!-- Date Range Picker -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/daterangepicker.css') }}">
    <script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('js/daterangepicker.js') }}"></script>

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear editor content
            var dq = document.getElementById('details_quill');
            if (dq) dq.innerHTML = '';

            // Define toolbar options
            var toolbarOptions = [
                [{
                    'font': []
                }],
                [{
                    'size': ['small', false, 'large', 'huge']
                }],
                ['bold', 'italic', 'underline', 'strike'],
                [{
                    'color': []
                }, {
                    'background': []
                }],
                [{
                    'script': 'sub'
                }, {
                    'script': 'super'
                }],
                [{
                    'header': [1, 2, 3, false]
                }],
                [{
                    'align': []
                }],
                ['blockquote', 'code-block'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }],
                [{
                    'indent': '-1'
                }, {
                    'indent': '+1'
                }],
                [{
                    'direction': 'rtl'
                }],
                ['link', 'image', 'video'],
                ['formula'],
                ['clean']
            ];

            // Initialize Quill editor for details only once
            if (!window.quillDetails) {
                window.quillDetails = new Quill('#details_quill', {
                    theme: 'snow',
                    modules: {
                        toolbar: toolbarOptions
                    },
                    placeholder: 'Enter details...'
                });

                // Enable drag-and-drop for images only
                function enableQuillImageDrop(quill) {
                    quill.root.addEventListener('drop', function(e) {
                        e.preventDefault();
                        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                            var file = e.dataTransfer.files[0];
                            if (file.type.startsWith('image/')) {
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    var range = quill.getSelection();
                                    var img = e.target.result;
                                    quill.insertEmbed(range ? range.index : 0, 'image', img, "user");
                                };
                                reader.readAsDataURL(file);
                            } else {
                                alert('Please drop an image file.');
                            }
                        }
                    });
                }
                enableQuillImageDrop(window.quillDetails);

                // Set initial content from textarea if available
                var detailsText = document.getElementById('details').value;
                if (detailsText && detailsText.trim().length > 0) {
                    window.quillDetails.root.innerHTML = detailsText;
                }
            }
        });

        // Global variable for development table
        var development_table;
        var dateRangePicker;

        function format(d) {
            var isCreatorOrAdmin = {!! json_encode(auth()->user()->hasRole('Super Admin') || auth()->id() == 'user_id_placeholder') !!};

            return `
                <div class="row" style="margin: 0; width: 100%;">
                    <div class="col-md-4">
                        <div class="box box-default">
                            <div class="box-body">
                                <p><strong>Document No:</strong> ${d.doc_no || '-'}</p>
                                <p><strong>Date & Time:</strong> ${d.datetime ? moment(d.datetime).format('YYYY-MM-DD HH:mm') : '-'}</p>
                                <p><strong>Related Document No:</strong> ${
                                    Array.isArray(d.related_doc_no)
                                        ? d.related_doc_no.map(r => r.doc_no).join(', ')
                                        : (typeof d.related_doc_no === 'object' && d.related_doc_no !== null
                                            ? d.related_doc_no.doc_no
                                            : (d.related_doc_no || '-'))
                                }</p>
                                <input type="hidden" class="doc-no" value="${d.doc_no || '-'}">
                                <input type="hidden" class="task-heading" value="${d.task_heading || '-'}">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="box box-default">
                            <div class="box-body">
                                <h5><strong>Details:</strong></h5>
                                <div class="details-content">
                                    ${d && d.details && typeof d.details === 'string' 
                                        ? (function(html) {
                                              const txt = document.createElement("textarea");
                                              txt.innerHTML = html;
                                              return txt.value;
                                          })(d.details) 
                                        : '-'}
                                </div>
                                ${d.comments && d.comments.length > 0 ? `
                                            <h5>Group Comments:</h5>
                                            ${d.comments.map(comment => `
                                        <div class="comment-box" style="border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                                            <p><strong>${comment.user_name || 'Unknown'}:</strong> ${comment.comment}</p>
                                            <small class="text-muted">${comment.created_at ? moment(comment.created_at).format('YYYY-MM-DD HH:mm') : ''}</small>
                                        </div>
                                    `).join('')}
                                        ` : ''}
                                <div style="margin-top: 15px;">
                                    <button class="btn btn-xs btn-info view-btn" data-id="${d.id}" style="margin-right: 5px;">
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                    ${isCreatorOrAdmin ? `
                                                <button class="btn btn-xs btn-primary edit-btn" data-id="${d.id}" style="margin-right: 5px;">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                            ` : ''}
                                    <button class="btn btn-xs btn-success add-comment-btn" data-id="${d.id}">
                                        <i class="fa fa-comment"></i> Add Group Comment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Function to get filter values
        function getFilters() {
            var startDate = dateRangePicker.data('daterangepicker').startDate;
            var endDate = dateRangePicker.data('daterangepicker').endDate;

            return {
                start_date: startDate ? startDate.format('YYYY-MM-DD') : '',
                end_date: endDate ? endDate.format('YYYY-MM-DD') : '',
                doc_no: $('#doc_no_filter').val(),
                user_id: $('#username_filter').val(),
                module_id: $('#module_filter').val(),
                type: $('#type_filter').val(),
                task_heading: $('#task_heading_filter').val(),
                related_doc_no: $('#related_doc_no_filter').val(),
                status: $('#status_filter').val()
            };
        }

        $(document).ready(function() {

            // Initialize date range picker
            dateRangePicker = $('#development_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#development_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    if (typeof development_table !== 'undefined') {
                        development_table.ajax.reload();
                    }
                }
            );
            $('#development_list_filter_date_range').on('apply.daterangepicker', function(ev, picker) {
                if (picker.chosenLabel === 'Custom Date Range') {
                    $('#target_custom_date_input').val('development_list_filter_date_range');
                    $('.custom_date_typing_modal').modal('show');
                }
            });

            $('#custom_date_apply_button').on('click', function() {
                debugger;
                if ($('#target_custom_date_input').val() == "development_list_filter_date_range") {
                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $(
                        '#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $(
                        '#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                        '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                    if (startDate.length === 10 && endDate.length === 10) {
                        let formattedStartDate = moment(startDate).format(moment_date_format);
                        let formattedEndDate = moment(endDate).format(moment_date_format);

                        $('#development_list_filter_date_range').val(
                            formattedStartDate + ' ~ ' + formattedEndDate
                        );

                        $('#development_list_filter_date_range').data('daterangepicker').setStartDate(moment(
                        startDate));
                        $('#development_list_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                }
            });

            $('#development_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#development_list_filter_date_range').val('');
                if (typeof development_table !== 'undefined') {
                    development_table.ajax.reload();
                }
            });
            //D 81 Added the following two line of code
            if ($('#development_list_filter_date_range').data('daterangepicker')) {
                $('#development_list_filter_date_range').data('daterangepicker').setStartDate(moment().startOf('year'));
                $('#development_list_filter_date_range').data('daterangepicker').setEndDate(moment().endOf('year'));
            }

            development_table = $('#development_table').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                scroller: true,
                responsive: true,
                ajax: {
                    url: '{{ route('list-development.completed') }}',
                    type: 'GET',
                    data: function(d) {
                        // Add filter parameters to the DataTables request
                        var filters = getFilters();
                        d.start_date = filters.start_date;
                        d.end_date = filters.end_date;
                        d.doc_no = filters.doc_no;
                        d.user_id = filters.user_id;
                        d.module_id = filters.module_id;
                        d.type = filters.type;
                        d.task_heading = filters.task_heading;
                        d.related_doc_no = filters.related_doc_no;
                        d.status = filters.status;

                        // Add custom parameters for filtering
                        $.extend(d, {
                            'start_date': filters.start_date,
                            'end_date': filters.end_date,
                            'doc_no': filters.doc_no,
                            'user_id': filters.user_id,
                            'module_id': filters.module_id,
                            'type': filters.type,
                            'task_heading': filters.task_heading,
                            'related_doc_no': filters.related_doc_no,
                            'status': filters.status
                        });
                    }
                },
                columns: [{
                        className: 'details-control',
                        orderable: true,
                        data: 'doc_no',
                        name: 'doc_no',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data || '';
                            }
                            return '<i class="fa fa-plus-circle text-primary" style="cursor: pointer; margin-right: 5px;"></i> ' +
                                (data || '');
                        }
                    },
                    {
                        data: 'datetime',
                        name: 'datetime',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data || '';
                            }
                            return data ? moment(data).format('YYYY-MM-DD HH:mm') : '';
                        }
                    },
                    {
                        data: 'module_name',
                        name: 'module_name'
                    },
                    {
                        data: 'task_heading',
                        name: 'task_heading'
                    },
                    {
                        data: 'related_doc_no',
                        name: 'related_doc_no',
                        render: function(data, type, row) {
                            if (Array.isArray(data)) {
                                return data.map(d => d.doc_no).join(', ');
                            }
                            if (typeof data === 'object' && data !== null) {
                                return data.doc_no || '';
                            }
                            return data || '';
                        }
                    },
                    {
                        data: 'priority',
                        name: 'priority',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data || '';
                            }
                            if (!data) return '';
                            var colors = {
                                'Urgent': 'red',
                                'Priority': 'yellow',
                                'Normal': 'lightblue'
                            };
                            return '<span class="badge" style="background-color: ' + (colors[
                                data] || 'lightgray') + '">' + data + '</span>';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            if (type === 'sort' || type === 'type') {
                                return data || '';
                            }
                            if (!data) return '';
                            return '<span class="label label-' + (data === 'Completed' ? 'success' :
                                    data === 'Not Completed' ? 'warning' : 'primary') + '">' +
                                data + '</span>';
                        }
                    },
                    {
                        data: 'username',
                        name: 'username'
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            // Apply filters
            $('#apply_filters').on('click', function() {
                development_table.ajax.reload(null, false); // false means don't reset paging
            });

            $('#development_list_filter_date_range, #doc_no_filter, #username_filter, #module_filter, #type_filter, #task_heading_filter, #related_doc_no_filter, #status_filter')
                .on('change', function() {
                    development_table.ajax.reload(null, false);
                });

            $('#development_list_filter_date_range').on('apply.daterangepicker cancel.daterangepicker', function() {
                development_table.ajax.reload(null, false);
            });

            // Reset filters
            $('#reset_filters').on('click', function() {
                // Reset form
                $('#development_list_filter_date_range').val('').trigger('change');
                $('#doc_no_filter').val('');
                $('#username_filter').val('').trigger('change');
                $('#module_filter').val('').trigger('change');
                $('#type_filter').val('').trigger('change');
                $('#task_heading_filter').val('');
                $('#related_doc_no_filter').val('');
                $('#status_filter').val('').trigger('change');

                // Reset the table
                development_table.ajax.reload();
            });
            // Initialize Select2 for all select2 elements
            $('.select2').select2({
                width: '100%',
                placeholder: 'Select an option',
                allowClear: true
            });

            // Handle view button click
            $(document).on('click', '.view-btn', function() {
                var id = $(this).data('id');
                window.location.href = '{{ route('development.show', '') }}/' + id;
            });

            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                window.location.href = '{{ route('development.edit', '') }}/' + id;
            });

            // Handle add comment button click
            $(document).on('click', '.add-comment-btn', function() {
                var id = $(this).data('id');
                $('#commentModal').modal('show');
                // update title
                $('#commentModalLabel').text('Add Comment for Document No: ' + $(this).closest('tr').find(
                    '.doc-no').val() + ' and Task Heading: ' + $(this).closest('tr').find(
                    '.task-heading').val());
                // Set the form action with the development ID
                var actionUrl = '{{ url('developments') }}/' + id + '/add-comment';
                $('#commentForm').attr('action', actionUrl);
                $('#commentForm').data('development-id', id);
            });


            // Handle comment form submission
            $('#commentForm').on('submit', function(e) {
                e.preventDefault();

                // Create form data
                var formData = new FormData(this);

                // Get the form action URL
                var url = $(this).attr('action');

                // Validate required fields first
                var commentType = $('select[name="group_comment[comment_type]"]').val();
                if (!commentType) {
                    toastr.error('@lang('development::lang.comment_type_required')');
                    return false;
                }



                // Get the form and submit button
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalBtnText = submitBtn.html();

                // Disable submit button and show loading state
                submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

                // Create new FormData and append all form fields
                var formData = new FormData();

                // Append all form fields
                form.find('input, select, textarea').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        if ($(this).is(':checkbox') || $(this).is(':radio')) {
                            if ($(this).is(':checked')) {
                                formData.append(name, $(this).val());
                            }
                        } else if ($(this).is('select[multiple]')) {
                            // Handle multiple select
                            var values = $(this).val() || [];
                            values.forEach(function(value) {
                                formData.append(name + '[]', value);
                            });
                        } else {
                            formData.append(name, $(this).val());
                        }
                    }
                });



                // Get the URL
                var id = form.data('development-id') || url.split('/').pop();
                var submitUrl = '{{ url('developments') }}/' + id + '/add-comment';

                // Log form data for debugging
                console.log('Submitting form to:', submitUrl);
                for (var pair of formData.entries()) {
                    console.log(pair[0] + ': ', pair[1]);
                }

                // Send AJAX request
                $.ajax({
                    url: submitUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Response received:', response);
                        if (response && response.success) {
                            toastr.success(response.message || 'Comment added successfully');

                            // Close the modal
                            $('#commentModal').modal('hide');

                            // Reload the DataTable
                            if (typeof development_table !== 'undefined') {
                                development_table.ajax.reload(null, false);
                            }
                        } else {
                            var errorMsg = response && response.message ? response.message :
                                'An unknown error occurred';
                            console.error('Error in response:', errorMsg);
                            toastr.error(errorMsg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        console.error('Response:', xhr.responseText);

                        var errorMessage = 'An error occurred while saving the comment';
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            } else if (response && response.errors) {
                                // Handle validation errors
                                var errors = [];
                                for (var field in response.errors) {
                                    if (response.errors.hasOwnProperty(field)) {
                                        errors.push(response.errors[field][0]);
                                    }
                                }
                                errorMessage = errors.join('\n');
                            }
                        } catch (e) {
                            console.error('Error parsing error response:', e);
                            if (xhr.statusText) {
                                errorMessage = xhr.statusText;
                            }
                        }

                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Always re-enable the submit button and restore original text
                        submitBtn.prop('disabled', false).html(originalBtnText);
                    }
                });
            });

            // Add event listener for opening and closing details
            $('#development_table tbody').on('click', 'td.details-control', function() {
                var tr = $(this).closest('tr');
                var row = development_table.row(tr);
                var icon = $(this).find('i');

                if (row.child.isShown()) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
                } else {
                    // Open this row
                    row.child(format(row.data())).show();
                    tr.addClass('shown');
                    icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
                }
            });

            // Handle click on plus/minus icon
            $('#development_table tbody').on('click', 'td.details-control i', function(e) {
                e.stopPropagation();
                $(this).closest('td').trigger('click');
            });
        });
    </script>

    <!-- Comment Modal -->
    <div class="modal fade" id="commentModal" tabindex="-1" role="dialog" aria-labelledby="commentModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="commentModalLabel">Add Comment for Document No: <span
                            id="docNo"></span> and Task Heading: <span id="taskHeading"></span></h4>
                </div>
                <form id="commentForm" method="POST" class="form-horizontal">
                    @csrf
                    <div class="modal-body" style="padding: 30px 25px;">
                        <div class="box box-solid">
                            <div class="box-body">
                                <div class="form-group">
                                    <div class="col-sm-12">
                                        <h4 class="box-title" style="margin-top: 0;">
                                            <i class="fa fa-comments"></i> @lang('development::lang.group_comments')
                                        </h4>
                                        <hr style="margin: 10px 10px 20px 10px;">
                                    </div>

                                    <!-- Comment Type -->
                                    <div class="col-sm-6 mb-3">
                                        <div class="form-group">
                                            <label class="control-label">@lang('development::lang.comment_type') <span
                                                    class="text-danger">*</span></label>
                                            <select name="group_comment[comment_type]" class="form-control select2"
                                                required>
                                                <option value="">@lang('development::lang.select_comment_type')</option>
                                                <option value="Issue Existing">@lang('development::lang.issue_existing')</option>
                                                <option value="No Issue">@lang('development::lang.no_issue')</option>
                                                <option value="New Task">@lang('development::lang.new_task')</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <div class="col-sm-6 ml-3">
                                        <div class="form-group">
                                            <label class="control-label">@lang('development::lang.status')</label>
                                            <select name="group_comment[status]" class="form-control select2">
                                                <option value="Pending">@lang('development::lang.pending')</option>
                                                <option value="Not Completed">@lang('development::lang.not_completed')</option>
                                                <option value="Completed">@lang('development::lang.completed')</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            {!! Form::label('details', __('development::lang.details') . ':*', ['class' => 'control-label']) !!}
                                            <div id="details_quill" style="height:200px; margin-bottom:24px;"></div>
                                            {!! Form::textarea('details', old('details'), [
                                                'class' => 'form-control',
                                                'id' => 'details',
                                                'style' => 'display: none;',
                                            ]) !!}
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="padding: 5px 10px;">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> @lang('messages.close')
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> @lang('messages.save')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection