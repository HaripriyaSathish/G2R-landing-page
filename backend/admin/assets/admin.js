/* G2R Admin interactions */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const icons = () => window.lucide && window.lucide.createIcons();
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* ---------- AJAX ---------- */
  async function api(path, data, method = 'POST') {
    const opts = { method, headers: { 'X-CSRF-Token': G2R.csrf, 'X-Requested-With': 'fetch', Accept: 'application/json' } };
    if (data instanceof FormData) opts.body = data;
    else if (data) { opts.body = new URLSearchParams(data); }
    const res = await fetch(G2R.base + path, opts);
    let json;
    try { json = await res.json(); } catch (e) { throw new Error('Server error (' + res.status + ')'); }
    if (!res.ok || json.ok === false) throw new Error(json.error || 'Request failed');
    return json;
  }
  window.G2Rapi = api;

  /* ---------- Centre popup for every result ---------- */
  let resultTimer;
  function toast(message, type = 'success') {
    const m = document.getElementById('resultModal');
    if (!m) return smallToast(message, type);
    const isErr = type === 'error';
    m.classList.toggle('error', isErr);
    m.querySelector('[data-result-title]').textContent = isErr ? 'Something went wrong' : 'Successfully done!';
    m.querySelector('[data-result-text]').textContent = message;
    // restart SVG / bar animations
    const icon = m.querySelector('[data-result-icon]');
    icon.replaceWith(icon.cloneNode(true));
    const bar = m.querySelector('.result-bar span');
    bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = '';
    m.classList.remove('open'); void m.offsetWidth;
    m.classList.add('open'); m.setAttribute('aria-hidden', 'false');
    m.querySelector('[data-close-modal]').focus();
    clearTimeout(resultTimer);
    if (!isErr) resultTimer = setTimeout(() => { m.classList.remove('open'); m.setAttribute('aria-hidden', 'true'); }, 2600);
  }
  function smallToast(message, type = 'success') {
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `<i data-lucide="${type === 'error' ? 'circle-alert' : 'circle-check'}"></i><span>${esc(message)}</span>`;
    $('#toasts').appendChild(el);
    icons();
    setTimeout(() => { el.classList.add('hide'); setTimeout(() => el.remove(), 300); }, type === 'error' ? 6000 : 3500);
  }
  window.toast = toast;
  $$('.toast-data').forEach((d) => toast(d.dataset.message, d.dataset.type));

  /* ---------- Sidebar / dropdown ---------- */
  $$('[data-toggle-sidebar]').forEach((b) => b.addEventListener('click', () => $('#app').classList.toggle('nav-open')));
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-dropdown]');
    $$('.dropdown.open').forEach((d) => { if (!trigger || d !== trigger.parentElement) d.classList.remove('open'); });
    if (trigger) trigger.parentElement.classList.toggle('open');
  });

  /* ---------- Modals ---------- */
  function openModal(id) { const m = document.getElementById(id); m.classList.add('open'); m.setAttribute('aria-hidden', 'false'); }
  function closeModal(m) { m.classList.remove('open'); m.setAttribute('aria-hidden', 'true'); }
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-close-modal]')) closeModal(e.target.closest('.modal'));
    else if (e.target.classList.contains('modal')) closeModal(e.target);
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.modal.open').forEach(closeModal); });

  /* ---------- Confirm ---------- */
  function confirmBox(title, text, okLabel = 'Delete') {
    return new Promise((resolve) => {
      const m = $('#confirmModal');
      $('[data-confirm-title]', m).textContent = title;
      $('[data-confirm-text]', m).textContent = text;
      const ok = $('[data-confirm-ok]', m);
      ok.textContent = okLabel;
      const done = (v) => { ok.onclick = null; closeModal(m); resolve(v); };
      ok.onclick = () => done(true);
      $$('[data-close-modal]', m).forEach((b) => (b.onclick = () => resolve(false)));
      openModal('confirmModal');
    });
  }
  window.confirmBox = confirmBox;
  document.addEventListener('submit', async (e) => {
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      e.preventDefault();
      if (await confirmBox(f.dataset.confirmTitle || 'Are you sure?', f.dataset.confirm, f.dataset.confirmOk || 'Delete')) {
        f.dataset.confirmed = '1';
        f.requestSubmit ? f.requestSubmit() : f.submit();
      }
      return;
    }
    const btn = f.querySelector('button[type=submit]:not([formaction]), button:not([type])');
    if (btn && !f.dataset.noLoading) btn.classList.add('loading');
  });

  /* ---------- Heading preview ---------- */
  function renderHeading(text) {
    return esc(text)
      .replace(/\{([^}]+)\}/g, '<span class="hl-o">$1</span>')
      .replace(/\[([^\]]+)\]/g, '<span class="hl-i">$1</span>')
      .replace(/\n/g, '<br>');
  }
  $$('.heading-input').forEach((t) => {
    const p = $(`[data-preview-for="${t.id}"]`);
    const up = () => (p.innerHTML = t.value.trim() ? renderHeading(t.value) : '');
    t.addEventListener('input', up); up();
  });

  /* ---------- Colour sync ---------- */
  $$('[data-color-sync]').forEach((c) => {
    const t = document.getElementById(c.dataset.colorSync);
    c.addEventListener('input', () => { t.value = c.value.toUpperCase(); t.dispatchEvent(new Event('input')); });
    t.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(t.value)) c.value = t.value; });
  });

  /* ---------- Icon picker ---------- */
  const ICONS = ['users','user','user-check','user-plus','users-round','briefcase','briefcase-business','building','building-2','factory','handshake','heart-handshake','target','trending-up','line-chart','bar-chart-3','pie-chart','award','trophy','crown','star','sparkles','rocket','zap','shield-check','shield','lock','badge-check','check-circle','clipboard-check','clipboard-list','file-text','file-check','scan-search','search','layers','layout-grid','presentation','graduation-cap','book-open','lightbulb','brain','cpu','code','laptop','monitor','smartphone','server','cloud','database','globe','globe-2','map','map-pin','navigation','compass','truck','package','warehouse','plane','ship','stethoscope','heart-pulse','hospital','banknote','wallet','landmark','coins','calculator','scale','gavel','wrench','settings','cog','hard-hat','hammer','headphones','headset','phone','mail','message-circle','message-square','send','calendar','clock','timer','hourglass','refresh-cw','repeat','arrow-right','arrow-up-right','link','external-link','thumbs-up','smile','heart','flag','bookmark','gift','leaf','sun','home','store','shopping-bag','network','workflow','git-branch','puzzle','infinity','circle-dollar-sign','percent','activity'];
  let iconTarget = null;
  function buildIcons(q = '') {
    const grid = $('[data-icon-grid]');
    const list = ICONS.filter((n) => n.includes(q.toLowerCase().trim()));
    grid.innerHTML = list.map((n) => `<button type="button" class="icon-opt" data-icon="${n}"><i data-lucide="${n}"></i>${n}</button>`).join('') || '<p class="muted">No match — you can still type any Lucide icon name.</p>';
    icons();
  }
  function setIconPreview(field, name) {
    const p = $('.icon-preview', field);
    p.innerHTML = `<i data-lucide="${esc(name || 'plus')}"></i>`;
    icons();
  }
  document.addEventListener('click', (e) => {
    const pick = e.target.closest('[data-pick-icon]');
    if (pick) { iconTarget = pick.closest('[data-icon-field]'); buildIcons(); $('[data-icon-search]').value = ''; openModal('iconModal'); return; }
    const opt = e.target.closest('[data-icon]');
    if (opt && iconTarget) {
      const input = $('input', iconTarget); input.value = opt.dataset.icon; setIconPreview(iconTarget, opt.dataset.icon);
      closeModal($('#iconModal'));
    }
  });
  const iconSearch = $('[data-icon-search]');
  if (iconSearch) iconSearch.addEventListener('input', (e) => buildIcons(e.target.value));
  $$('[data-icon-field] input').forEach((i) => i.addEventListener('change', () => setIconPreview(i.closest('[data-icon-field]'), i.value.trim())));

  /* ---------- Media picker ---------- */
  let mediaTarget = null, mediaSelected = null, mediaCache = [];
  const mm = $('#mediaModal');
  function mediaCard(m, picker) {
    return `<div class="media-card" data-media-id="${m.id}"><div class="media-img"><img loading="lazy" src="${esc(m.url)}" alt="${esc(m.alt)}"></div>
      <div class="media-info"><strong title="${esc(m.name)}">${esc(m.name)}</strong><small>${m.width ? m.width + '×' + m.height + ' · ' : ''}${esc(m.size_h)}</small></div></div>`;
  }
  async function loadMedia(q = '') {
    const grid = $('[data-media-grid]', mm);
    grid.innerHTML = '<p class="muted">Loading…</p>';
    try {
      const r = await api('ajax/media.php?action=list&q=' + encodeURIComponent(q), null, 'GET');
      mediaCache = r.items;
      grid.innerHTML = r.items.length ? r.items.map((m) => mediaCard(m, true)).join('') : '<div class="empty"><i data-lucide="image-off"></i><p>No images yet. Upload one!</p></div>';
      if (mediaSelected) { const c = $(`[data-media-id="${mediaSelected.id}"]`, grid); c && c.classList.add('selected'); }
      icons();
    } catch (err) { grid.innerHTML = `<div class="alert err">${esc(err.message)}</div>`; }
  }
  async function uploadFiles(files, onDone) {
    const list = Array.from(files).filter((f) => f.type.startsWith('image/') || /\.(avif|svg)$/i.test(f.name));
    if (!list.length) return toast('Please choose image files.', 'error');
    const fd = new FormData();
    list.forEach((f) => fd.append('files[]', f));
    try {
      const r = await api('ajax/media.php?action=upload', fd);
      r.errors.forEach((er) => toast(er, 'error'));
      if (r.items.length) toast(r.items.length + ' image(s) uploaded');
      onDone && onDone(r.items);
    } catch (err) { toast(err.message, 'error'); }
  }
  window.G2RuploadFiles = uploadFiles;
  if (mm) {
    document.addEventListener('click', (e) => {
      const pick = e.target.closest('[data-pick-media]');
      if (pick) {
        mediaTarget = pick.closest('[data-image-field]');
        const cur = $('input', mediaTarget).value;
        mediaSelected = cur ? { id: +cur } : null;
        $('[data-media-confirm]', mm).disabled = !mediaSelected;
        $('[data-media-search]', mm).value = '';
        openModal('mediaModal'); loadMedia(); return;
      }
      const clear = e.target.closest('[data-clear-media]');
      if (clear) {
        const f = clear.closest('[data-image-field]');
        $('input', f).value = ''; f.classList.remove('has-image');
        $('.image-thumb', f).innerHTML = '<span><i data-lucide="image-plus"></i>Choose image</span>';
        $('[data-image-name]', f).textContent = 'No image selected';
        $('[data-image-info]', f).textContent = 'JPG, PNG, WEBP, AVIF or SVG';
        clear.hidden = true; icons(); return;
      }
      const card = e.target.closest('#mediaModal .media-card');
      if (card) {
        $$('.media-card', mm).forEach((c) => c.classList.remove('selected'));
        card.classList.add('selected');
        mediaSelected = mediaCache.find((m) => m.id === +card.dataset.mediaId);
        $('[data-media-selected]', mm).textContent = 'Selected: ' + mediaSelected.name;
        $('[data-media-confirm]', mm).disabled = false;
      }
    });
    mm.addEventListener('dblclick', (e) => { if (e.target.closest('.media-card')) $('[data-media-confirm]', mm).click(); });
    $('[data-media-confirm]', mm).addEventListener('click', () => {
      if (!mediaTarget || !mediaSelected || !mediaSelected.url) return closeModal(mm);
      const f = mediaTarget, m = mediaSelected;
      $('input', f).value = m.id; f.classList.add('has-image');
      $('.image-thumb', f).innerHTML = `<img src="${esc(m.url)}" alt="">`;
      $('[data-image-name]', f).textContent = m.name;
      $('[data-image-info]', f).textContent = (m.width ? m.width + '×' + m.height + ' · ' : '') + m.size_h;
      $('[data-clear-media]', f).hidden = false;
      document.dispatchEvent(new CustomEvent('g2r:media-picked', { detail: f }));
      closeModal(mm);
    });
    let t;
    $('[data-media-search]', mm).addEventListener('input', (e) => { clearTimeout(t); t = setTimeout(() => loadMedia(e.target.value), 250); });
    $('[data-media-upload]', mm).addEventListener('change', (e) => uploadFiles(e.target.files, (items) => {
      e.target.value = '';
      if (items[0]) mediaSelected = items[0];
      $('[data-media-confirm]', mm).disabled = !mediaSelected;
      loadMedia();
    }));
    const body = $('.modal-body', mm), dz = $('[data-media-drop]', mm);
    ['dragenter', 'dragover'].forEach((ev) => body.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((ev) => body.addEventListener(ev, (e) => { e.preventDefault(); if (ev === 'drop' || !body.contains(e.relatedTarget)) dz.classList.remove('over'); }));
    body.addEventListener('drop', (e) => uploadFiles(e.dataTransfer.files, (items) => { if (items[0]) { mediaSelected = items[0]; $('[data-media-confirm]', mm).disabled = false; } loadMedia(); }));
  }

  /* ---------- Sortable lists (drag & drop) ---------- */
  $$('[data-sortable]').forEach((list) => {
    let dragged = null;
    list.addEventListener('dragstart', (e) => {
      const it = e.target.closest('.sort-item'); if (!it) return;
      dragged = it; it.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragend', async () => {
      if (!dragged) return;
      dragged.classList.remove('dragging'); dragged = null;
      const ids = $$('.sort-item', list).map((i) => i.dataset.id);
      $$('.order-num', list).forEach((n, i) => (n.textContent = i + 1));
      try { await api('ajax/reorder.php', { type: list.dataset.sortable, ids: ids.join(',') }); toast('Order saved'); }
      catch (err) { toast(err.message, 'error'); }
    });
    list.addEventListener('dragover', (e) => {
      e.preventDefault();
      if (!dragged) return;
      const after = $$('.sort-item:not(.dragging)', list).find((c) => e.clientY < c.getBoundingClientRect().top + c.offsetHeight / 2);
      after ? list.insertBefore(dragged, after) : list.appendChild(dragged);
    });
    // Only allow dragging from handle
    $$('.sort-item', list).forEach((it) => {
      const h = $('.drag-handle', it);
      if (h) { h.addEventListener('mousedown', () => (it.draggable = true)); it.addEventListener('dragend', () => (it.draggable = false)); }
    });
  });

  /* ---------- Toggle switches (ajax) ---------- */
  $$('[data-toggle]').forEach((cb) => cb.addEventListener('change', async () => {
    try {
      const r = await api('ajax/toggle.php', { type: cb.dataset.toggle, id: cb.dataset.id, value: cb.checked ? 1 : 0 });
      const item = cb.closest('.sort-item');
      if (item) item.classList.toggle('inactive', !cb.checked);
      const ic = item && $('.sec-icon', item); if (ic) ic.classList.toggle('off', !cb.checked);
      toast(r.message);
    } catch (err) { cb.checked = !cb.checked; toast(err.message, 'error'); }
  }));

  /* ---------- Password visibility ---------- */
  $$('[data-pw-toggle]').forEach((b) => b.addEventListener('click', () => {
    const i = b.previousElementSibling; i.type = i.type === 'password' ? 'text' : 'password';
    b.innerHTML = `<i data-lucide="${i.type === 'password' ? 'eye' : 'eye-off'}"></i>`; icons();
  }));

  /* ---------- Copy buttons ---------- */
  document.addEventListener('click', (e) => {
    const c = e.target.closest('[data-copy]');
    if (c) { navigator.clipboard.writeText(c.dataset.copy).then(() => toast('Copied to clipboard')); }
  });

  /* ---------- Unsaved changes warning + indicator ---------- */
  $$('form[data-watch]').forEach((f) => {
    let dirty = false;
    const bar = $('.form-actions', f);
    let note;
    if (bar) {
      note = document.createElement('span');
      note.className = 'unsaved-note'; note.hidden = true;
      note.innerHTML = '<i data-lucide="circle-dot"></i> You have unsaved changes';
      bar.prepend(note);
    }
    const mark = () => { if (!dirty) { dirty = true; if (note) { note.hidden = false; icons(); } } };
    f.addEventListener('input', mark);
    f.addEventListener('change', mark);
    f.addEventListener('submit', () => (dirty = false));
    // image picks don't fire input events
    f.addEventListener('click', (e) => { if (e.target.closest('[data-clear-media]')) mark(); });
    document.addEventListener('g2r:media-picked', (e) => { if (f.contains(e.detail)) mark(); });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  /* ---------- Ctrl/Cmd + S saves the main form ---------- */
  document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
      const f = $('form[data-watch]');
      if (f) { e.preventDefault(); const b = $('.form-actions button[type=submit]:last-child', f) || $('button[type=submit]', f); b ? b.click() : f.requestSubmit(); }
    }
  });

  /* ---------- Drag & drop an image straight onto an image field ---------- */
  $$('[data-image-field]').forEach((field) => {
    ['dragenter', 'dragover'].forEach((ev) => field.addEventListener(ev, (e) => { e.preventDefault(); field.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((ev) => field.addEventListener(ev, (e) => { e.preventDefault(); field.classList.remove('over'); }));
    field.addEventListener('drop', (e) => {
      if (!e.dataTransfer.files.length) return;
      uploadFiles([e.dataTransfer.files[0]], (items) => {
        const m = items[0]; if (!m) return;
        $('input', field).value = m.id; field.classList.add('has-image');
        $('.image-thumb', field).innerHTML = `<img src="${esc(m.url)}" alt="">`;
        $('[data-image-name]', field).textContent = m.name;
        $('[data-image-info]', field).textContent = (m.width ? m.width + '×' + m.height + ' · ' : '') + m.size_h;
        $('[data-clear-media]', field).hidden = false;
        document.dispatchEvent(new CustomEvent('g2r:media-picked', { detail: field }));
      });
    });
  });

  /* ---------- Character counters ---------- */
  $$('[data-count]').forEach((el) => {
    const max = +el.dataset.count;
    const c = document.createElement('small'); c.className = 'char-count';
    el.insertAdjacentElement('afterend', c);
    const up = () => { const n = el.value.length; c.textContent = n + ' / ' + max + ' characters'; c.classList.toggle('over', n > max); };
    el.addEventListener('input', up); up();
  });

  /* ---------- Quick filter lists ---------- */
  $$('[data-filter-list]').forEach((inp) => {
    const list = document.querySelector(inp.dataset.filterList);
    inp.addEventListener('input', () => {
      const q = inp.value.toLowerCase().trim();
      $$('.sort-item', list).forEach((it) => (it.hidden = q && !it.textContent.toLowerCase().includes(q)));
    });
  });

  /* ---------- Tooltips from title on icon buttons are native; add keyboard hint ---------- */
  $$('.form-actions button[type=submit]').forEach((b) => { if (!b.title) b.title = 'Save (Ctrl + S)'; });

  icons();
})();
