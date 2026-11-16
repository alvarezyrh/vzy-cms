/* PT MBG Page Builder — katalog widget, template section, dan CSS output. */
(function () {
'use strict';

const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const kebab = k => k.replace(/[A-Z]/g, m => '-' + m.toLowerCase());
const st = o => Object.entries(o).filter(([, v]) => v !== '' && v != null && v !== false).map(([k, v]) => kebab(k) + ':' + v).join(';');
const px = v => (v === '' || v == null) ? '' : (/^-?\d+(\.\d+)?$/.test(String(v)) ? v + 'px' : String(v));
const uid = () => 'x' + Math.random().toString(36).slice(2, 9);
const clone = o => JSON.parse(JSON.stringify(o));
const BR = s => esc(s).replace(/\n/g, '<br>');

/* ---------- CSS yang ikut tersimpan di halaman publik ---------- */
const OUT_CSS = `
.mbg-sec{position:relative;width:100%}
.mbg-in{width:min(1140px,92%);margin-inline:auto;position:relative;z-index:1}
.mbg-sec.mbg-full>.mbg-in{width:100%}
.mbg-sec.mbg-narrow>.mbg-in{width:min(760px,92%)}
.mbg-ov{position:absolute;inset:0;z-index:0;pointer-events:none}
.mbg-row{display:grid}
.mbg-col{display:flex;flex-direction:column;min-width:0}
.mbg-w{min-width:0}
.mbg-w img{max-width:100%}
.mbg-btn-outline{background:transparent;color:var(--turquoise-dark);border:2px solid var(--turquoise)}
.mbg-btn-outline:hover{background:var(--turquoise);color:#fff;transform:translateY(-3px)}
.mbg-btn-dark{background:var(--slate);color:#fff}
.mbg-btn-dark:hover{background:var(--slate-soft);transform:translateY(-3px)}
.mbg-btn-sm{padding:9px 18px;font-size:.85rem}
.mbg-btn-lg{padding:18px 34px;font-size:1.05rem}
.mbg-btn-full{display:flex;justify-content:center;width:100%}
.mbg-btns{display:flex;flex-wrap:wrap;gap:12px}
.mbg-ps::before{content:attr(data-n)!important;counter-increment:none!important}
.mbg-ps{height:100%}
.service-card.mbg-a-green .service-icon{background:linear-gradient(135deg,#22c55e,#15803d)!important;box-shadow:0 8px 24px rgba(34,197,94,.35)!important}
.service-card.mbg-a-teal .service-icon{background:linear-gradient(135deg,#0d9488,#0f766e)!important;box-shadow:0 8px 24px rgba(45,212,191,.35)!important}
.service-card.mbg-a-blue .service-icon{background:linear-gradient(135deg,#3b82f6,#1d4ed8)!important;box-shadow:0 8px 24px rgba(59,130,246,.35)!important}
.service-card.mbg-a-orange .service-icon{background:linear-gradient(135deg,#fb923c,#ea580c)!important;box-shadow:0 8px 24px rgba(251,146,60,.35)!important}
.service-card.mbg-a-purple .service-icon{background:linear-gradient(135deg,#a78bfa,#6d28d9)!important;box-shadow:0 8px 24px rgba(167,139,250,.35)!important}
.mbg-col>.mbg-w:only-child{flex:1;display:flex;flex-direction:column}
.mbg-col>.mbg-w:only-child>*{flex:1}
.mbg-card-h{height:100%}
.mbg-acc details{border:1px solid var(--border);border-radius:12px;background:#fff;margin-bottom:10px;overflow:hidden}
.mbg-acc summary{padding:16px 20px;font-weight:600;cursor:pointer;list-style:none;display:flex;justify-content:space-between;gap:12px;align-items:center}
.mbg-acc summary::-webkit-details-marker{display:none}
.mbg-acc summary::after{content:"+";font-size:1.4rem;color:var(--turquoise);line-height:1}
.mbg-acc details[open] summary::after{content:"\\2212"}
.mbg-acc details p{padding:0 20px 18px;color:var(--muted);margin:0}
.mbg-gal{display:grid;gap:12px}
.mbg-gal img{width:100%;height:100%;object-fit:cover;aspect-ratio:4/3;display:block}
.mbg-vid{position:relative;aspect-ratio:16/9;border-radius:14px;overflow:hidden;background:#000}
.mbg-vid iframe,.mbg-vid video{position:absolute;inset:0;width:100%;height:100%;border:0}
.mbg-stat{text-align:center}
.mbg-stat strong{display:block;font-size:clamp(2rem,4vw,3rem);line-height:1.1;font-weight:700}
.mbg-stat span{display:block;margin-top:6px;opacity:.85}
.mbg-price{position:relative;padding:34px 28px;border-radius:var(--radius);background:#fff;border:1px solid var(--border);height:100%;display:flex;flex-direction:column;transition:all .3s var(--ease)}
.mbg-price:hover{box-shadow:var(--shadow);transform:translateY(-4px)}
.mbg-price.hl{border:2px solid var(--turquoise);box-shadow:var(--shadow)}
.mbg-price .tag{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,var(--turquoise),var(--green));color:#fff;font-size:.72rem;font-weight:700;padding:4px 14px;border-radius:99px;text-transform:uppercase;letter-spacing:.06em}
.mbg-price h3{font-size:1.1rem;margin:0 0 10px}
.mbg-price .amt{font-size:2.4rem;font-weight:700;line-height:1}
.mbg-price .amt small{font-size:.9rem;font-weight:500;color:var(--muted)}
.mbg-price ul{margin:22px 0;display:flex;flex-direction:column;gap:10px;flex:1}
.mbg-price li{display:flex;gap:10px;font-size:.92rem;align-items:flex-start}
.mbg-price li i{color:var(--green);margin-top:4px}
.mbg-team{text-align:center;padding:26px 20px;border-radius:var(--radius);background:#fff;border:1px solid var(--border);height:100%;transition:all .3s var(--ease)}
.mbg-team:hover{box-shadow:var(--shadow);transform:translateY(-4px)}
.mbg-team img{width:110px;height:110px;border-radius:50%;object-fit:cover;margin:0 auto 16px;display:block;background:var(--off-white)}
.mbg-team h4{margin:0}
.mbg-team p{color:var(--muted);font-size:.88rem;margin:2px 0 12px}
.mbg-team a{color:var(--turquoise);font-size:1.1rem}
.mbg-ilist{list-style:none;margin:0;padding:0}
.mbg-ilist li{display:flex;gap:12px;align-items:flex-start;padding:7px 0}
.mbg-ilist li i{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;flex:none;background:rgba(13,148,136,.12);color:var(--turquoise);font-size:.9rem}
.mbg-social{display:flex;gap:10px;flex-wrap:wrap}
.mbg-social a{width:42px;height:42px;border-radius:50%;display:inline-grid;place-items:center;background:var(--off-white);color:var(--turquoise);border:1px solid var(--border);transition:all .3s var(--ease)}
.mbg-social a:hover{background:var(--turquoise);color:#fff;transform:translateY(-3px)}
.mbg-form{padding:28px;border-radius:var(--radius);background:#fff;border:1px solid var(--border);box-shadow:var(--shadow)}
.mbg-form h3{margin:0 0 6px}
.mbg-form p{color:var(--muted);margin:0 0 16px;font-size:.92rem}
.mbg-form input,.mbg-form textarea{width:100%;padding:12px 14px;border:1px solid #cbd5e1;border-radius:10px;font:inherit;margin-bottom:12px;background:#fff}
.mbg-form input:focus,.mbg-form textarea:focus{outline:2px solid var(--turquoise-light);border-color:transparent}
.mbg-form button{width:100%;justify-content:center}
.mbg-map iframe{width:100%;border:0;border-radius:14px;display:block}
.mbg-ph{padding:34px 20px;text-align:center;border:2px dashed #94a3b8;border-radius:14px;color:#64748b;background:#f8fafc}
.mbg-ph i{font-size:30px;color:var(--turquoise);display:block;margin-bottom:8px}
@media(max-width:992px){.mbg-sec.st-tablet .mbg-row{grid-template-columns:minmax(0,1fr)!important}.mbg-hide-t{display:none!important}}
@media(max-width:768px){.mbg-row{grid-template-columns:minmax(0,1fr)!important}.mbg-hide-m{display:none!important}.mbg-sec{background-attachment:scroll!important}}
@media(min-width:993px){.mbg-hide-d{display:none!important}}
`;

/* ---------- Definisi field ---------- */
const F = {
  text: (k, l, o = {}) => ({ k, t: 'text', l, ...o }),
  area: (k, l, o = {}) => ({ k, t: 'area', l, ...o }),
  code: (k, l, o = {}) => ({ k, t: 'code', l, ...o }),
  num: (k, l, o = {}) => ({ k, t: 'num', l, ...o }),
  range: (k, l, min, max, o = {}) => ({ k, t: 'range', l, min, max, ...o }),
  color: (k, l, o = {}) => ({ k, t: 'color', l, ...o }),
  sel: (k, l, opts, o = {}) => ({ k, t: 'sel', l, opts, ...o }),
  seg: (k, l, opts, o = {}) => ({ k, t: 'seg', l, opts, ...o }),
  img: (k, l, o = {}) => ({ k, t: 'img', l, ...o }),
  url: (k, l, o = {}) => ({ k, t: 'url', l, ...o }),
  icon: (k, l, o = {}) => ({ k, t: 'icon', l, ...o }),
  sw: (k, l, o = {}) => ({ k, t: 'sw', l, ...o }),
  list: (k, l, f, add, o = {}) => ({ k, t: 'list', l, f, add, ...o }),
  head: (l, o = {}) => ({ t: 'head', l, ...o }),
};
const ALIGN = [['left', '<i class="fas fa-align-left"></i>'], ['center', '<i class="fas fa-align-center"></i>'], ['right', '<i class="fas fa-align-right"></i>']];
const ACCENTS = [['teal', 'Teal'], ['green', 'Hijau'], ['blue', 'Biru'], ['orange', 'Oranye'], ['purple', 'Ungu']];

const WD = {};
function reg(type, label, icon, group, def, fields, html, tags = '') {
  WD[type] = { type, label, icon, group, def, fields, html, tags };
}

/* ---------- DASAR ---------- */
reg('heading', 'Judul', 'fa-heading', 'Dasar',
  { text: 'Judul Anda di sini', tag: 'h2', size: '', color: '', weight: '700', align: 'left', lh: '1.2' },
  [F.text('text', 'Teks'), F.sel('tag', 'Tag HTML', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p']),
   F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.num('size', 'Ukuran font (px)', { tab: 's', ph: 'otomatis' }),
   F.color('color', 'Warna', { tab: 's' }), F.sel('weight', 'Ketebalan', [['400', 'Normal'], ['500', 'Medium'], ['600', 'Semibold'], ['700', 'Bold'], ['800', 'Extra bold']], { tab: 's' }),
   F.num('lh', 'Tinggi baris', { tab: 's', step: 0.1 })],
  (s, x) => `<${s.tag}${x.f('text')} style="${st({ margin: 0, fontSize: px(s.size), color: s.color, fontWeight: s.weight, lineHeight: s.lh })}">${esc(s.text)}</${s.tag}>`, 'title judul h1');

reg('text', 'Teks', 'fa-align-left', 'Dasar',
  { html: '<p>Klik dua kali di sini untuk mengedit teks. Anda juga bisa memakai <strong>tebal</strong>, <em>miring</em>, dan <a href="#">tautan</a>.</p>', align: 'left', size: '', color: '', lh: '1.7' },
  [F.code('html', 'Konten (HTML)', { hint: 'Boleh memakai tag HTML: p, strong, em, a, ul, li, dll.' }),
   F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.num('size', 'Ukuran font (px)', { tab: 's', ph: 'otomatis' }),
   F.color('color', 'Warna', { tab: 's' }), F.num('lh', 'Tinggi baris', { tab: 's', step: 0.1 })],
  (s, x) => `<div${x.f('html', 1)} style="${st({ fontSize: px(s.size), color: s.color, lineHeight: s.lh })}">${s.html}</div>`, 'paragraf text');

reg('image', 'Gambar', 'fa-image', 'Dasar',
  { src: 'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=80', alt: '', link: '', align: 'center', width: 100, radius: 16, shadow: false, height: '', newTab: false },
  [F.img('src', 'Gambar'), F.text('alt', 'Teks alternatif (SEO)'), F.url('link', 'Tautan (opsional)'), F.sw('newTab', 'Buka di tab baru'),
   F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.range('width', 'Lebar (%)', 10, 100, { tab: 's' }),
   F.num('height', 'Tinggi tetap (px)', { tab: 's', ph: 'otomatis' }), F.num('radius', 'Radius sudut (px)', { tab: 's' }), F.sw('shadow', 'Bayangan', { tab: 's' })],
  (s) => {
    const img = `<img src="${esc(s.src)}" alt="${esc(s.alt)}" loading="lazy" style="${st({ width: s.width + '%', height: px(s.height), objectFit: s.height ? 'cover' : '', borderRadius: px(s.radius), boxShadow: s.shadow ? '0 18px 50px rgba(15,23,42,.22)' : '', display: 'inline-block', verticalAlign: 'top' })}">`;
    return s.link ? `<a href="${esc(s.link)}"${s.newTab ? ' target="_blank" rel="noopener"' : ''}>${img}</a>` : img;
  }, 'foto picture');

const btnCls = s => 'btn ' + ({ primary: 'btn-primary', outline: 'mbg-btn-outline', dark: 'mbg-btn-dark', light: 'btn-ghost' }[s.variant] || 'btn-primary') + (s.size === 'sm' ? ' mbg-btn-sm' : s.size === 'lg' ? ' mbg-btn-lg' : '') + (s.full ? ' mbg-btn-full' : '');
const btnV = [['primary', 'Utama (gradasi)'], ['outline', 'Outline'], ['dark', 'Gelap'], ['light', 'Transparan (di latar gelap)']];
reg('button', 'Tombol', 'fa-hand-pointer', 'Dasar',
  { text: 'Hubungi Kami', url: '#', variant: 'primary', size: 'md', icon: 'fas fa-arrow-right', iconPos: 'after', newTab: false, full: false, align: 'left' },
  [F.text('text', 'Teks tombol'), F.url('url', 'Tautan', { hint: 'Contoh: #contact, /public/pages/produk.html, https://wa.me/628…' }), F.sw('newTab', 'Buka di tab baru'),
   F.icon('icon', 'Ikon (Font Awesome)'), F.seg('iconPos', 'Posisi ikon', [['before', 'Sebelum'], ['after', 'Sesudah']]),
   F.sel('variant', 'Gaya', btnV, { tab: 's' }), F.seg('size', 'Ukuran', [['sm', 'Kecil'], ['md', 'Sedang'], ['lg', 'Besar']], { tab: 's' }),
   F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.sw('full', 'Lebar penuh', { tab: 's' })],
  (s, x) => {
    const i = s.icon ? `<i class="${esc(s.icon)}"></i>` : '';
    return `<a class="${btnCls(s)}" href="${esc(s.url)}"${s.newTab ? ' target="_blank" rel="noopener"' : ''}>${s.iconPos === 'before' ? i : ''}<span${x.f('text')}>${esc(s.text)}</span>${s.iconPos !== 'before' ? i : ''}</a>`;
  }, 'button cta link');

reg('buttons', 'Grup Tombol', 'fa-grip', 'Dasar',
  { t1: 'Jelajahi Layanan', u1: '#services', v1: 'primary', i1: 'fas fa-rocket', t2: 'Konsultasi Gratis', u2: '#contact', v2: 'light', i2: 'fas fa-play', size: 'md', align: 'left' },
  [F.head('Tombol 1'), F.text('t1', 'Teks'), F.url('u1', 'Tautan'), F.icon('i1', 'Ikon'), F.sel('v1', 'Gaya', btnV),
   F.head('Tombol 2 (kosongkan teks untuk menyembunyikan)'), F.text('t2', 'Teks'), F.url('u2', 'Tautan'), F.icon('i2', 'Ikon'), F.sel('v2', 'Gaya', btnV),
   F.seg('size', 'Ukuran', [['sm', 'Kecil'], ['md', 'Sedang'], ['lg', 'Besar']], { tab: 's' }), F.seg('align', 'Perataan', ALIGN, { tab: 's' })],
  (s) => {
    const b = (t, u, v, i) => t ? `<a class="${btnCls({ variant: v, size: s.size })}" href="${esc(u)}">${i ? `<i class="${esc(i)}"></i>` : ''}${esc(t)}</a>` : '';
    const j = { left: 'flex-start', center: 'center', right: 'flex-end' }[s.align] || 'flex-start';
    return `<div class="mbg-btns" style="justify-content:${j}">${b(s.t1, s.u1, s.v1, s.i1)}${b(s.t2, s.u2, s.v2, s.i2)}</div>`;
  }, 'button tombol ganda');

reg('icon', 'Ikon', 'fa-star', 'Dasar',
  { icon: 'fas fa-star', size: 40, color: '#0d9488', bg: '', box: 72, align: 'center' },
  [F.icon('icon', 'Ikon'), F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.num('size', 'Ukuran ikon (px)', { tab: 's' }),
   F.color('color', 'Warna ikon', { tab: 's' }), F.color('bg', 'Warna latar (lingkaran)', { tab: 's' }), F.num('box', 'Ukuran lingkaran (px)', { tab: 's' })],
  (s) => `<span style="${st({ display: 'inline-grid', placeItems: 'center', fontSize: px(s.size), color: s.color, background: s.bg, width: s.bg ? px(s.box) : '', height: s.bg ? px(s.box) : '', borderRadius: '50%' })}"><i class="${esc(s.icon)}"></i></span>`, 'icon');

reg('divider', 'Pembatas', 'fa-minus', 'Dasar',
  { style: 'solid', color: '#cbd5e1', weight: 1, width: 100, align: 'center' },
  [F.sel('style', 'Gaya', ['solid', 'dashed', 'dotted', 'double']), F.color('color', 'Warna'), F.num('weight', 'Ketebalan (px)'), F.range('width', 'Lebar (%)', 5, 100),
   F.seg('align', 'Perataan', ALIGN, { tab: 's' })],
  (s) => `<hr style="${st({ border: 0, borderTop: `${s.weight}px ${s.style} ${s.color}`, width: s.width + '%', margin: s.align === 'center' ? '0 auto' : s.align === 'right' ? '0 0 0 auto' : '0' })}">`, 'hr garis');

reg('spacer', 'Spasi', 'fa-arrows-up-down', 'Dasar', { height: 40 },
  [F.range('height', 'Tinggi (px)', 4, 300)],
  (s) => `<div style="height:${px(s.height)}" aria-hidden="true"></div>`, 'space jarak');

reg('video', 'Video', 'fa-circle-play', 'Dasar',
  { url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', ratio: '16/9', radius: 14 },
  [F.url('url', 'URL YouTube / Vimeo / file .mp4'), F.sel('ratio', 'Rasio', [['16/9', '16:9'], ['4/3', '4:3'], ['1/1', '1:1'], ['21/9', '21:9']], { tab: 's' }), F.num('radius', 'Radius sudut (px)', { tab: 's' })],
  (s) => {
    const u = String(s.url || '').trim(); let m, inner = '';
    if ((m = u.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{6,})/))) inner = `<iframe src="https://www.youtube.com/embed/${m[1]}" loading="lazy" allowfullscreen title="Video"></iframe>`;
    else if ((m = u.match(/vimeo\.com\/(\d+)/))) inner = `<iframe src="https://player.vimeo.com/video/${m[1]}" loading="lazy" allowfullscreen title="Video"></iframe>`;
    else if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(u)) inner = `<video src="${esc(u)}" controls playsinline></video>`;
    else inner = '<div class="mbg-ph" style="position:absolute;inset:0;display:grid;place-content:center"><i class="fas fa-circle-play"></i>Masukkan URL video</div>';
    return `<div class="mbg-vid" style="aspect-ratio:${esc(s.ratio)};border-radius:${px(s.radius)}">${inner}</div>`;
  }, 'youtube vimeo');

reg('html', 'HTML Kustom', 'fa-code', 'Dasar',
  { code: '<div style="padding:24px;border-radius:14px;background:#f0fdfa;border:1px solid #99f6e4">Kode HTML Anda</div>' },
  [F.code('code', 'Kode HTML', { hint: 'Dapat berisi HTML/CSS/JS bebas, atau shortcode seperti [product_catalog] dan [blog_posts].' })],
  (s) => s.code, 'embed script shortcode');

reg('map', 'Peta', 'fa-location-dot', 'Dasar',
  { address: 'Jakarta, Indonesia', height: 360, zoom: 14 },
  [F.text('address', 'Alamat / lokasi'), F.range('zoom', 'Zoom', 3, 20), F.num('height', 'Tinggi (px)', { tab: 's' })],
  (s) => `<div class="mbg-map"><iframe title="Peta" loading="lazy" style="height:${px(s.height)}" src="https://maps.google.com/maps?q=${encodeURIComponent(s.address)}&z=${s.zoom}&output=embed"></iframe></div>`, 'google maps lokasi');

/* ---------- KARTU ---------- */
reg('sechead', 'Judul Seksi', 'fa-heading', 'Kartu & Konten',
  { label: 'Layanan Utama', title: 'Tiga Pilar Keunggulan Kami', desc: 'Solusi terintegrasi dari strategi hingga eksekusi — disesuaikan dengan kebutuhan organisasi Anda.', align: 'center', color: '', descColor: '' },
  [F.text('label', 'Label kecil'), F.text('title', 'Judul'), F.area('desc', 'Deskripsi'), F.seg('align', 'Perataan', [['left', 'Kiri'], ['center', 'Tengah']], { tab: 's' }),
   F.color('color', 'Warna judul', { tab: 's' }), F.color('descColor', 'Warna deskripsi', { tab: 's' })],
  (s, x) => `<div class="section-header${s.align === 'center' ? ' center' : ''}" style="margin:0">`
    + (s.label ? `<div class="section-label"${x.f('label')}>${esc(s.label)}</div>` : '')
    + `<h2 class="section-title"${x.f('title')} style="${st({ color: s.color })}">${esc(s.title)}</h2>`
    + (s.desc ? `<p class="section-desc"${x.f('desc')} style="${st({ marginBottom: 0, color: s.descColor })}">${BR(s.desc)}</p>` : '') + '</div>', 'heading label title');

const waLink = (num, msg) => `https://wa.me/${String(num).replace(/\D/g, '')}?text=${encodeURIComponent(msg)}`;
reg('service', 'Kartu Layanan', 'fa-layer-group', 'Kartu & Konten',
  { icon: 'fas fa-code', title: 'IT Software', desc: 'Pengembangan aplikasi custom, sistem enterprise, API, dan produk digital dengan metodologi agile.', items: [{ t: 'Web & Mobile Application' }, { t: 'ERP / CRM / Custom System' }, { t: 'Cloud-native & Microservices' }], btn: 'Hubungi', wa: '6281313616756', accent: 'teal' },
  [F.icon('icon', 'Ikon'), F.text('title', 'Judul'), F.area('desc', 'Deskripsi'), F.list('items', 'Daftar poin', [F.text('t', 'Teks')], { t: 'Poin baru' }),
   F.text('btn', 'Teks tombol (kosongkan untuk menyembunyikan)'), F.text('wa', 'Nomor WhatsApp tujuan', { hint: 'Format internasional tanpa +, mis. 62812…' }),
   F.sel('accent', 'Warna ikon', ACCENTS, { tab: 's' })],
  (s, x) => `<article class="service-card mbg-card-h mbg-a-${esc(s.accent)}"><div class="service-icon"><i class="${esc(s.icon)}"></i></div><h3${x.f('title')}>${esc(s.title)}</h3><p${x.f('desc')}>${esc(s.desc)}</p>`
    + ((s.items || []).length ? `<ul>${s.items.map(i => `<li>${esc(i.t)}</li>`).join('')}</ul>` : '')
    + (s.btn ? `<a class="service-contact-button" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none" href="${esc(waLink(s.wa, 'Halo PT MBG, saya tertarik dengan layanan ' + s.title))}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> ${esc(s.btn)}</a>` : '') + '</article>', 'service card layanan');

reg('feature', 'Kotak Fitur', 'fa-square-check', 'Kartu & Konten',
  { icon: 'fas fa-shield-halved', title: 'Keamanan & Kepatuhan', desc: 'Standar keamanan data, best practice, dan dokumentasi profesional di setiap deliverable.' },
  [F.icon('icon', 'Ikon'), F.text('title', 'Judul'), F.area('desc', 'Deskripsi')],
  (s, x) => `<div class="about-feature"><div class="icon"><i class="${esc(s.icon)}"></i></div><div><h4${x.f('title')}>${esc(s.title)}</h4><p${x.f('desc')}>${esc(s.desc)}</p></div></div>`, 'feature box fitur');

reg('iconbox', 'Kotak Ikon', 'fa-cube', 'Kartu & Konten',
  { icon: 'fas fa-bolt', title: 'Cepat & Andal', desc: 'Deskripsi singkat tentang keunggulan Anda.', align: 'center', accent: 'teal' },
  [F.icon('icon', 'Ikon'), F.text('title', 'Judul'), F.area('desc', 'Deskripsi'), F.seg('align', 'Perataan', ALIGN, { tab: 's' }), F.sel('accent', 'Warna ikon', ACCENTS, { tab: 's' })],
  (s, x) => `<div class="service-card mbg-card-h mbg-a-${esc(s.accent)}" style="text-align:${esc(s.align)}"><div class="service-icon" style="${s.align === 'center' ? 'margin-inline:auto' : s.align === 'right' ? 'margin-left:auto' : ''}"><i class="${esc(s.icon)}"></i></div><h3${x.f('title')}>${esc(s.title)}</h3><p${x.f('desc')}>${esc(s.desc)}</p></div>`, 'icon box');

reg('testimonial', 'Testimoni', 'fa-quote-right', 'Kartu & Konten',
  { stars: 5, quote: 'Implementasi sistem custom berjalan mulus. Tim memahami kebutuhan operasional kami dan memberikan dokumentasi yang rapi.', name: 'Andi Rahman', role: 'CTO, Fintech Nusantara', avatar: '' },
  [F.range('stars', 'Bintang', 0, 5), F.area('quote', 'Kutipan'), F.text('name', 'Nama'), F.text('role', 'Jabatan, perusahaan'), F.img('avatar', 'Foto (opsional)')],
  (s, x) => {
    const ini = String(s.name || '?').split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();
    return `<div class="testimonial-card mbg-card-h"><div class="stars">${'★'.repeat(+s.stars || 0)}${'☆'.repeat(5 - (+s.stars || 0))}</div><blockquote${x.f('quote')}>“${esc(s.quote)}”</blockquote><div class="testimonial-author">`
      + (s.avatar ? `<img class="avatar" src="${esc(s.avatar)}" alt="" style="object-fit:cover">` : `<div class="avatar">${esc(ini)}</div>`)
      + `<div><strong${x.f('name')}>${esc(s.name)}</strong><span${x.f('role')}>${esc(s.role)}</span></div></div></div>`;
  }, 'testimonial review');

reg('process', 'Langkah Proses', 'fa-list-ol', 'Kartu & Konten',
  { num: '01', title: 'Discovery', desc: 'Memahami kebutuhan bisnis, stakeholder, dan constraint teknis melalui workshop & assessment.' },
  [F.text('num', 'Nomor'), F.text('title', 'Judul'), F.area('desc', 'Deskripsi')],
  (s, x) => `<div class="process-step mbg-ps" data-n="${esc(s.num)}"><h4${x.f('title')}>${esc(s.title)}</h4><p${x.f('desc')}>${esc(s.desc)}</p></div>`, 'step langkah');

reg('stat', 'Angka / Statistik', 'fa-chart-simple', 'Kartu & Konten',
  { number: '120', suffix: '+', label: 'Proyek Selesai', color: '#0d9488', labelColor: '' },
  [F.text('number', 'Angka'), F.text('suffix', 'Akhiran (mis. +, %, K)'), F.text('label', 'Label'), F.color('color', 'Warna angka', { tab: 's' }), F.color('labelColor', 'Warna label', { tab: 's' })],
  (s, x) => `<div class="mbg-stat"><strong style="color:${esc(s.color)}"><span${x.f('number')} style="display:inline;margin:0;opacity:1">${esc(s.number)}</span>${esc(s.suffix)}</strong><span${x.f('label')} style="${st({ color: s.labelColor })}">${esc(s.label)}</span></div>`, 'counter number');

reg('pricing', 'Kartu Harga', 'fa-tags', 'Kartu & Konten',
  { name: 'Profesional', price: 'Rp 2,5 jt', period: '/bulan', badge: 'Terpopuler', hl: true, items: [{ t: 'Hingga 10 pengguna' }, { t: 'Dukungan prioritas' }, { t: 'Laporan bulanan' }], btn: 'Pilih Paket', url: '#contact' },
  [F.text('name', 'Nama paket'), F.text('price', 'Harga'), F.text('period', 'Periode'), F.sw('hl', 'Sorot paket ini'), F.text('badge', 'Lencana (jika disorot)'),
   F.list('items', 'Fitur', [F.text('t', 'Teks')], { t: 'Fitur baru' }), F.text('btn', 'Teks tombol'), F.url('url', 'Tautan tombol')],
  (s, x) => `<div class="mbg-price${s.hl ? ' hl' : ''}">${s.hl && s.badge ? `<span class="tag">${esc(s.badge)}</span>` : ''}<h3${x.f('name')}>${esc(s.name)}</h3><div class="amt"><span${x.f('price')}>${esc(s.price)}</span> <small>${esc(s.period)}</small></div>`
    + `<ul>${(s.items || []).map(i => `<li><i class="fas fa-circle-check"></i>${esc(i.t)}</li>`).join('')}</ul><a class="${btnCls({ variant: s.hl ? 'primary' : 'outline', full: true })}" href="${esc(s.url)}">${esc(s.btn)}</a></div>`, 'harga paket price');

reg('team', 'Anggota Tim', 'fa-user-tie', 'Kartu & Konten',
  { photo: 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=300&q=80', name: 'Nama Lengkap', role: 'Jabatan', link: '' },
  [F.img('photo', 'Foto'), F.text('name', 'Nama'), F.text('role', 'Jabatan'), F.url('link', 'Tautan LinkedIn (opsional)')],
  (s, x) => `<div class="mbg-team"><img src="${esc(s.photo)}" alt="${esc(s.name)}"><h4${x.f('name')}>${esc(s.name)}</h4><p${x.f('role')}>${esc(s.role)}</p>${s.link ? `<a href="${esc(s.link)}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>` : ''}</div>`, 'tim team');

reg('cta', 'Kotak CTA', 'fa-bullhorn', 'Kartu & Konten',
  { title: 'Siap Memulai Proyek Anda?', desc: 'Diskusikan kebutuhan bisnis Anda bersama tim kami — konsultasi awal gratis.', btn: 'Hubungi Kami', url: '#contact', btn2: '', url2: '' },
  [F.text('title', 'Judul'), F.area('desc', 'Deskripsi'), F.text('btn', 'Tombol utama'), F.url('url', 'Tautan tombol utama'), F.text('btn2', 'Tombol kedua (opsional)'), F.url('url2', 'Tautan tombol kedua')],
  (s, x) => `<div class="cta-box"><h2${x.f('title')}>${esc(s.title)}</h2><p${x.f('desc')}>${esc(s.desc)}</p><div class="mbg-btns" style="justify-content:center">`
    + (s.btn ? `<a class="btn btn-primary" href="${esc(s.url)}">${esc(s.btn)}</a>` : '') + (s.btn2 ? `<a class="btn btn-ghost" href="${esc(s.url2)}">${esc(s.btn2)}</a>` : '') + '</div></div>', 'call to action banner');

/* ---------- LANJUTAN ---------- */
reg('gallery', 'Galeri Gambar', 'fa-images', 'Lanjutan',
  { cols: 3, gap: 12, radius: 12, items: [
    { src: 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80', alt: '' },
    { src: 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=600&q=80', alt: '' },
    { src: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=600&q=80', alt: '' }] },
  [F.list('items', 'Gambar', [F.img('src', 'Gambar'), F.text('alt', 'Teks alternatif')], { src: '', alt: '' }), F.range('cols', 'Kolom', 1, 6, { tab: 's' }), F.num('gap', 'Jarak (px)', { tab: 's' }), F.num('radius', 'Radius (px)', { tab: 's' })],
  (s) => `<div class="mbg-gal" style="grid-template-columns:repeat(${+s.cols || 3},1fr);gap:${px(s.gap)}">${(s.items || []).map(i => `<img src="${esc(i.src)}" alt="${esc(i.alt)}" loading="lazy" style="border-radius:${px(s.radius)}">`).join('')}</div>`, 'gallery foto');

reg('accordion', 'Akordeon / FAQ', 'fa-list-check', 'Lanjutan',
  { items: [{ q: 'Berapa lama pengerjaan proyek?', a: 'Tergantung skala dan kompleksitas. Estimasi diberikan setelah sesi discovery.' }, { q: 'Apakah ada dukungan purna jual?', a: 'Ya, kami menyediakan maintenance, monitoring, dan dukungan teknis setelah go-live.' }], open: false },
  [F.list('items', 'Pertanyaan', [F.text('q', 'Pertanyaan'), F.area('a', 'Jawaban')], { q: 'Pertanyaan baru', a: 'Jawaban.' }, { lbl: 'q' }), F.sw('open', 'Item pertama terbuka')],
  (s) => `<div class="mbg-acc">${(s.items || []).map((i, n) => `<details${s.open && n === 0 ? ' open' : ''}><summary>${esc(i.q)}</summary><p>${BR(i.a)}</p></details>`).join('')}</div>`, 'faq tanya jawab');

reg('iconlist', 'Daftar Ikon', 'fa-list-ul', 'Lanjutan',
  { items: [{ icon: 'fas fa-envelope', t: 'hello@vzytech.id' }, { icon: 'fas fa-phone', t: '+62 21 1234 5678' }, { icon: 'fas fa-location-dot', t: 'Jakarta, Indonesia' }], color: '' },
  [F.list('items', 'Item', [F.icon('icon', 'Ikon'), F.text('t', 'Teks')], { icon: 'fas fa-check', t: 'Item baru' }, { lbl: 't' }), F.color('color', 'Warna teks', { tab: 's' })],
  (s) => `<ul class="mbg-ilist" style="${st({ color: s.color })}">${(s.items || []).map(i => `<li><i class="${esc(i.icon)}"></i><span style="padding-top:4px">${esc(i.t)}</span></li>`).join('')}</ul>`, 'list kontak');

reg('social', 'Ikon Sosial', 'fa-share-nodes', 'Lanjutan',
  { items: [{ icon: 'fab fa-linkedin-in', url: '#' }, { icon: 'fab fa-instagram', url: '#' }, { icon: 'fab fa-github', url: '#' }, { icon: 'fab fa-youtube', url: '#' }], align: 'left' },
  [F.list('items', 'Jaringan', [F.icon('icon', 'Ikon'), F.url('url', 'URL')], { icon: 'fab fa-facebook-f', url: '#' }, { lbl: 'icon' }), F.seg('align', 'Perataan', ALIGN, { tab: 's' })],
  (s) => `<div class="mbg-social" style="justify-content:${{ left: 'flex-start', center: 'center', right: 'flex-end' }[s.align] || 'flex-start'}">${(s.items || []).map(i => `<a href="${esc(i.url)}" target="_blank" rel="noopener" aria-label="sosial"><i class="${esc(i.icon)}"></i></a>`).join('')}</div>`, 'social media');

reg('waform', 'Form WhatsApp', 'fa-brands fa-whatsapp', 'Lanjutan',
  { title: 'Kirim Pesan', desc: 'Isi form berikut dan Anda akan diarahkan ke WhatsApp kami.', num: '6281313616756', btn: 'Kirim via WhatsApp' },
  [F.text('title', 'Judul'), F.area('desc', 'Deskripsi'), F.text('num', 'Nomor WhatsApp tujuan'), F.text('btn', 'Teks tombol')],
  (s) => {
    const js = `event.preventDefault();var f=this;window.open('https://wa.me/${String(s.num).replace(/\D/g, '')}?text='+encodeURIComponent('Halo, saya '+f.nama.value+'.\\n'+f.pesan.value),'_blank');`;
    return `<form class="mbg-form" onsubmit="${esc(js)}"><h3>${esc(s.title)}</h3><p>${esc(s.desc)}</p><input name="nama" type="text" placeholder="Nama Anda" required><textarea name="pesan" rows="4" placeholder="Tulis pesan Anda" required></textarea><button class="btn btn-primary" type="submit"><i class="fab fa-whatsapp"></i> ${esc(s.btn)}</button></form>`;
  }, 'kontak form whatsapp');

reg('products', 'Katalog Produk', 'fa-box-open', 'Situs',
  {}, [], (s, x) => x.edit ? '<div class="mbg-ph"><i class="fas fa-box-open"></i><strong>Katalog Produk</strong><br>Daftar produk dirender otomatis di halaman publik.</div>' : '[product_catalog]', 'produk shortcode');
reg('blog', 'Daftar Blog', 'fa-newspaper', 'Situs',
  {}, [], (s, x) => x.edit ? '<div class="mbg-ph"><i class="fas fa-newspaper"></i><strong>Daftar Artikel Blog</strong><br>Artikel terbit dirender otomatis di halaman publik.</div>' : '[blog_posts]', 'artikel posts shortcode');

/* ---------- Template section ---------- */
const mk = {
  w: (type, over = {}) => ({ id: uid(), type, s: { ...clone(WD[type].def), ...over } }),
  col: (w, widgets, s = {}) => ({ id: uid(), w, s: { bg: '', pad: '', radius: '', shadow: false, border: '', valign: 'start', gap: 16, ...s }, widgets }),
  sec: (s, cols) => ({ id: uid(), s: { width: 'boxed', bg: '', bgImg: '', bgFixed: false, overlay: '', overlayOp: 50, padT: 70, padB: 70, gap: 24, minH: '', valign: 'center', stack: 'mobile', color: '', anchor: '', cls: '', hideD: false, hideT: false, hideM: false, ...s }, cols }),
};
const equalCols = (n, mkw, s = {}) => Array.from({ length: n }, (_, i) => mk.col(100 / n, mkw(i), s));

const TPL = [
  { key: 'hero', label: 'Hero', icon: 'fa-rocket', desc: 'Banner utama dengan gambar latar',
    make: () => mk.sec({ bgImg: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920&q=80', overlay: '#0f172a', overlayOp: 72, padT: 150, padB: 130, minH: 560, color: '#fff', anchor: 'home' }, [
      mk.col(100, [
        mk.w('heading', { text: 'Bangun Solusi Digital yang Menggerakkan Bisnis', tag: 'h1', size: 52, color: '#ffffff', lh: '1.15' }),
        mk.w('text', { html: '<p>Kami merancang dan mengembangkan software enterprise, aplikasi web & mobile yang scalable, aman, dan siap bertumbuh bersama perusahaan Anda.</p>', size: 18, color: 'rgba(255,255,255,.85)' }),
        mk.w('buttons', {}),
      ], { gap: 22 })]) },
  { key: 'about', label: 'Tentang Kami', icon: 'fa-circle-info', desc: 'Gambar + teks + 3 kotak fitur',
    make: () => mk.sec({ bg: '#ffffff', anchor: 'about' }, [
      mk.col(48, [mk.w('image', { height: 420, shadow: true, radius: 24 })]),
      mk.col(52, [
        mk.w('sechead', { label: 'Tentang Kami', title: 'Mitra Teknologi untuk Pertumbuhan Berkelanjutan', desc: '', align: 'left' }),
        mk.w('text', { html: '<p>PT MBG adalah perusahaan IT yang mengintegrasikan <strong>pengembangan software</strong>, <strong>konsultasi strategis</strong>, dan <strong>pendidikan digital</strong> dalam satu ekosistem.</p>' }),
        mk.w('feature', {}),
        mk.w('feature', { icon: 'fas fa-layer-group', title: 'Arsitektur Scalable', desc: 'Desain sistem yang siap scale-up seiring pertumbuhan bisnis dan beban pengguna.' }),
        mk.w('feature', { icon: 'fas fa-users-gear', title: 'Tim Lintas Disiplin', desc: 'Engineer, konsultan, dan trainer berpengalaman industri untuk hasil end-to-end.' }),
      ], { valign: 'center' })]) },
  { key: 'services', label: 'Layanan (3 kartu)', icon: 'fa-layer-group', desc: 'Judul + 3 kartu layanan',
    make: () => null }, // diisi di bawah
  { key: 'process', label: 'Proses Kerja (4 langkah)', icon: 'fa-list-ol', desc: '4 kartu langkah bernomor',
    make: () => mk.sec({ bg: '#ffffff', anchor: 'process' }, [
      mk.col(25, [mk.w('process', {})]),
      mk.col(25, [mk.w('process', { num: '02', title: 'Strategy & Design', desc: 'Merancang solusi, arsitektur, dan prototipe yang selaras dengan tujuan organisasi.' })]),
      mk.col(25, [mk.w('process', { num: '03', title: 'Build & Deliver', desc: 'Pengembangan iteratif, quality assurance, dan deployment dengan standar profesional.' })]),
      mk.col(25, [mk.w('process', { num: '04', title: 'Grow & Support', desc: 'Monitoring, optimasi, training, dan continuous improvement pasca go-live.' })])]) },
  { key: 'testimonials', label: 'Testimoni (3 kartu)', icon: 'fa-quote-right', desc: '3 kartu testimoni klien',
    make: () => mk.sec({ bg: '#f0fdfa', anchor: 'testimonials' }, [
      mk.col(33.3, [mk.w('testimonial', {})]),
      mk.col(33.3, [mk.w('testimonial', { quote: 'Konsultasi cloud migration sangat membantu. Roadmap-nya jelas, risiko terpetakan, dan eksekusinya sesuai timeline.', name: 'Sari Wulandari', role: 'IT Director, Retail Prima' })]),
      mk.col(33.3, [mk.w('testimonial', { quote: 'Pelatihan corporate untuk 80 engineer kami relevan dan praktik. Materi up-to-date dan instruktur sangat kompeten.', name: 'Budi Pratama', role: 'HRD Learning, TechCorp ID' })])]) },
  { key: 'stats', label: 'Statistik (4 angka)', icon: 'fa-chart-simple', desc: 'Strip angka pada latar gelap',
    make: () => mk.sec({ bg: '#0f172a', color: '#fff', padT: 56, padB: 56 }, [
      mk.col(25, [mk.w('stat', { color: '#5eead4', labelColor: 'rgba(255,255,255,.8)' })]),
      mk.col(25, [mk.w('stat', { number: '48', label: 'Klien Puas', color: '#5eead4', labelColor: 'rgba(255,255,255,.8)' })]),
      mk.col(25, [mk.w('stat', { number: '12', suffix: '', label: 'Tahun Pengalaman', color: '#5eead4', labelColor: 'rgba(255,255,255,.8)' })]),
      mk.col(25, [mk.w('stat', { number: '99', suffix: '%', label: 'Tingkat Retensi', color: '#5eead4', labelColor: 'rgba(255,255,255,.8)' })])]) },
  { key: 'pricing', label: 'Harga (3 paket)', icon: 'fa-tags', desc: '3 kartu paket harga',
    make: () => mk.sec({ bg: '#ffffff' }, [
      mk.col(33.3, [mk.w('pricing', { name: 'Starter', price: 'Rp 1 jt', hl: false, items: [{ t: 'Hingga 3 pengguna' }, { t: 'Dukungan email' }] })]),
      mk.col(33.3, [mk.w('pricing', {})]),
      mk.col(33.3, [mk.w('pricing', { name: 'Enterprise', price: 'Hubungi Kami', period: '', hl: false, items: [{ t: 'Pengguna tak terbatas' }, { t: 'SLA khusus' }, { t: 'Account manager' }] })])]) },
  { key: 'team', label: 'Tim (4 anggota)', icon: 'fa-user-tie', desc: '4 kartu anggota tim',
    make: () => mk.sec({ bg: '#ffffff' }, equalCols(4, () => [mk.w('team', {})])) },
  { key: 'faq', label: 'FAQ', icon: 'fa-circle-question', desc: 'Judul + akordeon',
    make: () => mk.sec({ bg: '#ffffff', width: 'narrow' }, [mk.col(100, [mk.w('sechead', { label: 'FAQ', title: 'Pertanyaan yang Sering Diajukan', desc: '' }), mk.w('accordion', { open: true })], { gap: 28 })]) },
  { key: 'cta', label: 'Ajakan Bertindak', icon: 'fa-bullhorn', desc: 'Kotak CTA gradasi',
    make: () => mk.sec({ bg: '#ffffff', anchor: 'contact' }, [mk.col(100, [mk.w('cta', {})])]) },
  { key: 'contact', label: 'Kontak', icon: 'fa-envelope', desc: 'Info kontak + form WhatsApp',
    make: () => mk.sec({ bg: '#f0fdfa', anchor: 'contact' }, [
      mk.col(50, [mk.w('sechead', { label: 'Kontak', title: 'Mari Berdiskusi', desc: 'Tim kami siap membantu kebutuhan teknologi bisnis Anda.', align: 'left' }), mk.w('iconlist', {}), mk.w('social', {})], { gap: 22 }),
      mk.col(50, [mk.w('waform', {})])]) },
  { key: 'products', label: 'Katalog Produk', icon: 'fa-box-open', desc: 'Shortcode katalog produk',
    make: () => mk.sec({ padT: 20, padB: 20 }, [mk.col(100, [mk.w('products', {})])]) },
  { key: 'blog', label: 'Daftar Blog', icon: 'fa-newspaper', desc: 'Shortcode daftar artikel',
    make: () => mk.sec({ padT: 20, padB: 20 }, [mk.col(100, [mk.w('blog', {})])]) },
];

/* services template: gabungkan judul + 3 kartu sebagai dua section agar judul tetap di tengah */
TPL.find(t => t.key === 'services').make = () => {
  const s = mk.sec({ bg: '#f0fdfa', anchor: 'services', padB: 70 }, [
    mk.col(33.3, [mk.w('service', {})]),
    mk.col(33.3, [mk.w('service', { icon: 'fas fa-chart-line', title: 'IT Consultant', desc: 'Pendampingan strategis untuk transformasi digital, optimasi infrastruktur, dan tata kelola TI.', items: [{ t: 'Digital Transformation Roadmap' }, { t: 'IT Audit & Assessment' }, { t: 'Cloud Migration Strategy' }], accent: 'green' })]),
    mk.col(33.3, [mk.w('service', { icon: 'fas fa-graduation-cap', title: 'Pendidikan Digital', desc: 'Program pelatihan dan kurikulum untuk membangun kompetensi teknis yang relevan dengan industri.', items: [{ t: 'Corporate IT Training' }, { t: 'Bootcamp Coding & Data' }, { t: 'Sertifikasi & Mentoring' }], accent: 'blue' })])]);
  return [mk.sec({ bg: '#f0fdfa', anchor: 'services', padB: 0 }, [mk.col(100, [mk.w('sechead', {})])]), Object.assign(s, { s: { ...s.s, anchor: '', padT: 40 } })];
};

/* preset susunan kolom */
const LAYOUTS = [[100], [50, 50], [33.3, 33.3, 33.3], [25, 25, 25, 25], [33.3, 66.7], [66.7, 33.3], [25, 50, 25], [20, 20, 20, 20, 20]];

/* template ber-judul: sisipkan Judul Seksi di atas kartu */
const withHead = (key, head) => {
  const t = TPL.find(x => x.key === key), orig = t.make;
  t.make = () => { const body = orig(); const hs = mk.sec({ bg: body.s.bg, padB: 0, anchor: body.s.anchor }, [mk.col(100, [mk.w('sechead', head)])]); body.s.anchor = ''; body.s.padT = 40; return [hs, body]; };
};
withHead('process', { label: 'Cara Kerja', title: 'Proses yang Transparan & Terukur', desc: 'Setiap engagement mengikuti kerangka kerja yang jelas agar kualitas dan komunikasi tetap terkendali.' });
withHead('testimonials', { label: 'Testimoni', title: 'Dipercaya Organisasi yang Bertumbuh', desc: 'Beberapa masukan dari mitra yang telah bekerja sama dengan tim kami.' });
withHead('pricing', { label: 'Paket', title: 'Pilih Paket yang Sesuai', desc: 'Harga transparan, tanpa biaya tersembunyi.' });
withHead('team', { label: 'Tim', title: 'Orang-orang di Balik PT MBG', desc: '' });

window.BX = { WD, TPL, LAYOUTS, OUT_CSS, F, mk, esc, st, px, uid, clone, btnCls };
})();
