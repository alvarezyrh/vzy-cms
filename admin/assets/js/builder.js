/* PT MBG Page Builder — inti editor (state, kanvas, drag & drop, panel pengaturan). */
(function () {
'use strict';
const B = window.BUILDER, X = window.BX;
const { WD, TPL, LAYOUTS, OUT_CSS, F, mk, esc, st, px, uid, clone } = X;
const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];

/* =====================================================================
   STATE
   ===================================================================== */
const state = {
  sections: [],
  page: clone(B.page),
  sel: null,            // {kind:'sec'|'col'|'wid', id}
  mode: 'lib',          // 'lib' | 'edit'
  libTab: 'widgets',    // widgets | tpl | nav | page
  setTab: 'c',
  insertAt: null,       // indeks section untuk klik template
  dirty: false,
};
let hist = [], hidx = -1, histTimer = null, saving = false;

/* ---------- inisialisasi data ---------- */
(function boot() {
  if (Array.isArray(B.layout)) {
    state.sections = B.layout.filter(Boolean);
  } else {
    const legacy = (B.legacy || '').trim();
    if (legacy) {
      const body = legacy.replace(/^\s*<style id="mbg-builder-css">[\s\S]*?<\/style>/, '').trim();
      let w;
      if (/^\[product_catalog\]$/i.test(body)) w = mk.w('products');
      else if (/^\[blog_posts\]$/i.test(body)) w = mk.w('blog');
      else w = mk.w('html', { code: body });
      state.sections = [mk.sec({ padT: 40, padB: 40, width: 'boxed' }, [mk.col(100, [w])])];
      state.page.site_chrome = state.page.site_chrome || (w.type !== 'html' ? 1 : state.page.site_chrome);
    }
  }
  hist = [JSON.stringify(state.sections)]; hidx = 0;
})();

/* ---------- pencari elemen ---------- */
function find(id) {
  for (let si = 0; si < state.sections.length; si++) {
    const sec = state.sections[si];
    if (sec.id === id) return { kind: 'sec', sec, si, obj: sec };
    for (let ci = 0; ci < sec.cols.length; ci++) {
      const col = sec.cols[ci];
      if (col.id === id) return { kind: 'col', sec, si, col, ci, obj: col };
      for (let wi = 0; wi < col.widgets.length; wi++) {
        if (col.widgets[wi].id === id) return { kind: 'wid', sec, si, col, ci, w: col.widgets[wi], wi, obj: col.widgets[wi] };
      }
    }
  }
  return null;
}
const cur = () => state.sel ? find(state.sel.id) : null;

/* =====================================================================
   RENDER HTML (dipakai untuk kanvas & output tersimpan)
   ===================================================================== */
const hideCls = s => (s.hideD ? ' mbg-hide-d' : '') + (s.hideT ? ' mbg-hide-t' : '') + (s.hideM ? ' mbg-hide-m' : '');
const bar = (kind, label, acts) => `<div class="bx-bar bx-bar-${kind}">${acts}<span class="bx-lbl">${esc(label)}</span></div>`;
const grip = (kind, id) => `<span class="bx-grip" draggable="true" data-kind="${kind}" data-id="${id}" title="Seret untuk memindahkan"><i class="fas fa-grip-vertical"></i></span>`;
const abtn = (act, id, icon, title) => `<button type="button" class="bx-act" data-act="${act}" data-id="${id}" title="${title}"><i class="fas ${icon}"></i></button>`;

function widgetHtml(w, edit) {
  const d = WD[w.type];
  if (!d) return '';
  const s = { ...d.def, ...w.s };
  const x = { edit, f: (k, h) => edit ? ` data-f="${k}"${h ? ' data-fh="1"' : ''}` : '' };
  const inner = d.html(s, x, w);
  const style = st({ marginTop: px(s.mt), marginBottom: px(s.mb), textAlign: ('align' in d.def) ? s.align : '' });
  const cls = 'mbg-w' + hideCls(s) + (s.cls ? ' ' + esc(s.cls) : '');
  const idAttr = s.anchor ? ` id="${esc(s.anchor)}"` : '';
  if (!edit) return `<div class="${cls}"${idAttr}${style ? ` style="${style}"` : ''}>${inner}</div>`;
  return `<div class="${cls}" data-wid="${w.id}"${style ? ` style="${style}"` : ''}>${bar('wid', d.label, grip('widget', w.id) + abtn('dup', w.id, 'fa-clone', 'Duplikat') + abtn('del', w.id, 'fa-trash', 'Hapus'))}${inner}</div>`;
}

function colHtml(col, sec, edit, last) {
  const s = col.s;
  const style = st({
    background: s.bg, padding: px(s.pad), borderRadius: px(s.radius), border: s.border ? `1px solid ${s.border}` : '',
    boxShadow: s.shadow ? '0 12px 40px rgba(15,23,42,.12)' : '', justifyContent: { start: 'flex-start', center: 'center', end: 'flex-end' }[s.valign] || '', gap: px(s.gap),
  });
  const body = col.widgets.map(w => widgetHtml(w, edit)).join('');
  if (!edit) return `<div class="mbg-col"${style ? ` style="${style}"` : ''}>${body}</div>`;
  return `<div class="mbg-col" data-cid="${col.id}"${style ? ` style="${style}"` : ''}>${bar('col', 'Kolom ' + Math.round(col.w) + '%', abtn('dup', col.id, 'fa-clone', 'Duplikat kolom') + abtn('del', col.id, 'fa-trash', 'Hapus kolom'))}`
    + (body || '<div class="bx-empty"><i class="fas fa-plus"></i> Seret widget ke sini</div>')
    + (last ? '' : `<div class="bx-resz" data-resz="${col.id}" title="Geser untuk mengubah lebar"></div>`) + '</div>';
}

function secHtml(sec, edit) {
  const s = sec.s;
  const style = st({
    backgroundColor: s.bg, backgroundImage: s.bgImg ? `url('${esc(s.bgImg)}')` : '', backgroundSize: s.bgImg ? 'cover' : '', backgroundPosition: s.bgImg ? 'center' : '',
    backgroundAttachment: s.bgFixed ? 'fixed' : '', paddingTop: px(s.padT), paddingBottom: px(s.padB), minHeight: px(s.minH), color: s.color,
    display: s.minH ? 'flex' : '', flexDirection: s.minH ? 'column' : '', justifyContent: s.minH ? ({ start: 'flex-start', center: 'center', end: 'flex-end' }[s.valign] || 'center') : '',
  });
  const cls = 'mbg-sec' + (s.width === 'full' ? ' mbg-full' : s.width === 'narrow' ? ' mbg-narrow' : '') + (s.stack === 'tablet' ? ' st-tablet' : '') + hideCls(s) + (s.cls ? ' ' + esc(s.cls) : '');
  const grid = sec.cols.map(c => `minmax(0,${(+c.w || 1).toFixed(2)}fr)`).join(' ');
  const row = `<div class="mbg-row" style="grid-template-columns:${grid};gap:${px(s.gap)}">${sec.cols.map((c, i) => colHtml(c, sec, edit, i === sec.cols.length - 1)).join('')}</div>`;
  const ov = s.overlay ? `<div class="mbg-ov" style="background:${esc(s.overlay)};opacity:${(+s.overlayOp || 0) / 100}"></div>` : '';
  const idAttr = s.anchor ? ` id="${esc(s.anchor)}"` : '';
  if (!edit) return `<section class="${cls}"${idAttr}${style ? ` style="${style}"` : ''}>${ov}<div class="mbg-in">${row}</div></section>`;
  return `<section class="${cls}" data-sid="${sec.id}"${style ? ` style="${style}"` : ''}>${bar('sec', 'Section', grip('section', sec.id) + abtn('addcol', sec.id, 'fa-table-columns', 'Tambah kolom') + abtn('dup', sec.id, 'fa-clone', 'Duplikat section') + abtn('del', sec.id, 'fa-trash', 'Hapus section'))}${ov}<div class="mbg-in">${row}</div></section>`;
}

function addZoneHtml() {
  const lays = LAYOUTS.slice(0, 6).map((l, i) => `<button type="button" class="bx-lay" data-act="newsec" data-i="${i}" title="${l.length} kolom">${l.map(w => `<span style="flex:${w}"></span>`).join('')}</button>`).join('');
  return `<div class="bx-add"><div class="bx-add-t"><i class="fas fa-plus"></i> ${state.sections.length ? 'Tambah section baru' : 'Halaman masih kosong — mulai dengan memilih susunan kolom'}</div><div class="bx-add-l">${lays}</div><div class="bx-add-s">atau seret widget / template dari panel kiri ke sini</div></div>`;
}

const docHtml = edit => state.sections.map(s => secHtml(s, edit)).join('');
const outputHtml = () => `<style id="mbg-builder-css">${OUT_CSS.trim()}</style>\n${docHtml(false)}`;

/* =====================================================================
   KANVAS (iframe)
   ===================================================================== */
const frame = $('#bxFrame');
let fdoc = null, root = null, dropEl = null, hovEl = null, editing = null;

const EDIT_CSS = `
html{scroll-behavior:auto!important}
body{background:#fff;min-height:100vh}
[data-sid],[data-cid],[data-wid]{position:relative}
.bx-bar{position:absolute;z-index:40;display:none;align-items:center;gap:1px;color:#fff;border-radius:6px;padding:2px 3px;font:600 11px/1 Inter,system-ui,sans-serif;white-space:nowrap;box-shadow:0 4px 12px rgba(0,0,0,.25);text-shadow:none;letter-spacing:0;text-transform:none}
.bx-bar *{font:inherit;color:inherit}
.bx-bar-sec{background:#3b82f6;top:0;left:0}
.bx-bar-col{background:#8b5cf6;top:0;left:50%;transform:translateX(-50%)}
.bx-bar-wid{background:#0d9488;top:-1px;right:0}
.bx-bar .bx-lbl{padding:0 6px;order:-1}
.bx-grip{display:inline-grid;place-items:center;width:22px;height:20px;cursor:grab;border-radius:4px;order:-2}
.bx-grip:hover,.bx-act:hover{background:rgba(255,255,255,.25)}
.bx-act{width:22px;height:20px;display:inline-grid;place-items:center;border:0;background:none;cursor:pointer;border-radius:4px;padding:0;font-size:11px}
.bx-hov>.bx-bar,.bx-sel>.bx-bar{display:flex}
[data-sid].bx-hov{outline:1px dashed #3b82f6;outline-offset:-1px}
[data-cid].bx-hov{outline:1px dashed #8b5cf6;outline-offset:-1px}
[data-wid].bx-hov{outline:1px dashed #0d9488;outline-offset:-1px}
[data-sid].bx-sel{outline:2px solid #3b82f6;outline-offset:-2px}
[data-cid].bx-sel{outline:2px solid #8b5cf6;outline-offset:-2px}
[data-wid].bx-sel{outline:2px solid #0d9488;outline-offset:-2px}
.bx-empty{min-height:90px;display:flex;align-items:center;justify-content:center;gap:8px;border:2px dashed rgba(100,116,139,.45);border-radius:10px;color:#64748b;font:500 13px Inter,system-ui,sans-serif;margin:4px;flex:1}
.mbg-col{min-height:64px}
.bx-resz{position:absolute;top:0;bottom:0;right:-14px;width:14px;cursor:col-resize;z-index:35;display:none}
.bx-resz::after{content:"";position:absolute;left:5px;top:15%;bottom:15%;width:4px;border-radius:4px;background:#8b5cf6}
[data-sid]:hover .bx-resz,.bx-resizing .bx-resz{display:block}
.mbg-hide-d,.mbg-hide-t,.mbg-hide-m{display:block!important;opacity:.4}
.bx-add{margin:0;padding:34px 20px;text-align:center;border:2px dashed #cbd5e1;border-radius:14px;margin:22px;color:#64748b;font:500 13px Inter,system-ui,sans-serif;background:#f8fafc}
.bx-add-t{font-weight:600;margin-bottom:14px;font-size:14px}
.bx-add-s{margin-top:14px;font-size:12px;opacity:.8}
.bx-add-l{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
.bx-lay{display:flex;gap:3px;width:84px;height:44px;padding:6px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;cursor:pointer}
.bx-lay:hover{border-color:#14b8a6;box-shadow:0 0 0 3px rgba(20,184,166,.18)}
.bx-lay span{background:#99f6e4;border-radius:3px}
.bx-drop{position:absolute;z-index:99;height:4px;border-radius:4px;background:#14b8a6;box-shadow:0 0 0 3px rgba(20,184,166,.28);pointer-events:none;display:none}
.bx-drop.sec{background:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.28);height:5px}
[contenteditable=true]{outline:2px solid #14b8a6!important;outline-offset:3px;cursor:text;border-radius:3px}
.bx-dragging{opacity:.4}
.bx-preview .bx-bar,.bx-preview .bx-resz,.bx-preview .bx-add,.bx-preview .bx-drop{display:none!important}
.bx-preview [data-sid],.bx-preview [data-cid],.bx-preview [data-wid]{outline:none!important}
.bx-preview .bx-empty{visibility:hidden}
.bx-preview .mbg-hide-d,.bx-preview .mbg-hide-t,.bx-preview .mbg-hide-m{opacity:.4}
`;

function initFrame() {
  frame.srcdoc = `<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="/public/home/css/style.css">
<style id="out">${OUT_CSS}</style><style>${EDIT_CSS}</style><style id="pagecss"></style></head><body><div id="root"></div></body></html>`;
  frame.addEventListener('load', () => {
    fdoc = frame.contentDocument; root = $('#root', fdoc);
    dropEl = fdoc.createElement('div'); dropEl.className = 'bx-drop'; fdoc.body.appendChild(dropEl);
    bindCanvas(); renderCanvas(); applyPageCss();
  }, { once: true });
}

function renderCanvas() {
  if (!root) return;
  root.innerHTML = docHtml(true) + addZoneHtml();
  applySel();
}
function applyPageCss() { if (fdoc) $('#pagecss', fdoc).textContent = state.page.custom_css || ''; }

function applySel() {
  if (!fdoc) return;
  $$('.bx-sel', fdoc).forEach(e => e.classList.remove('bx-sel'));
  if (state.sel) {
    const el = $(`[data-sid="${state.sel.id}"],[data-cid="${state.sel.id}"],[data-wid="${state.sel.id}"]`, fdoc);
    if (el) el.classList.add('bx-sel');
  }
}
let rafId = 0;
function refresh(panel) {
  cancelAnimationFrame(rafId);
  rafId = requestAnimationFrame(() => { renderCanvas(); if (panel) renderPanel(); else if (state.libTab === 'nav' && state.mode === 'lib') renderPanel(); });
}

/* =====================================================================
   HISTORY / DIRTY
   ===================================================================== */
function setDirty(v = true) {
  state.dirty = v;
  const c = $('#bxStatus'); c.textContent = v ? 'Belum disimpan' : 'Tersimpan'; c.classList.toggle('dirty', v); c.classList.remove('err');
}
function commit(now) {
  setDirty();
  clearTimeout(histTimer);
  const doit = () => {
    const j = JSON.stringify(state.sections);
    if (j === hist[hidx]) return;
    hist = hist.slice(0, hidx + 1); hist.push(j); if (hist.length > 120) hist.shift(); hidx = hist.length - 1; updUndo();
  };
  if (now) doit(); else histTimer = setTimeout(doit, 500);
}
function updUndo() { $('#bxUndo').disabled = hidx <= 0; $('#bxRedo').disabled = hidx >= hist.length - 1; }
function undo(d) {
  clearTimeout(histTimer);
  const j = JSON.stringify(state.sections);
  if (j !== hist[hidx]) { hist = hist.slice(0, hidx + 1); hist.push(j); hidx++; }
  const n = hidx + d; if (n < 0 || n >= hist.length) return;
  hidx = n; state.sections = JSON.parse(hist[hidx]); setDirty(); updUndo();
  if (state.sel && !find(state.sel.id)) { state.sel = null; state.mode = 'lib'; }
  renderCanvas(); renderPanel();
}

/* =====================================================================
   OPERASI DOKUMEN
   ===================================================================== */
function select(kind, id, tab) {
  state.sel = { kind, id }; state.mode = 'edit';
  if (tab) state.setTab = tab; else if (!['c', 's', 'a'].includes(state.setTab)) state.setTab = 'c';
  applySel(); renderPanel();
}
function deselect() { state.sel = null; state.mode = 'lib'; applySel(); renderPanel(); }

function insertSections(list, idx) {
  const arr = (Array.isArray(list) ? list : [list]).filter(Boolean);
  const at = idx == null || idx > state.sections.length ? state.sections.length : idx;
  state.sections.splice(at, 0, ...arr);
  commit(true); renderCanvas();
  select('sec', arr[0].id);
  scrollToEl(arr[0].id);
}
function newSectionFromLayout(widths) {
  return mk.sec({}, widths.map(w => mk.col(w, [])));
}
function scrollToEl(id) {
  setTimeout(() => { const el = fdoc && $(`[data-sid="${id}"],[data-cid="${id}"],[data-wid="${id}"]`, fdoc); if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' }); }, 30);
}
function addWidgetTo(type, colId, idx) {
  const f = find(colId); if (!f || f.kind !== 'col') return;
  const w = mk.w(type);
  f.col.widgets.splice(idx == null ? f.col.widgets.length : idx, 0, w);
  commit(true); renderCanvas(); select('wid', w.id); scrollToEl(w.id);
}
function addWidgetAuto(type) {            // klik widget di panel (tanpa drag)
  const s = cur(); let col = null;
  if (s) { if (s.kind === 'col') col = s.col; else if (s.kind === 'wid') col = s.col; else if (s.kind === 'sec') col = s.sec.cols[0]; }
  if (!col) {
    const sec = newSectionFromLayout([100]); state.sections.push(sec); col = sec.cols[0];
  }
  addWidgetTo(type, col.id, s && s.kind === 'wid' ? s.wi + 1 : null);
}
function moveWidget(wid, colId, idx) {
  const f = find(wid), t = find(colId); if (!f || !t) return;
  f.col.widgets.splice(f.wi, 1);
  if (f.col === t.col && f.wi < idx) idx--;
  t.col.widgets.splice(Math.max(0, idx), 0, f.w);
  commit(true); renderCanvas(); select('wid', wid);
}
function widgetToNewSection(src, idx, fromNew, type) {
  let w;
  if (fromNew) w = mk.w(type);
  else { const f = find(src); if (!f) return; f.col.widgets.splice(f.wi, 1); w = f.w; if (f.si < idx && false) idx--; }
  const sec = mk.sec({}, [mk.col(100, [w])]);
  state.sections.splice(Math.min(idx, state.sections.length), 0, sec);
  commit(true); renderCanvas(); select('wid', w.id); scrollToEl(w.id);
}
function moveSection(id, idx) {
  const i = state.sections.findIndex(s => s.id === id); if (i < 0) return;
  const [s] = state.sections.splice(i, 1);
  if (i < idx) idx--;
  state.sections.splice(Math.max(0, idx), 0, s);
  commit(true); renderCanvas(); select('sec', id);
}
function reId(o) {
  o.id = uid(); (o.cols || []).forEach(reId); (o.widgets || []).forEach(reId); return o;
}
function dup(id) {
  const f = find(id); if (!f) return;
  const c = reId(clone(f.obj));
  if (f.kind === 'sec') state.sections.splice(f.si + 1, 0, c);
  else if (f.kind === 'col') { f.sec.cols.splice(f.ci + 1, 0, c); rebalance(f.sec); }
  else f.col.widgets.splice(f.wi + 1, 0, c);
  commit(true); renderCanvas(); select(f.kind, c.id);
}
function del(id) {
  const f = find(id); if (!f) return;
  if (f.kind === 'sec') state.sections.splice(f.si, 1);
  else if (f.kind === 'col') {
    if (f.sec.cols.length === 1) { f.sec.cols[0].widgets = []; } else { f.sec.cols.splice(f.ci, 1); rebalance(f.sec); }
  } else f.col.widgets.splice(f.wi, 1);
  state.sel = null; state.mode = 'lib'; commit(true); renderCanvas(); renderPanel();
}
function rebalance(sec) {
  const t = sec.cols.reduce((a, c) => a + (+c.w || 0), 0) || 1;
  sec.cols.forEach(c => c.w = +((+c.w || 0) / t * 100).toFixed(2));
}
function addCol(secId) {
  const f = find(secId); if (!f || f.kind !== 'sec') return;
  const n = f.sec.cols.length;
  f.sec.cols.forEach(c => c.w = c.w * n / (n + 1));
  const c = mk.col(100 / (n + 1), []); f.sec.cols.push(c);
  rebalance(f.sec); commit(true); renderCanvas(); select('col', c.id);
}
function applyLayout(secId, widths) {
  const f = find(secId); if (!f) return;
  const sec = f.sec;
  while (sec.cols.length < widths.length) sec.cols.push(mk.col(10, []));
  while (sec.cols.length > widths.length) { const r = sec.cols.pop(); sec.cols[sec.cols.length - 1].widgets.push(...r.widgets); }
  sec.cols.forEach((c, i) => c.w = widths[i]);
  commit(true); renderCanvas(); renderPanel();
}

/* =====================================================================
   KANVAS: interaksi (pilih, hover, edit inline, drag & drop, resize)
   ===================================================================== */
let drag = null, dropTarget = null;

function bindCanvas() {
  const d = fdoc;
  // pilih & aksi
  d.addEventListener('click', e => {
    const t = e.target;
    const a = t.closest('a,form button,button[type=submit]'); if (a) e.preventDefault();
    if (editing) { if (!editing.contains(t)) editing.blur(); else return; }
    const act = t.closest('.bx-act');
    if (act) { e.preventDefault(); e.stopPropagation(); doAct(act.dataset.act, act.dataset.id, act); return; }
    const nl = t.closest('[data-act=newsec]');
    if (nl) { insertSections(newSectionFromLayout(LAYOUTS[+nl.dataset.i]), state.sections.length); return; }
    const w = t.closest('[data-wid]'), c = t.closest('[data-cid]'), s = t.closest('[data-sid]');
    if (w) select('wid', w.dataset.wid); else if (c) select('col', c.dataset.cid); else if (s) select('sec', s.dataset.sid); else deselect();
  });
  d.addEventListener('submit', e => e.preventDefault());
  d.addEventListener('mouseover', e => {
    const t = e.target; if (!t.closest) return;
    const el = t.closest('[data-wid]') || t.closest('[data-cid]') || t.closest('[data-sid]');
    if (el === hovEl) return;
    if (hovEl) hovEl.classList.remove('bx-hov');
    hovEl = el; if (el) el.classList.add('bx-hov');
  });
  d.addEventListener('mouseleave', () => { if (hovEl) { hovEl.classList.remove('bx-hov'); hovEl = null; } });
  // edit inline
  d.addEventListener('dblclick', e => {
    const el = e.target.closest('[data-f]'); if (!el) return;
    const wEl = el.closest('[data-wid]'); if (!wEl) return;
    startInline(el, wEl.dataset.wid);
  });
  d.addEventListener('keydown', e => {
    if (editing && e.key === 'Escape') { editing.dataset.cancel = '1'; editing.blur(); }
    else if (editing && e.key === 'Enter' && !editing.hasAttribute('data-fh')) { e.preventDefault(); editing.blur(); }
    else if (!editing) globalKeys(e);
  });
  // DnD
  d.addEventListener('dragstart', e => {
    const g = e.target.closest && e.target.closest('.bx-grip'); if (!g) { e.preventDefault(); return; }
    const kind = g.dataset.kind, id = g.dataset.id;
    drag = { kind, id };
    e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', id);
    const host = g.closest('[data-wid],[data-sid]');
    if (host) { try { e.dataTransfer.setDragImage(host, 20, 10); } catch (_) {} setTimeout(() => host.classList.add('bx-dragging'), 0); }
  });
  d.addEventListener('dragover', onDragOver);
  d.addEventListener('drop', onDrop);
  d.addEventListener('dragend', endDrag);
  d.addEventListener('dragleave', e => { if (!e.relatedTarget) hideDrop(); });
  // resize kolom
  d.addEventListener('mousedown', onResizeStart);
}

function doAct(act, id) {
  if (act === 'dup') dup(id);
  else if (act === 'del') del(id);
  else if (act === 'addcol') addCol(id);
}

function startInline(el, wid) {
  if (editing) return;
  editing = el; el.contentEditable = 'true'; el.focus();
  const r = fdoc.createRange(); r.selectNodeContents(el); const sel = fdoc.getSelection(); sel.removeAllRanges(); sel.addRange(r);
  const k = el.dataset.f, isHtml = el.hasAttribute('data-fh');
  const done = () => {
    el.removeEventListener('blur', done);
    el.contentEditable = 'false';
    const cancelled = el.dataset.cancel; editing = null;
    if (!cancelled) {
      const f = find(wid);
      if (f) { f.w.s[k] = isHtml ? el.innerHTML.trim() : el.textContent; commit(true); }
    }
    renderCanvas(); if (state.mode === 'edit') renderPanel();
  };
  el.addEventListener('blur', done);
}

function globalKeys(e) {
  const mod = e.ctrlKey || e.metaKey;
  if (mod && e.key.toLowerCase() === 'z') { e.preventDefault(); undo(e.shiftKey ? 1 : -1); }
  else if (mod && e.key.toLowerCase() === 'y') { e.preventDefault(); undo(1); }
  else if (mod && e.key.toLowerCase() === 's') { e.preventDefault(); save(); }
  else if (mod && e.key.toLowerCase() === 'd' && state.sel) { e.preventDefault(); dup(state.sel.id); }
  else if ((e.key === 'Delete' || e.key === 'Backspace') && state.sel && !/INPUT|TEXTAREA|SELECT/.test((e.target.tagName || ''))) { e.preventDefault(); del(state.sel.id); }
  else if (e.key === 'Escape' && state.sel) deselect();
}

/* ---------- drag & drop ---------- */
function endDrag() { drag = null; dropTarget = null; hideDrop(); $$('.bx-dragging', fdoc).forEach(e => e.classList.remove('bx-dragging')); }
function hideDrop() { if (dropEl) dropEl.style.display = 'none'; }
function showDrop(kind, top, left, width) {
  const sy = fdoc.defaultView.scrollY, sx = fdoc.defaultView.scrollX;
  dropEl.className = 'bx-drop ' + kind; dropEl.style.display = 'block';
  dropEl.style.top = (top + sy - 2) + 'px'; dropEl.style.left = (left + sx) + 'px'; dropEl.style.width = width + 'px';
}
const secEls = () => $$('#root > [data-sid]', fdoc);
function secIndexByY(y) {
  const els = secEls();
  for (let i = 0; i < els.length; i++) { const r = els[i].getBoundingClientRect(); if (y < r.top + r.height / 2) return i; }
  return els.length;
}
function nearestCol(secEl, x) {
  let best = null, bd = 1e9;
  $$('[data-cid]', secEl).forEach(c => { const r = c.getBoundingClientRect(); const d = x < r.left ? r.left - x : x > r.right ? x - r.right : 0; if (d < bd) { bd = d; best = c; } });
  return best;
}
function onDragOver(e) {
  if (!drag) return;
  e.preventDefault(); e.dataTransfer.dropEffect = drag.kind === 'new' || drag.kind === 'tpl' || drag.kind === 'layout' ? 'copy' : 'move';
  const y = e.clientY, x = e.clientX, win = fdoc.defaultView;
  if (y < 70) win.scrollBy(0, -18); else if (y > win.innerHeight - 70) win.scrollBy(0, 18);
  const t = e.target.nodeType === 1 ? e.target : e.target.parentElement;
  let target = null;
  const widgetLike = drag.kind === 'new' || drag.kind === 'widget';
  if (widgetLike) {
    const secEl = t.closest('[data-sid]');
    if (secEl) {
      const r = secEl.getBoundingClientRect();
      const edge = Math.min(14, r.height / 4);
      if (y - r.top < edge) target = { t: 'sec', idx: secEls().indexOf(secEl) };
      else if (r.bottom - y < edge) target = { t: 'sec', idx: secEls().indexOf(secEl) + 1 };
      else {
        const colEl = t.closest('[data-cid]') || nearestCol(secEl, x);
        if (colEl) {
          const items = [...colEl.children].filter(c => c.hasAttribute('data-wid') && c.dataset.wid !== (drag.kind === 'widget' ? drag.id : ''));
          let idx = items.length, ly;
          const cr = colEl.getBoundingClientRect();
          for (let i = 0; i < items.length; i++) { const ir = items[i].getBoundingClientRect(); if (y < ir.top + ir.height / 2) { idx = i; break; } }
          if (!items.length) ly = cr.top + 8; else if (idx < items.length) ly = items[idx].getBoundingClientRect().top; else ly = items[items.length - 1].getBoundingClientRect().bottom;
          const col = find(colEl.dataset.cid);
          let real = idx;
          if (drag.kind === 'widget') {
            const ids = col.col.widgets.map(w => w.id).filter(i => i !== drag.id);
            real = idx; const before = ids[idx]; real = before ? col.col.widgets.findIndex(w => w.id === before) : col.col.widgets.length;
          }
          target = { t: 'col', cid: colEl.dataset.cid, idx: real, ly, lx: cr.left + 4, lw: cr.width - 8 };
        }
      }
    }
    if (!target) target = { t: 'sec', idx: secIndexByY(y) };
  } else {
    target = { t: 'sec', idx: secIndexByY(y) };
  }
  dropTarget = target;
  if (target.t === 'col') showDrop('col', target.ly, target.lx, target.lw);
  else {
    const els = secEls();
    let ty, r0 = $('#root', fdoc).getBoundingClientRect();
    if (!els.length) ty = r0.top + 6;
    else if (target.idx >= els.length) ty = els[els.length - 1].getBoundingClientRect().bottom;
    else ty = els[target.idx].getBoundingClientRect().top;
    showDrop('sec', ty, 0, fdoc.documentElement.clientWidth);
  }
}
function onDrop(e) {
  if (!drag) return; e.preventDefault();
  const tg = dropTarget, dr = drag; endDrag();
  if (!tg) return;
  if (dr.kind === 'new') {
    if (tg.t === 'col') addWidgetTo(dr.type, tg.cid, tg.idx); else widgetToNewSection(null, tg.idx, true, dr.type);
  } else if (dr.kind === 'widget') {
    if (tg.t === 'col') moveWidget(dr.id, tg.cid, tg.idx); else widgetToNewSection(dr.id, tg.idx, false);
  } else if (dr.kind === 'section') {
    moveSection(dr.id, tg.t === 'sec' ? tg.idx : secIndexByY(e.clientY));
  } else if (dr.kind === 'tpl') {
    const t = TPL.find(x => x.key === dr.key); if (t) insertSections(t.make(), tg.t === 'sec' ? tg.idx : secIndexByY(e.clientY));
  } else if (dr.kind === 'layout') {
    insertSections(newSectionFromLayout(LAYOUTS[dr.i]), tg.t === 'sec' ? tg.idx : secIndexByY(e.clientY));
  }
}

/* ---------- resize kolom ---------- */
function onResizeStart(e) {
  const h = e.target.closest && e.target.closest('.bx-resz'); if (!h) return;
  e.preventDefault();
  const f = find(h.dataset.resz); if (!f) return;
  const sec = f.sec, i = f.ci, a = sec.cols[i], b = sec.cols[i + 1]; if (!b) return;
  const rowEl = h.closest('.mbg-row'), rw = rowEl.getBoundingClientRect().width;
  const total = sec.cols.reduce((s, c) => s + c.w, 0), startX = e.clientX, wa = a.w, wb = b.w;
  fdoc.body.classList.add('bx-resizing');
  const tip = fdoc.createElement('div'); tip.style.cssText = 'position:fixed;z-index:200;background:#8b5cf6;color:#fff;font:600 11px Inter,sans-serif;padding:3px 8px;border-radius:6px;pointer-events:none';
  fdoc.body.appendChild(tip);
  const move = ev => {
    let dw = (ev.clientX - startX) / rw * total;
    dw = Math.max(-(wa - 8), Math.min(wb - 8, dw));
    a.w = +(wa + dw).toFixed(2); b.w = +(wb - dw).toFixed(2);
    rowEl.style.gridTemplateColumns = sec.cols.map(c => `minmax(0,${c.w}fr)`).join(' ');
    tip.textContent = Math.round(a.w / total * 100) + '% | ' + Math.round(b.w / total * 100) + '%';
    tip.style.left = (ev.clientX + 12) + 'px'; tip.style.top = (ev.clientY + 12) + 'px';
  };
  const up = () => {
    fdoc.removeEventListener('mousemove', move); fdoc.removeEventListener('mouseup', up); window.removeEventListener('mouseup', up);
    fdoc.body.classList.remove('bx-resizing'); tip.remove();
    commit(true); renderCanvas(); if (state.mode === 'edit') renderPanel();
  };
  fdoc.addEventListener('mousemove', move); fdoc.addEventListener('mouseup', up); window.addEventListener('mouseup', up);
}

/* =====================================================================
   PANEL KIRI
   ===================================================================== */
const panel = $('#bxPanel');

function renderPanel() {
  const keepScroll = $('.bx-pbody', panel) ? $('.bx-pbody', panel).scrollTop : 0;
  if (state.mode === 'edit' && cur()) panelEdit(); else { state.mode = 'lib'; panelLib(); }
  const pb = $('.bx-pbody', panel); if (pb) pb.scrollTop = keepScroll;
}

function panelLib() {
  const tabs = [['widgets', 'fa-shapes', 'Widget'], ['tpl', 'fa-table-cells-large', 'Template'], ['nav', 'fa-sitemap', 'Navigator'], ['page', 'fa-gear', 'Halaman']];
  let body = '';
  if (state.libTab === 'widgets') {
    const groups = {}; Object.values(WD).forEach(w => (groups[w.group] = groups[w.group] || []).push(w));
    body = `<input class="bx-search" id="bxWSearch" placeholder="Cari widget…" autocomplete="off"><p class="bx-hint">Seret widget ke kanvas, atau klik untuk menambahkan ke kolom yang dipilih.</p>`
      + Object.entries(groups).map(([g, ws]) => `<div class="bx-group"><h4>${esc(g)}</h4><div class="bx-grid">${ws.map(w => `<div class="bx-item" draggable="true" data-new="${w.type}" data-q="${esc((w.label + ' ' + w.tags).toLowerCase())}"><i class="${w.icon.includes(' ') ? w.icon : 'fas ' + w.icon}"></i><span>${esc(w.label)}</span></div>`).join('')}</div></div>`).join('');
  } else if (state.libTab === 'tpl') {
    body = `<h4 class="bx-sec-title">Susunan kolom</h4><p class="bx-hint">Seret ke kanvas untuk membuat section kosong.</p><div class="bx-lay">${LAYOUTS.map((l, i) => `<button type="button" draggable="true" data-lay="${i}" title="${l.length} kolom">${l.map(w => `<span style="flex:${w}"></span>`).join('')}</button>`).join('')}</div>`
      + `<h4 class="bx-sec-title">Template section</h4><p class="bx-hint">Seret ke kanvas, atau klik untuk menyisipkan${state.insertAt != null ? ' di posisi yang dipilih' : ' di akhir halaman'}.</p>`
      + TPL.map(t => `<button type="button" class="bx-tpl" draggable="true" data-tpl="${t.key}"><strong><i class="fas ${t.icon}"></i> ${esc(t.label)}</strong><small>${esc(t.desc)}</small></button>`).join('');
  } else if (state.libTab === 'nav') {
    body = `<div class="bx-nav">${state.sections.length ? '<ul>' + state.sections.map((s, i) => navSec(s, i)).join('') + '</ul>' : '<div class="bx-empty-note">Belum ada elemen.</div>'}</div>`;
  } else {
    body = pageSettings();
  }
  panel.innerHTML = `<div class="bx-ptabs">${tabs.map(([k, ic, l]) => `<button class="bx-ptab${state.libTab === k ? ' active' : ''}" data-ptab="${k}"><i class="fas ${ic}"></i>${l}</button>`).join('')}</div><div class="bx-pbody">${body}</div>`;
}
const navLi = (kind, id, icon, label, inner) => `<li><div class="nv${state.sel && state.sel.id === id ? ' sel' : ''}" data-nav="${kind}:${id}"><i class="fas ${icon}"></i><span>${esc(label)}</span></div>${inner || ''}</li>`;
function navSec(s, i) {
  return navLi('sec', s.id, 'fa-table-rows', 'Section ' + (i + 1) + (s.s.anchor ? ' #' + s.s.anchor : ''), '<ul>' + s.cols.map((c, ci) => navLi('col', c.id, 'fa-table-columns', 'Kolom ' + (ci + 1) + ' (' + Math.round(c.w) + '%)',
    c.widgets.length ? '<ul>' + c.widgets.map(w => navLi('wid', w.id, (WD[w.type].icon.includes(' ') ? WD[w.type].icon.replace('fa-brands fa-whatsapp', 'fab fa-whatsapp') : WD[w.type].icon), WD[w.type].label + (w.s.title || w.s.text ? ' — ' + String(w.s.title || w.s.text).slice(0, 24) : ''))).join('') + '</ul>' : '')).join('') + '</ul>');
}

function pageSettings() {
  const p = state.page;
  const f = [F.text('title', 'Judul halaman'), F.sel('status', 'Status', [['draft', 'Draft'], ['published', 'Terbit']]),
    F.sw('site_chrome', 'Tampilkan navbar & footer situs'), F.text('seo_title', 'SEO title'), F.area('seo_description', 'SEO description'),
    F.code('custom_css', 'CSS kustom halaman', { hint: 'Dipakai pada halaman publik dan langsung tampil di kanvas.' })];
  return (p.is_home ? `<p class="bx-hint" style="color:#fcd34d"><i class="fas fa-triangle-exclamation"></i> Halaman <b>home</b> memakai file <code>public/home/index.html</code>. Hasil builder disimpan ke <code>public/pages/home.html</code>; tidak menggantikan homepage utama.</p>` : '')
    + f.map(x => fieldHtml(x, p, 'P:')).join('')
    + `<p class="bx-hint">Pintasan: <b>Ctrl+S</b> simpan · <b>Ctrl+Z / Y</b> undo/redo · <b>Ctrl+D</b> duplikat · <b>Delete</b> hapus · klik dua kali teks untuk edit langsung.</p>`;
}

/* ---------- panel edit elemen ---------- */
const SEC_FIELDS = [
  F.seg('width', 'Lebar konten', [['boxed', 'Boxed'], ['narrow', 'Sempit'], ['full', 'Penuh']]),
  F.num('gap', 'Jarak antar kolom (px)'), F.num('minH', 'Tinggi minimum (px)', { ph: 'otomatis' }),
  F.seg('valign', 'Posisi vertikal (jika tinggi minimum)', [['start', 'Atas'], ['center', 'Tengah'], ['end', 'Bawah']]),
  F.seg('stack', 'Tumpuk kolom di', [['mobile', 'Mobile'], ['tablet', 'Tablet & mobile']]),
  { t: 'cols' },
  F.color('bg', 'Warna latar', { tab: 's' }), F.img('bgImg', 'Gambar latar', { tab: 's' }), F.sw('bgFixed', 'Latar tetap (parallax)', { tab: 's' }),
  F.color('overlay', 'Warna overlay', { tab: 's' }), F.range('overlayOp', 'Opasitas overlay (%)', 0, 100, { tab: 's' }),
  F.color('color', 'Warna teks default', { tab: 's' }), F.num('padT', 'Padding atas (px)', { tab: 's' }), F.num('padB', 'Padding bawah (px)', { tab: 's' }),
  F.text('anchor', 'ID anchor', { tab: 'a', hint: 'Untuk tautan menu, mis. "about" → #about' }), F.text('cls', 'Kelas CSS', { tab: 'a' }),
  F.sw('hideD', 'Sembunyikan di desktop', { tab: 'a' }), F.sw('hideT', 'Sembunyikan di tablet', { tab: 'a' }), F.sw('hideM', 'Sembunyikan di mobile', { tab: 'a' }),
];
const COL_FIELDS = [
  F.range('w', 'Lebar relatif (%)', 5, 100, { root: true, step: 0.5 }),
  F.seg('valign', 'Posisi konten', [['start', 'Atas'], ['center', 'Tengah'], ['end', 'Bawah']]), F.num('gap', 'Jarak antar widget (px)'),
  F.color('bg', 'Warna latar', { tab: 's' }), F.num('pad', 'Padding (px)', { tab: 's' }), F.num('radius', 'Radius sudut (px)', { tab: 's' }),
  F.color('border', 'Warna border', { tab: 's' }), F.sw('shadow', 'Bayangan', { tab: 's' }),
];
const WID_ADV = [F.num('mt', 'Margin atas (px)', { tab: 'a' }), F.num('mb', 'Margin bawah (px)', { tab: 'a' }),
  F.text('anchor', 'ID anchor', { tab: 'a' }), F.text('cls', 'Kelas CSS', { tab: 'a' }),
  F.sw('hideD', 'Sembunyikan di desktop', { tab: 'a' }), F.sw('hideT', 'Sembunyikan di tablet', { tab: 'a' }), F.sw('hideM', 'Sembunyikan di mobile', { tab: 'a' })];

function panelEdit() {
  const f = cur(); let fields, title, icon, tabs;
  if (f.kind === 'wid') { const d = WD[f.w.type]; fields = [...d.fields, ...WID_ADV]; title = d.label; icon = d.icon.includes(' ') ? d.icon : 'fas ' + d.icon; tabs = [['c', 'Konten'], ['s', 'Gaya'], ['a', 'Lanjutan']]; }
  else if (f.kind === 'sec') { fields = SEC_FIELDS; title = 'Section'; icon = 'fas fa-table-rows'; tabs = [['c', 'Layout'], ['s', 'Gaya'], ['a', 'Lanjutan']]; }
  else { fields = COL_FIELDS; title = 'Kolom'; icon = 'fas fa-table-columns'; tabs = [['c', 'Kolom'], ['s', 'Gaya']]; }
  tabs = tabs.filter(([k]) => fields.some(x => (x.tab || 'c') === k));
  if (!tabs.find(([k]) => k === state.setTab)) state.setTab = tabs[0][0];
  const list = fields.filter(x => (x.tab || 'c') === state.setTab);
  const parentBtns = f.kind === 'wid' ? `<button class="bx-iconbtn" data-sel="col:${f.col.id}" title="Pilih kolom induk"><i class="fas fa-table-columns"></i></button>` : f.kind === 'col' ? `<button class="bx-iconbtn" data-sel="sec:${f.sec.id}" title="Pilih section induk"><i class="fas fa-table-rows"></i></button>` : '';
  panel.innerHTML = `<div class="bx-phead"><button class="bx-iconbtn" data-back title="Kembali ke daftar widget"><i class="fas fa-arrow-left"></i></button><h3><i class="${icon}"></i> ${esc(title)}</h3>${parentBtns}<button class="bx-iconbtn" data-dup title="Duplikat"><i class="fas fa-clone"></i></button><button class="bx-iconbtn" data-del title="Hapus"><i class="fas fa-trash"></i></button></div>`
    + `<div class="bx-stabs">${tabs.map(([k, l]) => `<button class="bx-stab${state.setTab === k ? ' active' : ''}" data-stab="${k}">${l}</button>`).join('')}</div>`
    + `<div class="bx-pbody">${list.map(x => x.t === 'cols' ? colsField(f) : fieldHtml(x, f.kind === 'wid' ? { ...WD[f.w.type].def, ...f.w.s } : x.root ? f.obj : f.obj.s, f.kind === 'wid' ? 's.' : x.root ? '' : 's.', f)).join('')}</div>`;
}
function colsField(f) {
  const cur = f.sec.cols.map(c => Math.round(c.w)).join('-');
  return `<div class="bx-f"><label>Susunan kolom (${f.sec.cols.length})</label><div class="bx-lay" style="grid-template-columns:repeat(4,1fr)">${LAYOUTS.map((l, i) => `<button type="button" data-applylay="${i}" class="${l.map(Math.round).join('-') === cur ? 'on' : ''}">${l.map(w => `<span style="flex:${w}"></span>`).join('')}</button>`).join('')}</div><div class="bx-sub">Pilih susunan untuk mengubah jumlah kolom. Lebar kolom juga bisa digeser langsung di kanvas.</div></div>`;
}

/* ---------- field renderer ---------- */
function fieldHtml(f, obj, base = '', ctx) {
  if (f.t === 'head') return `<h4 class="bx-sec-title">${esc(f.l)}</h4>`;
  const path = (f.root ? '' : base) + f.k;
  const v = base === 'P:' ? obj[f.k] : (f.root ? obj[f.k] : obj[f.k]);
  const P = `data-p="${esc(path)}"`;
  const lab = f.l ? `<label>${esc(f.l)}</label>` : '';
  const hint = f.hint ? `<div class="bx-sub">${esc(f.hint)}</div>` : '';
  const val = esc(v ?? '');
  switch (f.t) {
    case 'text': return `<div class="bx-f">${lab}<input class="bx-in" type="text" ${P} value="${val}" placeholder="${esc(f.ph || '')}">${hint}</div>`;
    case 'url': return `<div class="bx-f">${lab}<input class="bx-in" type="text" ${P} value="${val}" placeholder="https://…">${hint}</div>`;
    case 'area': return `<div class="bx-f">${lab}<textarea ${P} rows="3">${val}</textarea>${hint}</div>`;
    case 'code': return `<div class="bx-f">${lab}<textarea class="code" ${P} spellcheck="false" rows="7">${val}</textarea>${hint}</div>`;
    case 'num': return `<div class="bx-f">${lab}<input class="bx-in" type="number" data-t="num" step="${f.step || 1}" ${P} value="${val}" placeholder="${esc(f.ph || '')}"></div>`;
    case 'range': return `<div class="bx-f">${lab}<div class="bx-range"><input type="range" data-t="num" min="${f.min}" max="${f.max}" step="${f.step || 1}" ${P} value="${esc(v ?? f.min)}"><input class="bx-in" type="number" data-t="num" step="${f.step || 1}" ${P} value="${esc(v ?? '')}"></div></div>`;
    case 'color': return `<div class="bx-f">${lab}<div class="bx-color"><input type="color" data-t="colorpick" ${P} value="${/^#[0-9a-f]{6}$/i.test(v || '') ? v : '#ffffff'}"><input class="bx-in" type="text" data-t="colortxt" ${P} value="${val}" placeholder="kosong = default"><button class="bx-iconbtn" type="button" data-clr="${esc(path)}" title="Hapus warna"><i class="fas fa-xmark"></i></button></div></div>`;
    case 'sel': return `<div class="bx-f">${lab}<select ${P}>${f.opts.map(o => { const [a, b] = Array.isArray(o) ? o : [o, o]; return `<option value="${esc(a)}"${String(v) === String(a) ? ' selected' : ''}>${esc(b)}</option>`; }).join('')}</select></div>`;
    case 'seg': return `<div class="bx-f">${lab}<div class="bx-seg">${f.opts.map(([a, b]) => `<button type="button" class="${String(v) === String(a) ? 'on' : ''}" data-seg="${esc(path)}" data-v="${esc(a)}" ${f.root ? 'data-root' : ''}>${b}</button>`).join('')}</div></div>`;
    case 'sw': return `<div class="bx-f bx-sw"><label>${esc(f.l)}</label><input type="checkbox" data-t="sw" ${P} ${v ? 'checked' : ''}></div>`;
    case 'img': return `<div class="bx-f">${lab}<div class="bx-row">${v ? `<img class="bx-thumb" src="${val}" alt="">` : ''}<input class="bx-in" type="text" ${P} value="${val}" placeholder="URL gambar"><button class="bx-btn bx-btn-ghost bx-btn-sm" type="button" data-media="${esc(path)}"><i class="fas fa-image"></i></button></div></div>`;
    case 'icon': return `<div class="bx-f">${lab}<div class="bx-row"><i class="${val}" style="width:26px;text-align:center;color:#5eead4;font-size:16px"></i><input class="bx-in" type="text" ${P} value="${val}" placeholder="fas fa-star"><button class="bx-btn bx-btn-ghost bx-btn-sm" type="button" data-icon="${esc(path)}"><i class="fas fa-icons"></i></button></div></div>`;
    case 'list': {
      const items = Array.isArray(v) ? v : [];
      const lblK = f.lbl || f.f[0].k;
      return `<div class="bx-f">${lab}<div class="bx-list">${items.map((it, i) => `<div class="bx-li" data-li="${i}"><header data-lih><b>${esc(String(it[lblK] || 'Item ' + (i + 1)).slice(0, 40))}</b><button class="bx-mini" type="button" data-lu="${esc(path)}:${i}" title="Naik"><i class="fas fa-arrow-up"></i></button><button class="bx-mini" type="button" data-ld="${esc(path)}:${i}" title="Turun"><i class="fas fa-arrow-down"></i></button><button class="bx-mini" type="button" data-lx="${esc(path)}:${i}" title="Hapus"><i class="fas fa-xmark"></i></button></header><div class="bx-lib">${f.f.map(sf => fieldHtml(sf, it, path + '.' + i + '.')).join('')}</div></div>`).join('')}<button class="bx-btn bx-btn-ghost bx-btn-sm" type="button" data-la="${esc(path)}" style="align-self:flex-start"><i class="fas fa-plus"></i> Tambah item</button></div></div>`;
    }
  }
  return '';
}

/* ---------- baca/tulis path ---------- */
function targetObj() {
  const f = cur(); return f ? f.obj : null;
}
function getp(o, path) { return path.split('.').reduce((a, k) => a == null ? a : a[k], o); }
function setp(o, path, v) {
  const ks = path.split('.'); const last = ks.pop(); let a = o;
  for (const k of ks) { if (a[k] == null) a[k] = /^\d+$/.test(k) ? [] : {}; a = a[k]; }
  a[last] = v;
}
function applyValue(path, v) {
  if (path.startsWith('P:')) { setp(state.page, path.slice(2), v); setDirty(); if (path === 'P:custom_css') applyPageCss(); if (path === 'P:title') $('#bxPageTitle').textContent = v; return; }
  const o = targetObj(); if (!o) return;
  setp(o, path, v); commit(); refresh(false);
}

/* =====================================================================
   EVENT PANEL
   ===================================================================== */
function readEl(el) {
  const t = el.dataset.t;
  if (t === 'sw') return el.checked;
  if (t === 'num') return el.value === '' ? '' : parseFloat(el.value);
  return el.value;
}
panel.addEventListener('input', e => {
  const el = e.target;
  if (el.id === 'bxWSearch') {
    const q = el.value.trim().toLowerCase();
    $$('.bx-item', panel).forEach(i => i.style.display = !q || i.dataset.q.includes(q) ? '' : 'none');
    $$('.bx-group', panel).forEach(g => g.style.display = $$('.bx-item', g).some(i => i.style.display !== 'none') ? '' : 'none');
    return;
  }
  if (!el.dataset.p) return;
  const t = el.dataset.t;
  if (t === 'colortxt' && el.value && !/^(#[0-9a-f]{3,8}|rgba?\([^)]*\)|[a-z]+|linear-gradient\(.*\))$/i.test(el.value.trim())) return;
  const path = el.dataset.p, v = readEl(el);
  // sinkronkan pasangan kontrol (range+angka, warna+teks)
  $$(`[data-p="${CSS.escape(path)}"]`, panel).forEach(o => { if (o !== el && o.dataset.t !== 'sw') { if (o.type === 'color') { if (/^#[0-9a-f]{6}$/i.test(v)) o.value = v; } else o.value = v; } });
  applyValue(path, v);
});
panel.addEventListener('change', e => {
  const el = e.target; if (!el.dataset.p || el.tagName !== 'SELECT' && el.dataset.t !== 'sw') return;
  applyValue(el.dataset.p, readEl(el));
  if (el.dataset.p === 'P:site_chrome') setDirty();
});
panel.addEventListener('click', e => {
  const t = e.target;
  const q = sel => t.closest(sel);
  let el;
  if ((el = q('[data-ptab]'))) { state.libTab = el.dataset.ptab; renderPanel(); }
  else if ((el = q('[data-stab]'))) { state.setTab = el.dataset.stab; renderPanel(); }
  else if (q('[data-back]')) deselect();
  else if (q('[data-dup]')) dup(state.sel.id);
  else if (q('[data-del]')) del(state.sel.id);
  else if ((el = q('[data-sel]'))) { const [k, id] = el.dataset.sel.split(':'); select(k, id, 'c'); scrollToEl(id); }
  else if ((el = q('[data-nav]'))) { const [k, id] = el.dataset.nav.split(':'); select(k, id); scrollToEl(id); }
  else if ((el = q('[data-new]'))) addWidgetAuto(el.dataset.new);
  else if ((el = q('[data-tpl]'))) { const tp = TPL.find(x => x.key === el.dataset.tpl); const s = cur(); insertSections(tp.make(), s ? s.si + 1 : state.insertAt); }
  else if ((el = q('[data-lay]'))) { const s = cur(); insertSections(newSectionFromLayout(LAYOUTS[+el.dataset.lay]), s ? s.si + 1 : null); }
  else if ((el = q('[data-applylay]'))) applyLayout(cur().sec.id, LAYOUTS[+el.dataset.applylay]);
  else if ((el = q('[data-seg]'))) { applyValue(el.dataset.seg, el.dataset.v); $$(`[data-seg="${CSS.escape(el.dataset.seg)}"]`, panel).forEach(b => b.classList.toggle('on', b === el)); if (/^(P:)?w$/.test(el.dataset.seg)) renderPanel(); }
  else if ((el = q('[data-clr]'))) { applyValue(el.dataset.clr, ''); renderPanel(); }
  else if ((el = q('[data-media]'))) { const p = el.dataset.media; openMedia(url => { applyValue(p, url); renderPanel(); }); }
  else if ((el = q('[data-icon]'))) { const p = el.dataset.icon; openIcons(cls => { applyValue(p, cls); renderPanel(); }); }
  else if ((el = q('[data-la]'))) listOp('add', el.dataset.la);
  else if ((el = q('[data-lx]'))) listOp('del', ...el.dataset.lx.split(/:(?=\d+$)/));
  else if ((el = q('[data-lu]'))) listOp('up', ...el.dataset.lu.split(/:(?=\d+$)/));
  else if ((el = q('[data-ld]'))) listOp('down', ...el.dataset.ld.split(/:(?=\d+$)/));
  else if ((el = q('[data-lih]')) && !q('button')) el.parentElement.classList.toggle('open');
});
function listOp(op, path, i) {
  const o = targetObj(); if (!o) return;
  const f = cur(); const d = f.kind === 'wid' ? WD[f.w.type] : null;
  let arr = getp(o, path); if (!Array.isArray(arr)) { arr = []; setp(o, path, arr); }
  i = +i;
  if (op === 'add') {
    const def = d ? d.fields.find(x => x.k === path.replace(/^s\./, '')) : null;
    arr.push(clone(def ? def.add : {}));
  } else if (op === 'del') arr.splice(i, 1);
  else if (op === 'up' && i > 0) [arr[i - 1], arr[i]] = [arr[i], arr[i - 1]];
  else if (op === 'down' && i < arr.length - 1) [arr[i + 1], arr[i]] = [arr[i], arr[i + 1]];
  commit(true); renderCanvas();
  const open = op === 'add' ? arr.length - 1 : -1;
  renderPanel(); if (open >= 0) { const li = $$('.bx-li', panel).pop(); if (li) li.classList.add('open'); }
}

/* drag dari panel */
panel.addEventListener('dragstart', e => {
  const it = e.target.closest('[data-new],[data-tpl],[data-lay]'); if (!it) return;
  if (it.dataset.new) drag = { kind: 'new', type: it.dataset.new };
  else if (it.dataset.tpl) drag = { kind: 'tpl', key: it.dataset.tpl };
  else drag = { kind: 'layout', i: +it.dataset.lay };
  e.dataTransfer.effectAllowed = 'copy'; e.dataTransfer.setData('text/plain', 'bx');
});
panel.addEventListener('dragend', () => { drag = null; dropTarget = null; if (fdoc) endDrag(); });

/* =====================================================================
   MODAL: media & ikon
   ===================================================================== */
const modal = $('#bxModal');
function openModal(title, body) {
  modal.hidden = false;
  modal.innerHTML = `<div class="bx-dlg"><header><h3>${title}</h3><button class="bx-iconbtn" data-mx><i class="fas fa-xmark"></i></button></header><div class="bx-body">${body}</div></div>`;
}
const closeModal = () => { modal.hidden = true; modal.innerHTML = ''; };
modal.addEventListener('click', e => { if (e.target === modal || e.target.closest('[data-mx]')) closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

function openMedia(cb) {
  const draw = () => openModal('Pilih gambar', `<div class="bx-row" style="margin-bottom:14px"><label class="bx-btn bx-btn-primary" style="cursor:pointer"><i class="fas fa-upload"></i> Upload gambar<input type="file" id="bxUp" accept="image/*" hidden></label><input class="bx-in" id="bxUrl" placeholder="…atau tempel URL gambar lalu Enter"></div>`
    + (B.media.length ? `<div class="bx-media">${B.media.map(m => `<button type="button" data-url="${esc(m.url)}" title="${esc(m.name)}"><img src="${esc(m.url)}" alt="" loading="lazy"></button>`).join('')}</div>` : '<div class="bx-empty-note">Belum ada gambar di Media Library.</div>'));
  draw();
  modal.onclick = async e => {
    if (e.target === modal || e.target.closest('[data-mx]')) return closeModal();
    const b = e.target.closest('[data-url]'); if (b) { closeModal(); cb(b.dataset.url); }
  };
  modal.onchange = async e => {
    if (e.target.id !== 'bxUp' || !e.target.files[0]) return;
    const fd = new FormData(); fd.append('file', e.target.files[0]); fd.append('csrf_token', B.csrf);
    toast('Mengunggah…');
    try {
      const r = await (await fetch('builder.php?action=upload', { method: 'POST', body: fd })).json();
      if (!r.ok) return toast(r.error || 'Upload gagal', 'err');
      B.media.unshift({ url: r.url, name: r.name }); closeModal(); cb(r.url); toast('Gambar diunggah', 'ok');
    } catch (_) { toast('Upload gagal', 'err'); }
  };
  modal.onkeydown = e => { if (e.target.id === 'bxUrl' && e.key === 'Enter' && e.target.value.trim()) { const u = e.target.value.trim(); closeModal(); cb(u); } };
}
const ICONS = 'fas fa-code,fas fa-laptop-code,fas fa-mobile-screen,fas fa-server,fas fa-database,fas fa-cloud,fas fa-shield-halved,fas fa-lock,fas fa-key,fas fa-bug,fas fa-microchip,fas fa-network-wired,fas fa-chart-line,fas fa-chart-pie,fas fa-chart-simple,fas fa-graduation-cap,fas fa-book-open,fas fa-chalkboard-user,fas fa-users,fas fa-users-gear,fas fa-user-tie,fas fa-handshake,fas fa-lightbulb,fas fa-rocket,fas fa-bolt,fas fa-gear,fas fa-gears,fas fa-wrench,fas fa-layer-group,fas fa-cubes,fas fa-cube,fas fa-diagram-project,fas fa-sitemap,fas fa-compass,fas fa-bullseye,fas fa-flag-checkered,fas fa-trophy,fas fa-award,fas fa-star,fas fa-heart,fas fa-thumbs-up,fas fa-circle-check,fas fa-check,fas fa-arrow-right,fas fa-arrow-trend-up,fas fa-magnifying-glass,fas fa-envelope,fas fa-phone,fas fa-location-dot,fas fa-clock,fas fa-calendar,fas fa-globe,fas fa-link,fas fa-image,fas fa-video,fas fa-play,fas fa-headset,fas fa-comments,fas fa-bell,fas fa-bag-shopping,fas fa-cart-shopping,fas fa-credit-card,fas fa-box-open,fas fa-tags,fas fa-building,fas fa-house,fas fa-leaf,fas fa-fire,fas fa-gem,fas fa-palette,fas fa-pen-nib,fab fa-whatsapp,fab fa-facebook-f,fab fa-instagram,fab fa-linkedin-in,fab fa-github,fab fa-youtube,fab fa-twitter,fab fa-x-twitter,fab fa-tiktok,fab fa-telegram,fab fa-windows,fab fa-google,fab fa-microsoft,fab fa-apple,fab fa-android'.split(',');
function openIcons(cb) {
  openModal('Pilih ikon', `<input class="bx-search" id="bxIq" placeholder="Filter ikon (mis. cloud, user)…"><div class="bx-icons">${ICONS.map(c => `<button type="button" data-ic="${c}" title="${c}"><i class="${c}"></i></button>`).join('')}</div>`);
  modal.onclick = e => { if (e.target === modal || e.target.closest('[data-mx]')) return closeModal(); const b = e.target.closest('[data-ic]'); if (b) { closeModal(); cb(b.dataset.ic); } };
  modal.onchange = null;
  modal.oninput = e => { if (e.target.id === 'bxIq') { const q = e.target.value.toLowerCase(); $$('[data-ic]', modal).forEach(b => b.style.display = b.dataset.ic.includes(q) ? '' : 'none'); } };
  modal.onkeydown = null;
}

/* =====================================================================
   SIMPAN
   ===================================================================== */
let toastT;
function toast(msg, kind) { const t = $('#bxToast'); t.textContent = msg; t.className = 'bx-toast show ' + (kind || ''); clearTimeout(toastT); toastT = setTimeout(() => t.classList.remove('show'), 2600); }

async function save() {
  if (saving) return; if (editing) editing.blur();
  saving = true; const btn = $('#bxSave'); btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan…';
  try {
    const p = state.page;
    const res = await fetch('builder.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_token: B.csrf, id: p.id, title: p.title, status: p.status, site_chrome: p.site_chrome ? 1 : 0, seo_title: p.seo_title, seo_description: p.seo_description, custom_css: p.custom_css, builder_data: JSON.stringify(state.sections), content: outputHtml() }),
    });
    const r = await res.json();
    if (!r.ok) throw new Error(r.error || 'Gagal menyimpan');
    setDirty(false); toast('Tersimpan ' + r.saved_at + (r.synced ? '' : ' (peringatan: file publik gagal ditulis)'), r.synced ? 'ok' : 'err');
    if (r.url) $('#bxView').href = r.url;
  } catch (err) { toast(err.message, 'err'); const c = $('#bxStatus'); c.textContent = 'Gagal simpan'; c.classList.add('err'); }
  finally { saving = false; btn.disabled = false; btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Simpan'; }
}

/* =====================================================================
   TOP BAR
   ===================================================================== */
$('#bxSave').addEventListener('click', save);
$('#bxUndo').addEventListener('click', () => undo(-1));
$('#bxRedo').addEventListener('click', () => undo(1));
$('#bxPreview').addEventListener('click', e => { const on = fdoc.body.classList.toggle('bx-preview'); e.currentTarget.classList.toggle('bx-btn-primary', on); e.currentTarget.classList.toggle('bx-btn-ghost', !on); });
$$('[data-dev]').forEach(b => b.addEventListener('click', () => {
  $$('[data-dev]').forEach(x => x.classList.toggle('active', x === b));
  const s = $('#bxStage'); s.classList.remove('dev-tablet', 'dev-mobile'); if (b.dataset.dev !== 'desktop') s.classList.add('dev-' + b.dataset.dev);
}));
document.addEventListener('keydown', e => { if (!/INPUT|TEXTAREA|SELECT/.test(e.target.tagName || '')) globalKeys(e); else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); } });
window.addEventListener('beforeunload', e => { if (state.dirty) { e.preventDefault(); e.returnValue = ''; } });
$('#bxBack').addEventListener('click', e => { if (state.dirty && !confirm('Ada perubahan yang belum disimpan. Tetap keluar?')) e.preventDefault(); });

/* ---------- mulai ---------- */
renderPanel(); initFrame(); updUndo();
})();
