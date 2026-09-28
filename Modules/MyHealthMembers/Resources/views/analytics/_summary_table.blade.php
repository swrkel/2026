@php($rows = $rows ?? [])
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th style="width: 70%;">Description</th>
                <th class="text-right">Count / Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['label'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format((float)($row['total'] ?? 0)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="text-center text-muted">No data available for the selected period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
