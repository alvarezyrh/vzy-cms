# PT MBG — Website & CMS

Website perusahaan PT MBG beserta dashboard admin (CMS) berbasis **PHP murni**, tanpa framework dan **tanpa MySQL**. Semua data disimpan sebagai file JSON, sehingga aplikasi bisa dipasang di hampir semua hosting PHP hanya dengan upload file.

**Fitur utama:** Visual Page Builder (drag & drop) · Pages · Posts & Blog · Katalog Produk (SKU, pesan via WhatsApp) · Form "Hubungi Kami" → WhatsApp · Media · Komentar · Menu navigasi · SEO · Users (admin/editor) · Plugins (Social Share) · Activity log.

---

## Daftar Isi

1. [Ringkasan arsitektur](#1-ringkasan-arsitektur)
2. [Struktur folder](#2-struktur-folder)
3. [Alur request website publik](#3-alur-request-website-publik)
4. [Pembuatan halaman statis (sync)](#4-pembuatan-halaman-statis-sync)
5. [Lapisan data (JSON)](#5-lapisan-data-json)
6. [Dashboard admin](#6-dashboard-admin)
7. [Visual Page Builder](#7-visual-page-builder)
8. [Frontend publik](#8-frontend-publik)
9. [Keamanan](#9-keamanan)
10. [Persyaratan](#10-persyaratan)
11. [Instalasi di shared hosting (cPanel)](#11-instalasi-di-shared-hosting-cpanel)
12. [Instalasi di VPS (Apache / Nginx)](#12-instalasi-di-vps-apache--nginx)
13. [Menjalankan di komputer lokal](#13-menjalankan-di-komputer-lokal)
14. [Checklist setelah instalasi](#14-checklist-setelah-instalasi)
15. [Menyesuaikan konten & kontak](#15-menyesuaikan-konten--kontak)
16. [Backup, pindah hosting, dan reset](#16-backup-pindah-hosting-dan-reset)
17. [Troubleshooting](#17-troubleshooting)
18. [Login default & peran pengguna](#18-login-default--peran-pengguna)

---

## 1. Ringkasan arsitektur

Aplikasi terdiri dari **dua sisi** yang berbagi satu penyimpanan data:

```
                        ┌──────────────────────────────────────────┐
                        │              PENGUNJUNG                  │
                        └───────────────┬──────────────────────────┘
                                        │  GET /  /index.php  /produk.php  /blog.php
                                        ▼
        ┌───────────────────────────────────────────────────────────────┐
        │  index.php  (front controller publik)                         │
        │  • memilih file HTML statis berdasarkan URL                   │
        │  • menulis ulang URL lama /public/... → /xxx.php              │
        │  • menyisipkan public/assets/contact-modal.js                 │
        └───────────────┬───────────────────────────────────────────────┘
                        │ membaca
                        ▼
        ┌───────────────────────────────┐        ┌──────────────────────────┐
        │  public/  (HTML hasil generate│        │  uploads/  (gambar)      │
        │  home/ pages/ blog/ assets/)  │        └──────────────────────────┘
        └───────────────▲───────────────┘
                        │ ditulis ulang setiap kali admin menyimpan  (sync*)
        ┌───────────────┴───────────────────────────────────────────────┐
        │  includes/functions.php  — generator halaman & fungsi inti    │
        └───────────────▲───────────────────────────────────────────────┘
                        │ dipanggil oleh
        ┌───────────────┴───────────────────────────────────────────────┐
        │  admin/*.php  (dashboard)  +  admin/builder.php (page builder)│
        └───────────────┬───────────────────────────────────────────────┘
                        │ baca/tulis via config/database.php
                        ▼
        ┌───────────────────────────────────────────────────────────────┐
        │  database/*.json   (pengganti MySQL)                          │
        └───────────────────────────────────────────────────────────────┘
                        ▲
                        │ login /admin/login.php
        ┌───────────────┴──────────────┐
        │  ADMIN / EDITOR              │
        └──────────────────────────────┘
```

**Prinsip utama**

| Prinsip | Penjelasan |
|---|---|
| **Statis-dulu** | Halaman publik adalah file HTML hasil generate. Pengunjung tidak memicu query data sama sekali, sehingga cepat dan ringan. |
| **Generate saat simpan** | Setiap kali admin menyimpan post, produk, page, menu, atau plugin, halaman terkait dibuat ulang (fungsi `sync*`). |
| **Penyimpanan JSON** | Satu file JSON per "tabel" di `database/`. Tidak ada database server. |
| **Tanpa dependensi** | Tidak memakai Composer/NPM. Font Awesome dimuat dari CDN; sisanya kode sendiri. |

---

## 2. Struktur folder

```
genta-cms/
├── index.php                 Front controller publik (/, /index.php, /{slug}.php)
├── .htaccess                 Aturan Apache: keamanan, redirect URL lama, front controller
├── .htaccess.simple          Versi minimal .htaccess untuk hosting yang membatasi aturan
├── dev-router.php            Router `php -S` untuk pengembangan lokal (meniru .htaccess)
├── fix-permissions.php       Pemeriksa izin folder & versi PHP — HAPUS setelah instalasi
│
├── config/
│   └── database.php          Lapisan data JSON (CRUD) + seeding data awal      [diblokir]
│
├── includes/
│   └── functions.php         Fungsi inti: auth, CSRF, upload, generator HTML    [diblokir]
│
├── database/                 Data JSON (users, pages, posts, products, ...)     [diblokir, writable]
│
├── admin/                    Dashboard admin
│   ├── login.php · logout.php · index.php (dashboard) · profile.php
│   ├── pages.php · builder.php            Pages & Visual Page Builder
│   ├── posts.php · categories.php · tags.php · comments.php
│   ├── products.php · media.php · menus.php · seo.php
│   ├── users.php · plugins.php · settings.php
│   ├── includes/             auth.php (guard + bootstrap), header.php, sidebar.php, footer.php
│   └── assets/
│       ├── css/              dashboard.css, builder.css
│       └── js/               dashboard.js, builder.js, builder-widgets.js
│
├── public/                   Konten yang disajikan ke pengunjung
│   ├── home/                 index.html (beranda), css/style.css, js/main.js    [writable]
│   ├── pages/                Halaman hasil generate: produk.html, blog.html, …  [writable]
│   │   └── img/products/     Gambar produk bawaan
│   ├── blog/                 Artikel hasil generate + img/                      [writable]
│   ├── assets/
│   │   ├── brand/            Logo & favicon SVG
│   │   └── contact-modal.js  Popup "Hubungi Kami" → WhatsApp
│   └── index.php             Pengalih ke /index.php
│
└── uploads/                  Gambar yang diunggah dari admin                    [writable, dibuat otomatis]
```

---

## 3. Alur request website publik

### Pemetaan URL

| URL | Disajikan dari |
|---|---|
| `/` dan `/index.php` | `public/home/index.html` (beranda) |
| `/{slug}.php`, mis. `/produk.php`, `/blog.php`, `/about.php` | `public/pages/{slug}.html` |
| `/public/blog/{artikel}.html` | File statis artikel (disajikan langsung) |
| `/public/home/css/…`, `/public/assets/…`, `/uploads/…` | Aset statis (disajikan langsung) |
| `/admin/…` | Dashboard (PHP biasa) |
| `/public/home/`, `/public/home/index.html`, `/public/pages/{slug}.html` | **Redirect 301** ke URL `.php` yang baru |
| slug yang tidak ada | Halaman 404 |

### Urutan pemrosesan (Apache)

```
Request
  │
  ├─ uploads/*.php ?            → 403 (eksekusi PHP di uploads diblokir)
  ├─ public/home/ atau          → 301 ke /index.php atau /{slug}.php
  │  public/pages/*.html ?
  ├─ file/folder nyata ada ?    → disajikan langsung (aset, admin/*.php, dst.)
  ├─ database/ config/ includes/→ 403
  ├─ admin/ ?                   → diteruskan apa adanya
  └─ lainnya                    → index.php (front controller)
```

`dev-router.php` mengimplementasikan urutan yang sama untuk `php -S`.

### Yang dilakukan `index.php` pada setiap halaman

1. **Menentukan slug** dari URL (`/` atau `index.php` → `home`).
2. **Membaca** HTML statis yang sesuai.
3. **Menormalkan tautan**: path relatif (mis. `../home/css/style.css`) diubah menjadi absolut, karena halaman kini berada di root.
4. **Menulis ulang URL lama** `/public/home/…` dan `/public/pages/x.html` menjadi `/index.php` dan `/x.php`.
5. **Menyisipkan** `<script src="/public/assets/contact-modal.js">` sebelum `</body>`.
6. Mengirim hasilnya. Jika file tidak ada → 404.

Karena langkah 4 dan 5 dilakukan saat disajikan, halaman baru dari builder atau admin otomatis mendapat URL baru dan form "Hubungi Kami" tanpa perlu digenerate ulang.

### Form "Hubungi Kami"

`public/assets/contact-modal.js` menangkap klik pada tombol `.nav-cta` di navbar, membuka popup berisi **Nama, Instansi/Perusahaan, Email, Perihal, Isi Pesan**, lalu membuka `https://wa.me/6281313616756?text=…` di tab baru dengan pesan yang sudah tersusun. Tidak ada data yang dikirim ke server.

---

## 4. Pembuatan halaman statis (sync)

Semua file di `public/pages/`, `public/blog/`, dan sebagian `public/home/index.html` **dihasilkan** dari data JSON. Jangan diedit manual karena akan tertimpa.

| Aksi di admin | Fungsi yang dipanggil | Yang dibuat ulang |
|---|---|---|
| Simpan / hapus page | `syncPageToPublic()`, `syncPageMenuItem()`, `removePageFromPublic()` | `public/pages/{slug}.html`, item menu halaman |
| Simpan di Page Builder | `syncPageToPublic()` | `public/pages/{slug}.html` |
| Simpan / hapus produk | `syncProductCatalogPages()` | Semua page yang memuat `[product_catalog]` |
| Simpan / hapus / pulihkan post, ubah plugin Social Share | `syncBlogToPublic()` | `public/blog/*.html` + semua page yang memuat `[blog_posts]` |
| Simpan menu / pengaturan navigasi | `syncWebsiteNavigation()` | Navbar di `public/home/index.html` |
| Buka halaman admin mana pun | `ensureHomePageMenu()`, `seedWebsiteNavigation()`, `syncWebsiteNavigation()` (di `admin/includes/auth.php`) | Menjamin Home & navbar selalu konsisten |

### Shortcode

Konten sebuah page boleh memuat shortcode yang diganti saat generate:

| Shortcode | Diganti dengan |
|---|---|
| `[product_catalog]` | Grid kartu produk + popup pemesanan WhatsApp (`renderProductCatalog()`) |
| `[blog_posts]` | Daftar kartu artikel (`renderBlogList()`) |

Page yang memuat shortcode (atau dengan opsi **site chrome**) otomatis dibungkus navbar & footer situs (`publicCatalogNavigation()`, `publicCatalogFooter()`).

### Anatomi file hasil generate

```
<!DOCTYPE html>
<head>  title · meta description · favicon · Font Awesome · style.css · <style>CSS halaman</style></head>
<body class="catalog-page">
   [navbar situs]   ← bila site chrome
   konten page / hasil builder / hasil shortcode
   [footer situs]   ← bila site chrome
   <script>JS halaman</script>
</body>
```

---

## 5. Lapisan data (JSON)

Diatur oleh [config/database.php](config/database.php). Setiap "tabel" adalah satu file `database/{tabel}.json` berisi array objek dengan field `id`.

### API penyimpanan

| Fungsi | Kegunaan |
|---|---|
| `loadData($t)` / `saveData($t, $rows)` | Baca / tulis seluruh tabel |
| `findById($t, $id)` · `findWhere($t, fn)` · `findOne($t, fn)` | Pencarian |
| `insert($t, $row)` · `update($t, $id, $fields)` · `deleteById($t, $id)` | Ubah data (ID otomatis via `nextId()`) |
| `countWhere($t, fn)` | Hitung |
| `initDatabase()` | Membuat file & data awal bila belum ada (user `admin`, menu, plugin, dll.) |

### Tabel

| File | Isi |
|---|---|
| `users.json` | Akun admin/editor (password di-hash) |
| `settings.json` | Pengaturan situs, navigasi (`nav_brand`, `nav_cta_*`), zona waktu, Social Share |
| `pages.json` | Halaman: judul, slug, konten, `builder_data`, `custom_css/js`, `site_chrome`, `is_frontpage`, SEO |
| `posts.json` · `categories.json` · `tags.json` · `post_tags.json` | Artikel blog beserta relasinya |
| `products.json` | Katalog: nama, deskripsi, harga, gambar, SKU, stok, status |
| `menus.json` · `menu_items.json` | Menu navigasi |
| `media.json` | Metadata file upload |
| `comments.json` | Komentar & status moderasi |
| `plugins.json` | Daftar plugin & status aktif |
| `seo_settings.json` | Meta global & Google Analytics |
| `activity.json` | Log aktivitas admin |

### Diagram relasi

```
users ──< posts >── categories
              │
              └──< post_tags >── tags
posts ──< comments
pages ──< menu_items >── menus
products   (dirender ke page lewat [product_catalog])
media      (dipakai post, produk, builder)
```

### Batasan

Penyimpanan JSON cocok untuk website perusahaan dengan **sedikit admin** dan ratusan konten. Tidak ada penguncian file, sehingga tidak dirancang untuk banyak admin yang mengedit bersamaan atau puluhan ribu data.

### Lokasi folder data (opsional, lebih aman)

Default di `config/database.php`:

```php
define('DATA_DIR', __DIR__ . '/../database');
```

Jika hosting memungkinkan, pindahkan ke luar `public_html`:

```php
// sejajar dengan public_html, bukan di dalamnya
define('DATA_DIR', '/home/USERNAME/mbg-data');
```

Pindahkan semua `.json` ke folder baru itu dan pastikan bisa ditulis.

---

## 6. Dashboard admin

Alamat: `/admin/login.php`. Setiap halaman admin memuat `admin/includes/auth.php`, yang menjalankan sesi, `initDatabase()`, zona waktu, sinkronisasi navigasi, dan `requireLogin()`.

```
admin/page.php
   └─ includes/auth.php  → session, database, functions, requireLogin
        ├─ includes/header.php    (head, CSS)
        ├─ includes/sidebar.php   (menu, dibedakan per peran)
        ├─ … isi halaman …
        └─ includes/footer.php    (JS)
```

| Menu | File | Fungsi |
|---|---|---|
| Dashboard | `index.php` | Statistik & aktivitas terbaru |
| Posts / Categories / Tags | `posts.php`, `categories.php`, `tags.php` | Artikel blog (draft, publish, trash) |
| Pages | `pages.php` | Daftar & form halaman, tombol **Edit visual** (membuka builder di tab baru) |
| Products | `products.php` | Katalog, SKU, stok, gambar (maks. 5 MB) |
| Navigation | `menus.php` | Menu, brand, tombol CTA |
| Media | `media.php` | Pustaka upload |
| Comments | `comments.php` | Moderasi |
| SEO | `seo.php` | Meta & Analytics |
| Users *(admin)* | `users.php` | Akun & peran |
| Plugins *(admin)* | `plugins.php` | Aktif/nonaktif plugin, pengaturan Social Share |
| Settings *(admin)* | `settings.php` | Nama situs, zona waktu |
| Profile | `profile.php` | Ganti password |

### Plugin

Plugin bawaan ditulis di `initDatabase()`: Contact Form, Google Maps, **Social Share** (fungsional), Custom Code, Maintenance Mode. Saat ini hanya **Social Share** yang memengaruhi halaman (tombol share pada artikel).

---

## 7. Visual Page Builder

`admin/builder.php` + `assets/js/builder.js` + `assets/js/builder-widgets.js` + `assets/css/builder.css`. Tema editor berwarna cerah.

```
┌──────────────────────────────────────────────────────────────────┐
│ Top bar: judul · status · device · undo/redo · Lihat · Simpan    │
├───────────────┬──────────────────────────────────┬───────────────┤
│ Panel kiri    │  Stage (iframe pratinjau)        │ (inspector)   │
│ • Widget      │  halaman dirender langsung       │ pengaturan    │
│ • Layout      │  drag & drop antar section       │ elemen terpilih│
└───────────────┴──────────────────────────────────┴───────────────┘
```

**Model data:** layout disimpan sebagai JSON di field `builder_data` pada tabel `pages`. Saat simpan, builder juga mengirim hasil HTML (`content`), CSS, dan metadata SEO; server memvalidasi JSON, memperbarui `pages.json`, lalu memanggil `syncPageToPublic()`.

**Endpoint** (semua `POST` dengan token CSRF, respons JSON):

| Request | Fungsi |
|---|---|
| `builder.php` (JSON body) | Simpan halaman → `{ok, synced, saved_at, url}` |
| `builder.php?action=upload` | Upload gambar (jpg/png/gif/webp/svg), dicatat di `media.json` |

**Widget bawaan:** Hero · Tentang Kami · Layanan · Proses Kerja · Testimoni · Statistik · Harga · Tim · FAQ · Ajakan Bertindak · Kontak · Katalog Produk (shortcode) · Daftar Blog (shortcode).

**Fitur editor:** undo/redo, duplikat, hapus, pratinjau desktop/tablet/mobile, sembunyikan elemen per perangkat, lebar section (penuh/sempit), tumpuk kolom per perangkat.

**Pintasan:** `Ctrl+S` simpan · `Ctrl+Z` / `Ctrl+Y` undo/redo · `Ctrl+D` duplikat · `Delete` hapus.

> Halaman **home** memakai file `public/home/index.html` sehingga tidak dirender dari builder.

---

## 8. Frontend publik

| Berkas | Peran |
|---|---|
| `public/home/index.html` | Beranda: hero slider, tentang, layanan, proses, testimoni, kontak |
| `public/home/css/style.css` | Satu stylesheet untuk seluruh situs (beranda, katalog, blog, builder) |
| `public/home/js/main.js` | Slider hero, navbar saat scroll, animasi, menu mobile |
| `public/assets/contact-modal.js` | Popup Hubungi Kami → WhatsApp |
| `public/assets/brand/*.svg` | Logo (`mbg-mark`, `mbg-logo-light/dark`) & favicon |

**Desain:** warna tosca (`--turquoise`) dan hijau (`--green`) di atas slate gelap. Hero dan footer memakai gradien yang sama dengan pola titik (`--dots`). Variabel desain ada di `:root` pada `style.css`.

---

## 9. Keamanan

| Aspek | Implementasi |
|---|---|
| Folder sensitif | `database/`, `config/`, `includes/` diblokir lewat `.htaccess` (Apache), `dev-router.php` (lokal), atau aturan `location` (Nginx) |
| Upload | Hanya ekstensi gambar; nama file diacak; eksekusi `.php` di `uploads/` diblokir |
| CSRF | Token sesi (`csrfToken()`, `verifyCsrf()`) pada form dan endpoint builder |
| Autentikasi | Sesi PHP, password `password_hash`, pembatasan menu per peran (`requireAdmin()`) |
| XSS | Output di-escape dengan `e()` |
| Aktivitas | `logActivity()` mencatat aksi penting ke `activity.json` |
| Link eksternal | `rel="noopener noreferrer"` pada tautan yang membuka tab baru |

---

## 10. Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| **PHP** | **8.1 atau lebih baru** (disarankan 8.2+) |
| Ekstensi PHP | `json`, `mbstring`, `fileinfo`, `session` |
| Web server | Apache dengan `mod_rewrite` (paling mudah), LiteSpeed, atau Nginx |
| Database | **Tidak perlu MySQL** — lihat [bagian 5](#5-lapisan-data-json) |
| Domain | **Root domain atau subdomain**, mis. `https://mbg.co.id` atau `https://web.mbg.co.id` |

> ⚠️ **Jangan pasang di subfolder** seperti `https://domain.com/cms/`. Halaman memakai path absolut (`/public/...`, `/admin/...`, `/uploads/...`). Jika perlu memisahkan, buat **subdomain** dan arahkan document root-nya ke folder aplikasi.

---

## 11. Instalasi di shared hosting (cPanel)

Berlaku untuk cPanel, hPanel (Hostinger), DirectAdmin, dan panel serupa.

### Langkah 1 — Siapkan file

Kompres isi folder proyek menjadi `mbg-cms.zip` (tanpa folder `.git`).

### Langkah 2 — Atur versi PHP

**cPanel → Select PHP Version** (atau **MultiPHP Manager**): pilih **PHP 8.1+** dan pastikan `fileinfo` serta `mbstring` aktif.

### Langkah 3 — Upload & ekstrak

1. **File Manager → `public_html`** (atau document root subdomain). Kosongkan file bawaan seperti `index.html`.
2. Upload `mbg-cms.zip` lalu **Extract**.
3. Pastikan hasilnya **langsung** di `public_html`, bukan di `public_html/genta-cms/`:

   ```
   public_html/admin/
   public_html/database/
   public_html/public/
   public_html/index.php
   public_html/.htaccess      ← aktifkan "Show Hidden Files"
   ```

### Langkah 4 — Atur izin folder

Buka `https://domain-anda.com/fix-permissions.php`; semua baris harus **[OK]**. Jika ada **[FAIL]**, atur manual (**Change Permissions**):

| Folder | Izin |
|---|---|
| `database/`, `uploads/`, `public/pages/`, `public/blog/`, `public/home/` | `755` (jika gagal: `775`) |
| File di `database/` dan `public/home/index.html` | `644` (jika gagal: `664`) |

> Jangan gunakan `777` kecuali diminta support hosting.

### Langkah 5 — Login & amankan

1. **Hapus `fix-permissions.php`** dari server.
2. Buka `https://domain-anda.com/admin/login.php`.
3. Login `admin` / `admin123`, lalu **segera ganti password** di menu **Profile**.
4. Ikuti [checklist](#14-checklist-setelah-instalasi).

### Langkah 6 — Aktifkan HTTPS

Aktifkan SSL di **cPanel → SSL/TLS Status → Run AutoSSL**. Untuk memaksa HTTPS, tambahkan setelah `RewriteEngine On` di `.htaccess`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Pengaturan PHP tambahan (opsional)

Jika upload gambar besar gagal atau jam tidak sesuai WIB, buat `.user.ini` di root:

```ini
upload_max_filesize = 8M
post_max_size = 10M
date.timezone = Asia/Jakarta
```

---

## 12. Instalasi di VPS (Apache / Nginx)

Contoh untuk Ubuntu/Debian dengan PHP 8.2.

```bash
sudo apt update
sudo apt install php8.2 php8.2-mbstring php8.2-fpm unzip

sudo mkdir -p /var/www/mbg
sudo unzip mbg-cms.zip -d /var/www/mbg        # atau: git clone <repo> /var/www/mbg

cd /var/www/mbg
sudo chown -R www-data:www-data database uploads public
sudo find database uploads public -type d -exec chmod 775 {} \;
sudo find database uploads public -type f -exec chmod 664 {} \;
sudo rm -f fix-permissions.php
```

### Apache

```bash
sudo apt install apache2 libapache2-mod-php8.2
sudo a2enmod rewrite
```

`/etc/apache2/sites-available/mbg.conf`:

```apache
<VirtualHost *:80>
    ServerName mbg.co.id
    ServerAlias www.mbg.co.id
    DocumentRoot /var/www/mbg

    <Directory /var/www/mbg>
        AllowOverride All      # wajib: agar .htaccess aktif
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite mbg && sudo systemctl reload apache2
sudo apt install certbot python3-certbot-apache && sudo certbot --apache -d mbg.co.id -d www.mbg.co.id
```

<a id="nginx"></a>
### Nginx

Nginx **tidak membaca `.htaccess`**, jadi aturan keamanan dan routing harus ditulis di konfigurasi. Bagian ini **wajib**, jika tidak data di `database/` bisa diunduh siapa saja.

`/etc/nginx/sites-available/mbg`:

```nginx
server {
    listen 80;
    server_name mbg.co.id www.mbg.co.id;
    root /var/www/mbg;
    index index.php;

    client_max_body_size 10M;

    # Folder sensitif
    location ~ ^/(database|config|includes)(/|$) { deny all; return 403; }
    location ~* ^/uploads/.*\.php$ { deny all; return 403; }
    location ~ /\.(?!well-known) { deny all; return 403; }
    location = /dev-router.php { return 404; }

    # URL lama -> URL baru
    location ~ ^/public/home/(index\.html)?$ { return 301 /index.php; }
    location = /public/pages/home.html { return 301 /index.php; }
    location ~ ^/public/pages/([A-Za-z0-9_-]+)\.html$ { return 301 /$1.php; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # /produk.php, /about.php, dst. tidak ada sebagai file -> teruskan ke front controller
    location ~ \.php$ {
        try_files $uri /index.php?$query_string;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/mbg /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install certbot python3-certbot-nginx && sudo certbot --nginx -d mbg.co.id -d www.mbg.co.id
```

### Deploy memakai Git

Folder `database/` ikut tersimpan di repository. Jika men-deploy dengan `git pull`, data yang diedit di server (post, produk, password) bisa **tertimpa**. Pilihan aman:

- **Rekomendasi:** pindahkan data ke luar folder aplikasi ([bagian 5](#lokasi-folder-data-opsional-lebih-aman)); atau
- Backup `database/`, `uploads/`, `public/` sebelum `git pull`, lalu kembalikan.

---

## 13. Menjalankan di komputer lokal

Cukup PHP 8.1+, tanpa XAMPP dan tanpa MySQL:

```bash
cd genta-cms
php -S 127.0.0.1:8000 dev-router.php
```

| Alamat | Halaman |
|---|---|
| http://127.0.0.1:8000/ | Beranda website |
| http://127.0.0.1:8000/produk.php | Katalog produk |
| http://127.0.0.1:8000/blog.php | Blog |
| http://127.0.0.1:8000/admin/login.php | Dashboard admin (`admin` / `admin123`) |

`dev-router.php` meniru `.htaccess` (blokir folder sensitif, redirect URL lama, front controller) karena server bawaan PHP tidak membaca `.htaccess`. **Jalankan ulang** server setiap kali mengubah file ini.

Jika memakai **XAMPP/Laragon**, buat virtual host (mis. `http://mbg.test`) yang mengarah ke folder proyek. Jangan buka lewat `http://localhost/genta-cms/`.

---

## 14. Checklist setelah instalasi

- [ ] `fix-permissions.php` sudah **dihapus**
- [ ] Password `admin` sudah **diganti** (menu **Profile**)
- [ ] `https://domain-anda.com/database/users.json` → **403 Forbidden**
- [ ] `https://domain-anda.com/config/database.php` → **403 Forbidden**
- [ ] HTTPS aktif
- [ ] Simpan satu produk/post di admin, lalu pastikan halaman publiknya berubah (tanda folder `public/` writable)
- [ ] `https://domain-anda.com/` menampilkan beranda dan `/produk.php` terbuka
- [ ] Atur **Settings** (nama situs, zona waktu) dan **SEO**
- [ ] Sesuaikan nomor WhatsApp & email ([bagian 15](#15-menyesuaikan-konten--kontak))

---

## 15. Menyesuaikan konten & kontak

### Dari dashboard (tanpa coding)

| Yang ingin diubah | Menu |
|---|---|
| Halaman baru / desain visual | **Pages** → **Edit visual** |
| Artikel blog | **Posts** |
| Produk, harga, SKU, gambar | **Products** |
| Menu navbar & nama brand | **Navigation** |
| Tombol share artikel | **Plugins → Social Share** |
| Meta title/description, Analytics | **SEO** |
| Akun admin/editor | **Users** |

### Yang masih ditulis di kode

| Data | Lokasi |
|---|---|
| Nomor WhatsApp **form Hubungi Kami** (`6281313616756`) | `public/assets/contact-modal.js` (konstanta `WA` di baris atas) |
| Nomor WhatsApp pemesanan produk & footer | `includes/functions.php`, cari `$whatsappNumber` / `whatsappNumber` |
| Email kontak (`hello@vzytech.id`) | `includes/functions.php` (`publicCatalogFooter`) dan `public/home/index.html` |
| Konten beranda (hero, layanan, testimoni, kontak) | `public/home/index.html` |
| Warna & tampilan | `public/home/css/style.css` (variabel di `:root`) |
| Logo | Ganti SVG di `public/assets/brand/` |

Setelah mengubah `includes/functions.php`, simpan ulang satu produk atau post di admin agar halaman publik dibuat ulang.

> Pengaturan **URL Tombol CTA** di menu Navigation tidak lagi dipakai oleh tombol "Hubungi Kami", karena tombol itu kini selalu membuka form WhatsApp.

---

## 16. Backup, pindah hosting, dan reset

### Backup

Seluruh data situs ada di tiga folder:

```
database/   ← semua data (post, produk, user, pengaturan)
uploads/    ← gambar yang diunggah
public/     ← halaman hasil generate & logo
```

Contoh backup harian di VPS (crontab):

```bash
0 2 * * * cd /var/www/mbg && tar -czf /home/backup/mbg-$(date +\%F).tar.gz database uploads public
```

### Pindah hosting

1. Zip seluruh folder aplikasi, **termasuk** `database/` dan `uploads/`.
2. Upload & ekstrak di hosting baru ([bagian 11](#11-instalasi-di-shared-hosting-cpanel) / [12](#12-instalasi-di-vps-apache--nginx)).
3. Atur ulang izin folder. Tidak ada impor database.

### Reset ke kondisi awal

> ⚠️ Menghapus semua konten. Backup dulu.

1. Hapus semua `.json` di `database/` (**jangan** hapus `database/.htaccess`).
2. Buka `/admin/login.php`. Data awal dibuat otomatis; login kembali `admin` / `admin123`.

### Lupa password admin

Jika masih ada admin lain, ganti lewat menu **Users**. Jika tidak:

```bash
php -r "echo password_hash('PasswordBaru123', PASSWORD_DEFAULT), PHP_EOL;"
```

Ganti nilai `"password"` milik user `admin` di `database/users.json` dengan hash tersebut, login, lalu ganti lagi lewat **Profile**.

---

## 17. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| **403 Forbidden** di semua halaman | Server tidak mengizinkan sebagian aturan `.htaccess`. Ganti sementara dengan isi `.htaccess.simple`, lalu minta support mengaktifkan `AllowOverride All`. |
| **500 Internal Server Error** | Biasanya PHP < 8.1. Ubah di **Select PHP Version**; cek `error_log`. |
| Halaman putih / `syntax error, unexpected ...` | PHP terlalu lama. Gunakan 8.1+. |
| `/produk.php` atau `/about.php` menampilkan 404 | Halaman belum dipublikasikan atau belum digenerate: simpan ulang page di admin. Pada Nginx, pastikan blok `location ~ \.php$` memakai `try_files $uri /index.php`. |
| Beranda masih tampil di `/public/home/index.html` | Server belum memuat aturan redirect baru: jalankan ulang `php -S`, atau pastikan `.htaccess` terbaru terpasang; lalu hard refresh (Ctrl+Shift+R). |
| Login gagal terus padahal password benar | `database/` tidak writable atau sesi bermasalah. Jalankan `fix-permissions.php` dan bersihkan cookie. |
| Simpan berhasil tapi halaman publik tidak berubah | `public/pages/`, `public/blog/`, atau `public/home/` tidak writable. Atur ke `755`/`775`. |
| Upload gambar gagal | `uploads/` tidak writable, atau ukuran melebihi `upload_max_filesize` (produk maks. 5 MB). |
| CSS/logo/gambar tidak muncul, `/public/...` 404 | Aplikasi di subfolder. Pindahkan ke root domain/subdomain. |
| Tombol "Hubungi Kami" tidak membuka form | Halaman tidak melalui `index.php` (mis. dibuka langsung sebagai file statis). Buka lewat `/index.php` atau `/{slug}.php`. |
| `database/users.json` bisa dibuka di browser | **Bahaya.** Apache: pastikan `database/.htaccess` ada dan `AllowOverride All` aktif. Nginx: tambahkan aturan `location` dari [bagian 12](#nginx). |
| Jam/tanggal tidak sesuai WIB | Pilih zona waktu di **Settings**; atur juga `date.timezone = Asia/Jakarta` di PHP. |

---

## 18. Login default & peran pengguna

| Username | Password |
|---|---|
| `admin` | `admin123` |

**Segera ganti setelah login pertama.** Setelah root `/` kini menampilkan beranda, login admin hanya lewat `/admin/login.php`.

| Fitur | Admin | Editor |
|---|---|---|
| Posts, Pages (+ Builder), Products, Media, Comments, Navigation, SEO | ✅ | ✅ |
| Users, Plugins, Settings | ✅ | ❌ |
| Profile | ✅ | ✅ |

---

**Made by Genta** · © PT MBG
