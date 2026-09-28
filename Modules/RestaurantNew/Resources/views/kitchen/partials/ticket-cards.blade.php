@forelse($tickets as $ticket)
    <div class="restaurantnew-kot-card" data-status="{{ $ticket->status }}" data-ticket="{{ $ticket->ticket_no }}">
        <div class="restaurantnew-kot-card-header">
            <strong>{{ $ticket->ticket_no }}</strong>
            <span class="label label-info">{{ strtoupper($ticket->status) }}</span>
        </div>
        <div class="restaurantnew-kot-meta">
            <span>@lang('restaurantnew::lang.order'): #{{ $ticket->order_id }}</span>
            <span>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</span>
        </div>
        <div class="restaurantnew-kot-lines">
            @foreach($ticket->lines as $line)
                <div class="restaurantnew-kot-line">
                    <span class="qty">{{ number_format((float) $line->quantity, 3) }}</span>
                    <span class="item">{{ $line->item_name }}</span>
                    @if($line->modifiers_text)<small>{{ $line->modifiers_text }}</small>@endif
                    @if($line->special_instruction)<em>{{ $line->special_instruction }}</em>@endif
                </div>
            @endforeach
        </div>
        <div class="restaurantnew-kot-actions">
            <button class="btn btn-xs btn-warning restaurantnew-kot-status" data-id="{{ $ticket->id }}" data-status="preparing">@lang('restaurantnew::lang.start')</button>
            <button class="btn btn-xs btn-success restaurantnew-kot-status" data-id="{{ $ticket->id }}" data-status="completed">@lang('restaurantnew::lang.complete')</button>
            <a class="btn btn-xs btn-default" target="_blank" href="{{ route('restaurantnew.kitchen.print', $ticket->id) }}">@lang('restaurantnew::lang.reprint')</a>
            <button class="btn btn-xs btn-danger restaurantnew-kot-cancel" data-id="{{ $ticket->id }}">@lang('restaurantnew::lang.cancel')</button>
        </div>
    </div>
@empty
    <div class="alert alert-info">@lang('restaurantnew::lang.no_kitchen_tickets')</div>
@endforelse
