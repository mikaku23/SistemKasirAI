(function () {
  const getLocationSelect = (root = document) => root.querySelector('select[name="location_id"]');

  const getProductSelects = (root = document) => [
    ...root.querySelectorAll('select[data-product-select]'),
    ...root.querySelectorAll('select[name="product_id"]'),
  ];

  const getAllCatalog = () => {
    const catalog = window.__ALL_PRODUCT_CATALOG__;
    return catalog && typeof catalog === 'object' ? catalog : null;
  };

  const getCurrentCatalog = () => {
    const catalog = window.__PRODUCT_CATALOG__;
    return catalog && typeof catalog === 'object' ? catalog : null;
  };

  const mutateCatalog = (filtered) => {
    const current = getCurrentCatalog();
    if (!current) return;

    Object.keys(current).forEach((key) => delete current[key]);
    Object.entries(filtered || {}).forEach(([key, value]) => {
      current[key] = value;
    });
  };

  const normalizeLocation = (value) => {
    const cleaned = String(value ?? '').trim();
    return cleaned === '' ? '' : cleaned;
  };

  const filterCatalog = (locationId) => {
    const source = getAllCatalog();
    if (!source) {
      return {};
    }

    const normalizedLocation = normalizeLocation(locationId);
    const filtered = {};

    Object.entries(source).forEach(([barcode, product]) => {
      const productLocation = normalizeLocation(product?.location_id);
      if (normalizedLocation === '') return;
      if (productLocation === normalizedLocation) {
        filtered[barcode] = product;
      }
    });

    return filtered;
  };

  const refreshSelect = (select, locationId) => {
    const normalizedLocation = normalizeLocation(locationId);
    const options = [...select.options];

    options.forEach((option, index) => {
      if (index === 0) {
        option.hidden = false;
        option.disabled = false;
        return;
      }

      const optionLocation = normalizeLocation(option.dataset.locationId);
      const matches = normalizedLocation !== '' && optionLocation === normalizedLocation;

      option.hidden = !matches;
      option.disabled = !matches;
    });

    const selectedOption = select.selectedOptions?.[0];
    const selectedLocation = normalizeLocation(selectedOption?.dataset?.locationId);

    if (select.value && (normalizedLocation === '' || selectedLocation !== normalizedLocation)) {
      select.value = '';
      select.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  const refreshAll = () => {
    const locationSelect = getLocationSelect();
    const locationId = locationSelect?.value || '';

    const filteredCatalog = filterCatalog(locationId);
    if (getCurrentCatalog()) {
      mutateCatalog(filteredCatalog);
    }

    getProductSelects().forEach((select) => refreshSelect(select, locationId));
  };

  let scheduled = null;
  const scheduleRefresh = () => {
    if (scheduled) cancelAnimationFrame(scheduled);
    scheduled = requestAnimationFrame(() => {
      scheduled = null;
      refreshAll();
    });
  };

  const boot = () => {
    const locationSelect = getLocationSelect();
    if (locationSelect) {
      locationSelect.addEventListener('change', scheduleRefresh);
      locationSelect.addEventListener('input', scheduleRefresh);
    }

    const observer = new MutationObserver(() => scheduleRefresh());
    observer.observe(document.body, { childList: true, subtree: true });

    refreshAll();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  window.__refreshLocationProductFilter = refreshAll;
})();