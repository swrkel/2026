@extends('layouts.app')

@section('title', __('customers::lang.view_customer'))

@section('content')
<section class="content-header">
    <h1>
        @lang('customers::lang.view_customer')
        <small>@lang('customers::lang.customer_profile')</small>
    </h1>
</section>

<section class="content">

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border clearfix">
                    <h3 class="box-title pull-left">
                        {{ $customer->name }}
                        @if(!empty($customer->contact_id))
                            <small>({{ $customer->contact_id }})</small>
                        @endif
                    </h3>
                    <div class="pull-right">
                        <a href="{{ route('customers.index') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> @lang('messages.back')
                        </a>
                        <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-edit"></i> @lang('customers::lang.edit')
                        </a>
                        <a href="{{ route('customers.print_profile', $customer->id) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="fa fa-print"></i> @lang('customers::lang.print_profile')
                        </a>
                        <a href="{{ route('customers.documents.index', $customer->id) }}" class="btn btn-warning btn-sm">
                            <i class="fa fa-folder-open"></i> @lang('customers::lang.documents')
                        </a>
                        <a href="{{ route('customers.timeline.index', $customer->id) }}" class="btn btn-success btn-sm">
                            <i class="fa fa-clock-o"></i> @lang('customers::lang.timeline')
                        </a>
                        <a href="{{ route('customers.audit.index', $customer->id) }}" class="btn btn-danger btn-sm">
                            <i class="fa fa-shield"></i> @lang('customers::lang.audit_trail')
                        </a>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6">
                            <strong>@lang('customers::lang.customer_code')</strong><br>
                            {{ $customer->contact_id ?? '-' }}
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <strong>@lang('customers::lang.mobile')</strong><br>
                            {{ $customer->mobile ?? '-' }}
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <strong>@lang('customers::lang.email')</strong><br>
                            {{ $customer->email ?? '-' }}
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <strong>@lang('customers::lang.branch_location')</strong><br>
                            @php $branchId = $branchColumn ? data_get($customer, $branchColumn) : null; @endphp
                            {{ !empty($branchId) && $locations->has($branchId) ? $locations->get($branchId) : __('customers::lang.head_office_central') }}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>@lang('customers::lang.business_name')</strong><br>
                            {{ $customer->supplier_business_name ?? '-' }}
                        </div>
                        <div class="col-md-6">
                            <strong>@lang('customers::lang.address')</strong><br>
                            {{ implode(', ', array_filter([$customer->address_line_1 ?? null, $customer->address_line_2 ?? null, $customer->city ?? null, $customer->state ?? null, $customer->country ?? null])) ?: '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning" id="customer-attachments">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-paperclip"></i> @lang('customers::lang.customer_attachments')</h3>
                </div>
                <div class="box-body">
                    @if($attachmentsEnabled)
                        {!! Form::open(['url' => route('customers.attachments.store', $customer->id), 'method' => 'post', 'files' => true]) !!}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        {!! Form::label('title', __('customers::lang.attachment_title')) !!}
                                        {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => __('customers::lang.attachment_title')]) !!}
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        {!! Form::label('attachment', __('customers::lang.select_file')) !!}
                                        {!! Form::file('attachment', ['class' => 'form-control', 'required' => true]) !!}
                                        <small class="text-muted">@lang('customers::lang.attachment_help')</small>
                                    </div>
                                </div>
                                <div class="col-md-3" style="padding-top: 25px;">
                                    <button type="submit" class="btn btn-warning btn-block">
                                        <i class="fa fa-upload"></i> @lang('customers::lang.upload_attachment')
                                    </button>
                                </div>
                            </div>
                        {!! Form::close() !!}

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>@lang('customers::lang.title')</th>
                                        <th>@lang('customers::lang.file_name')</th>
                                        <th>@lang('customers::lang.file_size')</th>
                                        <th>@lang('customers::lang.created_at')</th>
                                        <th class="text-center">@lang('messages.action')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($attachments as $attachment)
                                        <tr>
                                            <td>{{ $attachment->title ?: '-' }}</td>
                                            <td>{{ $attachment->file_name }}</td>
                                            <td>{{ number_format(($attachment->file_size ?? 0) / 1024, 2) }} KB</td>
                                            <td>{{ !empty($attachment->created_at) ? date('Y-m-d H:i', strtotime($attachment->created_at)) : '' }}</td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="{{ route('customers.attachments.download', [$customer->id, $attachment->id]) }}" class="btn btn-info btn-xs">
                                                        <i class="fa fa-download"></i> @lang('customers::lang.download')
                                                    </a>
                                                    {!! Form::open(['url' => route('customers.attachments.destroy', [$customer->id, $attachment->id]), 'method' => 'delete', 'style' => 'display:inline;']) !!}
                                                        <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('{{ __('messages.sure') }}');">
                                                            <i class="fa fa-trash"></i> @lang('customers::lang.delete')
                                                        </button>
                                                    {!! Form::close() !!}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted text-center">@lang('customers::lang.no_records_found')</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            @lang('customers::lang.attachments_table_missing')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary" id="customer-documents">
                <div class="box-header with-border clearfix">
                    <h3 class="box-title"><i class="fa fa-folder-open"></i> @lang('customers::lang.customer_documents')</h3>
                    <div class="pull-right"><a href="{{ route('customers.documents.index', $customer->id) }}" class="btn btn-primary btn-xs"><i class="fa fa-plus"></i> @lang('customers::lang.manage_documents')</a></div>
                </div>
                <div class="box-body">
                    @if($documentsEnabled)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead><tr><th>@lang('customers::lang.title')</th><th>@lang('customers::lang.file_name')</th><th>@lang('customers::lang.expiry_date')</th><th>@lang('customers::lang.created_at')</th></tr></thead>
                                <tbody>
                                    @forelse($documents as $document)
                                        <tr>
                                            <td>{{ $document->title }}</td>
                                            <td>{{ $document->file_name }}</td>
                                            <td>{{ $document->expiry_date ?: '-' }}</td>
                                            <td>{{ !empty($document->created_at) ? date('Y-m-d H:i', strtotime($document->created_at)) : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">@lang('customers::lang.no_records_found')</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-warning">@lang('customers::lang.documents_table_missing')</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-success" id="customer-notes">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-sticky-note"></i> @lang('customers::lang.customer_notes')</h3>
                </div>
                <div class="box-body">
                    @if($notesEnabled)
                        {!! Form::open(['url' => route('customers.notes.store', $customer->id), 'method' => 'post']) !!}
                            <div class="form-group">
                                {!! Form::label('note_type', __('customers::lang.note_type')) !!}
                                {!! Form::select('note_type', ['general' => __('customers::lang.general'), 'collection' => __('customers::lang.collection'), 'service' => __('customers::lang.service')], null, ['class' => 'form-control']) !!}
                            </div>
                            <div class="form-group">
                                {!! Form::label('note', __('customers::lang.note')) !!}
                                {!! Form::textarea('note', null, ['class' => 'form-control', 'rows' => 3, 'required' => true]) !!}
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-plus"></i> @lang('customers::lang.add_note')
                            </button>
                        {!! Form::close() !!}

                        <hr>

                        @forelse($notes as $note)
                            <div class="well well-sm">
                                <div class="clearfix">
                                    <strong>{{ ucfirst($note->note_type ?? 'general') }}</strong>
                                    <small class="pull-right text-muted">{{ !empty($note->created_at) ? date('Y-m-d H:i', strtotime($note->created_at)) : '' }}</small>
                                </div>
                                <p style="white-space: pre-wrap; margin-top: 8px;">{{ $note->note }}</p>
                                {!! Form::open(['url' => route('customers.notes.destroy', [$customer->id, $note->id]), 'method' => 'delete', 'style' => 'display:inline;']) !!}
                                    <button type="submit" class="btn btn-link btn-xs text-danger" onclick="return confirm('{{ __('messages.sure') }}');">
                                        <i class="fa fa-trash"></i> @lang('customers::lang.delete')
                                    </button>
                                {!! Form::close() !!}
                            </div>
                        @empty
                            <p class="text-muted">@lang('customers::lang.no_records_found')</p>
                        @endforelse
                    @else
                        <div class="alert alert-warning">
                            @lang('customers::lang.notes_table_missing')
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history"></i> @lang('customers::lang.customer_activity_history')</h3>
                </div>
                <div class="box-body">
                    @if($activityEnabled)
                        @forelse($activities as $activity)
                            <div class="well well-sm">
                                <strong>{{ ucwords(str_replace('_', ' ', $activity->action ?? 'activity')) }}</strong>
                                <small class="pull-right text-muted">{{ !empty($activity->created_at) ? date('Y-m-d H:i', strtotime($activity->created_at)) : '' }}</small>
                                <div class="clearfix"></div>
                                <p style="margin-top: 8px;">{{ $activity->description ?? '-' }}</p>
                            </div>
                        @empty
                            <p class="text-muted">@lang('customers::lang.no_records_found')</p>
                        @endforelse
                    @else
                        <div class="alert alert-warning">
                            @lang('customers::lang.activity_table_missing')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
