(() => {
    const state = {
        theme: localStorage.getItem("glassAdminTheme") || "dark",
        sidebarCollapsed:
            localStorage.getItem("glassAdminSidebarCollapsed") === "true",
        activeView: "dashboard",
        activityPage: 1,
        productPage: 1,
        wizardStep: 1,
        confirmCallback: null,
        confirmVariant: "warn",
    };

    // viewMeta removed — navbar no longer displays per-view title/subtitle

    const activityRows = [
        [
            "2m ago",
            "POST /v1/chat/completions",
            "Acme Corp",
            "200",
            "120ms",
            "203.0.113.25",
        ],
        [
            "3m ago",
            "POST /v1/embeddings",
            "Globex Inc.",
            "200",
            "98ms",
            "198.51.100.42",
        ],
        [
            "5m ago",
            "POST /v1/images/generations",
            "Stark Industries",
            "200",
            "245ms",
            "203.0.113.15",
        ],
        [
            "7m ago",
            "POST /v1/speech/transcriptions",
            "Wayne Enterprises",
            "429",
            "302ms",
            "192.0.2.10",
        ],
        [
            "8m ago",
            "POST /v1/chat/completions",
            "Acme Corp",
            "200",
            "110ms",
            "203.0.113.25",
        ],
        [
            "13m ago",
            "POST /v1/audio/speech",
            "Cyberdyne Systems",
            "200",
            "154ms",
            "203.0.113.61",
        ],
        [
            "18m ago",
            "GET /v1/models",
            "Umbrella Corp",
            "200",
            "41ms",
            "192.0.2.80",
        ],
        [
            "22m ago",
            "POST /v1/chat/completions",
            "Oscorp",
            "503",
            "611ms",
            "203.0.113.12",
        ],
        [
            "26m ago",
            "GET /health",
            "Wayne Enterprises",
            "200",
            "11ms",
            "198.51.100.88",
        ],
        [
            "31m ago",
            "POST /v1/embeddings",
            "Globex Inc.",
            "200",
            "101ms",
            "192.0.2.38",
        ],
    ];

    const products = [
        {
            name: "Green Tea 250ml",
            sku: "GT-025",
            category: "Beverages",
            stock: 88,
            price: "$1.80",
            status: "Active",
        },
        {
            name: "Milk 1L",
            sku: "MK-100",
            category: "Dairy",
            stock: 12,
            price: "$2.15",
            status: "Near expiry",
        },
        {
            name: "Soap Refill",
            sku: "SP-330",
            category: "Household",
            stock: 320,
            price: "$0.95",
            status: "Active",
        },
        {
            name: "Coffee Sachet",
            sku: "CF-110",
            category: "Beverages",
            stock: 58,
            price: "$0.40",
            status: "Featured",
        },
        {
            name: "Rice 5kg",
            sku: "RC-500",
            category: "Staples",
            stock: 19,
            price: "$5.20",
            status: "Active",
        },
        {
            name: "Instant Noodle",
            sku: "IN-011",
            category: "Staples",
            stock: 142,
            price: "$0.32",
            status: "Active",
        },
        {
            name: "Mineral Water",
            sku: "MW-605",
            category: "Beverages",
            stock: 240,
            price: "$0.25",
            status: "Featured",
        },
        {
            name: "Shampoo 340ml",
            sku: "SH-340",
            category: "Personal Care",
            stock: 73,
            price: "$2.95",
            status: "Active",
        },
        {
            name: "Dish Soap",
            sku: "DS-120",
            category: "Household",
            stock: 44,
            price: "$1.15",
            status: "Active",
        },
        {
            name: "Chocolate Bar",
            sku: "CH-090",
            category: "Snacks",
            stock: 89,
            price: "$0.60",
            status: "Active",
        },
        {
            name: "Potato Chips",
            sku: "PC-220",
            category: "Snacks",
            stock: 51,
            price: "$0.85",
            status: "Active",
        },
        {
            name: "Oat Milk",
            sku: "OM-220",
            category: "Dairy",
            stock: 21,
            price: "$2.90",
            status: "Near expiry",
        },
        {
            name: "Energy Drink",
            sku: "ED-330",
            category: "Beverages",
            stock: 64,
            price: "$1.60",
            status: "Featured",
        },
        {
            name: "Toothpaste",
            sku: "TP-180",
            category: "Personal Care",
            stock: 97,
            price: "$1.30",
            status: "Active",
        },
        {
            name: "Laundry Detergent",
            sku: "LD-700",
            category: "Household",
            stock: 36,
            price: "$3.40",
            status: "Active",
        },
        {
            name: "Biscuits",
            sku: "BC-440",
            category: "Snacks",
            stock: 112,
            price: "$0.72",
            status: "Active",
        },
        {
            name: "Cooking Oil",
            sku: "CO-120",
            category: "Staples",
            stock: 18,
            price: "$4.10",
            status: "Low stock",
        },
        {
            name: "Tissue Pack",
            sku: "TS-031",
            category: "Household",
            stock: 190,
            price: "$0.55",
            status: "Active",
        },
        {
            name: "Yogurt",
            sku: "YG-101",
            category: "Dairy",
            stock: 16,
            price: "$1.20",
            status: "Near expiry",
        },
        {
            name: "Soda Can",
            sku: "SD-007",
            category: "Beverages",
            stock: 210,
            price: "$0.48",
            status: "Active",
        },
        {
            name: "Face Mask",
            sku: "FM-044",
            category: "Personal Care",
            stock: 140,
            price: "$0.18",
            status: "Active",
        },
        {
            name: "Soap Bar",
            sku: "SB-014",
            category: "Household",
            stock: 260,
            price: "$0.22",
            status: "Active",
        },
        {
            name: "Granola",
            sku: "GR-810",
            category: "Snacks",
            stock: 34,
            price: "$1.88",
            status: "Active",
        },
        {
            name: "Black Coffee",
            sku: "BK-140",
            category: "Beverages",
            stock: 66,
            price: "$0.68",
            status: "Featured",
        },
    ];

    const alerts = [
        {
            title: "High Error Rate Detected",
            meta: "AI Chat Completion API",
            time: "2m ago",
            tone: "danger",
            icon: "#icon-warning",
        },
        {
            title: "Rate Limit Exceeded",
            meta: "Consumer: Cyberdyne Systems",
            time: "5m ago",
            tone: "warn",
            icon: "#icon-lock",
        },
        {
            title: "Unusual Traffic Spike",
            meta: "API /v1/images/generations",
            time: "15m ago",
            tone: "warn",
            icon: "#icon-chart",
        },
        {
            title: "Failed Authentication Attempts",
            meta: "Multiple consumers",
            time: "23m ago",
            tone: "danger",
            icon: "#icon-warning",
        },
    ];

    const distribution = [
        { label: "AI Chat Completion API", value: 30.3, color: "#5b8cff" },
        { label: "Text Embeddings API", value: 21.4, color: "#38d6b0" },
        { label: "Image Generation API", value: 16.9, color: "#8f69ff" },
        { label: "Speech to Text API", value: 12.1, color: "#ffb44c" },
        { label: "Text to Speech API", value: 9.7, color: "#ff5f7c" },
        { label: "Others", value: 9.6, color: "#8e9ab8" },
    ];

    const navItems = Array.from(
        document.querySelectorAll(".nav-item[data-view]"),
    );
    const views = Array.from(
        document.querySelectorAll(".view[data-view-panel]"),
    );
    const groupPills = Array.from(
        document.querySelectorAll(".group-pill[data-group]"),
    );
    const themeVeil = document.getElementById("themeVeil");

    // Maps each view id to its sidebar group id, derived straight from the DOM
    // so the sidebar structure stays the single source of truth.
    const viewGroupMap = navItems.reduce((map, item) => {
        const group = item.closest(".nav-group");
        if (group && item.dataset.view) {
            map[item.dataset.view] = group.dataset.group;
        }
        return map;
    }, {});
    const themeToggle = document.querySelector('[data-action="theme-toggle"]');
    const sidebarToggle = document.querySelector(
        '[data-action="sidebar-toggle"]',
    );
    const openWizardButtons = document.querySelectorAll(
        '[data-action="open-wizard"]',
    );
    const goViewButtons = document.querySelectorAll('[data-action="go-view"]');
    const activityBody = document.getElementById("activityTableBody");
    const activityPrev = document.getElementById("activityPrev");
    const activityNext = document.getElementById("activityNext");
    const activityPageInfo = document.getElementById("activityPageInfo");
    const alertList = document.getElementById("alertList");
    const productBody = document.getElementById("productTableBody");
    const distributionLegend = document.getElementById("distributionLegend");
    const trafficChart = document.getElementById("trafficChart");
    const distributionChart = document.getElementById("distributionChart");
    const forecastRing = document.getElementById("forecastRing");
    const wizardModal = document.querySelector('[data-modal="wizard"]');
    const confirmModal = document.querySelector('[data-modal="confirm"]');
    const wizardProgress = document.getElementById("wizardProgressBar");
    const wizardLabel = document.getElementById("wizardLabel");
    const wizardHint = document.getElementById("wizardHint");
    const confirmTitle = document.getElementById("confirmTitle");
    const confirmMessage = document.getElementById("confirmMessage");
    const confirmIcon = document.getElementById("confirmIcon");
    const wizardSteps = Array.from(document.querySelectorAll(".wizard-step"));
    const stepIndicators = Array.from(
        document.querySelectorAll("[data-step-indicator]"),
    );
    const wizardBack = document.querySelector('[data-action="wizard-back"]');
    const wizardNext = document.querySelector('[data-action="wizard-next"]');
    const wizardSkip = document.querySelector('[data-action="wizard-skip"]');
    const confirmOk = document.querySelector('[data-action="confirm-ok"]');
    const cancelConfirm = document.querySelector(
        '[data-action="cancel-confirm"]',
    );
    const closeModalButtons = document.querySelectorAll(
        '[data-action="close-modal"]',
    );
    const toastStack = document.getElementById("toastStack");
    const body = document.body;
    const themeChoiceInputs = document.querySelectorAll(
        'input[name="theme-choice"]',
    );
    const switches = document.querySelectorAll(".switch");
    const searchInput = document.querySelector(
        '.search-field input[type="search"]',
    );
    const productPagination = document.querySelector(
        'section[data-view-panel="products"] .pagination--tight',
    );
    const productPrev = productPagination?.querySelector(
        "button.btn--secondary:first-child",
    );
    const productNext = productPagination?.querySelector(
        "button.btn--secondary:last-child",
    );
    const productPageInfo =
        productPagination?.querySelector(".pagination__meta");

    const perPage = {
        activity: 5,
        products: 5,
    };

    function escapeHtml(value) {
        return String(value)
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#39;");
    }

    const iconClassMap = {
        "#icon-logo": "fa-solid fa-shield-halved",
        "#icon-menu": "fa-solid fa-bars",
        "#icon-dashboard": "fa-solid fa-gauge-high",
        "#icon-products": "fa-solid fa-box",
        "#icon-inventory": "fa-solid fa-cubes",
        "#icon-cart": "fa-solid fa-cart-shopping",
        "#icon-ai": "fa-solid fa-robot",
        "#icon-people": "fa-solid fa-users",
        "#icon-settings": "fa-solid fa-gear",
        "#icon-search": "fa-solid fa-magnifying-glass",
        "#icon-moon": "fa-solid fa-moon",
        "#icon-sun": "fa-solid fa-sun",
        "#icon-bell": "fa-solid fa-bell",
        "#icon-chevron": "fa-solid fa-chevron-right",
        "#icon-plus": "fa-solid fa-plus",
        "#icon-edit": "fa-solid fa-pen-to-square",
        "#icon-trash": "fa-solid fa-trash",
        "#icon-chart": "fa-solid fa-chart-column",
        "#icon-health": "fa-solid fa-heart-pulse",
        "#icon-warning": "fa-solid fa-triangle-exclamation",
        "#icon-lock": "fa-solid fa-lock",
        "#icon-briefcase": "fa-solid fa-briefcase",
        "#icon-chat": "fa-solid fa-comments",
        "#icon-customer": "fa-solid fa-user-group",
    };

    function renderIcon(iconRef, extraClasses = "") {
        const classes = [
            "icon",
            extraClasses,
            iconClassMap[iconRef] || "fa-solid fa-circle",
        ]
            .filter(Boolean)
            .join(" ");
        return `<i class="${classes}" aria-hidden="true"></i>`;
    }

    function setTheme(theme) {
        state.theme = theme;
        document.documentElement.dataset.theme = theme;
        localStorage.setItem("glassAdminTheme", theme);

        themeChoiceInputs.forEach((input, idx) => {
            input.checked =
                (theme === "dark" && idx === 0) ||
                (theme === "light" && idx === 1);
        });
    }

    function setSidebarCollapsed(collapsed) {
        state.sidebarCollapsed = collapsed;
        body.classList.toggle("sidebar-collapsed", collapsed);
        localStorage.setItem("glassAdminSidebarCollapsed", String(collapsed));
    }

    function updateActiveNav(view) {
        navItems.forEach((item) =>
            item.classList.toggle("active", item.dataset.view === view),
        );
    }

    function updateActiveGroup(view) {
        const activeGroup = viewGroupMap[view];
        groupPills.forEach((pill) =>
            pill.classList.toggle("active", pill.dataset.group === activeGroup),
        );
    }

    function setView(view) {
        state.activeView = view;
        views.forEach((section) =>
            section.classList.toggle(
                "active",
                section.dataset.viewPanel === view,
            ),
        );
        updateActiveNav(view);
        updateActiveGroup(view);
        document.title = "Glass Admin Template";
    }

    function openModal(modalEl) {
        if (!modalEl) return;
        modalEl.hidden = false;
        requestAnimationFrame(() => modalEl.classList.add("is-visible"));
    }

    function closeModal(modalEl) {
        if (!modalEl) return;
        modalEl.classList.remove("is-visible");
        window.setTimeout(() => {
            modalEl.hidden = true;
        }, 220);
    }

    function toast(title, message, variant = "success") {
        const el = document.createElement("div");
        el.className = `toast toast--${variant}`;
        el.innerHTML = `<strong>${escapeHtml(title)}</strong><span>${escapeHtml(message)}</span>`;
        toastStack.appendChild(el);
        requestAnimationFrame(() => el.classList.add("is-visible"));
        window.setTimeout(() => {
            el.style.opacity = "0";
            el.style.transform = "translateY(8px)";
            window.setTimeout(() => el.remove(), 260);
        }, 2800);
    }

    function openConfirm({
        title,
        message,
        variant = "warn",
        icon = "#icon-warning",
        onConfirm,
    }) {
        state.confirmCallback =
            typeof onConfirm === "function" ? onConfirm : null;
        state.confirmVariant = variant;

        confirmModal.dataset.variant = variant;
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        confirmIcon.innerHTML = renderIcon(icon);
        confirmOk.textContent =
            variant === "danger"
                ? "Delete"
                : variant === "info"
                  ? "Continue"
                  : "Confirm";
        confirmOk.className = `btn ${variant === "danger" ? "btn--danger" : "btn--primary"}`;

        openModal(confirmModal);
    }

    function getPageSlice(list, page, limit) {
        const totalPages = Math.max(1, Math.ceil(list.length / limit));
        const safePage = Math.min(Math.max(page, 1), totalPages);
        const start = (safePage - 1) * limit;
        return {
            items: list.slice(start, start + limit),
            page: safePage,
            totalPages,
        };
    }

    function renderSparklines() {
        document.querySelectorAll(".sparkline").forEach((node) => {
            const values = node.dataset.sparkline.split(",").map(Number);
            const w = 220;
            const h = 52;
            const pad = 6;
            const max = Math.max(...values);
            const min = Math.min(...values);
            const range = Math.max(max - min, 1);
            const points = values.map((value, i) => {
                const x = pad + (i / (values.length - 1)) * (w - pad * 2);
                const y = h - pad - ((value - min) / range) * (h - pad * 2);
                return [x, y];
            });

            const line = `M ${points.map((p) => p.join(",")).join(" L ")}`;
            const area = `${line} L ${w - pad},${h - pad} L ${pad},${h - pad} Z`;

            node.innerHTML = `
        <svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" aria-hidden="true">
          <path class="area" d="${area}" fill="var(--accent)" opacity="0.15"></path>
          <path class="line" d="${line}" fill="none" stroke="var(--accent)" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"></path>
        </svg>
      `;
        });
    }

    function renderLineChart() {
        if (!trafficChart) return;
        const values = [58, 64, 52, 74, 69, 88, 81, 93, 85, 98, 89, 97];
        const labels = [
            "00:00",
            "03:00",
            "06:00",
            "09:00",
            "12:00",
            "15:00",
            "18:00",
            "21:00",
        ];
        const w = 900;
        const h = 320;
        const pad = 30;
        const max = Math.max(...values);
        const min = Math.min(...values);
        const range = Math.max(max - min, 1);
        const pts = values.map((value, i) => {
            const x = pad + (i / (values.length - 1)) * (w - pad * 2);
            const y = h - pad - ((value - min) / range) * (h - pad * 2);
            return [x, y];
        });
        const d = `M ${pts.map((p) => p.join(",")).join(" L ")}`;
        const area = `${d} L ${w - pad},${h - pad} L ${pad},${h - pad} Z`;

        trafficChart.innerHTML = `
      <svg viewBox="0 0 ${w} ${h}" preserveAspectRatio="none" aria-label="Traffic chart">
        <defs>
          <linearGradient id="lineFill" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="rgba(91,140,255,0.54)"></stop>
            <stop offset="100%" stop-color="rgba(91,140,255,0)"></stop>
          </linearGradient>
          <linearGradient id="lineStroke" x1="0" x2="1" y1="0" y2="0">
            <stop offset="0%" stop-color="#5b8cff"></stop>
            <stop offset="55%" stop-color="#8f69ff"></stop>
            <stop offset="100%" stop-color="#38d6b0"></stop>
          </linearGradient>
        </defs>
        <g opacity="0.24">
          ${Array.from({ length: 6 })
              .map((_, i) => {
                  const y = pad + i * ((h - pad * 2) / 5);
                  return `<line x1="${pad}" y1="${y}" x2="${w - pad}" y2="${y}" stroke="var(--grid-line)" stroke-width="1" />`;
              })
              .join("")}
        </g>
        <path d="${area}" fill="url(#lineFill)" opacity="0.9"></path>
        <path d="${d}" fill="none" stroke="url(#lineStroke)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></path>
        ${pts.map(([x, y]) => `<circle cx="${x}" cy="${y}" r="4.5" fill="var(--bg-panel-2)" stroke="var(--accent)" stroke-width="2"></circle>`).join("")}
        ${labels
            .map((label, i) => {
                const x = pad + (i / (labels.length - 1)) * (w - pad * 2);
                return `<text x="${x}" y="${h - 8}" text-anchor="middle" fill="var(--text-soft)" font-size="12" font-weight="600">${label}</text>`;
            })
            .join("")}
      </svg>
    `;
    }

    function renderDistribution() {
        if (!distributionChart || !distributionLegend) return;
        const total = distribution.reduce((acc, item) => acc + item.value, 0);
        let cursor = 0;
        const segments = distribution
            .map((item) => {
                const start = cursor;
                cursor += item.value;
                return `${item.color} ${start}% ${cursor}%`;
            })
            .join(", ");

        distributionChart.innerHTML = `
      <div class="donut-shell" style="--segments: ${segments};">
        <div class="donut-core">
          <strong>${total.toFixed(2)}M</strong>
          <span>Total</span>
        </div>
      </div>
    `;

        distributionLegend.innerHTML = distribution
            .map(
                (item) => `
      <div class="legend-item">
        <span class="legend-label"><i class="legend-swatch" style="background:${item.color}"></i>${escapeHtml(item.label)}</span>
        <strong>${item.value.toFixed(1)}%</strong>
      </div>
    `,
            )
            .join("");
    }

    function renderForecast() {
        if (!forecastRing) return;
        const days = 5;
        const progress = 100 - (days / 14) * 100;
        forecastRing.style.background = `
      radial-gradient(circle at center, var(--bg-panel) 0 58%, transparent 59%),
      conic-gradient(var(--accent) 0 ${progress}%, rgba(255,255,255,0.08) ${progress}% 100%)
    `;
        forecastRing.dataset.days = String(days);
        forecastRing.innerHTML = "";
    }

    function renderAlerts() {
        if (!alertList) return;
        alertList.innerHTML = alerts
            .map(
                (item) => `
      <div class="alert-item ${item.tone}">
        <div class="alert-item__meta">
          <strong>${escapeHtml(item.title)}</strong>
          <small>${escapeHtml(item.meta)}</small>
        </div>
        <small>${escapeHtml(item.time)}</small>
      </div>
    `,
            )
            .join("");
    }

    function renderActivityTable() {
        if (
            !activityBody ||
            !activityPageInfo ||
            !activityPrev ||
            !activityNext
        )
            return;
        const { items, page, totalPages } = getPageSlice(
            activityRows,
            state.activityPage,
            perPage.activity,
        );
        state.activityPage = page;
        activityPageInfo.textContent = `Page ${page} of ${totalPages}`;
        activityPrev.disabled = page === 1;
        activityNext.disabled = page === totalPages;

        activityBody.innerHTML = items
            .map((row) => {
                const status = row[3];
                const statusClass =
                    status === "200" ? "ok" : status === "429" ? "warn" : "bad";
                return `
        <tr class="fade-in">
          <td>${escapeHtml(row[0])}</td>
          <td>${escapeHtml(row[1])}</td>
          <td>${escapeHtml(row[2])}</td>
          <td><span class="status-tag ${statusClass}">${escapeHtml(status)}</span></td>
          <td>${escapeHtml(row[4])}</td>
          <td>${escapeHtml(row[5])}</td>
          <td class="actions-cell">
            <button class="action-btn" type="button" data-action="inspect-row" data-label="${escapeHtml(row[1])}" aria-label="Inspect row">
              ${renderIcon("#icon-search")}
            </button>
            <button class="action-btn danger" type="button" data-action="flag-row" data-label="${escapeHtml(row[1])}" aria-label="Flag row">
              ${renderIcon("#icon-warning")}
            </button>
          </td>
        </tr>
      `;
            })
            .join("");
    }

    function renderProductsTable() {
        if (
            !productBody ||
            !productPagination ||
            !productPageInfo ||
            !productPrev ||
            !productNext
        )
            return;
        const { items, page, totalPages } = getPageSlice(
            products,
            state.productPage,
            perPage.products,
        );
        state.productPage = page;
        productPageInfo.textContent = `Showing ${(page - 1) * perPage.products + 1}–${Math.min(page * perPage.products, products.length)} of ${products.length}`;
        productPrev.disabled = page === 1;
        productNext.disabled = page === totalPages;

        productBody.innerHTML = items
            .map((item) => {
                const statusClass =
                    item.status.toLowerCase().includes("near") ||
                    item.status.toLowerCase().includes("low")
                        ? "warn"
                        : "ok";
                return `
        <tr class="fade-in">
          <td>${escapeHtml(item.name)}</td>
          <td>${escapeHtml(item.sku)}</td>
          <td>${escapeHtml(item.category)}</td>
          <td>${escapeHtml(String(item.stock))}</td>
          <td>${escapeHtml(item.price)}</td>
          <td><span class="status-tag ${statusClass}">${escapeHtml(item.status)}</span></td>
          <td class="actions-cell">
            <button class="action-btn" type="button" data-action="edit" data-name="${escapeHtml(item.name)}" aria-label="Edit ${escapeHtml(item.name)}">
              ${renderIcon("#icon-edit")}
            </button>
            <button class="action-btn danger" type="button" data-action="delete" data-name="${escapeHtml(item.name)}" aria-label="Delete ${escapeHtml(item.name)}">
              ${renderIcon("#icon-trash")}
            </button>
          </td>
        </tr>
      `;
            })
            .join("");
    }

    function updateWizard() {
        if (
            !wizardProgress ||
            !wizardLabel ||
            !wizardHint ||
            wizardSteps.length === 0 ||
            stepIndicators.length === 0
        )
            return;

        wizardSteps.forEach((step) =>
            step.classList.toggle(
                "active",
                Number(step.dataset.step) === state.wizardStep,
            ),
        );
        stepIndicators.forEach((indicator) =>
            indicator.classList.toggle(
                "active",
                Number(indicator.dataset.stepIndicator) === state.wizardStep,
            ),
        );

        const progress = ((state.wizardStep - 1) / 2) * 100;
        wizardProgress.style.width = `${Math.max(progress, 10)}%`;

        const labels = [
            ["Step 1 of 3", "Basic info, identity, and categorization"],
            ["Step 2 of 3", "Pricing, stock, suppliers, and tracking"],
            ["Step 3 of 3", "Media, keywords, and publish review"],
        ];

        wizardLabel.textContent = labels[state.wizardStep - 1][0];
        wizardHint.textContent = labels[state.wizardStep - 1][1];
        wizardNext.textContent =
            state.wizardStep === 3 ? "Create Product" : "Next";
    }

    function openWizard() {
        state.wizardStep = 1;
        updateWizard();
        openModal(wizardModal);
    }

    function playThemeVeil(originEl) {
        if (!themeVeil) return;
        if (originEl) {
            const rect = originEl.getBoundingClientRect();
            const x = ((rect.left + rect.width / 2) / window.innerWidth) * 100;
            const y = ((rect.top + rect.height / 2) / window.innerHeight) * 100;
            themeVeil.style.setProperty("--veil-x", `${x}%`);
            themeVeil.style.setProperty("--veil-y", `${y}%`);
        }
        themeVeil.classList.remove("is-active");
        // eslint-disable-next-line no-unused-expressions
        void themeVeil.offsetWidth; // restart animation
        themeVeil.classList.add("is-active");
    }

    function bindThemeControls() {
        themeToggle?.addEventListener("click", () => {
            playThemeVeil(themeToggle);
            setTheme(state.theme === "dark" ? "light" : "dark");
            toast("Theme updated", `Switched to ${state.theme} mode`, "info");
        });

        themeChoiceInputs.forEach((input, idx) => {
            input.addEventListener("change", () => {
                if (input.checked) {
                    setTheme(idx === 0 ? "dark" : "light");
                }
            });
        });
    }

    function bindSidebar() {
        sidebarToggle?.addEventListener("click", () => {
            setSidebarCollapsed(!state.sidebarCollapsed);
        });
    }

    function bindViews() {
        navItems.forEach((item) => {
            item.addEventListener("click", () => setView(item.dataset.view));
        });

        goViewButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const view = button.dataset.go;
                if (view) setView(view);
            });
        });
    }

    function bindWizard() {
        openWizardButtons.forEach((button) => {
            button.addEventListener("click", openWizard);
        });

        wizardBack?.addEventListener("click", () => {
            if (state.wizardStep > 1) {
                state.wizardStep -= 1;
                updateWizard();
            }
        });

        wizardNext?.addEventListener("click", () => {
            if (state.wizardStep < 3) {
                state.wizardStep += 1;
                updateWizard();
                return;
            }

            openConfirm({
                title: "Create product?",
                message:
                    "Confirm creation of this new product using the wizard summary.",
                variant: "warn",
                icon: "#icon-plus",
                onConfirm: () => {
                    closeModal(wizardModal);
                    toast(
                        "Created",
                        "Product draft has been created successfully.",
                        "success",
                    );
                },
            });
        });

        wizardSkip?.addEventListener("click", () => {
            closeModal(wizardModal);
            toast(
                "Wizard skipped",
                "No product was created from this draft flow.",
                "info",
            );
        });
    }

    function bindConfirmation() {
        closeModalButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const modal = button.closest(".modal-backdrop");
                closeModal(modal);
            });
        });

        cancelConfirm?.addEventListener("click", () =>
            closeModal(confirmModal),
        );

        confirmOk?.addEventListener("click", () => {
            if (typeof state.confirmCallback === "function") {
                const callback = state.confirmCallback;
                state.confirmCallback = null;
                callback();
            }
            closeModal(confirmModal);
        });
    }

    function bindTables() {
        activityPrev?.addEventListener("click", () => {
            state.activityPage = Math.max(1, state.activityPage - 1);
            renderActivityTable();
        });

        activityNext?.addEventListener("click", () => {
            const totalPages = Math.ceil(
                activityRows.length / perPage.activity,
            );
            state.activityPage = Math.min(totalPages, state.activityPage + 1);
            renderActivityTable();
        });

        if (productPrev && productNext) {
            productPrev.addEventListener("click", () => {
                state.productPage = Math.max(1, state.productPage - 1);
                renderProductsTable();
            });

            productNext.addEventListener("click", () => {
                const totalPages = Math.ceil(
                    products.length / perPage.products,
                );
                state.productPage = Math.min(totalPages, state.productPage + 1);
                renderProductsTable();
            });
        }

        document.addEventListener("click", (event) => {
            const button = event.target.closest("[data-action]");
            if (!button) return;

            const action = button.dataset.action;
            const name =
                button.dataset.name || button.dataset.label || "this item";

            if (action === "edit") {
                openConfirm({
                    title: `Edit ${name}?`,
                    message: "Open the edit form with existing data prefilled.",
                    variant: "info",
                    icon: "#icon-edit",
                    onConfirm: () =>
                        toast(
                            "Edit ready",
                            `${name} is ready for editing.`,
                            "info",
                        ),
                });
            }

            if (action === "delete") {
                openConfirm({
                    title: `Delete ${name}?`,
                    message: "This action is destructive and cannot be undone.",
                    variant: "danger",
                    icon: "#icon-trash",
                    onConfirm: () =>
                        toast(
                            "Deleted",
                            `${name} has been removed from the list.`,
                            "danger",
                        ),
                });
            }

            if (action === "inspect-row") {
                toast("Inspection", `Reviewing ${name}.`, "info");
            }

            if (action === "flag-row") {
                openConfirm({
                    title: `Flag ${name}?`,
                    message:
                        "Flag this activity entry for review by the admin team.",
                    variant: "warn",
                    icon: "#icon-warning",
                    onConfirm: () =>
                        toast(
                            "Flagged",
                            `${name} was flagged for review.`,
                            "warn",
                        ),
                });
            }
        });
    }

    function bindKeyboard() {
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                if (confirmModal && !confirmModal.hidden)
                    closeModal(confirmModal);
                if (wizardModal && !wizardModal.hidden) closeModal(wizardModal);
            }

            if (
                (event.ctrlKey || event.metaKey) &&
                event.key.toLowerCase() === "k"
            ) {
                event.preventDefault();
                searchInput?.focus();
            }
        });
    }

    function bindSwitches() {
        switches.forEach((switchBtn) => {
            switchBtn.addEventListener("click", () => {
                const active = switchBtn.classList.toggle("switch--active");
                switchBtn.setAttribute("aria-pressed", String(active));
            });
        });
    }

    // skeleton loading removed; no finishLoading function

    function init() {
        setTheme(state.theme);
        setSidebarCollapsed(state.sidebarCollapsed);
        setView(state.activeView);

        renderSparklines();
        renderLineChart();
        renderDistribution();
        renderForecast();
        renderAlerts();
        renderActivityTable();
        renderProductsTable();
        updateWizard();

        bindThemeControls();
        bindSidebar();
        bindViews();
        bindWizard();
        bindConfirmation();
        bindTables();
        bindKeyboard();
        bindSwitches();

        // previously waited to remove skeleton; no longer needed
    }

    window.addEventListener("DOMContentLoaded", init);
})();
