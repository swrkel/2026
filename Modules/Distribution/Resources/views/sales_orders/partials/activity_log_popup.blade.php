<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('lang_v1.changed_activities')<!-- Changed Activities --></h4>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Description</th>
                            <th>Changed Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($activity->created_at)->format('Y-m-d H:i:s') }}</td>
                                <td>{{ optional($activity->causer)->username ?? optional($activity->causer)->first_name ?? '-' }}</td>
                                <td>{{ ucfirst($activity->description) }}</td>
                                <td>
                                    @php
                                        $props = $activity->properties;
                                        if (is_object($props) && method_exists($props, 'toArray')) {
                                            $props = $props->toArray();
                                        }
                                        $attributes = is_array($props) ? ($props['attributes'] ?? []) : [];
                                        $old = is_array($props) ? ($props['old'] ?? []) : [];
                                    @endphp
                                    @if($activity->description === 'deleted')
                                        <span class="text-danger">
                                            Deleted by {{ optional($activity->causer)->username ?? optional($activity->causer)->first_name ?? '' }}
                                            on {{ \Carbon\Carbon::parse($activity->created_at)->format('Y-m-d H:i:s') }}
                                        </span>
                                    @elseif(!empty($attributes))
                                        <button type="button" class="btn btn-xs btn-default btn-so-changed-details">Changed Details</button>
                                        <div class="so-changed-details-wrapper" style="display:none; margin-top:8px;">
                                            <p><strong>Date & Time:</strong> {{ \Carbon\Carbon::parse($activity->created_at)->format('Y-m-d H:i:s') }}</p>
                                            <p><strong>Who Changed:</strong> {{ optional($activity->causer)->username ?? optional($activity->causer)->first_name ?? '-' }}</p>
                                            @if(!empty($old))
                                                <table class="table table-condensed">
                                                    <thead>
                                                        <tr>
                                                            <th>Column</th>
                                                            <th>Original</th>
                                                            <th>Changed To</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($attributes as $key => $value)
                                                            @if(!in_array($key, ['updated_at', 'created_at']))
                                                                <tr>
                                                                    <td>{{ $key }}</td>
                                                                    <td>{{ $old[$key] ?? '-' }}</td>
                                                                    <td>{{ $value }}</td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                @foreach($attributes as $key => $value)
                                                    @if(!in_array($key, ['updated_at', 'created_at']))
                                                        <p><strong>{{ $key }}:</strong> {{ $value }}</p>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    @else
                                        -
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
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
    </div>
  </div>
</div>
