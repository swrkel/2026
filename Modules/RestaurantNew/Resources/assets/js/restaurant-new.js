(function () {
    'use strict';

    const q = (selector, context = document) => context.querySelector(selector);
    const qa = (selector, context = document) => Array.from(context.querySelectorAll(selector));
    const money = value => (Number(value) || 0).toFixed(4);
    const decodeBase64Json = value => {
        const binary = window.atob(value || 'W10=');
        const bytes = Uint8Array.from(binary, character => character.charCodeAt(0));
        return JSON.parse(new TextDecoder().decode(bytes));
    };
    const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
    }[character]));

    function setupWaiterOrder() {
        const form = q('#rest-order-form');
        if (!form) return;

        let cart = [];
        let pendingItem = null;
        const lines = q('#rest-cart-lines');
        const count = q('#rest-cart-count');
        const subtotal = q('#rest-cart-subtotal');
        const total = q('#rest-cart-total');
        const orderType = q('#rest-order-type');
        const tableSelect = q('#rest-table-select');
        const modal = q('#rest-modifier-modal');
        const modalGroups = q('#rest-modifier-groups');
        const modalTitle = q('#rest-modifier-title');
        const modalNote = q('#rest-modifier-note');

        function itemPrice(item) {
            if (orderType.value !== 'dine_in' && item.takeawayPrice !== null && item.takeawayPrice !== '') {
                return Number(item.takeawayPrice);
            }
            return Number(item.basePrice);
        }

        function render() {
            lines.innerHTML = '';
            if (!cart.length) {
                lines.innerHTML = '<div class="rest-empty">Tap a menu item to add it.</div>';
            }

            cart.forEach((item, index) => {
                const price = itemPrice(item) + item.modifierPrice;
                const modifierText = item.modifierNames.length ? item.modifierNames.join(', ') : 'No modifiers';
                const row = document.createElement('div');
                row.className = 'rest-cart-line rest-cart-line-configured';
                row.innerHTML = '<span><strong>' + escapeHtml(item.name) + '</strong>' +
                    '<small>' + escapeHtml(item.code) + ' · ' + money(price) + '</small>' +
                    '<small class="rest-cart-modifiers">' + escapeHtml(modifierText) + '</small>' +
                    '<input type="hidden" name="items[' + index + '][menu_item_id]" value="' + item.id + '">' +
                    '<input type="hidden" name="items[' + index + '][quantity]" value="' + item.qty + '">' +
                    item.modifierIds.map(id => '<input type="hidden" name="items[' + index + '][modifier_ids][]" value="' + id + '">').join('') +
                    '<input class="rest-line-note" name="items[' + index + '][notes]" value="' + escapeHtml(item.note) + '" placeholder="Item note"></span>' +
                    '<span class="rest-qty"><button type="button" data-delta="-1">−</button><span>' + money(item.qty) + '</span><button type="button" data-delta="1">+</button></span>' +
                    '<button type="button" class="rest-cart-remove"><i class="fa fa-trash"></i></button>';

                qa('[data-delta]', row).forEach(button => button.addEventListener('click', () => {
                    item.qty = Math.max(0.0001, item.qty + Number(button.dataset.delta));
                    render();
                }));
                q('.rest-cart-remove', row).addEventListener('click', () => {
                    cart.splice(index, 1);
                    render();
                });
                q('.rest-line-note', row).addEventListener('input', event => { item.note = event.target.value; });
                lines.appendChild(row);
            });

            const sum = cart.reduce((amount, item) => amount + item.qty * (itemPrice(item) + item.modifierPrice), 0);
            count.textContent = cart.reduce((amount, item) => amount + item.qty, 0).toFixed(0);
            subtotal.textContent = money(sum);
            total.textContent = money(sum);
        }

        function addItem(item, selectedModifiers = [], note = '') {
            const ids = selectedModifiers.map(modifier => Number(modifier.id)).sort((a, b) => a - b);
            const key = item.id + ':' + ids.join(',') + ':' + note.trim().toLowerCase();
            const existing = cart.find(row => row.key === key);
            if (existing) {
                existing.qty += 1;
            } else {
                cart.push({
                    key,
                    id: item.id,
                    code: item.code,
                    name: item.name,
                    basePrice: item.basePrice,
                    takeawayPrice: item.takeawayPrice,
                    modifierIds: ids,
                    modifierNames: selectedModifiers.map(modifier => modifier.name),
                    modifierPrice: selectedModifiers.reduce((sum, modifier) => sum + Number(modifier.price || 0), 0),
                    note: note.trim(),
                    qty: 1
                });
            }
            render();
        }

        function parseItem(button) {
            let modifiers = [];
            try {
                modifiers = decodeBase64Json(button.dataset.modifiers);
            } catch (error) {
                modifiers = [];
            }
            return {
                id: button.dataset.id,
                code: button.dataset.code,
                name: button.dataset.name,
                basePrice: button.dataset.price,
                takeawayPrice: button.dataset.takeawayPrice,
                modifiers
            };
        }

        function openModifierModal(item) {
            pendingItem = item;
            modalTitle.textContent = item.name;
            modalNote.value = '';
            modalGroups.innerHTML = '';

            item.modifiers.forEach(group => {
                const minimum = Math.max(Number(group.min || 0), group.required ? 1 : 0);
                const maximum = Math.max(1, Number(group.max || 1));
                const section = document.createElement('section');
                section.className = 'rest-modifier-group';
                section.dataset.minimum = minimum;
                section.dataset.maximum = maximum;
                section.innerHTML = '<header><strong>' + escapeHtml(group.name) + '</strong><small>' +
                    (minimum ? 'Select ' + minimum + '–' + maximum : 'Select up to ' + maximum) + '</small></header><div class="rest-modifier-options"></div>';
                const options = q('.rest-modifier-options', section);
                (group.options || []).forEach(option => {
                    const label = document.createElement('label');
                    label.className = 'rest-modifier-option';
                    label.innerHTML = '<input type="checkbox" value="' + option.id + '" data-name="' + escapeHtml(option.name) + '" data-price="' + Number(option.price || 0) + '">' +
                        '<span><strong>' + escapeHtml(option.name) + '</strong><small>' + (Number(option.price || 0) >= 0 ? '+' : '') + money(option.price) + '</small></span>';
                    const input = q('input', label);
                    input.addEventListener('change', () => {
                        const selected = qa('input:checked', section);
                        if (selected.length > maximum) {
                            input.checked = false;
                            window.alert('You can select a maximum of ' + maximum + ' option(s) for ' + group.name + '.');
                        }
                    });
                    options.appendChild(label);
                });
                modalGroups.appendChild(section);
            });

            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModifierModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            pendingItem = null;
        }

        qa('.rest-menu-item').forEach(button => button.addEventListener('click', () => {
            const item = parseItem(button);
            if (item.modifiers.length) {
                openModifierModal(item);
            } else {
                addItem(item);
            }
        }));

        q('#rest-add-configured-item')?.addEventListener('click', () => {
            if (!pendingItem) return;
            const selected = [];
            let valid = true;
            qa('.rest-modifier-group', modalGroups).forEach(group => {
                const checked = qa('input:checked', group);
                const minimum = Number(group.dataset.minimum || 0);
                const maximum = Number(group.dataset.maximum || 1);
                if (checked.length < minimum || checked.length > maximum) {
                    valid = false;
                    group.classList.add('invalid');
                } else {
                    group.classList.remove('invalid');
                }
                checked.forEach(input => selected.push({
                    id: input.value,
                    name: input.dataset.name,
                    price: Number(input.dataset.price || 0)
                }));
            });
            if (!valid) {
                window.alert('Complete the required modifier selections.');
                return;
            }
            addItem(pendingItem, selected, modalNote.value);
            closeModifierModal();
        });

        qa('[data-rest-modal-close]').forEach(button => button.addEventListener('click', closeModifierModal));
        modal?.addEventListener('click', event => {
            if (event.target === modal) closeModifierModal();
        });

        const search = q('#rest-menu-search');
        if (search) search.addEventListener('input', filterMenu);
        qa('[data-rest-category]').forEach(button => button.addEventListener('click', () => {
            qa('[data-rest-category]').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            filterMenu();
        }));

        function filterMenu() {
            const term = (search?.value || '').toLowerCase();
            const category = q('[data-rest-category].active')?.dataset.restCategory || 'all';
            const type = orderType.value;
            qa('.rest-menu-item').forEach(item => {
                const textMatches = (item.dataset.name + ' ' + item.dataset.code).toLowerCase().includes(term);
                const categoryMatches = category === 'all' || item.dataset.category === category;
                const typeMatches = type === 'dine_in' ? item.dataset.dineIn === '1' : (type === 'delivery' ? item.dataset.delivery === '1' : item.dataset.takeaway === '1');
                item.style.display = textMatches && categoryMatches && typeMatches ? '' : 'none';
            });
        }

        orderType.addEventListener('change', () => {
            tableSelect.disabled = orderType.value !== 'dine_in';
            if (tableSelect.disabled) tableSelect.value = '';
            filterMenu();
            render();
        });

        form.addEventListener('submit', event => {
            if (!cart.length) {
                event.preventDefault();
                window.alert('Add at least one menu item.');
            }
        });
    }

    function setupPayments() {
        qa('[data-rest-payment-toggle]').forEach(button => button.addEventListener('click', () => q('#' + button.dataset.restPaymentToggle)?.classList.toggle('open')));
        qa('[data-rest-add-payment]').forEach(button => button.addEventListener('click', () => {
            const container = button.closest('form').querySelector('[data-rest-payment-lines]');
            const index = container.children.length;
            const row = container.children[0].cloneNode(true);
            qa('select,input', row).forEach(element => {
                element.name = element.name.replace(/\[\d+\]/, '[' + index + ']');
                if (element.tagName === 'INPUT') element.value = '';
            });
            container.appendChild(row);
        }));
    }

    function setupRecipeLines() {
        qa('[data-rest-add-recipe-line]').forEach(button => button.addEventListener('click', () => {
            const container = q('[data-rest-recipe-lines]');
            const row = container.children[0].cloneNode(true);
            qa('input,select', row).forEach(element => element.value = '');
            container.appendChild(row);
        }));
    }

    function setupTableSearch() {
        qa('[data-rest-table-search]').forEach(input => input.addEventListener('input', () => {
            const table = q(input.dataset.restTableSearch);
            const term = input.value.toLowerCase();
            qa('tbody tr', table).forEach(row => row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none');
        }));
    }

    function setupAutoRefresh() {
        qa('[data-auto-refresh]').forEach(element => {
            const seconds = Number(element.dataset.autoRefresh);
            if (seconds > 0) {
                window.setTimeout(() => {
                    if (!document.hidden && !document.querySelector('form:focus-within')) window.location.reload();
                }, seconds * 1000);
            }
        });
    }

    function setupModifierAttachForm() {
        const form = q('#rest-attach-modifier-form');
        const item = q('#rest-modifier-menu-item');
        if (!form || !item) return;
        form.addEventListener('submit', event => {
            if (!item.value) {
                event.preventDefault();
                window.alert('Select a menu item.');
                return;
            }
            form.action = form.dataset.routeTemplate.replace('__ITEM__', item.value);
        });
    }

    function setupScreenRole() {
        const role = q('#rest-screen-role');
        const station = q('#rest-screen-station');
        if (!role || !station) return;
        const update = () => {
            station.disabled = role.value !== 'kitchen';
            if (station.disabled) station.value = '';
        };
        role.addEventListener('change', update);
        update();
    }

    setupWaiterOrder();
    setupPayments();
    setupRecipeLines();
    setupTableSearch();
    setupAutoRefresh();
    setupModifierAttachForm();
    setupScreenRole();
})();
