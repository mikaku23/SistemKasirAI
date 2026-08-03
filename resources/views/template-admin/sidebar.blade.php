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
      <a href="{{ route('dashboardadmin') }}" class="nav-item {{ $menu == 'dashboard' ? 'active' : '' }}">
        <i class="icon fa-solid fa-gauge-high" aria-hidden="true"></i><span>Dashboard</span>
      </a>
    </div>

    <div class="nav-group" data-group="users">
      <p class="nav-group__label">Users</p>
      <a href="{{ route('users.index') }}" class="nav-item {{ $menu == 'users' ? 'active' : '' }}">
        <i class="icon fa-solid fa-users" aria-hidden="true"></i><span>Users</span>
      </a>
      <a href="{{ route('roles.index') }}" class="nav-item {{ $menu == 'roles' ? 'active' : '' }}">
        <i class="icon fa-solid fa-user-shield" aria-hidden="true"></i><span>Roles</span>
      </a>
    </div>

    <div class="nav-group" data-group="inventory">
      <p class="nav-group__label">Inventory</p>
      <a href="{{ route('suppliers.index') }}" class="nav-item {{ $menu == 'suppliers' ? 'active' : '' }}">
        <i class="icon fa-solid fa-truck" aria-hidden="true"></i><span>Suppliers</span>
      </a>
      <a href="#" class="nav-item ">
        <i class="icon fa-solid fa-cube" aria-hidden="true"></i><span>Units</span>
      </a>
      <a href="#" class="nav-item ">
        <i class="icon fa-solid fa-tag" aria-hidden="true"></i><span>Categories</span>
      </a>
      <a href="#" class="nav-item ">
        <i class="icon fa-solid fa-box" aria-hidden="true"></i><span>Products</span>
      </a>
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
