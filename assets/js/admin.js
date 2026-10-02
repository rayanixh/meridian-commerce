/* admin.js — uploader, variant editor, multi-image, sidebar */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  /* Sidebar mobile drawer */
  const toggle = $('#adminMenuToggle');
  const sidebar = $('#adminSidebar');
  const overlay = $('#adminSidebarOverlay');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay && overlay.classList.toggle('open');
    });
    overlay && overlay.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
    });
  }

  /* Uploader (single image) */
  function bindUploader(uploader) {
    const file = uploader.querySelector('input[type=file]');
    const preview = uploader.querySelector('img');
    const hidden = uploader.parentElement.querySelector('input[type=hidden]');
    if (!file) return;
    uploader.addEventListener('click', (e) => {
      if (e.target.closest('button.remove, .remove')) return;
      file.click();
    });
    file.addEventListener('change', () => {
      if (!file.files[0]) return;
      const fd = new FormData();
      fd.append('file', file.files[0]);
      const field = uploader.dataset.field || 'image';
      const url = MERIDIAN.base + '/api/upload.php?field=' + encodeURIComponent(field);
      const old = preview ? preview.src : null;
      file.disabled = true;
      fetch(url, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
          file.disabled = false;
          if (!d.ok) { alert(d.error || 'Upload failed'); return; }
          if (preview) { preview.src = d.url; }
          else {
            const empty = uploader.querySelector('.uploader-empty');
            if (empty) empty.remove();
            const img = document.createElement('img');
            img.className = 'uploader-preview';
            img.src = d.url;
            uploader.appendChild(img);
          }
          if (hidden) hidden.value = d.path;
          uploader.classList.add('has-image');
        })
        .catch(() => { file.disabled = false; alert('Network error'); });
    });
  }
  $$('.uploader').forEach(bindUploader);

  /* Multi-image uploader */
  const multi = $('[data-multi-uploader]');
  if (multi) {
    const field = multi.dataset.field || 'gallery';
    const input = multi.querySelector('input[type=file]');
    const grid = multi.querySelector('.thumbs-grid');
    const hidden = multi.querySelector('input[type=hidden]');
    let items = [];
    try { items = JSON.parse(hidden.value || '[]'); } catch (e) {}
    function normalize(arr) {
      return arr.map(x => (typeof x === 'string' && x.startsWith('http')) ? x : (x && x.path ? APP_URL_BASE + '/' + x.path : x));
    }
    function render() {
      grid.innerHTML = items.map((url, i) => (
        '<div class="thumb-item">' +
        '<img src="' + url + '" alt="">' +
        '<button type="button" class="remove" data-idx="' + i + '">×</button>' +
        '</div>'
      )).join('');
      hidden.value = JSON.stringify(items);
      grid.querySelectorAll('.remove').forEach(b => {
        b.addEventListener('click', () => { items.splice(parseInt(b.dataset.idx, 10), 1); render(); });
      });
    }
    render();
    input.addEventListener('change', () => {
      const files = Array.from(input.files);
      let done = 0;
      files.forEach(f => {
        const fd = new FormData();
        fd.append('file', f);
        fetch(MERIDIAN.base + '/api/upload.php?field=' + encodeURIComponent(field), {
          method: 'POST', body: fd,
          headers: { 'X-CSRF-Token': MERIDIAN.csrf, 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => { if (d.ok) items.push(d.url); })
        .finally(() => { done++; if (done === files.length) render(); });
      });
    });
  }

  /* Variant editor */
  const variantEditor = $('[data-variant-editor]');
  if (variantEditor) {
    const list = variantEditor.querySelector('[data-variant-list]');
    const hidden = variantEditor.querySelector('input[type=hidden]');
    const addBtn = variantEditor.querySelector('[data-add-variant]');
    let groups = [];
    try { groups = JSON.parse(hidden.value || '[]'); } catch (e) {}
    function render() {
      list.innerHTML = groups.map((g, i) => (
        '<div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap">' +
        '<input type="text" placeholder="Type (e.g. Color)" value="' + (g.name || '') + '" data-i="' + i + '" data-k="name" style="flex:0 0 140px;padding:10px 12px;border:1px solid var(--c-border);border-radius:8px;font-size:13px;min-width:0">' +
        '<input type="text" placeholder="Values (comma separated)" value="' + (g.values || []).join(', ') + '" data-i="' + i + '" data-k="values" style="flex:1;padding:10px 12px;border:1px solid var(--c-border);border-radius:8px;font-size:13px;min-width:0">' +
        '<button type="button" data-remove="' + i + '" class="btn btn-ghost" style="padding:10px 12px;min-height:auto;min-width:auto">×</button>' +
        '</div>'
      )).join('');
      hidden.value = JSON.stringify(groups);
      list.querySelectorAll('input').forEach(inp => {
        inp.addEventListener('input', () => {
          const i = parseInt(inp.dataset.i, 10);
          if (inp.dataset.k === 'name') groups[i].name = inp.value;
          else groups[i].values = inp.value.split(',').map(s => s.trim()).filter(Boolean);
          hidden.value = JSON.stringify(groups);
        });
      });
      list.querySelectorAll('[data-remove]').forEach(b => {
        b.addEventListener('click', () => { groups.splice(parseInt(b.dataset.remove, 10), 1); render(); });
      });
    }
    addBtn.addEventListener('click', () => { groups.push({ name: '', values: [] }); render(); });
    render();
  }

  /* Toast */
  window.toast = window.toast || function (m) { alert(m); };

  /* Confirm */
  document.addEventListener('submit', (e) => { if (e.target.dataset.confirm && !confirm(e.target.dataset.confirm)) e.preventDefault(); });
  document.addEventListener('click', (e) => { const a = e.target.closest('a[data-confirm], button[data-confirm]'); if (a && !confirm(a.dataset.confirm)) e.preventDefault(); });

  /* Auto-save indicator */
  $$('form[data-autosave]').forEach(f => f.addEventListener('submit', () => {
    const btn = f.querySelector('[type=submit]');
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
  }));

  /* Color picker sync */
  $$('[data-color-pick]').forEach(c => {
    c.addEventListener('input', () => { c.parentElement.querySelector('input[type=text]').value = c.value; });
  });
})();
