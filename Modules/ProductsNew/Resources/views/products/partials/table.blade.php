@php
    $showPurchasePrice = (bool) ($canViewPurchasePrice ?? false);
    $stockDecimals = (int) config('productsnew.stock_decimals', 3);
    $currencyDecimals = (int) config('productsnew.currency_decimals', 4);
    $longCodeLimit = 18;

    $formatPriceRange = static function ($minimum, $maximum) use ($currencyDecimals): string {
        $minimum = (float) ($minimum ?? 0);
        $maximum = (float) ($maximum ?? $minimum);

        if (abs($maximum - $minimum) < 0.00001) {
            return number_format($minimum, $currencyDecimals);
        }

        return number_format($minimum, $currencyDecimals)
            . ' - '
            . number_format($maximum, $currencyDecimals);
    };
@endphp

<div class="table-responsive pn-products-table-wrap" role="region" aria-label="Products table" tabindex="0">
<table class="table table-bordered pn-table pn-products-table {{ $showPurchasePrice ? 'pn-has-purchase-price' : 'pn-without-purchase-price' }}">
    <colgroup>
        <col class="pn-col-action">
        <col class="pn-col-product">
        <col class="pn-col-sku">
        @if($showPurchasePrice)<col class="pn-col-purchase-price">@endif
        <col class="pn-col-selling-price">
        <col class="pn-col-category">
        <col class="pn-col-brand">
        <col class="pn-col-unit">
        <col class="pn-col-tax">
        <col class="pn-col-status">
    </colgroup>
    <thead>
        <tr>
            <th class="pn-product-action-column text-center">Action</th>
            <th class="pn-product-name-column">Product</th>
            <th class="pn-product-sku-column"><span class="pn-th-multiline">SKU /<br>Barcode</span></th>
            @if($showPurchasePrice)
                <th class="pn-product-purchase-price-column text-right"><span class="pn-th-multiline">Purchase<br>Price</span></th>
            @endif
            <th class="pn-product-selling-price-column text-right"><span class="pn-th-multiline">Selling<br>Price</span></th>
            <th class="pn-product-category-column">Category</th>
            <th class="pn-product-brand-column">Brand</th>
            <th class="pn-product-unit-column">Unit</th>
            <th class="pn-product-tax-column">Tax</th>
            <th class="pn-product-status-column text-center">Status</th>
        </tr>
    </thead>
    <tbody>
    @forelse($products as $product)
        @php
            $sku = trim((string) ($product->sku ?? ''));
            $barcode = trim((string) ($product->barcode ?? ''));
            $barcodeIsSeparate = $barcode !== '' && $barcode !== $sku;
            $hasLongCode = strlen($sku) > $longCodeLimit
                || ($barcodeIsSeparate && strlen($barcode) > $longCodeLimit);
            $purchasePrice = $formatPriceRange(
                $product->purchase_price_min ?? 0,
                $product->purchase_price_max ?? 0
            );
            $sellingPrice = $formatPriceRange(
                $product->selling_price_min ?? 0,
                $product->selling_price_max ?? 0
            );
            $detailsModalId = 'pn-product-code-details-' . (int) $product->id;
            $isInactive = (bool) ($product->not_for_selling ?? false)
                || (bool) ($product->is_inactive ?? false)
                || in_array(strtolower((string) ($product->products_new_status ?? '')), [
                    'inactive', 'suspended', 'discontinued', 'archived',
                ], true);
            $historyFrom = ! empty($product->created_at)
                ? date('Y-m-d', strtotime($product->created_at))
                : '2000-01-01';
        @endphp
        <tr>
            <td class="pn-product-action-column text-center">
                @php
                    $actionMenuId = 'pn-product-actions-' . (int) $product->id;
                @endphp
                <div class="pn-product-action-dropdown">
                    <button
                        type="button"
                        class="pn-btn pn-btn-primary pn-btn-sm pn-product-action-toggle"
                        data-pn-product-action-toggle
                        data-menu-id="{{ $actionMenuId }}"
                        aria-haspopup="true"
                        aria-controls="{{ $actionMenuId }}"
                        aria-expanded="false"
                        title="Open product actions"
                    >
                        <i class="fa fa-cog" aria-hidden="true"></i>
                        <span>Action</span>
                        <i class="fa fa-angle-down pn-product-action-arrow" aria-hidden="true"></i>
                    </button>

                    <ul
                        id="{{ $actionMenuId }}"
                        class="dropdown-menu pn-product-action-menu"
                        role="menu"
                        aria-label="Actions for {{ $product->name }}"
                        hidden
                    >
                        <li>
                            <a class="pn-btn pn-btn-sm pn-product-action-item pn-product-action-view"
                               href="{{ route('products-new.products.show', $product->id) }}">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                                <span>View</span>
                            </a>
                        </li>
                        <li>
                            <a class="pn-btn pn-btn-sm pn-product-action-item pn-product-action-edit"
                               href="{{ route('products-new.products.edit', $product->id) }}">
                                <i class="fa fa-pencil" aria-hidden="true"></i>
                                <span>Edit</span>
                            </a>
                        </li>
                        <li>
                            <a class="pn-btn pn-btn-sm pn-product-action-item pn-product-action-opening"
                               href="{{ route('products-new.products.opening-stock.edit', $product->id) }}">
                                <i class="fa fa-cubes" aria-hidden="true"></i>
                                <span>Add / Edit Opening Stock</span>
                            </a>
                        </li>
                        <li>
                            <a class="pn-btn pn-btn-sm pn-product-action-item pn-product-action-history"
                               href="{{ route('products-new.stock-history.index', [
                                   'product_id' => $product->id,
                                   'from_date' => $historyFrom,
                                   'to_date' => date('Y-m-d'),
                               ]) }}">
                                <i class="fa fa-history" aria-hidden="true"></i>
                                <span>Product History Report</span>
                            </a>
                        </li>
                        <li class="divider" role="separator"></li>
                        <li>
                            <form method="post"
                                  action="{{ route('products-new.products.status', $product->id) }}"
                                  class="pn-product-inline-action-form pn-product-confirm-form"
                                  data-confirm-first="{{ $isInactive ? 'Activate' : 'Deactivate' }} {{ $product->name }}?"
                                  data-confirm-second="Final confirmation: {{ $isInactive ? 'activate this product and make it available in current forms' : 'deactivate this product and hide it from current forms and dropdowns' }}?">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="active" value="{{ $isInactive ? 1 : 0 }}">
                                <button type="submit"
                                        class="pn-btn pn-btn-sm pn-product-action-item {{ $isInactive ? 'pn-product-action-activate' : 'pn-product-action-deactivate' }}">
                                    <i class="fa {{ $isInactive ? 'fa-check-circle' : 'fa-ban' }}" aria-hidden="true"></i>
                                    <span>{{ $isInactive ? 'Activate' : 'Deactivate' }}</span>
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="post"
                                  action="{{ route('products-new.products.destroy', $product->id) }}"
                                  class="pn-product-inline-action-form pn-product-confirm-form"
                                  data-confirm-first="Delete {{ $product->name }}? The system will verify that there are no transactions or stock balances."
                                  data-confirm-second="Final confirmation: permanently delete this unused product? This action cannot be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pn-btn pn-btn-sm pn-product-action-item pn-product-action-delete">
                                    <i class="fa fa-trash" aria-hidden="true"></i>
                                    <span>Delete</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </td>
            <td class="pn-product-name-column">
                <strong>{{ $product->name }}</strong><br>
                <small>{{ $product->type }}</small>
            </td>
            <td class="pn-product-sku-column">
                @if($hasLongCode)
                    <button
                        type="button"
                        class="pn-code-details-button"
                        data-toggle="modal"
                        data-target="#{{ $detailsModalId }}"
                        title="View the complete SKU, stock and price details"
                    >
                        <i class="fa fa-external-link-square" aria-hidden="true"></i>
                        <span>Click to View</span>
                    </button>
                @else
                    <span class="pn-product-code-value">{{ $sku !== '' ? $sku : '-' }}</span>
                    @if($barcodeIsSeparate)
                        <small class="pn-product-code-secondary">{{ $barcode }}</small>
                    @endif
                @endif
            </td>
            @if($showPurchasePrice)
                <td class="pn-product-purchase-price-column text-right">
                    <span class="pn-price-value">{{ $purchasePrice }}</span>
                </td>
            @endif
            <td class="pn-product-selling-price-column text-right">
                <span class="pn-price-value">{{ $sellingPrice }}</span>
            </td>
            <td class="pn-product-category-column">
                {{ $product->category_name ?: '-' }}
                @if($product->sub_category_name)
                    <br><small>{{ $product->sub_category_name }}</small>
                @endif
            </td>
            <td class="pn-product-brand-column">{{ $product->brand_name ?: '-' }}</td>
            <td class="pn-product-unit-column">{{ $product->unit_short_name ?: '-' }}</td>
            <td class="pn-product-tax-column">
                {{ $product->tax_name ?: '-' }}<br>
                <small>{{ ucfirst($product->tax_type ?? 'exclusive') }}</small>
            </td>
            <td class="pn-product-status-column text-center">
                <span class="pn-badge {{ $isInactive ? 'pn-badge-muted' : 'pn-badge-success' }}">
                    {{ $isInactive ? 'Inactive' : 'Active' }}
                </span>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ $showPurchasePrice ? 10 : 9 }}" class="text-center">No products found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="pn-products-scroll-hint no-print">
    <i class="fa fa-arrows-h" aria-hidden="true"></i>
    <span>Use the horizontal slider above when more columns are available.</span>
</div>

@foreach($products as $product)
    @php
        $sku = trim((string) ($product->sku ?? ''));
        $barcode = trim((string) ($product->barcode ?? ''));
        $barcodeIsSeparate = $barcode !== '' && $barcode !== $sku;
        $hasLongCode = strlen($sku) > $longCodeLimit
            || ($barcodeIsSeparate && strlen($barcode) > $longCodeLimit);
        $purchasePrice = $formatPriceRange(
            $product->purchase_price_min ?? 0,
            $product->purchase_price_max ?? 0
        );
        $sellingPrice = $formatPriceRange(
            $product->selling_price_min ?? 0,
            $product->selling_price_max ?? 0
        );
        $detailsModalId = 'pn-product-code-details-' . (int) $product->id;
    @endphp

    @if($hasLongCode)
        <div class="modal fade pn-product-code-modal" id="{{ $detailsModalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $detailsModalId }}-title">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <div class="pn-product-code-modal-heading">
                            <span class="pn-product-code-modal-icon"><i class="fa fa-cubes" aria-hidden="true"></i></span>
                            <div>
                                <h4 class="modal-title" id="{{ $detailsModalId }}-title">Product Details</h4>
                                <p>Complete SKU, stock and pricing information</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <dl class="pn-product-code-details-list">
                            <div>
                                <dt>Product Name</dt>
                                <dd>{{ $product->name }}</dd>
                            </div>
                            <div>
                                <dt>SKU</dt>
                                <dd class="pn-code-break">{{ $sku !== '' ? $sku : '-' }}</dd>
                            </div>
                            @if($barcodeIsSeparate)
                                <div>
                                    <dt>Barcode</dt>
                                    <dd class="pn-code-break">{{ $barcode }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Current Stock</dt>
                                <dd>{{ number_format((float) ($product->current_stock ?? 0), $stockDecimals) }}</dd>
                            </div>
                            @if($showPurchasePrice)
                                <div>
                                    <dt>Purchase Price</dt>
                                    <dd>{{ $purchasePrice }}</dd>
                                </div>
                            @endif
                            <div>
                                <dt>Selling Price</dt>
                                <dd>{{ $sellingPrice }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pn-btn pn-btn-light" data-dismiss="modal">Close</button>
                        <a class="pn-btn pn-btn-primary" href="{{ route('products-new.products.show', $product->id) }}">
                            <i class="fa fa-eye" aria-hidden="true"></i> View Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach


@push('javascript')
<script>
(function ($) {
    'use strict';

    var activeActionMenu = null;

    function placeActionMenu() {
        if (!activeActionMenu) {
            return;
        }

        var button = activeActionMenu.button.get(0);
        var menu = activeActionMenu.menu.get(0);

        if (!button || !menu) {
            closeActionMenu();
            return;
        }

        var rect = button.getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        activeActionMenu.menu.css({
            display: 'block',
            position: 'fixed',
            visibility: 'hidden',
            left: 0,
            top: 0,
            zIndex: 100000
        });

        var menuWidth = activeActionMenu.menu.outerWidth();
        var menuHeight = activeActionMenu.menu.outerHeight();
        var left = rect.left;
        var top = rect.bottom + 6;

        if (left + menuWidth > viewportWidth - 8) {
            left = Math.max(8, viewportWidth - menuWidth - 8);
        }

        if (top + menuHeight > viewportHeight - 8 && rect.top - menuHeight - 6 >= 8) {
            top = rect.top - menuHeight - 6;
        }

        activeActionMenu.menu.css({
            left: Math.round(left),
            top: Math.round(top),
            visibility: 'visible'
        });
    }

    function closeActionMenu() {
        if (!activeActionMenu) {
            return;
        }

        activeActionMenu.menu
            .removeClass('pn-product-action-menu-portal')
            .removeAttr('style')
            .attr('hidden', true)
            .appendTo(activeActionMenu.owner);

        activeActionMenu.button
            .attr('aria-expanded', 'false')
            .removeClass('is-open');

        activeActionMenu = null;
    }

    function openActionMenu($button) {
        var menuId = String($button.data('menu-id') || '');
        var $menu = menuId ? $('#' + menuId) : $();

        if (!$menu.length) {
            return;
        }

        if (activeActionMenu && activeActionMenu.button.get(0) === $button.get(0)) {
            closeActionMenu();
            return;
        }

        closeActionMenu();

        activeActionMenu = {
            button: $button,
            menu: $menu,
            owner: $menu.parent()
        };

        $menu
            .appendTo(document.body)
            .removeAttr('hidden')
            .addClass('pn-product-action-menu-portal');

        $button
            .attr('aria-expanded', 'true')
            .addClass('is-open');

        placeActionMenu();

        var $firstAction = $menu.find('a, button').filter(':visible').first();
        if ($firstAction.length) {
            window.setTimeout(function () {
                $firstAction.trigger('focus');
            }, 0);
        }
    }

    $(function () {
        $(document).on('click', '[data-pn-product-action-toggle]', function (event) {
            event.preventDefault();
            event.stopPropagation();
            openActionMenu($(this));
        });

        $(document).on('click', function (event) {
            if (!activeActionMenu) {
                return;
            }

            var target = event.target;
            if (activeActionMenu.menu.get(0).contains(target)
                || activeActionMenu.button.get(0).contains(target)) {
                return;
            }

            closeActionMenu();
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape' || event.keyCode === 27) {
                closeActionMenu();
            }
        });

        $(window).on('resize.pnProductActions scroll.pnProductActions', function () {
            placeActionMenu();
        });

        $(document).on('submit', '.pn-product-confirm-form', function (event) {
            var $form = $(this);

            if (!window.confirm($form.data('confirm-first') || 'Continue with this action?')) {
                event.preventDefault();
                return;
            }

            if (!window.confirm($form.data('confirm-second') || 'Final confirmation: continue?')) {
                event.preventDefault();
                return;
            }
        });

        $(document).on('click', '.pn-product-action-menu a', function () {
            closeActionMenu();
        });
    });
})(jQuery);
</script>
@endpush
