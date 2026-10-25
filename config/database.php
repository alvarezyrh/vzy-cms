<?php
/**
 * File-based Storage (JSON) — no DB driver needed
 * Pure vanilla PHP, works everywhere
 */

define('DATA_DIR', __DIR__ . '/../database');

function dataPath(string $table): string {
    return DATA_DIR . '/' . $table . '.json';
}

function loadData(string $table): array {
    $path = dataPath($table);
    if (!file_exists($path)) return [];
    $json = file_get_contents($path);
    return json_decode($json, true) ?: [];
}

function saveData(string $table, array $data): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);
    file_put_contents(dataPath($table), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function nextId(string $table): int {
    $data = loadData($table);
    if (empty($data)) return 1;
    return max(array_column($data, 'id')) + 1;
}

function findById(string $table, int $id): ?array {
    foreach (loadData($table) as $row) {
        if ((int)$row['id'] === $id) return $row;
    }
    return null;
}

function findWhere(string $table, callable $fn): array {
    return array_values(array_filter(loadData($table), $fn));
}

function findOne(string $table, callable $fn): ?array {
    foreach (loadData($table) as $row) {
        if ($fn($row)) return $row;
    }
    return null;
}

function insert(string $table, array $row): int {
    $data = loadData($table);
    $id = nextId($table);
    $row['id'] = $id;
    $data[] = $row;
    saveData($table, $data);
    return $id;
}

function update(string $table, int $id, array $fields): bool {
    $data = loadData($table);
    $found = false;
    foreach ($data as &$row) {
        if ((int)$row['id'] === $id) {
            $row = array_merge($row, $fields);
            $found = true;
            break;
        }
    }
    unset($row);
    if ($found) saveData($table, $data);
    return $found;
}

function deleteById(string $table, int $id): bool {
    $data = loadData($table);
    $new = array_values(array_filter($data, fn($r) => (int)$r['id'] !== $id));
    if (count($new) === count($data)) return false;
    saveData($table, $new);
    return true;
}

function countWhere(string $table, ?callable $fn = null): int {
    $data = loadData($table);
    if ($fn === null) return count($data);
    return count(array_filter($data, $fn));
}

function initDatabase(): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0755, true);

    if (!file_exists(dataPath('users'))) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        saveData('users', [[
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin@cms.local',
            'password' => $hash,
            'role' => 'admin',
            'full_name' => 'Administrator',
            'avatar' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]]);
    }

    foreach (['categories', 'tags', 'posts', 'post_tags', 'pages', 'products', 'menu_items', 'media', 'comments', 'activity'] as $t) {
        if (!file_exists(dataPath($t))) saveData($t, []);
    }

    if (!file_exists(dataPath('settings'))) {
        saveData('settings', [
            ['id' => 1, 'setting_key' => 'site_name', 'setting_value' => 'My CMS'],
            ['id' => 2, 'setting_key' => 'site_tagline', 'setting_value' => 'Powered by Vanilla PHP'],
            ['id' => 3, 'setting_key' => 'site_url', 'setting_value' => ''],
            ['id' => 4, 'setting_key' => 'posts_per_page', 'setting_value' => '10'],
            ['id' => 5, 'setting_key' => 'timezone', 'setting_value' => 'Asia/Jakarta'],
            ['id' => 6, 'setting_key' => 'date_format', 'setting_value' => 'd M Y'],
            ['id' => 7, 'setting_key' => 'allow_comments', 'setting_value' => '1'],
        ]);
    }

    if (!file_exists(dataPath('menus')) || empty(loadData('menus'))) {
        saveData('menus', [['id' => 1, 'name' => 'Primary Menu', 'location' => 'primary', 'created_at' => date('Y-m-d H:i:s')]]);
    }

    if (!file_exists(dataPath('seo_settings'))) {
        saveData('seo_settings', [[
            'id' => 1,
            'meta_title' => 'My CMS',
            'meta_description' => 'A powerful vanilla PHP CMS',
            'meta_keywords' => '',
            'og_image' => '',
            'google_analytics' => '',
            'robots_txt' => "User-agent: *\nAllow: /",
            'sitemap_enabled' => 1,
        ]]);
    }

    if (!file_exists(dataPath('plugins')) || empty(loadData('plugins'))) {
        saveData('plugins', [
            ['id' => 1, 'name' => 'Contact Form', 'slug' => 'contact-form', 'description' => 'Simple contact form plugin', 'version' => '1.0.0', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 2, 'name' => 'Google Maps', 'slug' => 'google-maps', 'description' => 'Embed Google Maps easily', 'version' => '1.2.0', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 3, 'name' => 'Social Share', 'slug' => 'social-share', 'description' => 'Add social media share buttons', 'version' => '1.1.0', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 4, 'name' => 'Custom Code', 'slug' => 'custom-code', 'description' => 'Insert custom header/footer code', 'version' => '1.0.0', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')],
            ['id' => 5, 'name' => 'Maintenance Mode', 'slug' => 'maintenance-mode', 'description' => 'Put site in maintenance mode', 'version' => '1.0.1', 'is_active' => 0, 'created_at' => date('Y-m-d H:i:s')],
        ]);
    }
}

function getDB() {
    return null;
}
