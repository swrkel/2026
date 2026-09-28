<tr data-rcm-mill-id="{{ $m->id }}">
    <td>{{ $m->code ?: '-' }}</td>
    <td>{{ $m->name }}</td>
    <td>{{ $locationNames[(int) $m->location_id] ?? '-' }}</td>
    <td class="rcm-num">{{ $m->capacity_per_hour !== null ? number_format($m->capacity_per_hour,$rcmQuantityPrecision) : '-' }}</td>
    <td><span class="rcm-status-pill {{ $m->active ? 'active' : 'inactive' }}">{{ $m->active ? 'Enabled' : 'Disabled' }}</span></td>
    <td data-rcm-no-export>
        <details class="rcm-action-menu">
            <summary><i class="fa fa-cog"></i> Action <i class="fa fa-caret-down"></i></summary>
            <div class="rcm-action-menu-items">
                <button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-view-mill-{{ $m->id }}"><i class="fa fa-eye"></i> View</button>
                <button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-edit-mill-{{ $m->id }}"><i class="fa fa-pencil"></i> Edit</button>
                @include('RiceMill::settings.partials.confirm-delete',['deleteRoute'=>route('rice-mill.settings.mill.delete',$m->id),'disabledReason'=>$m->has_transactions ? 'Cannot delete: already used in Production transactions.' : null])
                <form method="post" action="{{ route('rice-mill.settings.mill.toggle',$m->id) }}">@csrf
                    <button class="rcm-action-link" type="submit"><i class="fa {{ $m->active ? 'fa-ban' : 'fa-check-circle' }}"></i> {{ $m->active ? 'Disable' : 'Enable' }}</button>
                </form>
            </div>
        </details>
    </td>
</tr>
