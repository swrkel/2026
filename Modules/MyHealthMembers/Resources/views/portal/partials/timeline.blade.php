@if(empty($timeline) || count($timeline) === 0)
    <div class="empty-state"><i class="fa fa-list-alt"></i><br>No timeline records found.</div>
@else
    <ul class="timeline">
        @foreach($timeline as $item)
            <li>
                <span class="dot"><i class="fa fa-{{ $item->icon ?? 'circle' }}"></i></span>
                <strong>{{ $item->type ?? 'Record' }}</strong>
                @if(!empty($item->status)) <span class="label label-default">{{ ucfirst($item->status) }}</span> @endif
                <div>{{ $item->title ?? 'Health record' }}</div>
                @if(!empty($item->notes))<div class="text-muted">{{ \Illuminate\Support\Str::limit($item->notes, 120) }}</div>@endif
                <div class="time">{{ $item->date ?? '' }}</div>
            </li>
        @endforeach
    </ul>
@endif
