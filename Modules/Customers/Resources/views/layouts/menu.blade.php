<div class="customers-module-menu">
    @forelse($customer_menu ?? [] as $item)
        <a href="{{ $item['url'] }}" class="customers-module-menu-item {{ !empty($item['active']) ? 'active' : '' }}">
            <i class="{{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    @empty
        <span class="text-muted">No Customers menu items available.</span>
    @endforelse
</div>

<style>
    .customers-module-menu-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        margin-bottom: 6px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        color: #334155;
        font-weight: 600;
        background: #ffffff;
    }
    .customers-module-menu-item:hover,
    .customers-module-menu-item.active {
        background: #eff6ff;
        color: #1d4ed8;
        text-decoration: none;
        border-color: #bfdbfe;
    }
    .customers-module-menu-item i {
        width: 18px;
        text-align: center;
    }
</style>
