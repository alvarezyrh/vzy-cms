<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">MBG</div>
        <div class="sidebar-brand">PT <span>MBG</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="index.php" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Content</div>
            <?php $postsMenuOpen = in_array(($currentPage ?? ''), ['posts', 'categories', 'tags'], true); ?>
            <div class="nav-dropdown <?= $postsMenuOpen ? 'open' : '' ?>">
                <button type="button" class="nav-item nav-dropdown-toggle <?= $postsMenuOpen ? 'active' : '' ?>" aria-expanded="<?= $postsMenuOpen ? 'true' : 'false' ?>">
                    <i class="fas fa-newspaper"></i><span>Posts</span><i class="fas fa-chevron-down nav-chevron"></i>
                </button>
                <div class="nav-submenu">
                    <a href="posts.php" class="nav-item <?= ($currentPage ?? '') === 'posts' ? 'active' : '' ?>"><i class="fas fa-list"></i> Semua Posts</a>
                    <a href="categories.php" class="nav-item <?= ($currentPage ?? '') === 'categories' ? 'active' : '' ?>"><i class="fas fa-folder"></i> Categories</a>
                    <a href="tags.php" class="nav-item <?= ($currentPage ?? '') === 'tags' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Tags</a>
                </div>
            </div>
            <a href="pages.php" class="nav-item <?= ($currentPage ?? '') === 'pages' ? 'active' : '' ?>">
                <i class="fas fa-file-alt"></i> Pages
            </a>
            <a href="media.php" class="nav-item <?= ($currentPage ?? '') === 'media' ? 'active' : '' ?>">
                <i class="fas fa-images"></i> Media
            </a>
            <a href="comments.php" class="nav-item <?= ($currentPage ?? '') === 'comments' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Comments
            </a>
            <a href="menus.php" class="nav-item <?= ($currentPage ?? '') === 'menus' ? 'active' : '' ?>">
                <i class="fas fa-bars"></i> Navigation
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Tools</div>
            <a href="seo.php" class="nav-item <?= ($currentPage ?? '') === 'seo' ? 'active' : '' ?>">
                <i class="fas fa-search"></i> SEO
            </a>
            <?php if (isAdmin()): ?>
            <a href="users.php" class="nav-item <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="plugins.php" class="nav-item <?= ($currentPage ?? '') === 'plugins' ? 'active' : '' ?>">
                <i class="fas fa-puzzle-piece"></i> Plugins
            </a>
            <?php endif; ?>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <a href="profile.php" class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user"></i> Profile
            </a>
            <?php if (isAdmin()): ?>
            <a href="settings.php" class="nav-item <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="sidebar-clock">
        <div class="sidebar-clock-label"><i class="fas fa-clock"></i> Waktu Saat Ini</div>
        <div class="analog-clock" aria-label="Jam analog">
            <span class="clock-number n12">12</span><span class="clock-number n1">1</span><span class="clock-number n2">2</span>
            <span class="clock-number n3">3</span><span class="clock-number n4">4</span><span class="clock-number n5">5</span>
            <span class="clock-number n6">6</span><span class="clock-number n7">7</span><span class="clock-number n8">8</span>
            <span class="clock-number n9">9</span><span class="clock-number n10">10</span><span class="clock-number n11">11</span>
            <span class="clock-hand clock-hour" id="analogHour"></span><span class="clock-hand clock-minute" id="analogMinute"></span><span class="clock-hand clock-second" id="analogSecond"></span><span class="clock-center"></span>
        </div>
        <div class="analog-digital-time" id="analogDigitalTime">--:--:--</div>
        <div class="analog-date" id="analogDate">Memuat tanggal...</div>
    </div>
</aside>
