# Vanilla PHP CMS — by vzy

CMS ringan berbasis **Vanilla PHP 8** dengan dashboard modern (floating + rounded).

## Fitur Lengkap

### Content
- **Posts** — CRUD, category, tags, featured image, SEO, draft/publish/trash
- **Pages** — custom HTML / CSS / JS per page + set Frontpage
- **Products** — kelola katalog produk, unggah gambar, dan terima pesanan WhatsApp dengan jumlah pilihan melalui shortcode `[product_catalog]`
- **Categories & Tags**
- **Media Library** — upload multi gambar, hapus, copy path
- **Comments** — form di frontend, moderasi (approve / spam / delete)
- **Menu** — primary menu (custom / page / post)

### Tools
- **SEO** — meta global, OG image, GA, robots.txt, sitemap toggle
- **Users** — kelola admin & editor (admin only)
- **Plugins** — toggle on/off
- **Activity log** — di dashboard
- **Profile** — ubah nama, email, password
- **Settings** — site name, timezone, comments, dll

### Frontend
- Homepage (frontpage page atau latest posts)
- Single post + komentar
- Archive kategori & tag
- Search
- Sidebar kategori
- Pretty URL (via `.htaccess`)

### Design
- Dashboard **floating rounded** (sidebar, topbar, cards)
- Footer fixed: **Made by vzy**

## Login
- Username: `admin`
- Password: `admin123`

## Instalasi

1. PHP 8.0+ (disarankan 8.2+)
2. Extract folder `cms` ke web root
3. Pastikan `database/` dan `uploads/` writable:
   ```bash
   chmod 755 database uploads
   ```
4. Buka `http://localhost/cms/admin/login.php`
5. Data JSON dibuat otomatis saat pertama akses

## Roles

| Fitur | Admin | Editor |
|-------|-------|--------|
| Posts, Pages, Media, Comments, Menu, SEO | ✅ | ✅ |
| Users, Plugins, Settings | ✅ | ❌ |
| Profile | ✅ | ✅ |

## Struktur

```
cms/
├── admin/           # Dashboard
│   ├── media.php, products.php
│   ├── users.php
│   ├── comments.php
│   ├── posts.php, pages.php, ...
│   └── assets/
├── config/          # Storage JSON
├── database/        # Data (auto)
├── uploads/         # Media files
├── includes/
├── index.php        # Frontend
├── .htaccess
└── README.md
```

**Made by vzy**
