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