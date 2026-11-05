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
            <a href="../public/" class="nav-item" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-globe"></i> Website
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
            <a href="products.php" class="nav-item <?= ($currentPage ?? '') === 'products' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Products
            </a>
            <a href="menus.php" class="nav-item <?= ($currentPage ?? '') === 'menus' ? 'active' : '' ?>">
                <i class="fas fa-bars"></i> Navigation
            </a>
            <a href="media.php" class="nav-item <?= ($currentPage ?? '') === 'media' ? 'active' : '' ?>">
                <i class="fas fa-images"></i> Media
            </a>
            <a href="comments.php" class="nav-item <?= ($currentPage ?? '') === 'comments' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Comments
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
</aside>
