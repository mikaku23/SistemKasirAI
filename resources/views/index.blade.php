<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Glass UI Template</title>
  <meta name="description" content="Dark-first admin UI template with glass cards, sidebar toggle, light mode, skeleton loading, tables, wizard forms, and confirmation modals." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
</head>
<body class="loading">
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
      <div class="sidebar__brand">
        <button class="icon-btn sidebar__toggle" type="button" data-action="sidebar-toggle" aria-label="Toggle sidebar">
          <i class="icon fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="brand-mark">
          <i class="icon icon--brand fa-solid fa-shield-halved" aria-hidden="true"></i>
        </div>
        <div class="brand-copy">
          <span class="brand-kicker">AI Admin System</span>
          <strong>Glass Console</strong>
        </div>
      </div>

      <nav class="sidebar-nav" aria-label="Sidebar navigation">
        <div class="nav-group" data-group="gateway">
          <p class="nav-group__label">Gateway</p>
          <button class="nav-item active" type="button" data-view="dashboard">
            <i class="icon fa-solid fa-gauge-high" aria-hidden="true"></i><span>Dashboard</span>
          </button>
        </div>

        <div class="nav-group" data-group="catalog">
          <p class="nav-group__label">Catalog</p>
          <button class="nav-item" type="button" data-view="products">
            <i class="icon fa-solid fa-box" aria-hidden="true"></i><span>Products</span>
          </button>
          <button class="nav-item" type="button" data-view="inventory">
            <i class="icon fa-solid fa-cubes" aria-hidden="true"></i><span>Inventory</span>
          </button>
        </div>

        <div class="nav-group" data-group="intelligence">
          <p class="nav-group__label">Intelligence</p>
          <button class="nav-item" type="button" data-view="ai">
            <i class="icon fa-solid fa-robot" aria-hidden="true"></i><span>AI Center</span>
          </button>
          <button class="nav-item" type="button" data-view="customers">
            <i class="icon fa-solid fa-users" aria-hidden="true"></i><span>Customers</span>
          </button>
        </div>

        <div class="nav-group" data-group="security">
          <p class="nav-group__label">Security</p>
          <button class="nav-item" type="button" data-view="security">
            <i class="icon fa-solid fa-lock" aria-hidden="true"></i><span>Access Control</span>
          </button>
        </div>

        <div class="nav-group" data-group="system">
          <p class="nav-group__label">System</p>
          <button class="nav-item" type="button" data-view="settings">
            <i class="icon fa-solid fa-gear" aria-hidden="true"></i><span>Settings</span>
          </button>
        </div>
      </nav>

      <div class="sidebar__footer glass-card">
        <div class="status-dot status-dot--online"></div>
        <div>
          <p class="muted">System Status</p>
          <strong>Operational</strong>
        </div>
        <div class="sidebar__meta">
          <span>v2.4.1</span>
          <small>99.99% uptime</small>
        </div>
      </div>
    </aside>

    <div class="workspace">
      <header class="topbar glass-card">
        <div class="topbar__title">
          <p class="eyebrow">AI API Gateway</p>
          <div class="group-tabs" id="groupTabs" aria-hidden="true">
            <span class="group-pill" data-group="gateway">Gateway</span>
            <span class="group-pill" data-group="catalog">Catalog</span>
            <span class="group-pill" data-group="intelligence">Intelligence</span>
            <span class="group-pill" data-group="security">Security</span>
            <span class="group-pill" data-group="system">System</span>
          </div>
        </div>

        <div class="topbar__actions">
          <label class="search-field glass-input" aria-label="Search">
            <i class="icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" placeholder="Search products, logs, AI..." />
            <kbd>Ctrl K</kbd>
          </label>
          <button class="icon-btn" type="button" data-action="theme-toggle" aria-label="Toggle theme">
            <i class="icon theme-icon theme-icon--moon fa-solid fa-moon" aria-hidden="true"></i>
            <i class="icon theme-icon theme-icon--sun fa-solid fa-sun" aria-hidden="true"></i>
          </button>
          <button class="icon-btn" type="button" aria-label="Notifications">
            <i class="icon fa-solid fa-bell" aria-hidden="true"></i>
            <span class="badge badge--hot">3</span>
          </button>
          <div class="profile-chip">
            <div class="avatar">A</div>
            <div>
              <strong>Admin</strong>
              <small>Super Administrator</small>
            </div>
          </div>
        </div>
      </header>

      <main class="views">
        <!-- Dashboard -->
        <section class="view active" data-view-panel="dashboard">
          <div class="hero glass-card">
            <div>
              <div class="hero__label">
                <span class="pill pill--accent">Dark mode default</span>
                <span class="pill">Light mode ready</span>
              </div>
              <h2>Clean, modular, and lightweight admin interface.</h2>
              <p>This template includes sidebar collapse, theme switch, skeleton loading, wizard forms, liquid glass cards, tables, confirmation dialogs, charts, and responsive layouts.</p>
            </div>
            <div class="hero__actions">
              <button class="btn btn--primary" type="button" data-action="open-wizard">
                <i class="icon fa-solid fa-plus" aria-hidden="true"></i>
                Create Item
              </button>
              <button class="btn btn--ghost" type="button" data-action="go-view" data-go="ai">
                <i class="icon fa-solid fa-robot" aria-hidden="true"></i>
                AI Center
              </button>
            </div>
          </div>

          <section class="kpi-grid" aria-label="KPIs">
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Total Requests</span><i class="icon fa-solid fa-chart-column" aria-hidden="true"></i></div>
              <strong id="kpiRequests">24.58M</strong>
              <small>+18.2% vs last 24h</small>
              <div class="sparkline" data-sparkline="1,3,2,4,5,4,6,8,7,10"></div>
            </article>
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Successful</span><i class="icon fa-solid fa-heart-pulse" aria-hidden="true"></i></div>
              <strong id="kpiSuccess">23.68M</strong>
              <small>+20.5% vs last 24h</small>
              <div class="sparkline" data-sparkline="2,4,3,5,6,7,6,8,9,10"></div>
            </article>
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Error Rate</span><i class="icon fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
              <strong id="kpiError">0.89%</strong>
              <small>-8.7% vs last 24h</small>
              <div class="sparkline sparkline--danger" data-sparkline="6,5,7,6,5,4,4,3,2,2"></div>
            </article>
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Avg Response</span><i class="icon fa-solid fa-heart-pulse" aria-hidden="true"></i></div>
              <strong id="kpiResponse">142ms</strong>
              <small>-12.7% vs last 24h</small>
              <div class="sparkline" data-sparkline="8,8,7,6,6,5,4,4,3,3"></div>
            </article>
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Active APIs</span><i class="icon fa-solid fa-briefcase" aria-hidden="true"></i></div>
              <strong id="kpiApis">156</strong>
              <small>+7 vs last 24h</small>
              <div class="sparkline" data-sparkline="3,4,4,5,6,7,8,8,9,10"></div>
            </article>
            <article class="kpi-card glass-card">
              <div class="kpi-card__head"><span>Active Users</span><i class="icon fa-solid fa-users" aria-hidden="true"></i></div>
              <strong id="kpiConsumers">2.43K</strong>
              <small>+11.3% vs last 24h</small>
              <div class="sparkline" data-sparkline="1,2,2,3,4,4,5,6,6,7"></div>
            </article>
          </section>

          <section class="panel-grid">
            <article class="panel glass-card panel--wide">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Request Volume</p>
                  <h3>Traffic trend</h3>
                </div>
                <button class="chip-btn" type="button">View Analytics</button>
              </div>
              <div class="chart chart--line" id="trafficChart" aria-label="Traffic chart"></div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Requests by API</p>
                  <h3>Distribution</h3>
                </div>
                <button class="chip-btn" type="button">View All</button>
              </div>
              <div class="chart chart--donut" id="distributionChart" aria-label="Distribution chart"></div>
              <div class="legend" id="distributionLegend"></div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">System Health</p>
                  <h3>Live services</h3>
                </div>
              </div>
              <div class="health-list">
                <div class="health-item"><span>API Gateway Cluster</span><strong class="ok">Healthy</strong></div>
                <div class="health-item"><span>Rate Limiting Service</span><strong class="ok">Healthy</strong></div>
                <div class="health-item"><span>OAuth Service</span><strong class="ok">Healthy</strong></div>
                <div class="health-item"><span>Analytics Service</span><strong class="ok">Healthy</strong></div>
                <div class="health-item"><span>Log Aggregation</span><strong class="ok">Healthy</strong></div>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Alerts</p>
                  <h3>Watch list</h3>
                </div>
              </div>
              <div class="alert-list" id="alertList"></div>
            </article>
          </section>

          <section class="panel-grid">
            <article class="panel glass-card panel--wide">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Recent API Activity</p>
                  <h3>Paginated table</h3>
                </div>
                <div class="table-actions">
                  <button class="chip-btn" type="button">View All Activity</button>
                </div>
              </div>
              <div class="table-wrap">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>Time</th>
                      <th>Module</th>
                      <th>Consumer</th>
                      <th>Status</th>
                      <th>Response</th>
                      <th>IP</th>
                      <th class="th-actions">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="activityTableBody"></tbody>
                </table>
              </div>
              <div class="pagination">
                <button class="btn btn--secondary" type="button" id="activityPrev">
                  <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i> Back
                </button>
                <div class="pagination__meta">
                  <span id="activityPageInfo">Page 1 of 1</span>
                </div>
                <button class="btn btn--secondary" type="button" id="activityNext">
                  Next <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Quick Notes</p>
                  <h3>Design rules</h3>
                </div>
              </div>
              <ul class="bullet-list">
                <li>Short labels and compact navigation</li>
                <li>Glass cards with soft blur and glow</li>
                <li>Skeleton loading instead of spinners</li>
                <li>Clear hierarchy, charts, and tables</li>
                <li>Dark default with light theme option</li>
              </ul>
            </article>
          </section>
        </section>

        <!-- Products -->
        <section class="view" data-view-panel="products">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">Catalog</p>
              <h2>Products</h2>
              <p>Manage product cards, inline actions, filters, and a step-by-step creation wizard.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button" data-action="open-wizard">
                <i class="icon fa-solid fa-plus" aria-hidden="true"></i> New Product
              </button>
              <button class="btn btn--ghost" type="button">Import</button>
            </div>
          </div>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Search</p>
                  <h3>Filters</h3>
                </div>
              </div>
              <div class="filter-grid">
                <label class="form-field">
                  <span>Category</span>
                  <select>
                    <option>All Categories</option>
                    <option>Beverages</option>
                    <option>Snacks</option>
                    <option>Household</option>
                  </select>
                </label>
                <label class="form-field">
                  <span>Status</span>
                  <select>
                    <option>Active</option>
                    <option>Inactive</option>
                    <option>Featured</option>
                  </select>
                </label>
              </div>

              <div class="mini-stats">
                <div class="mini-stat">
                  <span>Featured</span><strong>24</strong>
                </div>
                <div class="mini-stat">
                  <span>Online</span><strong>1,240</strong>
                </div>
                <div class="mini-stat">
                  <span>Low Stock</span><strong class="warn">18</strong>
                </div>
              </div>

              <div class="stack">
                <article class="glass-card card-liquid">
                  <div class="card-liquid__title">
                    <i class="icon fa-solid fa-box" aria-hidden="true"></i>
                    <div>
                      <strong>Liquid Glass Card</strong>
                      <small>Polished surface and subtle glow</small>
                    </div>
                  </div>
                  <p>This card style is reused across dashboard, tables, forms, and AI panels.</p>
                </article>
                <article class="glass-card card-liquid">
                  <div class="card-liquid__title">
                    <i class="icon fa-solid fa-lock" aria-hidden="true"></i>
                    <div>
                      <strong>Secure Actions</strong>
                      <small>Confirmation required for destructive steps</small>
                    </div>
                  </div>
                  <p>Edit and delete actions trigger a themed modal before execution.</p>
                </article>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Creation Flow</p>
                  <h3>Multi-step wizard</h3>
                </div>
                <button class="chip-btn" type="button" data-action="open-wizard">Open</button>
              </div>

              <div class="stepper" aria-label="Wizard steps">
                <span class="step active" data-step-indicator="1">1</span>
                <span class="step" data-step-indicator="2">2</span>
                <span class="step" data-step-indicator="3">3</span>
              </div>

              <div class="wizard-preview">
                <div class="wizard-preview__progress"><span id="wizardProgressBar"></span></div>
                <div class="wizard-preview__meta">
                  <strong id="wizardLabel">Step 1 of 3</strong>
                  <small id="wizardHint">Basic info, identity, and categorization</small>
                </div>
              </div>

              <p class="muted">The modal includes progress indicators, next/back actions, and final confirmation.</p>
            </article>
          </section>

          <article class="panel glass-card panel--wide">
            <div class="panel__head">
              <div>
                <p class="eyebrow">Product table</p>
                <h3>Actions and confirmations</h3>
              </div>
              <button class="chip-btn" type="button">Export CSV</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="th-actions">Actions</th>
                  </tr>
                </thead>
                <tbody id="productTableBody"></tbody>
              </table>
            </div>
            <div class="pagination pagination--tight">
              <button class="btn btn--secondary" type="button">
                <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i> Back
              </button>
              <span class="pagination__meta">Showing 1–5 of 24</span>
              <button class="btn btn--secondary" type="button">
                Next <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
              </button>
            </div>
          </article>
        </section>

        <!-- Inventory -->
        <section class="view" data-view-panel="inventory">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">Inventory</p>
              <h2>Batch & Movement</h2>
              <p>Stock batches, FEFO/FIFO movement, restock forecasts, and clean operational tables.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button">
                <i class="icon fa-solid fa-plus" aria-hidden="true"></i> Receive Stock
              </button>
              <button class="btn btn--ghost" type="button">Stock Opname</button>
            </div>
          </div>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Restock</p>
                  <h3>Forecast</h3>
                </div>
              </div>
              <div class="forecast-card">
                <div class="forecast-ring" id="forecastRing"></div>
                <div class="forecast-copy">
                  <strong>5 days left</strong>
                  <p>Based on moving average sales and current stock.</p>
                  <div class="forecast-tags">
                    <span class="pill pill--ok">Safe</span>
                    <span class="pill pill--warn">14 near expiry</span>
                    <span class="pill pill--accent">FEFO active</span>
                  </div>
                </div>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Movement</p>
                  <h3>Live logs</h3>
                </div>
              </div>
              <ul class="movement-list">
                <li><span>+120</span><small>Incoming batch from supplier</small></li>
                <li><span>-18</span><small>POS checkout — FEFO applied</small></li>
                <li><span>-4</span><small>Return to supplier</small></li>
                <li><span>+7</span><small>Stock adjustment via opname</small></li>
              </ul>
            </article>
          </section>

          <article class="panel glass-card panel--wide">
            <div class="panel__head">
              <div>
                <p class="eyebrow">Stock batches</p>
                <h3>Current batch table</h3>
              </div>
              <button class="chip-btn" type="button">View Batch Rules</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Batch</th>
                    <th>Product</th>
                    <th>Received</th>
                    <th>Expired</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th class="th-actions">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>BT-00041</td><td>Green Tea</td><td>2026-07-30</td><td>2026-08-18</td><td>88</td><td><span class="status-tag ok">Active</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="BT-00041"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="BT-00041"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                  <tr>
                    <td>BT-00042</td><td>Milk 1L</td><td>2026-07-29</td><td>2026-08-03</td><td>12</td><td><span class="status-tag warn">Near expiry</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="BT-00042"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="BT-00042"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                  <tr>
                    <td>BT-00043</td><td>Soap Refill</td><td>2026-07-28</td><td>2026-09-12</td><td>320</td><td><span class="status-tag ok">Active</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="BT-00043"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="BT-00043"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </section>

        <!-- AI Center -->
        <section class="view" data-view-panel="ai">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">AI Core</p>
              <h2>Chatbot • CS • Product Search</h2>
              <p>Three channels, one core. Protected by guardrails, permissions, and ownership checks.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button">
                <i class="icon fa-solid fa-comments" aria-hidden="true"></i> Open Chat
              </button>
              <button class="btn btn--ghost" type="button">
                <i class="icon fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search Product
              </button>
            </div>
          </div>

          <section class="channel-grid">
            <article class="channel-card glass-card">
              <div class="channel-card__title">
                <i class="icon fa-solid fa-comments" aria-hidden="true"></i>
                <div>
                  <strong>Chatbot Website</strong>
                  <small>General help for visitors</small>
                </div>
              </div>
              <p>Answers FAQs, explains services, and routes complex issues to a handoff.</p>
            </article>

            <article class="channel-card glass-card">
              <div class="channel-card__title">
                <i class="icon fa-solid fa-user-group" aria-hidden="true"></i>
                <div>
                  <strong>AI Customer Service</strong>
                  <small>Restricted support for users</small>
                </div>
              </div>
              <p>Sandboxed by ownership checks so users cannot reach other users’ private data.</p>
            </article>

            <article class="channel-card glass-card">
              <div class="channel-card__title">
                <i class="icon fa-solid fa-box" aria-hidden="true"></i>
                <div>
                  <strong>AI Product Search</strong>
                  <small>Relevance and ranking</small>
                </div>
              </div>
              <p>Searches products using keywords, synonyms, ranking, and availability filters.</p>
            </article>
          </section>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Conversation</p>
                  <h3>AI UI mockup</h3>
                </div>
                <span class="pill pill--accent">Core Connected</span>
              </div>
              <div class="chat-box">
                <div class="chat bubble bubble--ai">Hello, I can help you with product search, account support, and FAQs.</div>
                <div class="chat bubble bubble--user">Find me the cheapest coffee sachet.</div>
                <div class="chat bubble bubble--ai">I found 5 relevant items. Showing the best match first.</div>
                <div class="chat composer glass-input">
                  <input type="text" placeholder="Type a message..." />
                  <button class="btn btn--primary" type="button">Send</button>
                </div>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Guardrails</p>
                  <h3>Secure AI rules</h3>
                </div>
              </div>
              <ul class="bullet-list bullet-list--secure">
                <li>Read-only by default</li>
                <li>No schema/database changes</li>
                <li>No destructive actions without approval</li>
                <li>No cross-user data access</li>
                <li>Handoff if ambiguous or sensitive</li>
              </ul>
              <div class="status-strip">
                <span class="pill pill--ok">Ownership checks</span>
                <span class="pill pill--warn">Tool whitelist</span>
                <span class="pill pill--accent">Audit logging</span>
              </div>
            </article>
          </section>

          <article class="panel glass-card panel--wide">
            <div class="panel__head">
              <div>
                <p class="eyebrow">Knowledge Base</p>
                <h3>FAQ and support snippets</h3>
              </div>
              <button class="chip-btn" type="button">Manage Articles</button>
            </div>
            <div class="knowledge-grid">
              <div class="knowledge-item"><strong>Operational hours</strong><span>Mon–Sat 08:00–21:00</span></div>
              <div class="knowledge-item"><strong>Returns</strong><span>Within 3 days with receipt</span></div>
              <div class="knowledge-item"><strong>Shipping</strong><span>Same-day for selected zones</span></div>
              <div class="knowledge-item"><strong>Support</strong><span>Escalate to human CS when needed</span></div>
            </div>
          </article>
        </section>

        <!-- Customers -->
        <section class="view" data-view-panel="customers">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">Customers</p>
              <h2>Visitor & Support</h2>
              <p>Show mobile-friendly support cards, ticket handoffs, and simple profile data.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button">Open Ticket</button>
              <button class="btn btn--ghost" type="button">View History</button>
            </div>
          </div>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Support</p>
                  <h3>Customer care card</h3>
                </div>
              </div>
              <div class="support-card">
                <div class="avatar avatar--large">C</div>
                <div>
                  <strong>Guest #2184</strong>
                  <p>Looking for order status and product availability.</p>
                  <div class="status-strip">
                    <span class="pill pill--accent">Active chat</span>
                    <span class="pill pill--ok">Ownership safe</span>
                  </div>
                </div>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Tickets</p>
                  <h3>Handoff queue</h3>
                </div>
              </div>
              <div class="ticket-list">
                <div class="ticket-item"><strong>Pending</strong><span>Refund question • 2m</span></div>
                <div class="ticket-item"><strong>Assigned</strong><span>Order issue • 9m</span></div>
                <div class="ticket-item"><strong>Resolved</strong><span>Product search help • 21m</span></div>
              </div>
            </article>
          </section>
        </section>

        <!-- Security -->
        <section class="view" data-view-panel="security">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">Security</p>
              <h2>Access Control</h2>
              <p>Roles, auth guardrails, and API keys — every destructive action is confirmation-guarded.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button" data-action="open-wizard">
                <i class="icon fa-solid fa-plus" aria-hidden="true"></i> New API Key
              </button>
              <button class="btn btn--ghost" type="button">Export Audit Log</button>
            </div>
          </div>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Roles</p>
                  <h3>Permission guardrails</h3>
                </div>
              </div>
              <div class="settings-form">
                <div class="settings-row">
                  <label>Admin — full access</label>
                  <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
                </div>
                <div class="settings-row">
                  <label>Manager — orders &amp; inventory</label>
                  <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
                </div>
                <div class="settings-row">
                  <label>Staff — dashboard only</label>
                  <button class="switch" type="button" aria-pressed="false"><span></span></button>
                </div>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Auth</p>
                  <h3>Guardrail cards</h3>
                </div>
              </div>
              <div class="stack">
                <article class="glass-card card-liquid">
                  <div class="card-liquid__title">
                    <i class="icon fa-solid fa-lock" aria-hidden="true"></i>
                    <div>
                      <strong>OAuth Providers</strong>
                      <small>Google, GitHub connected</small>
                    </div>
                  </div>
                  <p>Two providers active with scoped, short-lived access tokens.</p>
                </article>
                <article class="glass-card card-liquid">
                  <div class="card-liquid__title">
                    <i class="icon fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <div>
                      <strong>Threat Protection</strong>
                      <small>Rate limit + IP filtering</small>
                    </div>
                  </div>
                  <p>Suspicious requests are throttled and logged automatically.</p>
                </article>
              </div>
            </article>
          </section>

          <article class="panel glass-card panel--wide">
            <div class="panel__head">
              <div>
                <p class="eyebrow">API keys</p>
                <h3>Active credentials</h3>
              </div>
              <button class="chip-btn" type="button">Rotate All</button>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Key</th>
                    <th>Owner</th>
                    <th>Scope</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th class="th-actions">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>sk-live-•••41af</td><td>Acme Corp</td><td>Read/Write</td><td>2026-06-11</td><td><span class="status-tag ok">Active</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="sk-live-•••41af"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="sk-live-•••41af"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                  <tr>
                    <td>sk-live-•••7be2</td><td>Globex Inc.</td><td>Read only</td><td>2026-05-02</td><td><span class="status-tag ok">Active</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="sk-live-•••7be2"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="sk-live-•••7be2"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                  <tr>
                    <td>sk-test-•••90cd</td><td>Stark Industries</td><td>Sandbox</td><td>2026-07-22</td><td><span class="status-tag warn">Expiring</span></td>
                    <td class="actions-cell"><button class="action-btn" data-action="edit" data-name="sk-test-•••90cd"><i class="icon fa-solid fa-pen-to-square" aria-hidden="true"></i></button><button class="action-btn danger" data-action="delete" data-name="sk-test-•••90cd"><i class="icon fa-solid fa-trash" aria-hidden="true"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </article>
        </section>

        <!-- Settings -->
        <section class="view" data-view-panel="settings">
          <div class="section-head glass-card">
            <div>
              <p class="eyebrow">Settings</p>
              <h2>Appearance & Security</h2>
              <p>Theme switch, compact forms, security switches, and aligned fields.</p>
            </div>
            <div class="section-head__actions">
              <button class="btn btn--primary" type="button">Save Changes</button>
              <button class="btn btn--ghost" type="button">Reset</button>
            </div>
          </div>

          <section class="panel-grid panel-grid--two">
            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Appearance</p>
                  <h3>Theme control</h3>
                </div>
              </div>
              <div class="toggle-group">
                <label class="toggle-card">
                  <div>
                    <strong>Dark mode</strong>
                    <small>Default enterprise view</small>
                  </div>
                  <input type="radio" name="theme-choice" checked />
                </label>
                <label class="toggle-card">
                  <div>
                    <strong>Light mode</strong>
                    <small>Clean, bright alternative</small>
                  </div>
                  <input type="radio" name="theme-choice" />
                </label>
              </div>
            </article>

            <article class="panel glass-card">
              <div class="panel__head">
                <div>
                  <p class="eyebrow">Security</p>
                  <h3>Guardrails</h3>
                </div>
              </div>
              <div class="settings-form">
                <div class="settings-row">
                  <label>Allow destructive actions</label>
                  <button class="switch" type="button" aria-pressed="false"><span></span></button>
                </div>
                <div class="settings-row">
                  <label>Audit every AI call</label>
                  <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
                </div>
                <div class="settings-row">
                  <label>Customer data sandbox</label>
                  <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
                </div>
              </div>
            </article>
          </section>

          <article class="panel glass-card panel--wide">
            <div class="panel__head">
              <div>
                <p class="eyebrow">Aligned form</p>
                <h3>Horizontal layout</h3>
              </div>
              <button class="chip-btn" type="button">Preview</button>
            </div>
            <form class="aligned-form">
              <div class="aligned-row">
                <label for="settingName">System name</label>
                <input id="settingName" type="text" value="SistemKasirAI" />
              </div>
              <div class="aligned-row">
                <label for="settingEmail">Support email</label>
                <input id="settingEmail" type="email" value="support@example.com" />
              </div>
              <div class="aligned-row">
                <label for="settingPhone">Hotline</label>
                <input id="settingPhone" type="tel" value="+62 812-3456-7890" />
              </div>
            </form>
          </article>
        </section>
      </main>
    </div>
  </div>

  <!-- Skeleton screen removed -->

  <!-- Wizard modal -->
  <div class="modal-backdrop" data-modal="wizard" hidden>
    <div class="modal glass-card modal--large" role="dialog" aria-modal="true" aria-labelledby="wizardTitle">
      <div class="modal__head">
        <div>
          <p class="eyebrow">Create flow</p>
          <h3 id="wizardTitle">Create Product</h3>
        </div>
        <button class="icon-btn" type="button" data-action="close-modal" aria-label="Close wizard">
          <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>

      <div class="stepper stepper--modal">
        <span class="step active" data-step-indicator="1">1</span>
        <span class="step" data-step-indicator="2">2</span>
        <span class="step" data-step-indicator="3">3</span>
      </div>

      <div class="wizard-body">
        <div class="wizard-step active" data-step="1">
          <div class="wizard-step__head">
            <h4>Basic Info</h4>
            <p>Identity, barcode, and category.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field">
              <span>Name</span>
              <input type="text" placeholder="Product name" />
            </label>
            <label class="form-field">
              <span>Barcode</span>
              <input type="text" placeholder="000123456789" />
            </label>
            <label class="form-field">
              <span>Category</span>
              <select>
                <option>Choose category</option>
                <option>Beverages</option>
                <option>Snacks</option>
              </select>
            </label>
            <label class="form-field">
              <span>Slug</span>
              <input type="text" placeholder="auto-generated" />
            </label>
          </div>
        </div>

        <div class="wizard-step" data-step="2">
          <div class="wizard-step__head">
            <h4>Pricing & Stock</h4>
            <p>Unit, supplier, prices, and minimum stock.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field">
              <span>Unit</span>
              <select>
                <option>pcs</option>
                <option>dus</option>
                <option>kg</option>
              </select>
            </label>
            <label class="form-field">
              <span>Supplier</span>
              <select>
                <option>Choose supplier</option>
                <option>PT Makmur</option>
                <option>CV Sumber</option>
              </select>
            </label>
            <label class="form-field">
              <span>Purchase Price</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Sale Price</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Min Stock</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Track Expiry</span>
              <select>
                <option>Yes</option>
                <option>No</option>
              </select>
            </label>
          </div>
        </div>

        <div class="wizard-step" data-step="3">
          <div class="wizard-step__head">
            <h4>Media & Publish</h4>
            <p>Image, description, and publishing controls.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field form-field--full">
              <span>Description</span>
              <textarea rows="4" placeholder="Write a short product description..."></textarea>
            </label>
            <label class="form-field">
              <span>Image URL</span>
              <input type="text" placeholder="https://..." />
            </label>
            <label class="form-field">
              <span>Search Keywords</span>
              <input type="text" placeholder="coffee, sachet, instant" />
            </label>
          </div>
          <div class="toggle-line">
            <label>Featured product</label>
            <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
          </div>
        </div>
      </div>

      <div class="wizard-actions">
        <button class="btn btn--secondary" type="button" data-action="wizard-back">Back</button>
        <div class="wizard-actions__right">
          <button class="btn btn--ghost" type="button" data-action="wizard-skip">Skip</button>
          <button class="btn btn--primary" type="button" data-action="wizard-next">Next</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm modal -->
  <div class="modal-backdrop" data-modal="confirm" hidden>
    <div class="modal glass-card modal--small" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <div class="modal__head">
        <div class="confirm-icon" id="confirmIcon">
          <i class="icon fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        </div>
        <button class="icon-btn" type="button" data-action="close-modal" aria-label="Close dialog">
          <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
      <h3 id="confirmTitle">Confirm action</h3>
      <p id="confirmMessage">Are you sure you want to continue?</p>
      <div class="modal__actions">
        <button class="btn btn--secondary" type="button" data-action="cancel-confirm">Cancel</button>
        <button class="btn btn--danger" type="button" data-action="confirm-ok">Confirm</button>
      </div>
    </div>
  </div>

  <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>
  <div class="theme-transition-veil" id="themeVeil" aria-hidden="true"></div>

  <script src="{{ asset('assets/js/script.js') }}"></script>
</body>
</html>
