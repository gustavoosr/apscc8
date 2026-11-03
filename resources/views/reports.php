<div class="card-eco mb-4"><div class="card-body-pad">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-3"><label class="form-label small text-muted mb-1">Relatório</label>
      <select class="form-select form-select-sm" id="rType"><option value="consumption-water">Consumo de água</option><option value="consumption-energy">Consumo de energia</option><option value="waste">Resíduos</option><option value="goals">Metas ambientais</option></select></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">Organização</label><select class="form-select form-select-sm" id="rOrg"><option value="">Todas</option></select></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">Setor</label><select class="form-select form-select-sm" id="rSector"><option value="">Todos</option></select></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">De (mês)</label><input type="month" class="form-control form-control-sm" id="rFrom"></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">Até (mês)</label><input type="month" class="form-control form-control-sm" id="rTo"></div>
  </div>
  <div class="mt-3 d-flex gap-2 flex-wrap">
    <button class="btn btn-primary btn-sm" id="rRun"><i class="fa-solid fa-play me-1"></i>Gerar</button>
    <button class="btn btn-outline-primary btn-sm" id="rCsv" disabled><i class="fa-solid fa-file-csv me-1"></i>Exportar CSV</button>
    <button class="btn btn-outline-primary btn-sm" id="rPrint" disabled><i class="fa-solid fa-print me-1"></i>Imprimir / PDF</button>
  </div>
</div></div>
<div class="card-eco" id="rResult"><div class="empty-state"><i class="fa-regular fa-file-lines"></i>Selecione os filtros e clique em <strong>Gerar</strong>.</div></div>
<style>@media print { .sidebar, .topbar, .disclaimer, #rFilters, .card-eco:first-of-type, .no-print { display: none !important; } .main { margin: 0; } .content { padding: 0; } }</style>
<script>
document.addEventListener('DOMContentLoaded', async () => {
  const $ = (id) => document.getElementById(id);
  const orgs = (await Api.get('/organizations', { per_page: 100 })).data;
  $('rOrg').innerHTML += orgs.map((o) => `<option value="${UI.esc(o.id)}">${UI.esc(o.name)}</option>`).join('');
  async function loadSectors() {
    const j = await Api.get('/sectors', { per_page: 100, organization_id: $('rOrg').value || undefined });
    $('rSector').innerHTML = '<option value="">Todos</option>' + j.data.map((s) => `<option value="${UI.esc(s.id)}">${UI.esc(s.name)}</option>`).join('');
  }
  $('rOrg').addEventListener('change', loadSectors); loadSectors();

  function query() {
    const [kind, sub] = $('rType').value.split('-');
    const path = '/reports/' + kind;
    return { path, params: { type: sub, organization_id: $('rOrg').value || undefined, sector_id: $('rSector').value || undefined, from: $('rFrom').value || undefined, to: $('rTo').value || undefined } };
  }

  $('rRun').addEventListener('click', async () => {
    const { path, params } = query();
    try {
      const j = await Api.get(path, params);
      const rows = j.data;
      $('rCsv').disabled = $('rPrint').disabled = !rows.length;
      if (!rows.length) { $('rResult').innerHTML = '<div class="empty-state"><i class="fa-regular fa-folder-open"></i>Nenhum dado para os filtros informados.</div>'; return; }
      const cols = Object.keys(rows[0]);
      const title = $('rType').selectedOptions[0].text;
      $('rResult').innerHTML = `<div class="card-head"><h2>${UI.esc(title)}</h2><small class="text-muted">${rows.length} linha(s) · gerado em ${new Date().toLocaleString('pt-BR')}</small></div>
        <div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr>${cols.map((c) => `<th>${UI.esc(c)}</th>`).join('')}</tr></thead><tbody>${
        rows.map((r) => '<tr>' + cols.map((c) => `<td>${typeof r[c] === 'number' ? UI.nf(r[c], 3) : UI.esc(r[c] ?? '—')}</td>`).join('') + '</tr>').join('')}</tbody></table></div>`;
    } catch (e) { UI.showError(e); }
  });

  $('rCsv').addEventListener('click', () => {
    const { path, params } = query();
    location.href = '/api/v1' + path + Api.qs({ ...params, format: 'csv' });
  });
  $('rPrint').addEventListener('click', () => window.print());
});
</script>
