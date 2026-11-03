/* Utilidades de interface: escape HTML, formatação, toasts e confirmação. */
const UI = (() => {
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const nf = (v, d = 2) => (v === null || v === undefined || v === '') ? '—' : Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: d });
  const pct = (v) => (v === null || v === undefined) ? '—' : nf(v, 1) + '%';
  const date = (s) => s ? s.slice(0, 10).split('-').reverse().join('/') : '—';
  const month = (s) => s ? s.slice(5, 7) + '/' + s.slice(0, 4) : '—';

  function toast(message, type = 'success') {
    let box = document.getElementById('toasts');
    if (!box) { box = document.createElement('div'); box.id = 'toasts'; box.className = 'toast-container position-fixed top-0 end-0 p-3'; document.body.appendChild(box); }
    const icon = { success: 'fa-circle-check', danger: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation' }[type] || 'fa-circle-info';
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body"><i class="fa-solid ${icon} me-2"></i>${esc(message)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    box.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4500 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
  }

  function confirmDialog(message, okLabel = 'Excluir') {
    return new Promise((resolve) => {
      const wrap = document.createElement('div');
      wrap.className = 'modal fade';
      wrap.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
        <div class="modal-body text-center p-4"><i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-3"></i><p class="mb-0">${esc(message)}</p></div>
        <div class="modal-footer justify-content-center border-0 pt-0"><button class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger" id="ok">${esc(okLabel)}</button></div></div></div>`;
      document.body.appendChild(wrap);
      const m = new bootstrap.Modal(wrap);
      let answer = false;
      wrap.querySelector('#ok').addEventListener('click', () => { answer = true; m.hide(); });
      wrap.addEventListener('hidden.bs.modal', () => { wrap.remove(); resolve(answer); });
      m.show();
    });
  }

  const statusBadge = (s) => {
    const map = { ATINGIDA: ['badge-ok', 'Atingida'], EM_ANDAMENTO: ['badge-warn', 'Em andamento'], ATRASADA: ['badge-bad', 'Atrasada'], ATIVO: ['badge-ok', 'Ativo'], ENCERRADO: ['badge-neutral', 'Encerrado'] };
    const [c, l] = map[s] || ['badge-neutral', s];
    return `<span class="badge-soft ${c}">${l}</span>`;
  };
  const activeBadge = (a) => Number(a) ? '<span class="badge-soft badge-ok">Ativo</span>' : '<span class="badge-soft badge-neutral">Inativo</span>';

  /** Mensagem de erro de API → toast; erros de campo são anexados ao texto. */
  function showError(e) {
    let msg = e.message || 'Erro inesperado.';
    if (e.errors && e.errors.length && e.errors[0].message) msg += ' ' + e.errors.map((x) => (x.field ? x.field + ': ' : '') + x.message).join(' | ');
    toast(msg, 'danger');
  }

  return { esc, nf, pct, date, month, toast, confirmDialog, statusBadge, activeBadge, showError };
})();

document.addEventListener('DOMContentLoaded', () => {
  const sb = document.getElementById('sidebar');
  const bd = document.getElementById('sidebarBackdrop');
  const toggle = () => { sb?.classList.toggle('open'); bd?.classList.toggle('show'); };
  document.getElementById('menuToggle')?.addEventListener('click', toggle);
  bd?.addEventListener('click', toggle);
  document.getElementById('logoutBtn')?.addEventListener('click', async () => {
    try { await Api.post('/auth/logout'); } catch (_) { /* segue para o login */ }
    location.href = '/login';
  });
});
