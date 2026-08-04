(function () {
    const form = document.querySelector("[data-step-form]");
    if (!form) return;

    const money = (v) =>
        "Rp " +
        new Intl.NumberFormat("id-ID").format(
            Math.max(0, Math.round(Number(v || 0))),
        );
    const discountSettings = Array.isArray(window.__DISCOUNT_SETTINGS__)
        ? window.__DISCOUNT_SETTINGS__
        : [];
    const tbody = form.querySelector("[data-items-tbody]");
    const template = document.getElementById("transactionRowTemplate");
    const addRowBtn = form.querySelector('[data-action="add-transaction-row"]');
    const paidInput = form.querySelector("[data-paid-input]");
    const taxSelect = form.querySelector('select[name="tax_setting_id"]');
    const paymentMethodSelect = form.querySelector(
        'select[name="payment_method"]',
    );
    const transactionAtInput = form.querySelector(
        'input[name="transaction_at"]',
    );
    const discountRuleNote = form.querySelector("[data-discount-rule-note]");

    const displays = {
        subtotal: form.querySelector('[data-summary-display="subtotal"]'),
        itemDiscount: form.querySelector(
            '[data-summary-display="item-discount"]',
        ),
        transactionDiscount: form.querySelector(
            '[data-summary-display="transaction-discount"]',
        ),
        afterDiscount: form.querySelector(
            '[data-summary-display="after-discount"]',
        ),
        tax: form.querySelector('[data-summary-display="tax"]'),
        total: form.querySelector('[data-summary-display="total"]'),
        change: form.querySelector('[data-summary-display="change"]'),
    };

    const hidden = {
        subtotal: form.querySelector('[data-summary-field="subtotal-hidden"]'),
        discount: form.querySelector('[data-summary-field="discount-hidden"]'),
        tax: form.querySelector('[data-summary-field="tax-hidden"]'),
        total: form.querySelector('[data-summary-field="total-hidden"]'),
        change: form.querySelector('[data-summary-field="change-hidden"]'),
    };

    const review = {
        location: form.querySelector('[data-review-field="location"]'),
        taxSetting: form.querySelector('[data-review-field="tax-setting"]'),
        shift: form.querySelector('[data-review-field="shift"]'),
        paymentMethod: form.querySelector(
            '[data-review-field="payment-method"]',
        ),
        customerName: form.querySelector('[data-review-field="customer-name"]'),
        customerPhone: form.querySelector(
            '[data-review-field="customer-phone"]',
        ),
        transactionAt: form.querySelector(
            '[data-review-field="transaction-at"]',
        ),
        productSummary: form.querySelector(
            '[data-review-field="product-summary"]',
        ),
        subtotal: form.querySelector('[data-review-field="subtotal"]'),
        itemDiscount: form.querySelector('[data-review-field="item-discount"]'),
        transactionDiscount: form.querySelector(
            '[data-review-field="transaction-discount"]',
        ),
        afterDiscount: form.querySelector(
            '[data-review-field="after-discount"]',
        ),
        tax: form.querySelector('[data-review-field="tax"]'),
        total: form.querySelector('[data-review-field="total"]'),
        paid: form.querySelector('[data-review-field="paid"]'),
        change: form.querySelector('[data-review-field="change"]'),
        discountRule: form.querySelector('[data-review-field="discount-rule"]'),
        notes: form.querySelector('[data-review-field="notes"]'),
    };

    const parseDate = (v) => {
        if (!v) return null;
        const d = new Date(v);
        return Number.isNaN(d.getTime()) ? null : d;
    };

    const taxValue = () => {
        const selected = taxSelect?.selectedOptions?.[0];
        return {
            type: selected?.dataset.taxType || "fixed",
            value: Number(selected?.dataset.taxValue || 0),
            label: selected ? selected.textContent.trim() : "-",
        };
    };

    const productInfoFromRow = (row) => {
        const select = row.querySelector("[data-product-select]");
        const selected = select?.selectedOptions?.[0];
        return {
            id: selected?.value || "",
            name: selected?.dataset.productName || "",
            salePrice: Number(selected?.dataset.salePrice || 0),
            promoDiscount: Number(selected?.dataset.promoDiscount || 0),
            stock: Number(selected?.dataset.stockOnHand || 0),
            unit: selected?.dataset.unitLabel || "-",
        };
    };

    const syncRowNames = () => {
        [...tbody.querySelectorAll("[data-item-row]")].forEach((row, index) => {
            const p = row.querySelector("[data-product-select]");
            const q = row.querySelector("[data-qty-input]");
            if (p) p.name = `items[${index}][product_id]`;
            if (q) q.name = `items[${index}][quantity]`;
        });
    };

    const updateRow = (row) => {
        const info = productInfoFromRow(row);
        const qtyInput = row.querySelector("[data-qty-input]");
        const unitPriceInput = row.querySelector("[data-unit-price-input]");
        const discountInput = row.querySelector("[data-discount-input]");
        const stockDisplay = row.querySelector("[data-stock-display]");
        const stockWarning = row.querySelector("[data-stock-warning]");
        const lineTotalDisplay = row.querySelector("[data-line-total-display]");
        const qty = Math.max(1, Number(qtyInput?.value || 1));
        const unitPrice = Number(info.salePrice || 0);
        const promo = Number(info.promoDiscount || 0);
        const gross = qty * unitPrice;
        const itemDiscount = Math.min(gross, qty * promo);
        const lineNet = Math.max(0, gross - itemDiscount);

        if (unitPriceInput) unitPriceInput.value = money(unitPrice);
        if (discountInput) discountInput.value = money(promo);
        if (stockDisplay)
            stockDisplay.value = info.id ? String(info.stock) : "-";
        if (stockWarning)
            stockWarning.textContent = info.id
                ? `Stok tersedia: ${new Intl.NumberFormat("id-ID").format(info.stock)} ${info.unit}`
                : "Stok: -";
        if (lineTotalDisplay) lineTotalDisplay.value = money(lineNet);
        if (info.stock > 0 && qty > info.stock)
            qtyInput.value = String(info.stock);

        return {
            gross,
            itemDiscount,
            lineNet,
            productName: info.name,
            qty,
            unitPrice,
            promo,
        };
    };

    const resolveDiscountSetting = (baseAmount) => {
        const txDate = parseDate(transactionAtInput?.value) || new Date();
        const candidates = discountSettings.filter((setting) => {
            if (!setting.is_active) return false;
            const startsAt = parseDate(setting.starts_at);
            const endsAt = parseDate(
                setting.ends_at ? `${setting.ends_at}T23:59:59` : null,
            );
            if (startsAt && startsAt > txDate) return false;
            if (endsAt && endsAt < txDate) return false;
            return baseAmount >= Number(setting.minimum_total_amount || 0);
        });

        candidates.sort(
            (a, b) =>
                Number(b.is_default) - Number(a.is_default) ||
                Number(b.priority || 0) - Number(a.priority || 0) ||
                Number(b.minimum_total_amount || 0) -
                    Number(a.minimum_total_amount || 0) ||
                Number(b.discount_value || 0) - Number(a.discount_value || 0) ||
                Number(b.id || 0) - Number(a.id || 0),
        );
        return candidates[0] || null;
    };

    const calculateDiscountAmount = (setting, baseAmount) => {
        if (!setting || baseAmount <= 0) return 0;
        if (setting.discount_type === "percent")
            return Math.max(
                0,
                Math.round(
                    (baseAmount * Number(setting.discount_value || 0)) / 100,
                ),
            );
        return Math.min(
            baseAmount,
            Math.max(0, Number(setting.discount_value || 0)),
        );
    };

    const calc = () => {
        const rows = [...tbody.querySelectorAll("[data-item-row]")];
        let grossSubtotal = 0;
        let itemDiscountTotal = 0;
        const productSummaryLines = [];

        rows.forEach((row) => {
            const info = updateRow(row);
            grossSubtotal += info.gross;
            itemDiscountTotal += info.itemDiscount;
            if (info.productName)
                productSummaryLines.push(
                    `${info.productName} • Qty ${info.qty} • Unit ${money(info.unitPrice)} • Promo/Pcs ${money(info.promo)}`,
                );
        });

        const subtotalAfterItemDiscount = Math.max(
            0,
            grossSubtotal - itemDiscountTotal,
        );
        const txSetting = resolveDiscountSetting(subtotalAfterItemDiscount);
        const txDiscount = calculateDiscountAmount(
            txSetting,
            subtotalAfterItemDiscount,
        );
        const afterDiscount = Math.max(
            0,
            subtotalAfterItemDiscount - txDiscount,
        );
        const tax = taxValue();
        const taxAmount =
            tax.type === "percent"
                ? Math.round((afterDiscount * tax.value) / 100)
                : Math.max(0, tax.value);
        const total = Math.max(0, afterDiscount + taxAmount);

        if (paymentMethodSelect.value !== "cash") {
            paidInput.value = String(total);
            paidInput.readOnly = true;
        } else {
            paidInput.readOnly = false;
        }

        const paid = Number(paidInput.value || 0);
        const change = Math.max(0, paid - total);

        displays.subtotal.value = money(grossSubtotal);
        displays.itemDiscount.value = money(itemDiscountTotal);
        displays.transactionDiscount.value = money(txDiscount);
        displays.afterDiscount.value = money(afterDiscount);
        displays.tax.value = money(taxAmount);
        displays.total.value = money(total);
        displays.change.value = money(change);

        hidden.subtotal.value = String(grossSubtotal);
        hidden.discount.value = String(txDiscount);
        hidden.tax.value = String(taxAmount);
        hidden.total.value = String(total);
        hidden.change.value = String(change);

        review.location.textContent =
            form
                .querySelector('select[name="location_id"] option:checked')
                ?.textContent?.trim() || "-";
        review.taxSetting.textContent = tax.label;
        review.shift.textContent =
            form
                .querySelector('select[name="shift"] option:checked')
                ?.textContent?.trim() || "-";
        review.paymentMethod.textContent =
            form
                .querySelector('select[name="payment_method"] option:checked')
                ?.textContent?.trim() || "-";
        review.customerName.textContent =
            form.querySelector('input[name="customer_name"]')?.value?.trim() ||
            "-";
        review.customerPhone.textContent =
            form.querySelector('input[name="customer_phone"]')?.value?.trim() ||
            "-";
        review.transactionAt.textContent = transactionAtInput?.value || "-";
        review.productSummary.textContent = productSummaryLines.length
            ? productSummaryLines.join(" | ")
            : "Belum ada product dipilih.";
        review.subtotal.textContent = money(grossSubtotal);
        review.itemDiscount.textContent = money(itemDiscountTotal);
        review.transactionDiscount.textContent = money(txDiscount);
        review.afterDiscount.textContent = money(afterDiscount);
        review.tax.textContent = money(taxAmount);
        review.total.textContent = money(total);
        review.paid.textContent = money(paid);
        review.change.textContent = money(change);
        review.discountRule.textContent = txSetting
            ? `${txSetting.code} — ${txSetting.name} (${txSetting.discount_type === "percent" ? `${txSetting.discount_value}%` : money(txSetting.discount_value)}) untuk minimal ${money(txSetting.minimum_total_amount)}`
            : "Tidak ada diskon transaksi aktif.";
        review.notes.textContent =
            form.querySelector('textarea[name="notes"]')?.value?.trim() || "-";

        if (discountRuleNote) {
            discountRuleNote.textContent = txSetting
                ? `Diskon aktif: ${txSetting.code} (${txSetting.name})`
                : "Tidak ada diskon transaksi aktif.";
        }
    };

    const bindRow = (row) => {
        const productSelect = row.querySelector("[data-product-select]");
        const qtyInput = row.querySelector("[data-qty-input]");
        const removeBtn = row.querySelector(
            '[data-action="remove-transaction-row"]',
        );

        productSelect?.addEventListener("change", () => {
            updateRow(row);
            calc();
        });

        qtyInput?.addEventListener("input", () => {
            updateRow(row);
            calc();
        });

        removeBtn?.addEventListener("click", () => {
            const rows = tbody.querySelectorAll("[data-item-row]");
            if (rows.length <= 1) {
                const ps = row.querySelector("[data-product-select]");
                const qi = row.querySelector("[data-qty-input]");
                if (ps) ps.value = "";
                if (qi) qi.value = 1;
                calc();
                return;
            }

            row.remove();
            syncRowNames();
            calc();
        });
    };

    [...tbody.querySelectorAll("[data-item-row]")].forEach(bindRow);
    syncRowNames();

    addRowBtn?.addEventListener("click", () => {
        const clone = template.content.firstElementChild.cloneNode(true);
        clone.querySelectorAll("input,select").forEach((el) => {
            if (el.type === "number") el.value = 1;
            if (el.tagName === "SELECT") el.value = "";
        });
        tbody.appendChild(clone);
        syncRowNames();
        bindRow(clone);
        calc();
    });

    [taxSelect, paymentMethodSelect, paidInput, transactionAtInput].forEach(
        (el) => {
            el?.addEventListener("change", calc);
            el?.addEventListener("input", calc);
        },
    );

    form.querySelector('select[name="location_id"]')?.addEventListener(
        "change",
        calc,
    );
    form.querySelector('select[name="shift"]')?.addEventListener(
        "change",
        calc,
    );
    form.querySelector('input[name="customer_name"]')?.addEventListener(
        "input",
        calc,
    );
    form.querySelector('input[name="customer_phone"]')?.addEventListener(
        "input",
        calc,
    );
    form.querySelector('textarea[name="notes"]')?.addEventListener(
        "input",
        calc,
    );

    calc();
})();
