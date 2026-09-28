<div class="egg-pos-panel egg-recent-panel">
    <div class="egg-pos-panel-head">
        <div class="egg-panel-title-wrap">
            <span class="egg-panel-title-icon egg-panel-title-icon-blue"><i class="fa fa-list-alt"></i></span>
            <div>
                <h3>Recent Activity</h3>
                <p>Latest Egg Management transactions in the selected range.</p>
            </div>
        </div>
        <a href="{{ route('egg.reports.movements', request()->query()) }}" class="egg-panel-link">View Movements <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="egg-pos-panel-body egg-table-wrap">
        <table class="egg-dashboard-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Reference</th>
                    <th class="text-right">Value / Pieces</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentActivity as $activity)
                    <tr>
                        <td>{{ $activity['date'] }}</td>
                        <td><span class="egg-activity-type egg-activity-{{ $activity['theme'] }}"><i class="{{ $activity['icon'] }}"></i>{{ $activity['type'] }}</span></td>
                        <td><strong>{{ $activity['reference'] }}</strong></td>
                        <td class="text-right">{{ $activity['display_value'] }}</td>
                        <td><span class="egg-status-pill">{{ ucfirst($activity['status']) }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="egg-dashboard-empty">
                            <span class="egg-empty-icon"><i class="fa fa-inbox"></i></span>
                            <strong>No activity found</strong>
                            <small>No Egg Management transactions are available for this filter.</small>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
