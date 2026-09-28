(() => {
  'use strict';

  const norm = value => String(value ?? '').toLowerCase().trim();
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
  const moneyText = value => String(value ?? '0');
  const markBound = (el, key) => {
    if (!el) return false;
    const name = `reoBound${key}`;
    if (el.dataset[name] === '1') return false;
    el.dataset[name] = '1';
    return true;
  };

  const saveBlob = (content, type, filename) => {
    const blob = new Blob([content], { type });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
  };

  const safeName = value => String(value || 'report').replace(/[^A-Za-z0-9_-]+/g, '_');
  const visibleCells = row => Array.from(row.cells).filter(cell => !cell.hidden && getComputedStyle(cell).display !== 'none' && !cell.hasAttribute('data-reo-no-export'));
  const csvCell = value => '"' + String(value || '').replace(/"/g, '""').trim() + '"';

  // ----------------------------- Shared modals -----------------------------
  const shareModal = document.querySelector('[data-reo-share-modal]');
  const shareForm = shareModal?.querySelector('[data-reo-share-form]');
  const shareTitle = shareModal?.querySelector('[data-reo-share-title]');
  const shareChannel = shareModal?.querySelector('[data-reo-share-channel]');
  const shareRecipient = shareModal?.querySelector('[data-reo-share-recipient]');
  const shareRecipientLabel = shareModal?.querySelector('[data-reo-recipient-label]');
  const shareRecipientHelp = shareModal?.querySelector('[data-reo-recipient-help]');
  const shareMessage = shareModal?.querySelector('[data-reo-share-message]');
  const shareStatus = shareModal?.querySelector('[data-reo-share-status]');
  const shareSubmit = shareModal?.querySelector('[data-reo-share-submit]');
  let currentShare = null;

  const closeShare = () => {
    if (!shareModal) return;
    shareModal.hidden = true;
    currentShare = null;
  };

  const openShare = (channel, url, title, payload = {}) => {
    if (!shareModal || !shareForm || !url) return;
    currentShare = { channel, url, title, payload };
    shareModal.hidden = false;
    if (shareTitle) shareTitle.textContent = `${channel === 'whatsapp' ? 'WhatsApp' : channel.toUpperCase()} · ${title || 'Report'}`;
    if (shareChannel) shareChannel.value = channel;
    if (shareRecipient) {
      shareRecipient.value = '';
      shareRecipient.type = channel === 'email' ? 'email' : 'text';
      shareRecipient.required = channel !== 'whatsapp';
    }
    if (shareRecipientLabel) shareRecipientLabel.textContent = channel === 'email' ? 'Email Address' : 'Mobile / Phone Number';
    if (shareRecipientHelp) {
      shareRecipientHelp.textContent = channel === 'whatsapp'
        ? 'Phone number is optional. Leave blank to choose the WhatsApp contact after opening WhatsApp.'
        : channel === 'sms'
          ? 'A downloadable report link will be included in the SMS.'
          : 'A downloadable report link will be included in the email.';
    }
    if (shareMessage) shareMessage.value = title || 'Report';
    if (shareStatus) shareStatus.textContent = '';
    if (shareSubmit) shareSubmit.disabled = false;
    setTimeout(() => shareRecipient?.focus(), 50);
  };

  shareModal?.querySelectorAll('[data-reo-modal-close]').forEach(el => el.addEventListener('click', closeShare));

  shareForm?.addEventListener('submit', async event => {
    event.preventDefault();
    if (!currentShare) return;
    if (shareStatus) shareStatus.textContent = 'Preparing downloadable link...';
    if (shareSubmit) shareSubmit.disabled = true;

    const body = new FormData();
    body.append('channel', currentShare.channel);
    body.append('recipient', shareRecipient?.value || '');
    body.append('message', shareMessage?.value || '');
    Object.entries(currentShare.payload || {}).forEach(([key, value]) => {
      if (value !== null && value !== undefined) body.append(key, String(value));
    });

    try {
      const response = await fetch(currentShare.url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;
        throw new Error(firstError || data?.message || `Unable to share report (HTTP ${response.status}).`);
      }
      if (data.url) {
        window.open(data.url, '_blank', 'noopener');
        closeShare();
        return;
      }
      if (shareStatus) {
        shareStatus.innerHTML = '';
        const text = document.createElement('span');
        text.textContent = data.message || 'Completed successfully.';
        shareStatus.appendChild(text);
        if (data.download_url) {
          shareStatus.appendChild(document.createTextNode(' '));
          const link = document.createElement('a');
          link.href = data.download_url;
          link.target = '_blank';
          link.rel = 'noopener';
          link.textContent = 'Open downloadable link';
          shareStatus.appendChild(link);
        }
      }
    } catch (error) {
      if (shareStatus) shareStatus.textContent = error.message || 'Unable to share report.';
    } finally {
      if (shareSubmit) shareSubmit.disabled = false;
    }
  });

  const auditModal = document.querySelector('[data-reo-audit-modal]');
  const auditContent = auditModal?.querySelector('[data-reo-audit-content]');
  const closeAudit = () => { if (auditModal) auditModal.hidden = true; };
  auditModal?.querySelectorAll('[data-reo-audit-close]').forEach(el => el.addEventListener('click', closeAudit));

  // ----------------------------- Dynamic initializers -----------------------------
  const initCatalog = root => {
    root.querySelectorAll('[data-reo-catalog-multiselect]').forEach(catalogMulti => {
      if (!markBound(catalogMulti, 'Catalog')) return;

      const toggle = catalogMulti.querySelector('[data-reo-catalog-toggle]');
      const menu = catalogMulti.querySelector('[data-reo-catalog-menu]');
      const search = catalogMulti.querySelector('[data-reo-catalog-search]');
      const summary = catalogMulti.querySelector('[data-reo-catalog-summary]');
      const count = catalogMulti.querySelector('[data-reo-catalog-count]');
      const selectAll = catalogMulti.querySelector('[data-reo-catalog-select-all]');
      const clear = catalogMulti.querySelector('[data-reo-catalog-clear]');
      const done = catalogMulti.querySelector('[data-reo-catalog-done]');
      const options = () => Array.from(catalogMulti.querySelectorAll('[data-reo-catalog-option]'));

      const setOpen = open => {
        catalogMulti.classList.toggle('is-open', open);
        if (menu) menu.hidden = !open;
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) setTimeout(() => search?.focus(), 20);
      };

      const updateSummary = () => {
        const selected = options().filter(option => option.checked);
        if (summary) {
          if (selected.length === 0) summary.textContent = 'Select Product Categories & Sub Categories';
          else if (selected.length === 1) summary.textContent = selected[0].dataset.label || '1 item selected';
          else summary.textContent = `${selected.length} items selected`;
        }
        if (count) count.textContent = `${selected.length} selected`;
      };

      const filterOptions = () => {
        const q = norm(search?.value);
        catalogMulti.querySelectorAll('[data-reo-catalog-group]').forEach(group => {
          const rows = Array.from(group.querySelectorAll('[data-reo-catalog-option-row]'));
          if (!q) {
            rows.forEach(row => { row.hidden = false; });
            group.hidden = false;
            return;
          }
          const groupMatches = norm(group.dataset.search).includes(q);
          let any = false;
          rows.forEach(row => {
            const matches = groupMatches || norm(row.dataset.search).includes(q);
            row.hidden = !matches;
            if (matches) any = true;
          });
          group.hidden = !any;
        });
      };

      toggle?.addEventListener('click', event => {
        event.preventDefault();
        setOpen(menu?.hidden !== false);
      });
      done?.addEventListener('click', () => setOpen(false));
      search?.addEventListener('input', filterOptions);
      options().forEach(option => option.addEventListener('change', updateSummary));

      selectAll?.addEventListener('click', () => {
        options().forEach(option => {
          const row = option.closest('[data-reo-catalog-option-row]');
          const group = option.closest('[data-reo-catalog-group]');
          if (!row?.hidden && !group?.hidden && !option.disabled) option.checked = true;
        });
        updateSummary();
      });

      clear?.addEventListener('click', () => {
        options().forEach(option => { option.checked = false; });
        updateSummary();
      });

      const form = catalogMulti.closest('form');
      form?.addEventListener('reset', () => setTimeout(() => {
        if (search) search.value = '';
        filterOptions();
        updateSummary();
      }, 0));

      updateSummary();
    });
  };

  const initSourceSearch = root => {
    root.querySelectorAll('#reo-source-search').forEach(sourceSearch => {
      if (!markBound(sourceSearch, 'SourceSearch')) return;
      sourceSearch.addEventListener('input', () => {
        const q = norm(sourceSearch.value);
        const pane = sourceSearch.closest('[data-reo-tab-pane]') || root;
        pane.querySelectorAll('[data-source-row]').forEach(row => {
          row.hidden = q !== '' && !norm(row.dataset.search).includes(q);
        });
      });
    });
  };

  const initConfirmForms = root => {
    root.querySelectorAll('form[data-confirm]').forEach(form => {
      if (!markBound(form, 'Confirm')) return;
      form.addEventListener('submit', event => {
        if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault();
      });
    });
  };

  const initDirectShare = root => {
    root.querySelectorAll('[data-reo-direct-share]').forEach(button => {
      if (!markBound(button, 'DirectShare')) return;
      button.addEventListener('click', () => openShare(
        button.dataset.channel,
        button.dataset.shareUrl,
        button.dataset.shareTitle || 'Report',
        {}
      ));
    });
  };

  const initAuditButtons = root => {
    root.querySelectorAll('[data-reo-audit-button]').forEach(button => {
      if (!markBound(button, 'Audit')) return;
      button.addEventListener('click', () => {
        const template = document.getElementById(button.dataset.auditTarget || '');
        if (!template || !auditModal || !auditContent) return;
        auditContent.innerHTML = template.innerHTML;
        auditModal.hidden = false;
      });
    });
  };

  const initReportToolbars = root => {
    root.querySelectorAll('[data-reo-toolbar]').forEach(toolbar => {
      if (!markBound(toolbar, 'Toolbar')) return;
      const pane = toolbar.closest('[data-reo-tab-pane]') || document;
      const table = pane.querySelector(`#${CSS.escape(toolbar.dataset.tableId || '')}`) || document.getElementById(toolbar.dataset.tableId || '');
      if (!table) return;

      const search = toolbar.querySelector('[data-reo-report-search]');
      const pageSize = toolbar.querySelector('[data-reo-page-size]');
      const dateFrom = toolbar.querySelector('[data-reo-date-from]');
      const dateTo = toolbar.querySelector('[data-reo-date-to]');
      const dateRangeControl = toolbar.querySelector('[data-reo-date-range-control]');
      const dateRangeDisplay = toolbar.querySelector('[data-reo-date-range-display]');
      const dateRangePopover = toolbar.querySelector('[data-reo-date-range-popover]');
      const dateTypingModal = toolbar.querySelector('[data-reo-date-typing-modal]');
      const dateTypingError = toolbar.querySelector('[data-reo-date-typing-error]');
      const dateTypingApply = toolbar.querySelector('[data-reo-date-typing-apply]');
      const dateDigits = Array.from(toolbar.querySelectorAll('[data-reo-date-digit]'));
      const fyStartMonth = Math.max(1, Math.min(12, parseInt(toolbar.dataset.fyStartMonth || '1', 10) || 1));
      const count = toolbar.querySelector('[data-reo-count]');
      const pageLabel = toolbar.querySelector('[data-reo-page-label]');
      const prev = toolbar.querySelector('[data-reo-prev]');
      const next = toolbar.querySelector('[data-reo-next]');
      let page = 1;

      const rows = () => Array.from(table.tBodies).flatMap(body => Array.from(body.rows));
      const dataRows = () => rows().filter(row => !row.querySelector('[colspan]'));
      const filteredRows = () => {
        const q = norm(search?.value);
        return dataRows().filter(row => q === '' || norm(row.innerText).includes(q));
      };

      const render = () => {
        const matches = filteredRows();
        const size = Math.max(1, parseInt(pageSize?.value || '25', 10));
        const pages = Math.max(1, Math.ceil(matches.length / size));
        if (page > pages) page = pages;
        const start = (page - 1) * size;
        const visible = new Set(matches.slice(start, start + size));
        dataRows().forEach(row => { row.hidden = !visible.has(row); });
        rows().filter(row => row.querySelector('[colspan]')).forEach(row => { row.hidden = matches.length > 0; });
        if (count) count.textContent = `${matches.length} transactions`;
        if (pageLabel) pageLabel.textContent = `${page} / ${pages}`;
        if (prev) prev.disabled = page <= 1;
        if (next) next.disabled = page >= pages;
      };

      search?.addEventListener('input', () => { page = 1; render(); });
      pageSize?.addEventListener('change', () => { page = 1; render(); });
      prev?.addEventListener('click', () => { if (page > 1) { page--; render(); } });
      next?.addEventListener('click', () => {
        const size = Math.max(1, parseInt(pageSize?.value || '25', 10));
        const pages = Math.max(1, Math.ceil(filteredRows().length / size));
        if (page < pages) { page++; render(); }
      });

      const pad2 = value => String(value).padStart(2, '0');
      const localDate = (year, monthIndex, day) => new Date(year, monthIndex, day, 12, 0, 0, 0);
      const toIso = value => `${value.getFullYear()}-${pad2(value.getMonth() + 1)}-${pad2(value.getDate())}`;
      const isoToParts = iso => {
        const match = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return null;
        return { year: match[1], month: match[2], day: match[3] };
      };
      const toDisplay = iso => {
        const parts = isoToParts(iso);
        return parts ? `${parts.day}/${parts.month}/${parts.year}` : '';
      };
      const updateDateRangeDisplay = () => {
        if (!dateRangeDisplay) return;
        const from = toDisplay(dateFrom?.value);
        const to = toDisplay(dateTo?.value);
        dateRangeDisplay.value = from && to ? `${from} ~ ${to}` : '';
      };

      const presetDates = preset => {
        const today = new Date();
        const year = today.getFullYear();
        if (preset === 'this_year') return [localDate(year, 0, 1), localDate(year, 11, 31)];
        if (preset === 'last_year') return [localDate(year - 1, 0, 1), localDate(year - 1, 11, 31)];
        if (preset === 'this_fy' || preset === 'last_fy') {
          let startYear = today.getMonth() + 1 >= fyStartMonth ? year : year - 1;
          if (preset === 'last_fy') startYear -= 1;
          return [localDate(startYear, fyStartMonth - 1, 1), localDate(startYear + 1, fyStartMonth - 1, 0)];
        }
        return null;
      };

      const currentPreset = () => {
        if (!dateFrom?.value || !dateTo?.value) return 'custom';
        for (const preset of ['this_year', 'last_year', 'this_fy', 'last_fy']) {
          const range = presetDates(preset);
          if (range && dateFrom.value === toIso(range[0]) && dateTo.value === toIso(range[1])) return preset;
        }
        return 'custom';
      };

      const markActivePreset = () => {
        const active = currentPreset();
        toolbar.querySelectorAll('[data-reo-range-preset]').forEach(button => {
          button.classList.toggle('is-active', button.dataset.reoRangePreset === active);
        });
      };

      const setDates = (from, to) => {
        if (dateFrom) dateFrom.value = toIso(from);
        if (dateTo) dateTo.value = toIso(to);
        updateDateRangeDisplay();
        markActivePreset();
      };

      const reloadDateRange = () => {
        const filterUrl = toolbar.dataset.filterUrl;
        if (!filterUrl) return;
        const url = new URL(filterUrl, window.location.origin);
        if (dateFrom?.value) url.searchParams.set('date_from', dateFrom.value);
        if (dateTo?.value) url.searchParams.set('date_to', dateTo.value);
        if (window.ReportsOtherTabs?.navigate) {
          window.ReportsOtherTabs.navigate(url.toString(), { force: true, pushState: true });
        } else {
          window.location.assign(url.toString());
        }
      };

      const closeDatePopover = () => {
        if (dateRangePopover) dateRangePopover.hidden = true;
        dateRangeControl?.classList.remove('is-open');
      };
      const openDatePopover = () => {
        if (!dateRangePopover) return;
        markActivePreset();
        dateRangePopover.hidden = false;
        dateRangeControl?.classList.add('is-open');
      };
      const toggleDatePopover = () => {
        if (!dateRangePopover) return;
        if (dateRangePopover.hidden) openDatePopover(); else closeDatePopover();
      };

      toolbar.querySelectorAll('[data-reo-date-range-toggle]').forEach(toggle => {
        toggle.addEventListener('click', event => {
          event.preventDefault();
          event.stopPropagation();
          toggleDatePopover();
        });
      });

      const digitValue = (side, part) => dateDigits
        .filter(input => input.dataset.side === side && input.dataset.part === part)
        .sort((a, b) => Number(a.dataset.index) - Number(b.dataset.index))
        .map(input => input.value.replace(/\D/g, '').slice(0, 1))
        .join('');

      const fillSideDigits = (side, iso) => {
        const parts = isoToParts(iso);
        if (!parts) return;
        const values = { day: parts.day, month: parts.month, year: parts.year };
        dateDigits.forEach(input => {
          if (input.dataset.side !== side) return;
          const value = values[input.dataset.part] || '';
          input.value = value[Number(input.dataset.index)] || '';
        });
      };

      const validIsoFromDigits = side => {
        const day = digitValue(side, 'day');
        const month = digitValue(side, 'month');
        const year = digitValue(side, 'year');
        if (day.length !== 2 || month.length !== 2 || year.length !== 4) return '';
        const d = Number(day);
        const m = Number(month);
        const y = Number(year);
        const parsed = localDate(y, m - 1, d);
        if (parsed.getFullYear() !== y || parsed.getMonth() !== m - 1 || parsed.getDate() !== d) return '';
        return toIso(parsed);
      };

      const closeDateTyping = () => {
        if (dateTypingModal) dateTypingModal.hidden = true;
        if (dateTypingError) {
          dateTypingError.hidden = true;
          dateTypingError.textContent = '';
        }
      };
      const openDateTyping = () => {
        closeDatePopover();
        if (!dateTypingModal) return;
        fillSideDigits('from', dateFrom?.value || toIso(new Date()));
        fillSideDigits('to', dateTo?.value || toIso(new Date()));
        if (dateTypingError) {
          dateTypingError.hidden = true;
          dateTypingError.textContent = '';
        }
        dateTypingModal.hidden = false;
        const first = dateDigits.find(input => input.dataset.side === 'from');
        window.setTimeout(() => { first?.focus(); first?.select(); }, 0);
      };

      dateDigits.forEach((input, index) => {
        input.addEventListener('input', () => {
          input.value = input.value.replace(/\D/g, '').slice(0, 1);
          if (input.value && dateDigits[index + 1]) {
            dateDigits[index + 1].focus();
            dateDigits[index + 1].select();
          }
        });
        input.addEventListener('keydown', event => {
          if (event.key === 'Backspace' && !input.value && dateDigits[index - 1]) {
            event.preventDefault();
            dateDigits[index - 1].focus();
            dateDigits[index - 1].select();
          }
          if (event.key === 'Enter') {
            event.preventDefault();
            dateTypingApply?.click();
          }
        });
        input.addEventListener('focus', () => input.select());
      });

      toolbar.querySelectorAll('[data-reo-date-typing-close]').forEach(button => {
        button.addEventListener('click', closeDateTyping);
      });

      dateTypingApply?.addEventListener('click', () => {
        const fromIso = validIsoFromDigits('from');
        const toIsoValue = validIsoFromDigits('to');
        if (!fromIso || !toIsoValue) {
          if (dateTypingError) {
            dateTypingError.textContent = 'Please enter a valid From and To date.';
            dateTypingError.hidden = false;
          }
          return;
        }
        if (fromIso > toIsoValue) {
          if (dateTypingError) {
            dateTypingError.textContent = 'From date cannot be after To date.';
            dateTypingError.hidden = false;
          }
          return;
        }
        if (dateFrom) dateFrom.value = fromIso;
        if (dateTo) dateTo.value = toIsoValue;
        updateDateRangeDisplay();
        markActivePreset();
        closeDateTyping();
        reloadDateRange();
      });

      toolbar.querySelectorAll('[data-reo-range-preset]').forEach(button => {
        button.addEventListener('click', () => {
          const preset = button.dataset.reoRangePreset;
          if (preset === 'custom') {
            openDateTyping();
            return;
          }
          const range = presetDates(preset);
          if (!range) return;
          setDates(range[0], range[1]);
          closeDatePopover();
          reloadDateRange();
        });
      });

      updateDateRangeDisplay();
      markActivePreset();

      toolbar.querySelectorAll('[data-reo-action]').forEach(button => {
        button.addEventListener('click', () => {
          const action = button.dataset.reoAction;
          const title = toolbar.dataset.reportTitle || 'Report';
          const exportRows = filteredRows();
          const headerRows = Array.from(table.tHead?.rows || []);

          if (action === 'print' || action === 'pdf') {
            const matches = new Set(exportRows);
            dataRows().forEach(row => { row.hidden = !matches.has(row); });
            window.print();
            setTimeout(render, 150);
            return;
          }

          if (action === 'csv') {
            const lines = [...headerRows, ...exportRows].map(row => visibleCells(row).map(cell => csvCell(cell.innerText)).join(','));
            saveBlob('\ufeff' + lines.join('\r\n'), 'text/csv;charset=utf-8', safeName(title) + '.csv');
            return;
          }

          if (action === 'excel') {
            const clone = table.cloneNode(false);
            if (table.tHead) clone.appendChild(table.tHead.cloneNode(true));
            const body = document.createElement('tbody');
            exportRows.forEach(row => body.appendChild(row.cloneNode(true)));
            clone.appendChild(body);
            clone.querySelectorAll('[data-reo-no-export]').forEach(el => el.remove());
            const html = `<html><head><meta charset="utf-8"></head><body>${clone.outerHTML}</body></html>`;
            saveBlob(html, 'application/vnd.ms-excel;charset=utf-8', safeName(title) + '.xls');
            return;
          }

          if (action === 'columns') {
            const headers = Array.from(table.tHead?.rows?.[0]?.cells || []);
            if (!headers.length) return;
            document.querySelectorAll('.reo-column-panel').forEach(el => el.remove());
            const panel = document.createElement('div');
            panel.className = 'reo-column-panel';
            headers.forEach((header, index) => {
              const label = document.createElement('label');
              const check = document.createElement('input');
              check.type = 'checkbox';
              check.checked = !header.hidden;
              check.addEventListener('change', () => {
                Array.from(table.rows).forEach(row => { if (row.cells[index]) row.cells[index].hidden = !check.checked; });
              });
              label.append(check, document.createTextNode(' ' + (header.innerText.trim() || `Column ${index + 1}`)));
              panel.appendChild(label);
            });
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'reo-btn reo-btn-primary reo-btn-sm';
            close.textContent = 'Done';
            close.addEventListener('click', () => panel.remove());
            panel.appendChild(close);
            document.body.appendChild(panel);
            return;
          }

          if (['email', 'sms', 'whatsapp'].includes(action) && toolbar.dataset.shareUrl) {
            openShare(action, toolbar.dataset.shareUrl, title, {
              date_from: dateFrom?.value || '',
              date_to: dateTo?.value || '',
              search: search?.value || ''
            });
          }
        });
      });

      render();
    });
  };

  const previewCache = new Map();
  const initReceiptPreview = root => {
    root.querySelectorAll('[data-reo-receipt-entry]').forEach(receiptForm => {
      if (!markBound(receiptForm, 'ReceiptPreview')) return;

      const source = receiptForm.querySelector('[data-reo-source]');
      const date = receiptForm.querySelector('[data-reo-receipt-date]');
      const membership = receiptForm.querySelector('[data-reo-membership]');
      const membershipHelp = receiptForm.querySelector('[data-reo-membership-help]');
      const receiptNo = receiptForm.querySelector('[data-reo-receipt-no]');
      const amountWords = receiptForm.querySelector('[data-reo-amount-words]');
      const detailsBody = receiptForm.querySelector('[data-reo-source-details]');
      const total = receiptForm.querySelector('[data-reo-total]');
      const cheques = receiptForm.querySelector('[data-reo-cheques]');
      const status = receiptForm.querySelector('[data-reo-preview-status]');
      const save = receiptForm.querySelector('[data-reo-save-receipt]');
      const printDesign = receiptForm.querySelector('[data-reo-print-design]');
      let requestToken = 0;
      let activeController = null;

      const emptyCell = message => {
        if (!detailsBody) return;
        detailsBody.innerHTML = '';
        for (let i = 0; i < 5; i += 1) {
          const tr = document.createElement('tr');
          tr.className = 'reo-receipt-placeholder-row';
          const name = document.createElement('td');
          const amount = document.createElement('td');
          amount.className = 'reo-text-right';
          name.textContent = i === 0 ? message : '\u00a0';
          amount.textContent = i === 0 ? '0' : '\u00a0';
          tr.append(name, amount);
          detailsBody.appendChild(tr);
        }
      };

      const renderDetails = rows => {
        if (!detailsBody) return;
        detailsBody.innerHTML = '';
        if (!rows?.length) {
          emptyCell('No mapped Source details found.');
          return;
        }
        rows.forEach(row => {
          const tr = document.createElement('tr');
          const name = document.createElement('td');
          name.textContent = row.source_detail || '—';
          const amount = document.createElement('td');
          amount.className = 'reo-text-right';
          amount.textContent = moneyText(row.amount_formatted);
          tr.append(name, amount);
          detailsBody.appendChild(tr);
        });
      };

      const renderCheques = rows => {
        if (!cheques) return;
        cheques.innerHTML = '';
        if (!rows?.length) {
          const empty = document.createElement('div');
          empty.className = 'reo-cheque-row reo-cheque-placeholder';
          ['Cheque Number', 'Bank', 'Cheque Date'].forEach((value, index) => {
            const span = document.createElement('span');
            span.textContent = index === 0 ? 'No cheque payments found' : value;
            empty.appendChild(span);
          });
          cheques.appendChild(empty);
          return;
        }
        rows.forEach(row => {
          const div = document.createElement('div');
          div.className = 'reo-cheque-row';
          [row.cheque_number || '—', row.bank_name || '—', row.cheque_date || '—'].forEach(value => {
            const span = document.createElement('span');
            span.textContent = value;
            div.appendChild(span);
          });
          cheques.appendChild(div);
        });
      };

      const applyPreview = data => {
        if (receiptNo) receiptNo.textContent = data.receipt_no || 'Not configured';
        if (amountWords) amountWords.textContent = data.amount_in_words || '—';
        if (total) total.textContent = data.total_formatted || '0';
        renderDetails(data.details || []);
        renderCheques(data.cheques || []);

        if (membership) {
          if (data.membership_is_manual) {
            membership.readOnly = false;
            membership.classList.remove('reo-readonly');
            if (!membership.value || membership.dataset.autoValue === membership.value) membership.value = '';
            membership.dataset.autoValue = '';
            if (membershipHelp) membershipHelp.textContent = 'Membership No is not available automatically. Manual entry is required.';
          } else {
            membership.value = data.membership_no || '';
            membership.dataset.autoValue = membership.value;
            membership.readOnly = true;
            membership.classList.add('reo-readonly');
            if (membershipHelp) membershipHelp.textContent = 'Membership No loaded automatically and is locked.';
          }
        }

        const canSave = Boolean(data.available && data.numbering_configured && !data.existing && Number(data.matched_transactions || 0) > 0);
        if (save) save.disabled = !canSave;

        if (status) {
          status.innerHTML = '';
          if (data.existing) {
            status.className = 'reo-live-status reo-live-warning';
            status.appendChild(document.createTextNode(`A Receipt already exists for this Source/date: ${data.existing.receipt_no}. `));
            const link = document.createElement('a');
            link.href = data.existing.view_url;
            link.textContent = 'View Receipt';
            status.appendChild(link);
          } else if (!data.available) {
            status.className = 'reo-live-status reo-live-error';
            status.textContent = data.message || 'Automatic Source data is not available.';
          } else if (!data.numbering_configured) {
            status.className = 'reo-live-status reo-live-warning';
            status.textContent = 'Configure Prefix & Starting Nos before saving.';
          } else if (Number(data.matched_transactions || 0) < 1) {
            status.className = 'reo-live-status reo-live-warning';
            status.textContent = 'No source transactions found for this Source and date.';
          } else {
            status.className = 'reo-live-status reo-live-success';
            status.textContent = `${data.matched_transactions} matching transaction(s) loaded. Receipt is ready to save.`;
          }
        }
      };

      const loadPreview = async () => {
        if (!source?.value || !date?.value) return;
        const token = ++requestToken;
        const key = `${source.value}|${date.value}`;

        if (previewCache.has(key)) {
          applyPreview(previewCache.get(key));
          return;
        }

        activeController?.abort();
        activeController = new AbortController();
        if (status) {
          status.className = 'reo-live-status';
          status.textContent = 'Loading Source details...';
        }
        if (save) save.disabled = true;

        try {
          const url = new URL(receiptForm.dataset.previewUrl, window.location.origin);
          url.searchParams.set('source_id', source.value);
          url.searchParams.set('receipt_date', date.value);
          const response = await fetch(url.toString(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeController.signal,
            credentials: 'same-origin'
          });
          const data = await response.json().catch(() => ({}));
          if (token !== requestToken) return;
          if (!response.ok) throw new Error(data.message || `Unable to load Receipt data (HTTP ${response.status}).`);
          previewCache.set(key, data);
          applyPreview(data);
        } catch (error) {
          if (error?.name === 'AbortError' || token !== requestToken) return;
          emptyCell('Unable to load Source details.');
          if (status) {
            status.className = 'reo-live-status reo-live-error';
            status.textContent = error.message || 'Unable to load Receipt data.';
          }
          if (save) save.disabled = true;
        }
      };

      printDesign?.addEventListener('click', () => {
        document.body.classList.add('reo-print-receipt-design');
        const cleanup = () => document.body.classList.remove('reo-print-receipt-design');
        window.addEventListener('afterprint', cleanup, { once: true });
        window.print();
        setTimeout(cleanup, 1000);
      });

      source?.addEventListener('change', loadPreview);
      date?.addEventListener('change', loadPreview);
      if (source?.value && date?.value) loadPreview();
    });
  };

  const initDynamic = (root = document) => {
    initCatalog(root);
    initSourceSearch(root);
    initConfirmForms(root);
    initDirectShare(root);
    initAuditButtons(root);
    initReportToolbars(root);
    initReceiptPreview(root);
  };

  // ----------------------------- Instant tab engine -----------------------------
  const initInstantTabs = () => {
    const nav = document.querySelector('[data-reo-tabs]');
    const host = document.querySelector('[data-reo-tab-content]');
    if (!nav || !host || !markBound(nav, 'InstantTabs')) return;

    const links = Array.from(nav.querySelectorAll('[data-reo-tab]'));
    const panes = new Map();
    const requests = new Map();
    let activeTab = host.dataset.activeTab || 'receipt';

    const initialPane = document.createElement('div');
    initialPane.className = 'reo-tab-pane is-active';
    initialPane.dataset.reoTabPane = activeTab;
    while (host.firstChild) initialPane.appendChild(host.firstChild);
    host.appendChild(initialPane);
    panes.set(activeTab, initialPane);
    initDynamic(initialPane);

    const getTabFromUrl = value => {
      try {
        return new URL(value, window.location.origin).searchParams.get('tab') || 'receipt';
      } catch (_) {
        return 'receipt';
      }
    };

    const setActiveState = tab => {
      links.forEach(link => {
        const active = link.dataset.reoTab === tab;
        link.classList.toggle('active', active);
        link.setAttribute('aria-current', active ? 'page' : 'false');
      });
      panes.forEach((pane, key) => {
        const active = key === tab;
        pane.hidden = !active;
        pane.classList.toggle('is-active', active);
      });
      host.dataset.activeTab = tab;
      activeTab = tab;
    };

    const fragmentUrl = value => {
      const url = new URL(value, window.location.origin);
      url.searchParams.set('fragment', '1');
      return url;
    };

    const fetchPane = async (tab, value, force = false) => {
      if (!force && panes.has(tab)) return panes.get(tab);
      if (!force && requests.has(tab)) return requests.get(tab);

      const promise = (async () => {
        const response = await fetch(fragmentUrl(value).toString(), {
          headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });
        if (!response.ok) throw new Error(`Unable to load tab (HTTP ${response.status}).`);
        const html = await response.text();

        let pane = panes.get(tab);
        if (!pane) {
          pane = document.createElement('div');
          pane.className = 'reo-tab-pane';
          pane.dataset.reoTabPane = tab;
          pane.hidden = true;
          host.appendChild(pane);
          panes.set(tab, pane);
        }
        pane.innerHTML = html;
        initDynamic(pane);
        return pane;
      })().finally(() => requests.delete(tab));

      requests.set(tab, promise);
      return promise;
    };

    const navigate = async (value, options = {}) => {
      const { force = false, pushState = true, replaceState = false, prefetch = false } = options;
      const url = new URL(value, window.location.origin);
      const tab = getTabFromUrl(url.toString());

      if (!prefetch && panes.has(tab) && !force) {
        setActiveState(tab);
        if (pushState) history.pushState({ reoTab: tab }, '', url.toString());
        else if (replaceState) history.replaceState({ reoTab: tab }, '', url.toString());
        return panes.get(tab);
      }

      let spinnerTimer = null;
      if (!prefetch) {
        spinnerTimer = setTimeout(() => host.classList.add('reo-tab-loading'), 90);
      }

      try {
        const pane = await fetchPane(tab, url.toString(), force);
        if (!prefetch) {
          setActiveState(tab);
          if (pushState) history.pushState({ reoTab: tab }, '', url.toString());
          else if (replaceState) history.replaceState({ reoTab: tab }, '', url.toString());
        }
        return pane;
      } catch (error) {
        if (!prefetch) window.location.assign(url.toString());
        return null;
      } finally {
        if (spinnerTimer) clearTimeout(spinnerTimer);
        host.classList.remove('reo-tab-loading');
      }
    };

    window.ReportsOtherTabs = { navigate, fetchPane };

    nav.addEventListener('click', event => {
      const link = event.target.closest('[data-reo-tab]');
      if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
      event.preventDefault();
      navigate(link.href, { pushState: true });
    });

    // Inner tab links (warnings/actions) use the same fast path.
    document.addEventListener('click', event => {
      const link = event.target.closest('a[href]');
      if (!link || link.closest('[data-reo-tabs]') || event.defaultPrevented) return;
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button > 0) return;
      try {
        const url = new URL(link.href, window.location.origin);
        if (url.origin !== window.location.origin) return;
        if (!url.pathname.endsWith('/reports-other/cash-receipt')) return;
        if (!url.searchParams.has('tab')) return;
        event.preventDefault();
        navigate(url.toString(), { pushState: true });
      } catch (_) {}
    });

    window.addEventListener('popstate', () => {
      const tab = getTabFromUrl(window.location.href);
      if (panes.has(tab)) {
        setActiveState(tab);
      } else {
        navigate(window.location.href, { pushState: false, replaceState: false });
      }
    });

    history.replaceState({ reoTab: activeTab }, '', window.location.href);

    // Prefetch every other tab quietly after first paint. By the time the user
    // clicks a tab, its HTML is normally already in memory and switching is instant.
    const prefetch = () => {
      let delay = 0;
      links.forEach(link => {
        const tab = link.dataset.reoTab;
        if (!tab || tab === activeTab || panes.has(tab)) return;
        setTimeout(() => navigate(link.href, { prefetch: true, pushState: false }), delay);
        delay += 120;
      });
    };
    // Start almost immediately after the first paint. Requests are staggered so
    // the active tab remains responsive while the remaining tabs warm in memory.
    setTimeout(prefetch, 25);
  };

  initDynamic(document);
  initInstantTabs();

  // Global close/open behaviors remain delegated so they also work for lazy panes.
  document.addEventListener('click', event => {
    document.querySelectorAll('[data-reo-catalog-multiselect].is-open').forEach(multi => {
      if (!multi.contains(event.target)) {
        multi.classList.remove('is-open');
        const menu = multi.querySelector('[data-reo-catalog-menu]');
        if (menu) menu.hidden = true;
        multi.querySelector('[data-reo-catalog-toggle]')?.setAttribute('aria-expanded', 'false');
      }
    });
    document.querySelectorAll('[data-reo-date-range-control]').forEach(control => {
      if (control.contains(event.target)) return;
      const popover = control.querySelector('[data-reo-date-range-popover]');
      if (popover) popover.hidden = true;
      control.classList.remove('is-open');
    });
  });

  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    closeShare();
    closeAudit();
    document.querySelectorAll('.reo-column-panel').forEach(el => el.remove());
    document.querySelectorAll('[data-reo-date-range-popover]').forEach(el => { el.hidden = true; });
    document.querySelectorAll('[data-reo-date-range-control]').forEach(el => el.classList.remove('is-open'));
    document.querySelectorAll('[data-reo-date-typing-modal]').forEach(el => { el.hidden = true; });
    document.querySelectorAll('[data-reo-catalog-multiselect].is-open').forEach(multi => {
      multi.classList.remove('is-open');
      const menu = multi.querySelector('[data-reo-catalog-menu]');
      if (menu) menu.hidden = true;
    });
  });
})();
