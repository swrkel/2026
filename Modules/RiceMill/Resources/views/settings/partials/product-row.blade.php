<tr data-rcm-product-id="{{ $p->id }}">
    <td>{{ $p->code ?: '-' }}</td>
    <td>{{ $p->name }}</td>
    <td>{{ $p->rice_type ?: '-' }}</td>
    <td>{{ $p->paddy_variety_id ? ($varietyNames[(int)$p->paddy_variety_id] ?? ('Variety #'.$p->paddy_variety_id)) : '-' }}</td>
    <td class="rcm-num">{{ number_format($p->current_qty,$rcmQuantityPrecision) }}</td>
    <td><span class="rcm-status-pill {{ $p->active ? 'active' : 'inactive' }}">{{ $p->active ? 'Enabled' : 'Disabled' }}</span></td>
    <td data-rcm-no-export><details class="rcm-action-menu"><summary><i class="fa fa-cog"></i> Action <i class="fa fa-caret-down"></i></summary><div class="rcm-action-menu-items"><button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-view-product-{{ $p->id }}"><i class="fa fa-eye"></i> View</button><button type="button" class="rcm-action-link" data-rcm-open-modal="rcm-edit-product-{{ $p->id }}"><i class="fa fa-pencil"></i> Edit</button>@include('RiceMill::settings.partials.confirm-delete',['deleteRoute'=>route('rice-mill.settings.product.delete',$p->id),'disabledReason'=>$p->has_transactions ? 'Cannot delete: already used in Rice Mill transactions.' : null])<form method="post" action="{{ route('rice-mill.settings.product.toggle',$p->id) }}">@csrf<button class="rcm-action-link" type="submit"><i class="fa {{ $p->active ? 'fa-ban' : 'fa-check-circle' }}"></i> {{ $p->active ? 'Disable' : 'Enable' }}</button></form></div></details></td>
</tr>
