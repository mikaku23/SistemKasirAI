(function () {
    const context = window.__SUPPLIER_RETURN_CONTEXT__ || {};
    const productCatalog = Array.isArray(context.productCatalog) ? context.productCatalog : [];
    const batchCatalog = Array.isArray(context.batchCatalog) ? context.batchCatalog : [];
    const initialItems = Array.isArray(context.oldItems) ? context.oldItems : [];

    const supplierSelect = document.getElementById('supplier_id');
    const locationSelect = document.getElementById('location_id');
    const returnItems = document.getElementById('returnItems');
    const addButton = document.getElementById('addReturnItem');
    const template = document.getElementById('returnItemTemplate');
    const summaryItemCount = document.getElementById('summaryItemCount');
    const summaryQty = document.getElementById('summaryQty');
    const summaryAmount = document.getElementById('summaryAmount');
    const filterHint = document.querySelector('[data-filter-hint]');

    if (!supplierSelect || !locationSelect || !returnItems || !addButton || !template) {
        return;
    }

    const money = (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(value || 0);
    const supplierId = () => String(supplierSelect.value || '');
    const locationId = () => String(locationSelect.value || '');
    const filtersReady = () => supplierId() !== '' && locationId() !== '';

    const filteredBatches = () => {
        if (!filtersReady()) return [];
        return batchCatalog.filter((item) => String(item.supplier_id) === supplierId() && String(item.location_id) === locationId());
    };

    const filteredProducts = () => {
        const batches = filteredBatches();
        if (batches.length > 0) {
            const map = new Map();
            batches.forEach((batch) => {
                const key = String(batch.product_id || '0');
                if (!map.has(key)) {
                    map.set(key, {
                        id: Number(batch.product_id || 0),
                        supplier_id: Number(batch.supplier_id || 0),
                        location_id: Number(batch.location_id || 0),
                        supplier_name: batch.supplier_name || '',
                        location_name: batch.location_name || '',
                        product_name: batch.product_name || '',
                        product_sku: batch.product_sku || '',
                        product_category: batch.product_category || '',
                        batch_count: 0,
                        qty_total: 0,
                    });
                }

                const product = map.get(key);
                product.batch_count += 1;
                product.qty_total += Number(batch.qty_remaining || 0);
            });

            return [...map.values()].map((product) => ({
                ...product,
                label: [
                    product.product_name || '-',
                    product.product_sku ? `SKU ${product.product_sku}` : null,
                    product.supplier_name || '-',
                    product.location_name || '-',
                    `batch ${product.batch_count}`,
                    `stok ${product.qty_total}`,
                ].filter(Boolean).join(' · '),
            }));
        }

        // Fallback only if batch catalog is empty. This keeps old data usable.
        return productCatalog.filter((item) => String(item.supplier_id) === supplierId() && String(item.location_id) === locationId());
    };

    const updateNames = () => {
        [...returnItems.querySelectorAll('[data-return-item]')].forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach((field) => {
                const key = field.getAttribute('data-field');
                field.name = `items[${index}][${key}]`;
            });
        });
    };

    const syncSummary = () => {
        const rows = [...returnItems.querySelectorAll('[data-return-item]')];
        let qty = 0;
        let amount = 0;

        rows.forEach((row) => {
            const quantity = parseInt(row.querySelector('[data-field="quantity"]')?.value || '0', 10);
            const unitPrice = parseInt(row.querySelector('[data-field="unit_price"]')?.value || '0', 10);
            qty += Number.isFinite(quantity) ? quantity : 0;
            amount += (Number.isFinite(quantity) ? quantity : 0) * (Number.isFinite(unitPrice) ? unitPrice : 0);
        });

        if (summaryItemCount) summaryItemCount.textContent = String(rows.length);
        if (summaryQty) summaryQty.textContent = String(qty);
        if (summaryAmount) summaryAmount.textContent = money(amount);
    };

    const refreshHint = () => {
        const ready = filtersReady();
        addButton.disabled = !ready;

        if (!filterHint) return;

        const title = filterHint.querySelector('strong');
        const body = filterHint.querySelector('p');
        const batches = filteredBatches();
        const products = filteredProducts();

        if (title) {
            title.textContent = ready ? 'Filter aktif' : 'Menunggu filter';
        }

        if (body) {
            body.textContent = ready
                ? `Product tersedia: ${products.length}. Batch aktif cocok: ${batches.length}.`
                : 'Pilih supplier dan location agar product dan batch yang cocok muncul.';
        }
    };

    const refreshProductOptions = (row) => {
        const select = row.querySelector('[data-field="product_id"]');
        if (!select) return;

        const current = String(select.value || '');
        const products = filteredProducts();

        select.innerHTML = '<option value="">Pilih product</option>' + products.map((product) => (
            `<option value="${product.id}" data-supplier-id="${product.supplier_id}" data-supplier-name="${product.supplier_name || ''}" data-location-id="${product.location_id}" data-location-name="${product.location_name || ''}">${product.label}</option>`
        )).join('');

        if (current && [...select.options].some((option) => option.value === current)) {
            select.value = current;
        } else if (products.length === 1) {
            select.value = String(products[0].id);
        } else {
            select.value = '';
        }
    };

    const refreshBatchOptions = (row) => {
        const productSelect = row.querySelector('[data-field="product_id"]');
        const batchSelect = row.querySelector('[data-field="stock_batch_id"]');
        const quantityInput = row.querySelector('[data-field="quantity"]');
        const unitPriceInput = row.querySelector('[data-field="unit_price"]');

        if (!productSelect || !batchSelect || !quantityInput || !unitPriceInput) return;

        const currentBatch = String(batchSelect.value || '');
        const productId = String(productSelect.value || '');
        const batches = filteredBatches().filter((batch) => String(batch.product_id) === productId);

        batchSelect.innerHTML = '<option value="">Pilih batch</option>' + batches.map((batch) => (
            `<option value="${batch.id}" data-qty-remaining="${batch.qty_remaining}" data-purchase-price="${batch.purchase_price}">${batch.label}</option>`
        )).join('');

        if (currentBatch && [...batchSelect.options].some((option) => option.value === currentBatch)) {
            batchSelect.value = currentBatch;
        } else if (batches.length === 1) {
            batchSelect.value = String(batches[0].id);
        } else {
            batchSelect.value = '';
        }

        const currentOption = batchSelect.options[batchSelect.selectedIndex];
        const remaining = parseInt(currentOption?.dataset?.qtyRemaining || '0', 10);
        const purchasePrice = parseInt(currentOption?.dataset?.purchasePrice || '0', 10);

        if (remaining > 0) {
            quantityInput.value = String(remaining);
        }

        if (!unitPriceInput.value && purchasePrice > 0) {
            unitPriceInput.value = String(purchasePrice);
        }
    };

    const syncRow = (row) => {
        refreshProductOptions(row);
        refreshBatchOptions(row);
        updateNames();
    };

    const wireRow = (row) => {
        const productSelect = row.querySelector('[data-field="product_id"]');
        const batchSelect = row.querySelector('[data-field="stock_batch_id"]');
        const quantityInput = row.querySelector('[data-field="quantity"]');
        const unitPriceInput = row.querySelector('[data-field="unit_price"]');
        const removeBtn = row.querySelector('[data-remove-item]');

        if (productSelect) {
            productSelect.addEventListener('change', () => {
                refreshBatchOptions(row);
                updateNames();
                syncSummary();
            });
        }

        if (batchSelect) {
            batchSelect.addEventListener('change', () => {
                const option = batchSelect.options[batchSelect.selectedIndex];
                const qtyRemaining = parseInt(option?.dataset?.qtyRemaining || '0', 10);
                const purchasePrice = parseInt(option?.dataset?.purchasePrice || '0', 10);

                if (qtyRemaining > 0) {
                    quantityInput.value = String(qtyRemaining);
                }

                if (!unitPriceInput.value && purchasePrice > 0) {
                    unitPriceInput.value = String(purchasePrice);
                }

                syncSummary();
            });
        }

        [quantityInput, unitPriceInput].forEach((input) => {
            if (!input) return;
            input.addEventListener('input', syncSummary);
            input.addEventListener('change', syncSummary);
        });

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                if (returnItems.querySelectorAll('[data-return-item]').length <= 1) return;
                row.remove();
                updateNames();
                syncSummary();
            });
        }
    };

    const createRow = (item = null) => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-return-item]');
        if (!row) return null;

        returnItems.appendChild(fragment);
        syncRow(row);
        wireRow(row);

        if (item) {
            const productSelect = row.querySelector('[data-field="product_id"]');
            const batchSelect = row.querySelector('[data-field="stock_batch_id"]');
            const quantityInput = row.querySelector('[data-field="quantity"]');
            const unitPriceInput = row.querySelector('[data-field="unit_price"]');
            const notesInput = row.querySelector('[data-field="notes"]');

            if (item.product_id && productSelect) {
                productSelect.value = String(item.product_id);
                refreshBatchOptions(row);
            }

            if (item.stock_batch_id && batchSelect) {
                batchSelect.value = String(item.stock_batch_id);
                const option = batchSelect.options[batchSelect.selectedIndex];
                const qtyRemaining = parseInt(option?.dataset?.qtyRemaining || item.quantity || '0', 10);
                const purchasePrice = parseInt(option?.dataset?.purchasePrice || item.unit_price || '0', 10);
                if (qtyRemaining > 0 && quantityInput) quantityInput.value = String(qtyRemaining);
                if (purchasePrice > 0 && unitPriceInput) unitPriceInput.value = String(purchasePrice);
            }

            if (item.quantity !== undefined && item.quantity !== null && quantityInput) {
                quantityInput.value = String(item.quantity);
            }

            if (item.unit_price !== undefined && item.unit_price !== null && unitPriceInput) {
                unitPriceInput.value = String(item.unit_price);
            }

            if (item.notes !== undefined && item.notes !== null && notesInput) {
                notesInput.value = String(item.notes);
            }
        }

        updateNames();
        syncSummary();
        return row;
    };

    const refreshAll = () => {
        refreshHint();
        returnItems.querySelectorAll('[data-return-item]').forEach((row) => {
            const selectedProduct = row.querySelector('[data-field="product_id"]')?.value || '';
            const selectedBatch = row.querySelector('[data-field="stock_batch_id"]')?.value || '';

            refreshProductOptions(row);
            const productSelect = row.querySelector('[data-field="product_id"]');
            if (productSelect && selectedProduct && [...productSelect.options].some((option) => option.value === String(selectedProduct))) {
                productSelect.value = String(selectedProduct);
            }

            refreshBatchOptions(row);
            const batchSelect = row.querySelector('[data-field="stock_batch_id"]');
            if (batchSelect && selectedBatch && [...batchSelect.options].some((option) => option.value === String(selectedBatch))) {
                batchSelect.value = String(selectedBatch);
            }

            refreshBatchOptions(row);
        });
        updateNames();
        syncSummary();
    };

    supplierSelect.addEventListener('change', refreshAll);
    locationSelect.addEventListener('change', refreshAll);

    addButton.addEventListener('click', () => {
        if (addButton.disabled) return;
        createRow();
    });

    if (initialItems.length > 0) {
        initialItems.forEach((item) => createRow(item));
    } else {
        createRow();
    }

    refreshAll();
})();
