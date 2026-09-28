<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.changed_activities')</h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('lang_v1.date')</th>
                            <th>@lang('lang_v1.user')</th>
                            <th>@lang('lang_v1.description')</th>
                            <th>@lang('lang_v1.changed_details')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>{{ @format_datetime($activity->created_at) }}</td>
                                <td>{{ $activity->causer->username ?? '' }}</td>
                                <td>{{ $activity->description }}</td>
                                <td>
                                    @if(!empty($activity->changes['attributes']))
                                        <button type="button" class="btn btn-xs btn-default btn-changed-details">Changed Details</button>
                                        <div class="changed-details-wrapper" style="display:none; margin-top:8px;">
                                            @if($activity->description == 'deleted')
                                                <span class="text-danger">Deleted by {{ $activity->causer->username ?? '' }} on {{ @format_datetime($activity->created_at) }}</span>
                                            @else
                                                <p><strong>Date & Time:</strong> {{ @format_datetime($activity->created_at) }}</p>
                                                <p><strong>Who Changed:</strong> {{ $activity->causer->username ?? '' }}</p>
                                                <table class="table table-condensed">
                                                    <thead>
                                                        <tr>
                                                            <th>Column</th>
                                                            <th>Original</th>
                                                            <th>Changed</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($activity->changes['attributes'] as $key => $value)
                                                            @if(!in_array($key, ['updated_at', 'created_at']))
                                                                <tr>
                                                                    <td>{{ $key }}</td>
                                                                    <td>{{ $activity->changes['old'][$key] ?? '-' }}</td>
                                                                    <td>{{ $value }}</td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
  </div>
</div>
