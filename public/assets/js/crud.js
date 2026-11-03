/*
 * Motor genérico de telas CRUD sobre a API REST.
 * Configuração: { endpoint, title, columns, fields, filters, canWrite, canDelete, sort }
 */
const Crud = (() => {
  const cache = {};

  async function fetchOptions(src, ctx = {}) {
    let url = src.url.replace(/\{(\w+)\}/g, (_, k) => encodeURIComponent(ctx[k] ?? ''));
    if (/\{/.test(src.url) && /=\s*(&|$)/.test(url)) return []; // dependência ainda sem valor
    if (!cache[url]) {
      const j = await Api.get(url.replace(/^\/api\/v1/, ''));
      cache[url] = j.data;
    }
    return cache[url].map((o) => ({ value: o[src.value || 'id'], label: typeof src.label === 'function' ? src.label(o) : o[src.label || 'name'] }));
  }

  function init(cfg) {
    const state = { page: 1, perPage: 10, sort: cfg.sort || '', filters: {}, rows: [], editing: null };
    const root = cfg.root ? document.querySelector(cfg.root) : document.getElementById('crud');
    const uid = Math.random().toString(36).slice(2, 6);
    root.innerHTML = `
      <div class="card-eco">
        <div class="card-head">
          <h2><i class="${UI.esc(cfg.icon || 'fa-solid fa-list')} me-2 text-success"></i>${UI.esc(cfg.title)}</h2>
          ${cfg.canWrite !== false ? `<button class="btn btn-primary btn-sm" id="btnNew"><i class="fa-solid fa-plus me-1"></i>${UI.esc(cfg.newLabel || 'Novo')}</button>` : ''}
        </div>
        <div class="card-body-pad border-bottom" id="filters"></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead></thead><tbody></tbody></table></div>
        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-2">
          <small class="text-muted" id="info"></small><nav><ul class="pagination pagination-sm mb-0" id="pager"></ul></nav>
        </div>
      </div>
      <div class="modal fade" id="formModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
        <form id="form" novalidate>
          <div class="modal-header"><h5 class="modal-title" id="modalTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><div class="row g-3" id="fields"></div></div>
          <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="btnSave"><i class="fa-solid fa-check me-1"></i>Salvar</button></div>
        </form></div></div></div>`;

    const modal = new bootstrap.Modal(root.querySelector('#formModal'));
    const form = root.querySelector('#form');
    const thead = root.querySelector('thead');
    const tbody = root.querySelector('tbody');

    // ---- cabeçalho
    thead.innerHTML = '<tr>' + cfg.columns.map((c) => `<th class="${c.sort ? 'sortable' : ''}" data-sort="${c.sort || ''}">${UI.esc(c.label)} <i class="fa-solid fa-sort fa-xs opacity-25"></i></th>`).join('') + '<th class="text-end">Ações</th></tr>';
    thead.addEventListener('click', (e) => {
      const th = e.target.closest('th.sortable'); if (!th) return;
      const f = th.dataset.sort;
      state.sort = state.sort === f ? '-' + f : f;
      load();
    });

    // ---- filtros
    const filtersBox = root.querySelector('#filters');
    (async () => {
      if (!cfg.filters || !cfg.filters.length) { filtersBox.remove(); return; }
      let html = '<div class="row g-2 align-items-end">';
      for (const f of cfg.filters) {
        html += `<div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">${UI.esc(f.label)}</label>`;
        if (f.type === 'select') {
          const opts = f.options || await fetchOptions(f.source);
          html += `<select class="form-select form-select-sm" data-filter="${f.name}"><option value="">Todos</option>${opts.map((o) => `<option value="${UI.esc(o.value)}">${UI.esc(o.label)}</option>`).join('')}</select>`;
        } else {
          html += `<input class="form-control form-control-sm" data-filter="${f.name}" type="${f.type || 'text'}" placeholder="${UI.esc(f.placeholder || '')}">`;
        }
        html += '</div>';
      }
      html += '<div class="col-auto"><button class="btn btn-sm btn-outline-secondary" id="clearFilters">Limpar</button></div></div>';
      filtersBox.innerHTML = html;
      let t;
      filtersBox.addEventListener('input', (e) => {
        const n = e.target.dataset.filter; if (!n) return;
        state.filters[n] = e.target.value; state.page = 1;
        clearTimeout(t); t = setTimeout(load, 300);
      });
      filtersBox.querySelector('#clearFilters').addEventListener('click', () => {
        state.filters = {}; filtersBox.querySelectorAll('[data-filter]').forEach((i) => { i.value = ''; }); state.page = 1; load();
      });
    })();

    // ---- listagem
    async function load() {
      tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}" class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div></td></tr>`;
      try {
        const j = await Api.get(cfg.endpoint, { page: state.page, per_page: state.perPage, sort: state.sort || undefined, ...state.filters, ...(cfg.baseParams || {}) });
        state.rows = j.data;
        render(j.meta);
      } catch (e) { tbody.innerHTML = ''; UI.showError(e); }
    }

    function render(meta) {
      if (!state.rows.length) {
        tbody.innerHTML = `<tr><td colspan="${cfg.columns.length + 1}"><div class="empty-state"><i class="fa-regular fa-folder-open"></i>Nenhum registro encontrado.</div></td></tr>`;
      } else {
        tbody.innerHTML = state.rows.map((r) => '<tr>' + cfg.columns.map((c) => `<td>${c.render ? c.render(r) : UI.esc(r[c.key])}</td>`).join('') +
          `<td class="text-end text-nowrap">${actions(r)}</td></tr>`).join('');
      }
      root.querySelector('#info').textContent = meta ? `${meta.total} registro(s) · página ${meta.page} de ${meta.total_pages}` : '';
      const pager = root.querySelector('#pager');
      pager.innerHTML = '';
      if (meta && meta.total_pages > 1) {
        const add = (label, p, dis, act) => { pager.insertAdjacentHTML('beforeend', `<li class="page-item ${dis ? 'disabled' : ''} ${act ? 'active' : ''}"><a class="page-link" href="#" data-page="${p}">${label}</a></li>`); };
        add('&laquo;', meta.page - 1, meta.page === 1);
        const start = Math.max(1, meta.page - 2), end = Math.min(meta.total_pages, meta.page + 2);
        for (let p = start; p <= end; p++) add(p, p, false, p === meta.page);
        add('&raquo;', meta.page + 1, meta.page === meta.total_pages);
      }
    }

    const canEditRow = (r) => (cfg.canWrite !== false) && (!cfg.canEditRow || cfg.canEditRow(r));
    const actions = (r) => {
      let h = (cfg.rowActions || []).map((a) => `<button class="btn btn-sm btn-light" title="${UI.esc(a.title)}" data-act="${a.name}" data-id="${r.id}"><i class="${a.icon}"></i></button> `).join('');
      if (canEditRow(r)) h += `<button class="btn btn-sm btn-light" title="Editar" data-act="edit" data-id="${r.id}"><i class="fa-solid fa-pen"></i></button> `;
      if (cfg.canDelete !== false) h += `<button class="btn btn-sm btn-light text-danger" title="Excluir" data-act="del" data-id="${r.id}"><i class="fa-solid fa-trash"></i></button>`;
      return h;
    };

    root.addEventListener('click', async (e) => {
      const pg = e.target.closest('[data-page]');
      if (pg) { e.preventDefault(); state.page = Number(pg.dataset.page); load(); return; }
      const btn = e.target.closest('[data-act]'); if (!btn) return;
      const row = state.rows.find((r) => String(r.id) === btn.dataset.id);
      if (btn.dataset.act === 'edit') openForm(row);
      else if (btn.dataset.act === 'del') remove(row);
      else { const a = cfg.rowActions.find((x) => x.name === btn.dataset.act); a.handler(row, { reload: load }); }
    });
    root.querySelector('#btnNew')?.addEventListener('click', () => openForm(null));

    // ---- formulário
    async function openForm(row) {
      state.editing = row;
      root.querySelector('#modalTitle').textContent = row ? 'Editar ' + (cfg.singular || 'registro') : 'Novo ' + (cfg.singular || 'registro');
      const box = root.querySelector('#fields');
      box.innerHTML = '';
      for (const f of cfg.fields) {
        if (row && f.createOnly) continue;
        const col = f.col || 6;
        const id = `f_${uid}_${f.name}`;
        let input;
        if (f.type === 'select') input = `<select class="form-select" id="${id}" name="${f.name}"></select>`;
        else if (f.type === 'textarea') input = `<textarea class="form-control" id="${id}" name="${f.name}" rows="2" maxlength="${f.max || 500}"></textarea>`;
        else if (f.type === 'checkbox') input = `<div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="${id}" name="${f.name}"><label class="form-check-label" for="${id}">${UI.esc(f.checkLabel || 'Ativo')}</label></div>`;
        else if (f.type === 'multiselect') input = `<select class="form-select" id="${id}" name="${f.name}" multiple size="4"></select>`;
        else input = `<input class="form-control" id="${id}" name="${f.name}" type="${f.type || 'text'}" ${f.step ? `step="${f.step}"` : ''} ${f.min !== undefined ? `min="${f.min}"` : ''} ${f.max && f.type !== 'textarea' ? `maxlength="${f.max}"` : ''} ${f.autocomplete ? `autocomplete="${f.autocomplete}"` : ''}>`;
        box.insertAdjacentHTML('beforeend', `<div class="col-12 col-md-${col}" data-field="${f.name}">${f.type === 'checkbox' ? '' : `<label class="form-label" for="${id}">${UI.esc(f.label)}${f.required && !(row && f.optionalOnEdit) ? ' <span class="text-danger">*</span>' : ''}</label>`}${input}<div class="invalid-feedback"></div>${f.help ? `<div class="form-text">${UI.esc(f.help)}</div>` : ''}</div>`);
      }
      // selects dinâmicos
      const ctx = () => Object.fromEntries(cfg.fields.map((f) => [f.name, form.elements[f.name]?.value ?? state.editing?.[f.name]]));
      const fillSelect = async (f, keep) => {
        const sel = form.elements[f.name]; if (!sel) return;
        const opts = f.options || await fetchOptions(f.source, ctx());
        const cur = keep ?? sel.value;
        sel.innerHTML = (f.type === 'multiselect' ? '' : `<option value="">${f.required ? 'Selecione…' : '—'}</option>`) + opts.map((o) => `<option value="${UI.esc(o.value)}">${UI.esc(o.label)}</option>`).join('');
        if (f.type === 'multiselect') { (Array.isArray(cur) ? cur : []).forEach((v) => { [...sel.options].forEach((o) => { if (String(o.value) === String(v)) o.selected = true; }); }); }
        else sel.value = cur ?? '';
      };
      const selects = cfg.fields.filter((f) => (f.type === 'select' || f.type === 'multiselect') && !(row && f.createOnly));
      for (const f of selects) {
        await fillSelect(f, row ? (f.get ? f.get(row) : row[f.name]) : (f.default ?? (f.autoSingle ? undefined : '')));
        if (f.autoSingle && !row) { const s = form.elements[f.name]; if (s.options.length === 2) s.selectedIndex = 1; }
        if (f.dependsOn) {
          form.elements[f.dependsOn]?.addEventListener('change', () => { fillSelect(f, ''); });
        }
      }
      // dependentes precisam recarregar após autoSingle ter escolhido o pai
      for (const f of selects.filter((x) => x.dependsOn)) await fillSelect(f, row ? (f.get ? f.get(row) : row[f.name]) : '');

      for (const f of cfg.fields) {
        if (f.type === 'select' || f.type === 'multiselect' || (row && f.createOnly)) continue;
        const el = form.elements[f.name]; if (!el) continue;
        const val = row ? (f.get ? f.get(row) : row[f.name]) : f.default;
        if (f.type === 'checkbox') el.checked = row ? Boolean(Number(row[f.name])) : (f.default ?? true);
        else el.value = val ?? '';
      }
      form.classList.remove('was-validated');
      modal.show();
    }

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      form.querySelectorAll('.is-invalid').forEach((i) => i.classList.remove('is-invalid'));
      const body = {};
      for (const f of cfg.fields) {
        if (state.editing && f.createOnly) continue;
        const el = form.elements[f.name]; if (!el) continue;
        if (f.type === 'checkbox') body[f.name] = el.checked ? 1 : 0;
        else if (f.type === 'multiselect') body[f.name] = [...el.selectedOptions].map((o) => Number(o.value));
        else if (el.value === '') { if (state.editing && !f.required) body[f.name] = null; else if (f.required && !(state.editing && f.optionalOnEdit)) body[f.name] = ''; }
        else body[f.name] = el.value;
      }
      const btn = root.querySelector('#btnSave'); btn.disabled = true;
      try {
        const j = state.editing ? await Api.put(`${cfg.endpoint}/${state.editing.id}`, body) : await Api.post(cfg.endpoint, body);
        modal.hide(); UI.toast(j.message); Object.keys(cache).forEach((k) => delete cache[k]);
        load();
      } catch (err) {
        if (err.errors && err.errors.length) {
          err.errors.forEach((x) => {
            const w = form.querySelector(`[data-field="${x.field}"]`);
            if (w) { w.querySelector('.form-control, .form-select')?.classList.add('is-invalid'); w.querySelector('.invalid-feedback').textContent = x.message; }
          });
        }
        UI.toast(err.message, 'danger');
      } finally { btn.disabled = false; }
    });

    async function remove(row) {
      if (!await UI.confirmDialog(cfg.confirmDelete || 'Deseja realmente excluir este registro? Esta ação não pode ser desfeita.')) return;
      try { await Api.del(`${cfg.endpoint}/${row.id}`); UI.toast('Registro excluído com sucesso.'); if (state.rows.length === 1 && state.page > 1) state.page--; load(); }
      catch (e) { UI.showError(e); }
    }

    load();
    return { reload: load };
  }

  return { init, fetchOptions };
})();
