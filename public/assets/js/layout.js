document.addEventListener('DOMContentLoaded', () => {
  initFlashToasts();
  initTableManagers();
  initWizardForms();
  initAutoSlug();
  initDraftPersistence();
  initReviewSync();
  initCsvExport();
  initConfirmationDialogs();
});

const tableStateMap = new WeakMap();
const confirmState = {
  form: null,
  submitter: null,
  modal: null,
  title: null,
  message: null,
  icon: null,
  confirmButton: null,
  cancelButton: null,
};

function initFlashToasts() {
  const flashToasts = Array.isArray(window.__FLASH_TOASTS) ? window.__FLASH_TOASTS : [];
  flashToasts.forEach((item) => {
    if (!item || typeof item !== 'object') {
      return;
    }

    toast(
      item.title || toastTitleForVariant(item.variant),
      item.message || '',
      item.variant || 'info',
    );
  });
}

function toastTitleForVariant(variant) {
  switch ((variant || '').toLowerCase()) {
    case 'success':
      return 'Berhasil';
    case 'danger':
    case 'error':
      return 'Gagal';
    case 'warn':
    case 'warning':
      return 'Peringatan';
    default:
      return 'Info';
  }
}

function initConfirmationDialogs() {
  confirmState.modal = document.querySelector('[data-modal="confirm"]');
  confirmState.title = document.getElementById('confirmTitle');
  confirmState.message = document.getElementById('confirmMessage');
  confirmState.icon = document.getElementById('confirmIcon');
  confirmState.confirmButton = document.querySelector('[data-action="confirm-ok"]');
  confirmState.cancelButton = document.querySelector('[data-action="cancel-confirm"]');

  if (!confirmState.modal || !confirmState.title || !confirmState.message || !confirmState.icon || !confirmState.confirmButton || !confirmState.cancelButton) {
    return;
  }

  document.querySelectorAll('form[data-confirm-form]').forEach((form) => {
    if (form.dataset.confirmBound === '1') {
      return;
    }

    form.dataset.confirmBound = '1';
    form.addEventListener('submit', (event) => {
      if (form.dataset.confirmBypass === '1') {
        delete form.dataset.confirmBypass;
        return;
      }

      event.preventDefault();

      confirmState.form = form;
      confirmState.submitter = event.submitter || form.querySelector('[type="submit"]');

      const title = form.getAttribute('data-confirm-title') || 'Confirm action';
      const message =
        form.getAttribute('data-confirm-message') ||
        'Are you sure you want to continue?';
      const variant = (form.getAttribute('data-confirm-variant') || 'warn').toLowerCase();
      const icon = form.getAttribute('data-confirm-icon') || iconForVariant(variant);

      openConfirm({
        title,
        message,
        variant,
        icon,
      });
    });
  });

  confirmState.confirmButton.addEventListener('click', () => {
    const form = confirmState.form;
    if (!form) {
      closeConfirm();
      return;
    }

    const submitter = confirmState.submitter;
    form.dataset.confirmBypass = '1';

    closeConfirm();

    window.setTimeout(() => {
      if (form.dataset.confirmBypass === '1') {
        delete form.dataset.confirmBypass;
      }
    }, 0);

    window.requestAnimationFrame(() => {
      try {
        if (typeof form.requestSubmit === 'function') {
          if (submitter && submitter.form === form) {
            form.requestSubmit(submitter);
          } else {
            form.requestSubmit();
          }
        } else {
          form.submit();
        }
      } catch (error) {
        delete form.dataset.confirmBypass;
        form.submit();
      }
    });
  });

  confirmState.cancelButton.addEventListener('click', closeConfirm);

  confirmState.modal.addEventListener('click', (event) => {
    if (event.target === confirmState.modal) {
      closeConfirm();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !confirmState.modal.hidden) {
      closeConfirm();
    }
  });
}

function iconForVariant(variant) {
  switch ((variant || '').toLowerCase()) {
    case 'success':
      return 'fa-solid fa-circle-check';
    case 'danger':
    case 'error':
      return 'fa-solid fa-triangle-exclamation';
    case 'info':
      return 'fa-solid fa-circle-info';
    case 'warn':
    case 'warning':
    default:
      return 'fa-solid fa-triangle-exclamation';
  }
}

function openConfirm({ title, message, variant, icon }) {
  confirmState.title.textContent = title || 'Confirm action';
  confirmState.message.textContent = message || 'Are you sure you want to continue?';

  confirmState.icon.innerHTML = `<i class="icon ${escapeHtml(icon || iconForVariant(variant))}" aria-hidden="true"></i>`;
  confirmState.modal.dataset.variant = variant || 'warn';

  confirmState.modal.hidden = false;
  requestAnimationFrame(() => {
    confirmState.modal.classList.add('is-visible');
  });

  confirmState.confirmButton.className = `btn ${variant === 'danger' ? 'btn--danger' : variant === 'info' ? 'btn--primary' : 'btn--primary'}`;
  confirmState.confirmButton.textContent = variant === 'danger'
    ? 'Hapus'
    : variant === 'info'
      ? 'Pulihkan'
      : 'Simpan';
}

function closeConfirm() {
  if (!confirmState.modal) {
    return;
  }

  confirmState.modal.classList.remove('is-visible');
  window.setTimeout(() => {
    confirmState.modal.hidden = true;
  }, 180);

  confirmState.form = null;
  confirmState.submitter = null;
}

function toast(title, message, variant = 'info') {
  const stack = document.getElementById('toastStack');
  if (!stack) {
    return;
  }

  const el = document.createElement('div');
  const safeVariant = ['success', 'danger', 'warn', 'info'].includes(String(variant).toLowerCase())
    ? String(variant).toLowerCase()
    : 'info';

  el.className = `toast toast--${safeVariant}`;
  el.innerHTML = `
    <strong>${escapeHtml(title || toastTitleForVariant(safeVariant))}</strong>
    <span>${escapeHtml(message || '')}</span>
  `;

  stack.appendChild(el);
  requestAnimationFrame(() => {
    el.classList.add('is-visible');
  });

  window.setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(8px)';
    window.setTimeout(() => el.remove(), 220);
  }, 3000);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

function initTableManagers() {
  const tables = new Set();

  document.querySelectorAll('[data-table-search-target]').forEach((control) => {
    const table = resolveTableFromSelector(control.getAttribute('data-table-search-target'));
    if (table) {
      tables.add(table);
    }
  });

  document.querySelectorAll('[data-table-filter-target]').forEach((control) => {
    const table = resolveTableFromSelector(control.getAttribute('data-table-filter-target'));
    if (table) {
      tables.add(table);
    }
  });

  document.querySelectorAll('[data-table-pagination-target]').forEach((control) => {
    const table = resolveTableFromSelector(control.getAttribute('data-table-pagination-target'));
    if (table) {
      tables.add(table);
    }
  });

  tables.forEach((table) => {
    ensureTableState(table);
    renderTable(table);
  });
}

function ensureTableState(table) {
  if (tableStateMap.has(table)) {
    return tableStateMap.get(table);
  }

  const state = {
    table,
    pageSize: getTablePageSize(table),
    currentPage: 1,
    search: '',
    filter: '',
    rows: getDataRows(table),
    emptyRow: table.querySelector('[data-empty-row]'),
    searchControls: Array.from(document.querySelectorAll(`[data-table-search-target="${escapeAttrValue(getTableSelector(table))}"]`)),
    filterControls: Array.from(document.querySelectorAll(`[data-table-filter-target="${escapeAttrValue(getTableSelector(table))}"]`)),
    paginationControls: Array.from(document.querySelectorAll(`[data-table-pagination-target="${escapeAttrValue(getTableSelector(table))}"]`)),
  };

  bindTableControls(state);
  tableStateMap.set(table, state);
  return state;
}

function bindTableControls(state) {
  state.searchControls.forEach((input) => {
    if (input.dataset.boundSearch === '1') {
      return;
    }

    input.dataset.boundSearch = '1';
    input.addEventListener('input', () => {
      state.search = input.value.trim().toLowerCase();
      state.currentPage = 1;
      renderTable(state.table);
    });
  });

  state.filterControls.forEach((select) => {
    if (select.dataset.boundFilter === '1') {
      return;
    }

    select.dataset.boundFilter = '1';
    select.addEventListener('change', () => {
      state.filter = (select.value || '').trim().toLowerCase();
      state.currentPage = 1;
      renderTable(state.table);
    });
  });

  state.paginationControls.forEach((panel) => {
    if (panel.dataset.boundPagination === '1') {
      return;
    }

    panel.dataset.boundPagination = '1';

    panel.querySelectorAll('[data-page-action]').forEach((button) => {
      button.addEventListener('click', () => {
        const action = button.getAttribute('data-page-action');
        if (action === 'prev') {
          state.currentPage = Math.max(1, state.currentPage - 1);
        } else if (action === 'next') {
          state.currentPage += 1;
        }
        renderTable(state.table);
      });
    });
  });
}

function renderTable(table) {
  const state = tableStateMap.get(table) || ensureTableState(table);
  const matchedRows = getMatchedRows(state);
  const pageSize = Math.max(1, Number(state.pageSize) || 10);
  const total = matchedRows.length;
  const totalPages = Math.max(1, Math.ceil(total / pageSize));

  if (state.currentPage > totalPages) {
    state.currentPage = totalPages;
  }

  if (state.currentPage < 1) {
    state.currentPage = 1;
  }

  const startIndex = total === 0 ? 0 : (state.currentPage - 1) * pageSize;
  const endIndex = total === 0 ? 0 : Math.min(startIndex + pageSize, total);
  const visibleRows = matchedRows.slice(startIndex, endIndex);

  state.rows.forEach((row) => {
    row.style.display = 'none';
  });

  visibleRows.forEach((row) => {
    row.style.display = '';
  });

  if (state.emptyRow) {
    state.emptyRow.style.display = total === 0 ? '' : 'none';
  }

  updatePaginationControls(state, total, startIndex, endIndex, totalPages);
}

function getMatchedRows(state) {
  return state.rows.filter((row) => {
    const searchable = (row.dataset.searchText || row.textContent || '').toLowerCase();
    const searchMatch = !state.search || searchable.includes(state.search);

    const rowStatus = (row.dataset.status || '').toLowerCase();
    const filterMatch = !state.filter || rowStatus === state.filter;

    return searchMatch && filterMatch;
  });
}

function updatePaginationControls(state, total, startIndex, endIndex, totalPages) {
  const shouldShowPagination = total > state.pageSize;

  state.paginationControls.forEach((panel) => {
    panel.style.display = shouldShowPagination ? '' : 'none';

    const info = panel.querySelector('[data-page-info]');
    if (info) {
      info.textContent = shouldShowPagination
        ? `Showing ${startIndex + 1}-${endIndex} of ${total}`
        : total === 0
          ? 'Showing 0-0 of 0'
          : `Showing 1-${total} of ${total}`;
    }

    const prev = panel.querySelector('[data-page-action="prev"]');
    const next = panel.querySelector('[data-page-action="next"]');

    if (prev) {
      prev.disabled = state.currentPage <= 1 || total === 0;
    }

    if (next) {
      next.disabled = state.currentPage >= totalPages || total === 0;
    }
  });
}

function getDataRows(table) {
  return Array.from(table.querySelectorAll('tbody tr')).filter((row) => !row.hasAttribute('data-empty-row'));
}

function getTablePageSize(table) {
  const direct = table.getAttribute('data-page-size');
  const wrapper = table.closest('[data-page-size]');
  const raw = direct || (wrapper ? wrapper.getAttribute('data-page-size') : null);
  const size = Number(raw || 10);
  return Number.isFinite(size) && size > 0 ? size : 10;
}

function getTableSelector(table) {
  if (table.id) {
    return `#${table.id}`;
  }

  return table.getAttribute('data-table-selector') || '';
}

function resolveTableFromSelector(selector) {
  if (!selector) {
    return null;
  }

  try {
    return document.querySelector(selector);
  } catch (error) {
    return null;
  }
}

function escapeAttrValue(value) {
  return String(value || '').replace(/"/g, '\\"');
}

function initWizardForms() {
  document.querySelectorAll('[data-step-form]').forEach((form) => {
    const steps = Array.from(form.querySelectorAll('.wizard-step'));
    const indicators = Array.from(form.querySelectorAll('[data-step-indicator]'));
    const prevButton = form.querySelector('[data-step-action="prev"]');
    const nextButton = form.querySelector('[data-step-action="next"]');
    const skipButton = form.querySelector('[data-step-action="skip"]');
    const submitButton = form.querySelector('[data-step-submit]');

    if (!steps.length) {
      return;
    }

    let currentStep = Number(form.dataset.stepCurrent || 1) || 1;

    const render = () => {
      steps.forEach((step) => {
        const stepNumber = Number(step.dataset.step);
        step.classList.toggle('active', stepNumber === currentStep);
      });

      indicators.forEach((indicator) => {
        const stepNumber = Number(indicator.dataset.stepIndicator);
        indicator.classList.toggle('active', stepNumber === currentStep);
      });

      if (prevButton) {
        prevButton.disabled = currentStep === 1;
      }

      if (nextButton) {
        nextButton.hidden = currentStep >= steps.length;
      }

      if (submitButton) {
        submitButton.hidden = currentStep < steps.length;
      }

      if (skipButton) {
        skipButton.hidden = currentStep >= steps.length;
      }

      form.dataset.stepCurrent = String(currentStep);
      saveDraftStep(form, currentStep);
      syncReviewFields(form);
    };

    const goToStep = (step) => {
      const target = Math.min(Math.max(step, 1), steps.length);
      currentStep = target;
      render();
    };

    const validateCurrentStep = () => {
      const current = steps.find((step) => Number(step.dataset.step) === currentStep);
      if (!current) {
        return true;
      }

      const requiredFields = Array.from(current.querySelectorAll('[required]'));
      for (const field of requiredFields) {
        if (!field.checkValidity()) {
          field.reportValidity();
          return false;
        }
      }

      return true;
    };

    prevButton?.addEventListener('click', () => goToStep(currentStep - 1));
    nextButton?.addEventListener('click', () => {
      if (!validateCurrentStep()) {
        return;
      }

      goToStep(currentStep + 1);
    });
    skipButton?.addEventListener('click', () => goToStep(steps.length));

    form.addEventListener('submit', () => {
      clearDraft(form);
    });

    restoreDraft(form, steps.length).then((restoredStep) => {
      if (restoredStep) {
        currentStep = restoredStep;
      }
      render();
    });

    form.addEventListener('input', () => {
      syncReviewFields(form);
      saveDraftForm(form, currentStep);
    });

    render();
  });
}

function initAutoSlug() {
  document.querySelectorAll('[data-autoslug-source]').forEach((source) => {
    const form = source.closest('form');
    if (!form) {
      return;
    }

    const targetName = source.getAttribute('data-autoslug-target');
    if (!targetName) {
      return;
    }

    const target = form.querySelector(`[name="${escapeSelectorValue(targetName)}"]`);
    if (!target) {
      return;
    }

    let userTouchedTarget = Boolean(target.value && target.value.trim().length > 0);

    target.addEventListener('input', () => {
      userTouchedTarget = true;
    });

    source.addEventListener('input', () => {
      if (userTouchedTarget && target.value.trim() !== '') {
        return;
      }

      const slug = slugify(source.value);
      target.value = slug;
      target.dispatchEvent(new Event('input', { bubbles: true }));
      target.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });
}

function initDraftPersistence() {
  document.querySelectorAll('[data-draft-key]').forEach((form) => {
    form.querySelectorAll('input, textarea, select').forEach((field) => {
      field.addEventListener('change', () => saveDraftForm(form));
    });
  });
}


function initReviewSync() {
  document.querySelectorAll('[data-step-form]').forEach((form) => syncReviewFields(form));
}

function syncReviewFields(form) {
  form.querySelectorAll('[data-review-field]').forEach((output) => {
    if (output.hasAttribute('data-review-static')) {
      output.textContent = output.getAttribute('data-review-static') || '-';
      return;
    }

    const reviewKey = output.getAttribute('data-review-field') || '';
    const sourceName = output.getAttribute('data-review-source') || reviewKey;
    const source = sourceName
      ? form.querySelector(`[name="${escapeSelectorValue(sourceName)}"]`)
      : null;

    output.textContent = reviewValueFromSource(source, reviewKey, output);
  });
}

function reviewValueFromSource(source, reviewKey = '', output = null) {
  if (!source) {
    return '-';
  }

  const rawMask = output?.getAttribute('data-review-mask') || source.getAttribute('data-review-mask') || '';
  const shouldMask =
    rawMask === '1' ||
    source.type === 'password' ||
    reviewKey === 'security_answer' ||
    source.name === 'security_answer';

  if (shouldMask) {
    const hasValue = String(source.value || '').trim().length > 0;
    return hasValue ? 'Tersimpan' : '-';
  }

  if (source.type === 'file') {
    const files = Array.from(source.files || []);
    if (!files.length) {
      return '-';
    }

    return files.map((file) => file.name).join(', ');
  }

  if (source.type === 'datetime-local') {
    const parsed = source.value ? new Date(source.value) : null;
    if (parsed && !Number.isNaN(parsed.getTime())) {
      return parsed.toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
      }).trim();
    }
  }

  if (source.type === 'date') {
    const parsed = source.value ? new Date(source.value) : null;
    if (parsed && !Number.isNaN(parsed.getTime())) {
      return parsed.toLocaleDateString('id-ID', {
        dateStyle: 'medium',
      }).trim();
    }
  }

  if (source.type === 'time') {
    return source.value || '-';
  }

  if (source.type === 'checkbox') {
    return source.checked ? 'Ya' : 'Tidak';
  }

  if (source.tagName === 'SELECT') {
    if (source.multiple) {
      const selected = Array.from(source.selectedOptions || [])
        .map((option) => option.textContent.replace(/\s+/g, ' ').trim())
        .filter(Boolean);
      return selected.length ? selected.join(', ') : '-';
    }

    return source.options?.[source.selectedIndex]?.textContent.replace(/\s+/g, ' ').trim() || source.value || '-';
  }

  const value = String(source.value ?? '').trim();
  return value !== '' ? value : '-';
}

function initCsvExport() {
  document.querySelectorAll('[data-action="export-table"]').forEach((button) => {
    button.addEventListener('click', () => {
      const target = button.getAttribute('data-target');
      const table = target ? document.querySelector(target) : null;
      if (!table) {
        return;
      }

      const state = tableStateMap.get(table) || ensureTableState(table);
      const rows = getMatchedRows(state);
      if (!rows.length) {
        toast('Info', 'Tidak ada data yang bisa diexport.', 'info');
        return;
      }

      const headers = Array.from(table.querySelectorAll('thead th'))
        .map((th) => th.textContent.replace(/\s+/g, ' ').trim())
        .filter((text) => text && text.toLowerCase() !== 'actions');

      const lines = [headers.join(',')];
      rows.forEach((row) => {
        const cells = Array.from(row.querySelectorAll('td')).slice(0, headers.length);
        const values = cells.map((cell) => csvEscape(cell.textContent.replace(/\s+/g, ' ').trim()));
        lines.push(values.join(','));
      });

      const baseName = button.getAttribute('data-export-name') || table.id || 'table';
      const safeName = String(baseName)
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'table';

      const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `${safeName}-export.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);

      toast('Berhasil', 'Data table berhasil diexport.', 'success');
    });
  });
}

function slugify(value) {
  return String(value || '')
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

function escapeSelectorValue(value) {
  const stringValue = String(value || '');
  if (window.CSS && typeof window.CSS.escape === 'function') {
    return CSS.escape(stringValue);
  }
  return stringValue.replace(/["\\]/g, '\\$&');
}

function csvEscape(value) {
  const text = String(value ?? '');
  if (/[",\n]/.test(text)) {
    return '"' + text.replace(/"/g, '""') + '"';
  }
  return text;
}

function draftKey(form) {
  return form.getAttribute('data-draft-key') || '';
}


function saveDraftForm(form, step = null) {
  const key = draftKey(form);
  if (!key) {
    return;
  }

  const data = {};
  form.querySelectorAll('input, textarea, select').forEach((field) => {
    if (!field.name) {
      return;
    }

    if (field.type === 'file' || field.type === 'password' || field.dataset.draftSkip === '1') {
      return;
    }

    if (field.type === 'checkbox') {
      data[field.name] = field.checked ? '1' : '0';
      return;
    }

    data[field.name] = field.value;
  });

  if (step !== null) {
    data.__step = String(step);
  }

  localStorage.setItem(key, JSON.stringify(data));
}

function saveDraftStep(form, step) {
  const key = draftKey(form);
  if (!key) {
    return;
  }

  const current = loadDraft(form);
  current.__step = String(step);
  localStorage.setItem(key, JSON.stringify(current));
}

async function restoreDraft(form, totalSteps) {
  const key = draftKey(form);
  if (!key) {
    return null;
  }

  const draft = loadDraft(form);
  if (!Object.keys(draft).length) {
    return null;
  }

  form.querySelectorAll('input, textarea, select').forEach((field) => {
    if (!field.name || !(field.name in draft)) {
      return;
    }

    if (field.type === 'file' || field.type === 'password' || field.dataset.draftSkip === '1') {
      return;
    }

    if (field.type === 'checkbox') {
      field.checked = draft[field.name] === '1';
      return;
    }

    field.value = draft[field.name];
  });

  syncReviewFields(form);

  const draftStep = Number(draft.__step || 1);
  if (draftStep >= 1 && draftStep <= totalSteps) {
    return draftStep;
  }

  return 1;
}

function loadDraft(form) {
  const key = draftKey(form);
  if (!key) {
    return {};
  }

  try {
    return JSON.parse(localStorage.getItem(key) || '{}');
  } catch (error) {
    return {};
  }
}

function clearDraft(form) {
  const key = draftKey(form);
  if (!key) {
    return;
  }

  localStorage.removeItem(key);
}
