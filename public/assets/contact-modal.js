/* Tombol "Hubungi Kami" di navbar -> form -> WhatsApp */
(function () {
  var WA = '6281313616756';
  var css = '.cx-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.5);z-index:2000;padding:20px;overflow:auto}.cx-modal.is-open{display:flex}.cx-panel{position:relative;width:min(100%,500px);max-height:100%;overflow:auto;background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid rgba(148,163,184,.25);border-radius:22px;padding:26px;box-shadow:0 28px 70px rgba(15,23,42,.25);color:#0f172a;font-family:inherit}.cx-close{position:absolute;top:12px;right:12px;width:36px;height:36px;border:0;border-radius:50%;background:#e2e8f0;color:#0f172a;font-size:1.5rem;line-height:1;cursor:pointer}.cx-panel h3{margin:0 0 4px;font-size:1.4rem}.cx-sub{margin:0 0 16px;color:#64748b;font-size:.9rem}.cx-form{display:grid;gap:13px}.cx-f{display:grid;gap:6px}.cx-f label{font-size:.78rem;font-weight:700;color:#334155}.cx-f input,.cx-f textarea{width:100%;border:1px solid #cbd5e1;border-radius:12px;padding:10px 12px;font:inherit;background:#fff;color:#0f172a;box-sizing:border-box}.cx-f textarea{min-height:100px;resize:vertical}.cx-f input:focus,.cx-f textarea:focus{outline:none;border-color:#0d9488;box-shadow:0 0 0 3px rgba(13,148,136,.15)}.cx-submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:12px;background:linear-gradient(135deg,#0d9488,#2dd4bf);color:#fff;padding:12px 18px;font:700 .95rem inherit;font-family:inherit;cursor:pointer;box-shadow:0 12px 24px rgba(13,148,136,.2)}';
  var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

  var m = document.createElement('div');
  m.className = 'cx-modal'; m.setAttribute('aria-hidden', 'true');
  m.innerHTML = '<div class="cx-panel" role="dialog" aria-modal="true" aria-labelledby="cxTitle">'
    + '<button type="button" class="cx-close" aria-label="Tutup">&times;</button>'
    + '<h3 id="cxTitle">Hubungi Kami</h3><p class="cx-sub">Isi form berikut, pesan akan dikirim lewat WhatsApp.</p>'
    + '<form class="cx-form">'
    + '<div class="cx-f"><label for="cxNama">Nama</label><input id="cxNama" name="nama" required placeholder="Nama lengkap"></div>'
    + '<div class="cx-f"><label for="cxInstansi">Instansi / Perusahaan</label><input id="cxInstansi" name="instansi" placeholder="Nama instansi atau perusahaan"></div>'
    + '<div class="cx-f"><label for="cxEmail">Alamat Email</label><input id="cxEmail" name="email" type="email" required placeholder="nama@email.com"></div>'
    + '<div class="cx-f"><label for="cxPerihal">Perihal</label><input id="cxPerihal" name="perihal" required placeholder="Tujuan pesan"></div>'
    + '<div class="cx-f"><label for="cxPesan">Isi Pesan</label><textarea id="cxPesan" name="pesan" required placeholder="Tulis pesan Anda..."></textarea></div>'
    + '<button type="submit" class="cx-submit"><i class="fab fa-whatsapp" aria-hidden="true"></i> Kirim ke WhatsApp</button>'
    + '</form></div>';
  document.body.appendChild(m);

  var form = m.querySelector('form');
  function open() { m.classList.add('is-open'); m.setAttribute('aria-hidden', 'false'); setTimeout(function () { form.nama.focus(); }, 50); }
  function close() { m.classList.remove('is-open'); m.setAttribute('aria-hidden', 'true'); }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('.nav-cta');
    if (a) { e.preventDefault(); open(); }
  });
  m.querySelector('.cx-close').addEventListener('click', close);
  m.addEventListener('click', function (e) { if (e.target === m) close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var v = function (n) { return (form[n].value || '').trim(); };
    var text = ['Halo PT MBG, saya ingin menghubungi Anda.', '',
      '*Nama:* ' + v('nama'), '*Instansi/Perusahaan:* ' + (v('instansi') || '-'),
      '*Email:* ' + v('email'), '*Perihal:* ' + v('perihal'), '', '*Pesan:*', v('pesan')].join('\n');
    window.open('https://wa.me/' + WA + '?text=' + encodeURIComponent(text), '_blank', 'noopener,noreferrer');
    form.reset(); close();
  });
})();
