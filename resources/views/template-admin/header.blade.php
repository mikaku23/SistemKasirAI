<header class="topbar glass-card">
    <div class="topbar__title">
        <p class="eyebrow">AI API Gateway</p>
        <div class="group-tabs" id="groupTabs" aria-hidden="true">
            <span class="group-pill" data-group="gateway">Gateway</span>
            <span class="group-pill" data-group="user">User</span>
            <span class="group-pill" data-group="inventory">Inventory</span>
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
        <form action="{{ route('logout') }}" method="POST" class="profile-chip" style="cursor:pointer;">
            @csrf
            <button type="submit" class="icon-btn"
                style="display:flex; align-items:center; gap:0.75rem; padding:0; border:0; background:transparent; color:inherit;"
                aria-label="Logout">
                <div class="avatar">A</div>
                <div style="text-align:left;">
                    <strong>{{ auth()->user()?->name ?? 'Admin' }}</strong>
                    <small>{{ auth()->user()?->role?->name ?? 'Administrator' }}</small>
                </div>
            </button>
        </form>
    </div>
</header>