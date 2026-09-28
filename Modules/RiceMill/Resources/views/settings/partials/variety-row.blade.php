<tr data-rcm-variety-id="{{ $v->id }}">
    <td>{{ $v->code }}</td>
    <td>{{ $v->name }}</td>
    <td class="rcm-num">{{ $v->default_moisture_percent !== null ? number_format($v->default_moisture_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->foreign_matter_limit_percent !== null ? number_format($v->foreign_matter_limit_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->expected_rice_yield_percent !== null ? number_format($v->expected_rice_yield_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->expected_broken_rice_percent !== null ? number_format($v->expected_broken_rice_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->expected_bran_percent !== null ? number_format($v->expected_bran_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->expected_husk_percent !== null ? number_format($v->expected_husk_percent,3) : '-' }}</td>
    <td class="rcm-num">{{ $v->expected_process_loss_percent !== null ? number_format($v->expected_process_loss_percent,3) : '-' }}</td>
    <td>{{ $v->quality_grade ?: '-' }}</td>
    <td>{{ $v->lot_sequence_prefix }}</td>
    <td class="rcm-num">{{ number_format($v->lot_opening_number ?: 1,0) }}</td>
    <td class="rcm-num">{{ number_format($v->lot_next_number,0) }}</td>
    <td>{{ $v->lot_next_preview }}</td>
    <td><span class="rcm-status-pill {{ $v->active ? 'active' : 'inactive' }}">{{ $v->active ? 'Enabled' : 'Disabled' }}</span></td>
    <td data-rcm-no-export>
        <details class="rcm-action-menu">
            <summary><i class="fa fa-cog"></i> Action <i class="fa fa-caret-down"></i></summary>
            <div class="rcm-action-menu-items">
                <button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-view-variety-{{ $v->id }}"><i class="fa fa-eye"></i> View</button>
                <button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-edit-variety-{{ $v->id }}"><i class="fa fa-pencil"></i> Edit</button>
                @include('RiceMill::settings.partials.confirm-delete',['deleteRoute'=>route('rice-mill.settings.variety.delete',$v->id),'disabledReason'=>$v->has_transactions ? 'Cannot delete: already used in transactions.' : null])
                <form method="post" action="{{ route('rice-mill.settings.variety.toggle',$v->id) }}">@csrf<button class="rcm-action-link" type="submit"><i class="fa {{ $v->active ? 'fa-ban' : 'fa-check-circle' }}"></i> {{ $v->active ? 'Disable' : 'Enable' }}</button></form>
            </div>
        </details>
    </td>
</tr>
