@extends('layouts.app')
@section('title', __('lang_v1.backup'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.backup')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    
  @if (session('notification') || !empty($notification))
    <div class="row">
        <div class="col-sm-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                @if(!empty($notification['msg']))
                    {{$notification['msg']}}
                @elseif(session('notification.msg'))
                    {{ session('notification.msg') }}
                @endif
              </div>
          </div>  
      </div>     
  @endif

  <div class="row">
    <div class="col-sm-12">
      @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
          <div class="box-tools">
              @can('backup')
                    <a id="create-new-backup-button" style="margin: 10px;margin-top: 20px" href="#" data-url="{{ url('backup/create') }}" class="btn btn-primary pull-right">
                        <i class="fa fa-plus"></i> @lang('lang_v1.create_new_backup')
                    </a>
              @endcan
          </div>
        @endslot
        @if (count($backups))
                <table class="table table-striped table-bordered" id="backup_table">
                  <thead>
                  <tr>
                      <th>@lang('lang_v1.file')</th>
                      <th>@lang('lang_v1.size')</th>
                      <th>@lang('lang_v1.date')</th>
                      <th>@lang('lang_v1.age')</th>
                      <th>@lang('messages.actions')</th>
                  </tr>
                  </thead>
                    <tbody>
                    @foreach($backups as $backup)
                        @php $restoreUrl = url('backup/restore/'.$backup['name']); @endphp
                        <tr data-backup-file="{{ $backup['name'] }}">
                            <td>{{ $backup['name'] }}</td>
                            <td>{{ humanFilesize($backup['size_raw']) }}</td>
                            <td>
                                {{ $backup['date'] }}
                            </td>
                            <td>
                                {{ $backup['age'] ?? '-' }}
                            </td>
                            <td>
                              <a class="btn btn-xs btn-success"
                                   href="{{action('BackUpController@download', [$backup['name']])}}"><i
                                        class="fa fa-cloud-download"></i> @lang('lang_v1.download')</a>
                                <a class="btn btn-xs btn-danger backup-delete-btn"
                                   data-file="{{ $backup['name'] }}"
                                   data-url="{{ action('BackUpController@delete', [$backup['name']]) }}"
                                   href="{{ action('BackUpController@delete', [$backup['name']]) }}"><i class="fa fa-trash-o"></i>
                                    @lang('messages.delete')</a>
                                @can('backup.restore')
                                <a class="btn btn-xs btn-primary link_confirmation" data-button-type="restore"
                                   href="{{$restoreUrl}}"><i class="fa fa-undo"></i>
                                    Restore</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
              </table>
            @else
                <div class="well">
                    <h4>There are no backups</h4>
                </div>
            @endif
            <br>
            <strong>@lang('lang_v1.auto_backup_instruction'):</strong><br>
        <code>{{$cron_job_command}}</code>
      @endcomponent
    </div>
  </div>
</section>
<div class="modal-dialog modal-lg no-print" role="document" style="width: 70%;" id="uploadBackup">
  <div class="modal-content">
    <div class="modal-header">
    <h4 class="modal-title" id="modalTitle"> 
      Upload Database Backup
    </h4>
</div>
@can('backup.upload')
    <div class="modal-body">
         {!! Form::open(['url' => action('BackUpController@store'), 'method' => 'post', 'id'=>'upload_backup_form','files' => true ]) !!}
    
        <div class="row">
             <div class="col-6" style="margin-left:20px">
                  <div class="form-group">
                    {!! Form::label('Upload Backup') !!} {!! Form::file('backup', ['id' => 'backup', 'accept' => '.gz,.tar','required'=>true]); !!}
                    <p class="help-block">Allowed types: .tar, .gz. Max size: {{ (int)env('BACKUP_UPLOAD_MAX_KB', 512000) / 1024 }} MB.</p>
                   </div>
            </div>
        </div>
       <div class="modal-footer">
            <button type="submit" class="btn btn-default no-print">Upload</button>
        </div>
         {!! Form::close() !!}
      </div>
@endcan

</div>



{{-- @if(session('status'))
    
    @if(session('status')[0]["success"] == 1)
        <script>
            toastr.success("{{ session('status')[0]['msg'] }}");
        </script>
    @elseif(session('status')[0]["success"] == 0)
        <script>toastr.error("{{ session('status')[0]['msg'] }}");</script>
    @endif

@endif --}}

   

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);

    // BCK-CREATE-AJAX-034: create one database backup without page refresh and without duplicate jobs.
    $(document).off('click.backupInstantCreate', '#create-new-backup-button');
    $(document).on('click.backupInstantCreate', '#create-new-backup-button', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $(this);
        var url = $btn.data('url') || $btn.attr('href');
        var originalHtml = $btn.html();

        if ($btn.data('busy')) {
            return false;
        }

        $btn.data('busy', true)
            .addClass('disabled')
            .html('<i class="fa fa-spinner fa-spin"></i> Creating');

        $.ajax({
            method: 'POST',
            url: url,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                $btn.data('busy', false).removeClass('disabled').html(originalHtml);

                if (response && (response.success === 1 || response.success === true)) {
                    if (response.row_html) {
                        if ($('#backup_table').length) {
                            $('#backup_table tbody').prepend(response.row_html);
                        } else {
                            $('.well:contains("There are no backups")').replaceWith(
                                '<table class="table table-striped table-bordered" id="backup_table">' +
                                '<thead><tr>' +
                                '<th>@lang('lang_v1.file')</th>' +
                                '<th>@lang('lang_v1.size')</th>' +
                                '<th>@lang('lang_v1.date')</th>' +
                                '<th>@lang('lang_v1.age')</th>' +
                                '<th>@lang('messages.actions')</th>' +
                                '</tr></thead><tbody>' + response.row_html + '</tbody></table>'
                            );
                        }
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.msg || 'Backup created successfully.');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error((response && response.msg) ? response.msg : 'Backup create failed.');
                    } else {
                        alert((response && response.msg) ? response.msg : 'Backup create failed.');
                    }
                }
            },
            error: function(xhr) {
                $btn.data('busy', false).removeClass('disabled').html(originalHtml);
                var msg = 'Backup create failed.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            }
        });

        return false;
    });

    // BCK-DEL-INSTANT-033: delete one backup without refreshing or rescanning the full backup list.
    $(document).off('click.backupInstantDelete', '.backup-delete-btn');
    $(document).on('click.backupInstantDelete', '.backup-delete-btn', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        var $btn = $(this);
        var url = $btn.data('url') || $btn.attr('href');
        var file = $btn.data('file') || '';
        var $row = $btn.closest('tr');
        var originalHtml = $btn.html();

        if ($btn.data('busy')) {
            return false;
        }

        if (!confirm('Are you sure you want to delete this backup?')) {
            return false;
        }

        $btn.data('busy', true)
            .addClass('disabled')
            .html('<i class="fa fa-spinner fa-spin"></i> Deleting');

        $.ajax({
            method: 'POST',
            url: url,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                file: file
            },
            success: function(response) {
                if (response && (response.success === 1 || response.success === true)) {
                    $row.fadeOut(120, function() {
                        $(this).remove();
                        if ($('#backup_table tbody tr').length === 0) {
                            $('#backup_table').replaceWith('<div class="well"><h4>There are no backups</h4></div>');
                        }
                    });

                    if (typeof toastr !== 'undefined') {
                        toastr.success(response.msg || 'Backup deleted successfully.');
                    }
                } else {
                    $btn.data('busy', false).removeClass('disabled').html(originalHtml);
                    if (typeof toastr !== 'undefined') {
                        toastr.error((response && response.msg) ? response.msg : 'Backup delete failed.');
                    } else {
                        alert((response && response.msg) ? response.msg : 'Backup delete failed.');
                    }
                }
            },
            error: function(xhr) {
                $btn.data('busy', false).removeClass('disabled').html(originalHtml);
                var msg = 'Backup delete failed.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            }
        });

        return false;
    });
  });
</script>

@endsection