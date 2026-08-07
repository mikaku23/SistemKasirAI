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
        @auth
        @php
        $authUser = auth()->user();
        $userName = $authUser?->name ?? 'User';

        $roleName = $authUser?->user_role_name
        ?? $authUser?->role_name
        ?? $authUser?->role?->name
        ?? $authUser?->roles?->first()?->name
        ?? 'User';

        $avatarPath = $authUser?->avatar;
        $avatarUrl = null;

        if (!empty($avatarPath)) {
        $avatarUrl = preg_match('#^https?://#i', $avatarPath)
        ? $avatarPath
        : \Illuminate\Support\Facades\Storage::disk('public')->url($avatarPath);
        }
        @endphp

        <details class="profile-menu">
            <summary class="profile-chip profile-chip--button" aria-label="Open profile menu" aria-haspopup="menu">
                @if (!empty($avatarUrl))
                <div
                    class="avatar profile-chip__avatar"
                    style="background-image:url('{{ $avatarUrl }}'); background-size:cover; background-position:center; background-repeat:no-repeat;"></div>
                @else
                <div class="avatar profile-chip__avatar">{{ strtoupper(mb_substr($userName ?? 'U', 0, 1)) }}</div>
                @endif

                <div class="profile-chip__copy">
                    <strong>{{ $userName }}</strong>
                    <small>{{ $roleName }}</small>
                </div>

                <i class="icon profile-chip__chevron fa-solid fa-chevron-down" aria-hidden="true"></i>
            </summary>

            <div class="profile-menu__dropdown" role="menu" aria-label="Profile actions">
                <div class="profile-menu__info">
                    @if (!empty($avatarUrl))
                    <div
                        class="avatar avatar--large"
                        style="background-image:url('{{ $avatarUrl }}'); background-size:cover; background-position:center; background-repeat:no-repeat;"></div>
                    @else
                    <div class="avatar avatar--large">{{ strtoupper(mb_substr($userName ?? 'U', 0, 1)) }}</div>
                    @endif

                    <div class="profile-menu__copy">
                        <strong>{{ $userName }}</strong>
                        <small>{{ $roleName }}</small>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="profile-menu__logout" type="submit">
                        <i class="icon fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </details>
        @else
        <div class="profile-chip">
            <div class="avatar">U</div>
            <div>
                <strong>Guest</strong>
                <small>Unauthorized</small>
            </div>
        </div>
        @endauth

    </div>
</header>