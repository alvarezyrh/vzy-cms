# PT MBG — Website & CMS

Website perusahaan PT MBG beserta dashboard admin (CMS) berbasis **PHP murni**, tanpa framework dan **tanpa MySQL**. Semua data disimpan sebagai file JSON, jadi aplikasi ini bisa dipasang di hampir semua hosting PHP hanya dengan upload file.

**Fitur utama:** Posts & Blog · Pages · Katalog Produk (SKU, stok, pesan via WhatsApp) · Media · Komentar · Menu navigasi · SEO · Users (admin/editor) · Plugins (termasuk Social Share) · Activity log.

---

## Daftar Isi

1. [Persyaratan](#1-persyaratan)
2. [Tentang database (penting dibaca)](#2-tentang-database-penting-dibaca)
3. [Struktur folder](#3-struktur-folder)
4. [Instalasi di shared hosting (cPanel)](#4-instalasi-di-shared-hosting-cpanel)
5. [Instalasi di VPS (Apache / Nginx)](#5-instalasi-di-vps-apache--nginx)
6. [Menjalankan di komputer lokal](#6-menjalankan-di-komputer-lokal)
7. [Checklist setelah instalasi](#7-checklist-setelah-instalasi)
8. [Menyesuaikan konten & kontak](#8-menyesuaikan-konten--kontak)
9. [Backup, pindah hosting, dan reset](#9-backup-pindah-hosting-dan-reset)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Persyaratan

| Kebutuhan | Keterangan |
|---|---|
| **PHP** | **8.1 atau lebih baru** (disarankan 8.2+) |
| Ekstensi PHP | `json`, `mbstring`, `fileinfo`, `session` (biasanya sudah aktif di semua hosting) |
| Web server | Apache dengan `mod_rewrite` (paling mudah), LiteSpeed, atau Nginx |
| Database | **Tidak perlu MySQL** — lihat bagian 2 |
| Domain | Dipasang di **root domain atau subdomain**, misalnya `https://mbg.co.id` atau `https://web.mbg.co.id` |

> ⚠️ **Jangan pasang di subfolder** seperti `https://domain.com/cms/`. Halaman website memakai path absolut (`/public/...`, `/admin/...`, `/uploads/...`), sehingga harus berada di root domain/subdomain. Jika perlu memisahkan, buat **subdomain** dan arahkan document root-nya ke folder aplikasi.

---

## 2. Tentang database (penting dibaca)

Aplikasi ini **tidak memakai MySQL/MariaDB**. Penggantinya adalah file JSON di folder `database/`:

| File | Isi |
|---|---|
| `users.json` | Akun admin/editor (password tersimpan sebagai hash) |
| `settings.json` | Pengaturan situs, navigasi, Social Share |
| `posts.json`, `categories.json`, `tags.json`, `post_tags.json` | Artikel blog |
| `pages.json` | Halaman (Home, Produk, Blog, dll.) |
| `products.json` | Katalog produk, SKU, stok |
| `menus.json`, `menu_items.json` | Menu navigasi |
| `media.json`, `comments.json`, `plugins.json`, `seo_settings.json`, `activity.json` | Data lainnya |

**Artinya untuk hosting:**

- ✅ **Tidak perlu** membuat database, user database, atau mengisi host/username/password di mana pun.
- ✅ Jika folder `database/` kosong atau belum ada, file-file di atas **dibuat otomatis** saat halaman admin pertama kali dibuka (dengan akun default `admin` / `admin123`).
- ⚠️ Folder `database/` **wajib bisa ditulis** oleh PHP (izin `755` atau `775`).
- ⚠️ Folder `database/` **wajib tidak bisa diakses dari browser**. Untuk Apache/LiteSpeed sudah diatur otomatis oleh file `database/.htaccess`. Untuk Nginx, tambahkan aturan di [bagian 5](#nginx).

### Lokasi folder data (opsional, lebih aman)

Secara default lokasi data diatur di `config/database.php`:

```php
define('DATA_DIR', __DIR__ . '/../database');
```

Jika hosting Anda memungkinkan, pindahkan folder data **ke luar** `public_html` agar tidak mungkin diakses dari web. Contoh pada cPanel:

```php
// folder: /home/USERNAME/mbg-data  (sejajar dengan public_html, bukan di dalamnya)
define('DATA_DIR', '/home/USERNAME/mbg-data');
```

Lalu pindahkan semua file `.json` dari `database/` ke folder baru tersebut dan pastikan folder itu bisa ditulis.

### Batasan

Penyimpanan JSON sangat cocok untuk website perusahaan dengan **sedikit admin** dan ratusan konten. Aplikasi ini tidak dirancang untuk banyak admin yang mengedit secara bersamaan atau puluhan ribu data.

---

## 3. Struktur folder

```
vzy-cms/
├── admin/              Dashboard admin (login di /admin/login.php)
├── config/             database.php — lokasi & fungsi penyimpanan data   [diblokir dari web]
├── database/           Data JSON (pengganti MySQL)                       [diblokir dari web, harus writable]
├── includes/           functions.php — fungsi inti & generator halaman   [diblokir dari web]
├── public/
│   ├── home/           Halaman beranda (index.html, css, js)             [writable]
│   ├── pages/          Halaman hasil generate: produk.html, blog.html    [writable]
│   ├── blog/           Halaman artikel hasil generate + img/             [writable]
│   └── assets/brand/   Logo PT MBG (SVG) & favicon
├── uploads/            Gambar yang diunggah dari admin                   [writable, dibuat otomatis]
├── .htaccess           Aturan Apache (keamanan & pretty URL)
├── index.php           Root domain → diarahkan ke halaman login admin
├── fix-permissions.php Pemeriksa izin folder — HAPUS setelah instalasi
└── dev-router.php      Router untuk menjalankan di komputer lokal (tidak dipakai di hosting)
```

**Cara kerja halaman publik:** halaman produk, blog, dan artikel adalah file HTML statis yang **dibuat ulang otomatis** setiap kali Anda menyimpan produk, post, page, atau pengaturan plugin dari admin. Karena itu folder `public/pages/`, `public/blog/`, dan `public/home/` harus bisa ditulis. Jangan mengedit file di `public/pages/` dan `public/blog/` secara manual, karena akan tertimpa.

---

## 4. Instalasi di shared hosting (cPanel)

Panduan ini berlaku untuk cPanel, hPanel (Hostinger), DirectAdmin, dan panel serupa.

### Langkah 1 — Siapkan file

Di komputer Anda, kompres seluruh isi folder proyek menjadi `mbg-cms.zip`. Folder `.git` tidak perlu ikut.

### Langkah 2 — Atur versi PHP

1. Buka **cPanel → Select PHP Version** (atau **MultiPHP Manager**).
2. Pilih **PHP 8.1 atau lebih baru** (disarankan 8.2/8.3) untuk domain Anda.
3. Pastikan ekstensi `fileinfo` dan `mbstring` dicentang.

### Langkah 3 — Upload & ekstrak

1. Buka **File Manager → `public_html`** (atau document root subdomain Anda).
2. Kosongkan dulu file bawaan hosting seperti `index.html` atau `default.php`, jika ada.
3. Klik **Upload**, pilih `mbg-cms.zip`, lalu klik kanan file tersebut → **Extract**.
4. Pastikan hasilnya **langsung** berada di `public_html`, bukan di `public_html/vzy-cms/`:

   ```
   public_html/admin/
   public_html/database/
   public_html/public/
   public_html/index.php
   public_html/.htaccess      ← aktifkan "Show Hidden Files" untuk melihatnya
   ```

   Jika terekstrak ke subfolder, pindahkan semua isinya (**termasuk `.htaccess`**) ke `public_html`.

### Langkah 4 — Atur izin folder

Cara termudah:

1. Buka `https://domain-anda.com/fix-permissions.php` di browser.
2. Semua baris harus **[OK]**. File ini juga memeriksa versi PHP dan ekstensinya.

Jika ada **[FAIL]**, atur manual lewat File Manager (klik kanan → **Change Permissions**):

| Folder | Izin |
|---|---|
| `database/`, `uploads/`, `public/pages/`, `public/blog/`, `public/home/` | `755` (jika masih gagal: `775`) |
| File di dalam `database/` dan `public/home/index.html` | `644` (jika masih gagal: `664`) |

> Jangan gunakan `777` kecuali diminta oleh support hosting.

### Langkah 5 — Login & amankan

1. **Hapus `fix-permissions.php`** dari server.
2. Buka `https://domain-anda.com/admin/login.php`.
3. Login dengan **`admin` / `admin123`**, lalu **segera ganti password** di menu **Profile**.
4. Ikuti [checklist setelah instalasi](#7-checklist-setelah-instalasi).

### Langkah 6 — Aktifkan HTTPS

Aktifkan SSL gratis di **cPanel → SSL/TLS Status → Run AutoSSL** (atau menu Let's Encrypt). Untuk memaksa HTTPS, tambahkan baris berikut di bagian **atas** `.htaccess`, tepat setelah `RewriteEngine On`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Pengaturan PHP tambahan (opsional)

Jika upload gambar besar gagal atau jam di website tidak sesuai WIB, atur di **Select PHP Version → Options** atau buat file `.user.ini` di root:

```ini
upload_max_filesize = 8M
post_max_size = 10M
date.timezone = Asia/Jakarta
```

---

## 5. Instalasi di VPS (Apache / Nginx)

Contoh untuk Ubuntu/Debian dengan PHP 8.2. Sesuaikan versi PHP, domain, dan path.

```bash
# 1. Paket yang dibutuhkan
sudo apt update
sudo apt install php8.2 php8.2-mbstring php8.2-fpm unzip   # fileinfo & json sudah bawaan

# 2. Salin aplikasi
sudo mkdir -p /var/www/mbg
sudo unzip mbg-cms.zip -d /var/www/mbg        # atau: git clone <repo> /var/www/mbg

# 3. Izin folder (www-data = user web server di Ubuntu/Debian)
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
        # Wajib: agar .htaccess (termasuk proteksi folder database/) aktif
        AllowOverride All
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

Nginx **tidak membaca `.htaccess`**, jadi aturan keamanannya harus ditulis di konfigurasi server. Bagian ini **wajib**, jika tidak data di `database/` bisa diunduh siapa saja.

`/etc/nginx/sites-available/mbg`:

```nginx
server {
    listen 80;
    server_name mbg.co.id www.mbg.co.id;
    root /var/www/mbg;
    index index.php index.html;

    client_max_body_size 10M;

    # Blokir folder sensitif (data JSON, konfigurasi, kode inti)
    location ~ ^/(database|config|includes)(/|$) { deny all; return 403; }

    # Blokir eksekusi PHP di folder upload
    location ~* ^/uploads/.*\.php$ { deny all; return 403; }

    # Blokir file tersembunyi (.htaccess, .git, dll.)
    location ~ /\.(?!well-known) { deny all; return 403; }

    # Jangan jalankan helper lokal di server
    location = /dev-router.php { return 404; }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
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

Folder `database/` saat ini **ikut tersimpan di repository**. Jika Anda men-deploy dengan `git pull`, data yang diedit di server (post, produk, password) bisa **tertimpa** oleh data dari repository.

Pilihan aman:

- **Rekomendasi:** pindahkan data ke luar folder aplikasi ([lihat bagian 2](#lokasi-folder-data-opsional-lebih-aman)), sehingga `git pull` hanya memperbarui kode; atau
- Backup `database/`, `uploads/`, dan `public/` sebelum `git pull`, lalu kembalikan setelahnya.

---

## 6. Menjalankan di komputer lokal

Cukup PHP 8.1+, tanpa XAMPP dan tanpa MySQL:

```bash
cd vzy-cms
php -S 127.0.0.1:8000 dev-router.php
```

| Alamat | Halaman |
|---|---|
| http://127.0.0.1:8000/admin/login.php | Dashboard admin (`admin` / `admin123`) |
| http://127.0.0.1:8000/public/home/ | Beranda website |
| http://127.0.0.1:8000/public/pages/produk.html | Katalog produk |
| http://127.0.0.1:8000/public/pages/blog.html | Blog |

`dev-router.php` meniru aturan `.htaccess`, termasuk memblokir `database/`, `config/`, dan `includes/`, karena server bawaan PHP tidak membaca `.htaccess`.

Jika memakai **XAMPP/Laragon**, buat virtual host (misalnya `http://mbg.test`) yang mengarah ke folder proyek. Jangan buka lewat `http://localhost/vzy-cms/` karena aplikasi harus berada di root domain.

---

## 7. Checklist setelah instalasi

- [ ] `fix-permissions.php` sudah **dihapus** dari server
- [ ] Password `admin` sudah **diganti** (menu **Profile**)
- [ ] `https://domain-anda.com/database/users.json` menampilkan **403 Forbidden**, bukan isi file
- [ ] `https://domain-anda.com/config/database.php` menampilkan **403 Forbidden**
- [ ] HTTPS aktif
- [ ] Coba simpan satu produk/post di admin, lalu pastikan halaman publiknya ikut berubah (tanda folder `public/` bisa ditulis)
- [ ] Atur **Settings** (nama situs, zona waktu) dan **SEO** di dashboard
- [ ] Sesuaikan nomor WhatsApp & email ([bagian 8](#8-menyesuaikan-konten--kontak))

---

## 8. Menyesuaikan konten & kontak

### Dari dashboard admin (tanpa coding)

| Yang ingin diubah | Menu |
|---|---|
| Artikel blog | **Posts** — halaman blog & artikel diperbarui otomatis |
| Produk, harga, SKU, stok, gambar | **Products** |
| Menu navbar, nama brand, tombol CTA | **Navigation** |
| Tombol share artikel (jaringan, posisi, label) | **Plugins → Social Share** |
| Meta title/description, Google Analytics | **SEO** |
| Akun admin/editor | **Users** |

### Yang masih ditulis langsung di kode

| Data | Lokasi |
|---|---|
| Nomor WhatsApp pemesanan & footer (`6281313616756`) | `includes/functions.php`, cari `$whatsappNumber` |
| Email kontak (`hello@vzytech.id`) | `includes/functions.php` (fungsi `publicCatalogFooter`) dan `public/home/index.html` |
| Konten beranda (hero, layanan, testimoni, kontak) | `public/home/index.html` |
| Logo | Ganti file SVG di `public/assets/brand/` (`mbg-mark.svg`, `favicon.svg`, `mbg-logo-light.svg`, `mbg-logo-dark.svg`) |

Setelah mengubah `includes/functions.php`, buka admin lalu simpan ulang satu produk atau post agar halaman publik dibuat ulang.

### Halaman yang tampil saat membuka domain

Secara default, membuka `https://domain-anda.com/` **diarahkan ke halaman login admin**. Agar pengunjung langsung melihat website, ubah baris ke-3 di `index.php` (root):

```php
header('Location: public/home/');
```

Admin tetap bisa masuk lewat `https://domain-anda.com/admin/login.php`.

---

## 9. Backup, pindah hosting, dan reset

### Backup

Seluruh data situs ada di tiga folder. Cukup salin (atau zip) ketiganya secara berkala:

```
database/   ← semua data (post, produk, user, pengaturan)
uploads/    ← gambar yang diunggah
public/     ← halaman hasil generate & logo
```

Contoh backup harian di VPS (crontab):

```bash
0 2 * * * cd /var/www/mbg && tar -czf /home/backup/mbg-$(date +\%F).tar.gz database uploads public
```

Di cPanel: **File Manager → pilih ketiga folder → Compress**, lalu unduh file zip-nya.

### Pindah hosting

1. Zip seluruh folder aplikasi di hosting lama, **termasuk** `database/` dan `uploads/`.
2. Upload & ekstrak di hosting baru, mengikuti [bagian 4](#4-instalasi-di-shared-hosting-cpanel) atau [bagian 5](#5-instalasi-di-vps-apache--nginx).
3. Atur ulang izin folder. Tidak ada impor database, karena data ikut terbawa di dalam `database/`.

### Reset ke kondisi awal (instalasi bersih)

> ⚠️ Ini menghapus semua konten. Backup dulu.

1. Hapus semua file `.json` di `database/`. **Jangan** hapus `database/.htaccess`.
2. Buka `/admin/login.php`. Data awal dibuat otomatis dan login kembali ke `admin` / `admin123`.

### Lupa password admin

Jika masih ada admin lain, ganti password lewat menu **Users**. Jika tidak, buat hash baru di komputer:

```bash
php -r "echo password_hash('PasswordBaru123', PASSWORD_DEFAULT), PHP_EOL;"
```

Lalu buka `database/users.json` dan ganti nilai `"password"` milik user `admin` dengan hash tersebut (tetap di dalam tanda kutip). Login, lalu ganti lagi password lewat menu **Profile**.

---

## 10. Troubleshooting

| Gejala | Penyebab & solusi |
|---|---|
| **403 Forbidden** di semua halaman | Server tidak mengizinkan sebagian aturan `.htaccess`. Ganti sementara isi `.htaccess` dengan isi `.htaccess.simple`, lalu hubungi support agar `AllowOverride All` diaktifkan. |
| **500 Internal Server Error** | Biasanya versi PHP < 8.1. Ubah di **Select PHP Version**. Cek juga **Errors** di cPanel atau `error_log` di folder aplikasi. |
| Halaman putih / error `syntax error, unexpected ...` | Versi PHP terlalu lama. Gunakan PHP 8.1+. |
| Login gagal terus padahal password benar | Folder `database/` tidak bisa ditulis atau sesi PHP bermasalah. Jalankan `fix-permissions.php` (upload ulang jika sudah dihapus) dan bersihkan cookie browser. |
| Simpan produk/post berhasil, tapi halaman website tidak berubah | Folder `public/pages/`, `public/blog/`, atau `public/home/` tidak bisa ditulis. Atur izin ke `755`/`775`. |
| Upload gambar gagal | Folder `uploads/` tidak bisa ditulis, atau ukuran file melebihi `upload_max_filesize`. Produk maksimal 5 MB. |
| CSS/logo/gambar tidak muncul, link ke `/public/...` 404 | Aplikasi dipasang di subfolder. Pindahkan ke root domain atau subdomain ([bagian 1](#1-persyaratan)). |
| `database/users.json` bisa dibuka di browser | **Bahaya.** Di Apache, pastikan file `database/.htaccess` ada dan `AllowOverride All` aktif. Di Nginx, tambahkan aturan `location` dari [bagian 5](#nginx). |
| Jam/tanggal tidak sesuai WIB | Pilih zona waktu di **Settings** dashboard. Untuk berjaga-jaga, atur juga `date.timezone = Asia/Jakarta` di pengaturan PHP hosting ([bagian 4](#pengaturan-php-tambahan-opsional)). |

---

## Login default

| Username | Password |
|---|---|
| `admin` | `admin123` |

**Segera ganti setelah login pertama.**

## Peran pengguna

| Fitur | Admin | Editor |
|---|---|---|
| Posts, Pages, Products, Media, Comments, Navigation, SEO | ✅ | ✅ |
| Users, Plugins, Settings | ✅ | ❌ |
| Profile | ✅ | ✅ |

---

**Made by vzy** · © PT MBG
