(() => {
    "use strict";

    const GRID_SELECTOR = ".stats-grid";
    const CARD_SELECTOR = ".stats-grid .stat-card";

    const COLORS = [
        "#5b8cff",
        "#8f69ff",
        "#38d6b0",
        "#ffb44c",
        "#ff5f7c",
        "#7bdcff",
        "#9b8cff",
        "#64e8c5",
    ];

    function parseNumber(text) {
        const cleaned = String(text ?? "")
            .replace(/[^\d,.\-]/g, "")
            .trim();

        if (!cleaned) {
            return 0;
        }

        let normalized = cleaned;

        if (normalized.includes(",") && normalized.includes(".")) {
            if (normalized.lastIndexOf(",") > normalized.lastIndexOf(".")) {
                normalized = normalized.replace(/\./g, "").replace(",", ".");
            } else {
                normalized = normalized.replace(/,/g, "");
            }
        } else if (normalized.includes(",")) {
            normalized = normalized.replace(",", ".");
        }

        const value = Number.parseFloat(normalized);
        return Number.isFinite(value) ? value : 0;
    }

    function formatDisplayValue(value) {
        if (!Number.isFinite(value)) {
            return "0";
        }

        if (Math.abs(value - Math.round(value)) < 0.00001) {
            return new Intl.NumberFormat("id-ID").format(Math.round(value));
        }

        return new Intl.NumberFormat("id-ID", {
            maximumFractionDigits: 2,
        }).format(value);
    }

    function polarToCartesian(cx, cy, r, angleDeg) {
        const rad = (angleDeg - 90) * Math.PI / 180;
        return {
            x: cx + r * Math.cos(rad),
            y: cy + r * Math.sin(rad),
        };
    }

    function buildSlicePath(cx, cy, r, startAngle, endAngle) {
        const start = polarToCartesian(cx, cy, r, endAngle);
        const end = polarToCartesian(cx, cy, r, startAngle);
        const largeArcFlag = endAngle - startAngle <= 180 ? 0 : 1;

        return [
            `M ${cx} ${cy}`,
            `L ${start.x.toFixed(3)} ${start.y.toFixed(3)}`,
            `A ${r} ${r} 0 ${largeArcFlag} 0 ${end.x.toFixed(3)} ${end.y.toFixed(3)}`,
            "Z",
        ].join(" ");
    }

    function buildLabelPosition(cx, cy, r, startAngle, endAngle) {
        const angle = startAngle + (endAngle - startAngle) / 2;
        const labelRadius = r * 0.66;
        const point = polarToCartesian(cx, cy, labelRadius, angle);
        return {
            x: point.x,
            y: point.y,
            angle,
        };
    }

    function createTooltip() {
        let tooltip = document.querySelector(".stats-chart-tooltip");
        if (tooltip) return tooltip;

        tooltip = document.createElement("div");
        tooltip.className = "stats-chart-tooltip";
        tooltip.hidden = true;
        tooltip.innerHTML = `
            <strong></strong>
            <span></span>
        `;
        document.body.appendChild(tooltip);
        return tooltip;
    }

    function renderEmptyState(grid) {
        const empty = document.createElement("div");
        empty.className = "stats-chart-empty";
        empty.innerHTML = "<strong>Tidak ada data statistik untuk divisualkan.</strong>";
        grid.replaceWith(empty);
    }

    function renderPieChart(grid, items) {
        const total = items.reduce((sum, item) => sum + item.value, 0);

        if (items.length === 0) {
            renderEmptyState(grid);
            return;
        }

        const panel = document.createElement("section");
        panel.className = "stats-chart-panel glass-card";
        panel.setAttribute("aria-label", "Stock batch pie chart overview");

        const legendMarkup = items
            .map((item, index) => {
                const percent = total > 0 ? (item.value / total) * 100 : 0;
                return `
                    <div class="stats-chart-legend__item" data-legend-index="${index}" role="button" tabindex="0" aria-label="${escapeHtml(item.label)}">
                        <span class="stats-chart-legend__swatch" style="background:${item.color}"></span>
                        <span class="stats-chart-legend__name">${escapeHtml(item.label)}</span>
                        <span class="stats-chart-legend__meta">${escapeHtml(item.valueText)} · ${percent.toFixed(1)}%</span>
                    </div>
                `;
            })
            .join("");

        panel.innerHTML = `
            <div class="stats-chart-panel__head">
                <div>
                    <p class="eyebrow">STATISTICS</p>
                    <h3>Diagram Lingkaran Stock Batches</h3>
                    <p>Hover ke setiap irisan untuk melihat label dan nilai.</p>
                </div>
                <div class="pill pill--accent" style="pointer-events:none;">${escapeHtml(items.length)} Segmen</div>
            </div>

            <div class="stats-chart-panel__body">
                <div class="stats-chart-canvas" data-chart-canvas>
                    <div class="stats-chart-center">
                        <strong>${escapeHtml(formatDisplayValue(total))}</strong>
                        <span>Total</span>
                    </div>
                </div>

                <div class="stats-chart-legend" data-chart-legend>
                    ${legendMarkup}
                </div>
            </div>
        `;

        grid.parentNode.insertBefore(panel, grid);

        const canvas = panel.querySelector("[data-chart-canvas]");
        const legend = panel.querySelector("[data-chart-legend]");
        const tooltip = createTooltip();
        const cx = 160;
        const cy = 160;
        const outerRadius = 120;
        const innerPadding = 6;
        const strokeWidth = 0;
        const chartRadius = outerRadius - innerPadding;

        let angleCursor = -90;
        const slices = [];

        const svgParts = [`<svg viewBox="0 0 320 320" role="img" aria-label="Diagram lingkaran statistik stock batches">`];

        items.forEach((item, index) => {
            const fraction = total > 0 ? item.value / total : 0;
            const sliceAngle = total > 0 ? Math.max(0.5, fraction * 360) : (360 / items.length);
            const startAngle = angleCursor;
            const endAngle = angleCursor + sliceAngle;
            angleCursor = endAngle;

            const path = buildSlicePath(cx, cy, chartRadius, startAngle, endAngle);
            const mid = buildLabelPosition(cx, cy, chartRadius, startAngle, endAngle);
            const visibleLabel = fraction >= 0.075 || items.length <= 4;
            const labelX = mid.x;
            const labelY = mid.y;
            const textRotate = mid.angle > 90 && mid.angle < 270 ? mid.angle + 180 : mid.angle;

            svgParts.push(`
                <path
                    class="stats-chart-slice"
                    data-slice-index="${index}"
                    data-label="${escapeHtml(item.label)}"
                    data-value="${escapeHtml(item.valueText)}"
                    data-percent="${(fraction * 100).toFixed(2)}"
                    data-color="${item.color}"
                    fill="${item.color}"
                    d="${path}"
                    tabindex="0"
                    role="img"
                    aria-label="${escapeHtml(item.label)}: ${escapeHtml(item.valueText)} (${(fraction * 100).toFixed(1)}%)"
                ></path>
            `);

            if (visibleLabel) {
                svgParts.push(`
                    <text
                        class="stats-chart-slice__label"
                        x="${labelX.toFixed(2)}"
                        y="${labelY.toFixed(2)}"
                        text-anchor="middle"
                        dominant-baseline="middle"
                        transform="rotate(${textRotate.toFixed(2)} ${labelX.toFixed(2)} ${labelY.toFixed(2)})"
                    >${escapeHtml(item.label)}</text>
                `);
            }

            slices.push({
                index,
                label: item.label,
                valueText: item.valueText,
                percent: fraction * 100,
                color: item.color,
            });
        });

        if (strokeWidth > 0) {
            svgParts.push(`<circle cx="${cx}" cy="${cy}" r="${chartRadius}" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="${strokeWidth}"></circle>`);
        }

        svgParts.push(`</svg>`);
        canvas.insertAdjacentHTML("afterbegin", svgParts.join(""));

        const sliceNodes = Array.from(panel.querySelectorAll(".stats-chart-slice"));
        const legendNodes = Array.from(panel.querySelectorAll(".stats-chart-legend__item"));

        function clearActive() {
            sliceNodes.forEach((node) => node.classList.remove("is-active"));
            legendNodes.forEach((node) => node.classList.remove("is-active"));
            tooltip.classList.remove("is-visible");
            tooltip.hidden = true;
        }

        function setActive(index, event) {
            const item = slices[index];
            if (!item) return;

            sliceNodes.forEach((node) => {
                node.classList.toggle("is-active", Number(node.dataset.sliceIndex) === index);
            });

            legendNodes.forEach((node) => {
                node.classList.toggle("is-active", Number(node.dataset.legendIndex) === index);
            });

            tooltip.hidden = false;
            tooltip.classList.add("is-visible");
            tooltip.innerHTML = `
                <strong><span class="stats-chart-tooltip__dot" style="background:${item.color}"></span>${escapeHtml(item.label)}</strong>
                <span>Value: ${escapeHtml(item.valueText)} · ${item.percent.toFixed(1)}%</span>
            `;

            positionTooltip(event);
        }

        function positionTooltip(event) {
            if (!event) return;
            const pad = 14;
            const rect = panel.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();

            let left = event.clientX + 18;
            let top = event.clientY + 18;

            if (left + tooltipRect.width > window.innerWidth - pad) {
                left = event.clientX - tooltipRect.width - 18;
            }

            if (top + tooltipRect.height > window.innerHeight - pad) {
                top = event.clientY - tooltipRect.height - 18;
            }

            tooltip.style.left = `${Math.max(pad, left)}px`;
            tooltip.style.top = `${Math.max(pad, top)}px`;
        }

        sliceNodes.forEach((node) => {
            node.addEventListener("mouseenter", (event) => setActive(Number(node.dataset.sliceIndex), event));
            node.addEventListener("mousemove", positionTooltip);
            node.addEventListener("mouseleave", clearActive);
            node.addEventListener("focus", (event) => setActive(Number(node.dataset.sliceIndex), event));
            node.addEventListener("blur", clearActive);
        });

        legendNodes.forEach((node) => {
            node.addEventListener("mouseenter", (event) => {
                const index = Number(node.dataset.legendIndex);
                setActive(index, event);
            });
            node.addEventListener("mousemove", positionTooltip);
            node.addEventListener("mouseleave", clearActive);
            node.addEventListener("focus", (event) => {
                const index = Number(node.dataset.legendIndex);
                setActive(index, event);
            });
            node.addEventListener("blur", clearActive);
        });

        panel.addEventListener("mouseleave", clearActive);

        grid.setAttribute("data-chart-hidden", "true");
        grid.setAttribute("aria-hidden", "true");
        grid.hidden = true;
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#39;");
    }

    function init() {
        const grid = document.querySelector(GRID_SELECTOR);
        const cards = Array.from(document.querySelectorAll(CARD_SELECTOR));

        if (!grid || cards.length === 0) return;

        const items = cards.map((card, index) => {
            const label = card.querySelector("span")?.textContent?.trim() || `Stat ${index + 1}`;
            const valueText = card.querySelector("strong")?.textContent?.trim() || "0";
            const value = Math.max(0, parseNumber(valueText));
            return {
                label,
                value,
                valueText,
                color: COLORS[index % COLORS.length],
            };
        });

        renderPieChart(grid, items);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, { once: true });
    } else {
        init();
    }
})();
