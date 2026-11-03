<div class="card-eco mb-4"><div class="card-body-pad">
  <div class="row g-2 align-items-end" id="dashFilters">
    <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Organização</label><select class="form-select form-select-sm" id="fOrg"><option value="">Todas</option></select></div>
    <div class="col-6 col-md-3"><label class="form-label small text-muted mb-1">Setor</label><select class="form-select form-select-sm" id="fSector"><option value="">Todos</option></select></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">De (mês)</label><input type="month" class="form-control form-control-sm" id="fFrom"></div>
    <div class="col-6 col-md-2"><label class="form-label small text-muted mb-1">Até (mês)</label><input type="month" class="form-control form-control-sm" id="fTo"></div>
    <div class="col-12 col-md-2 d-grid"><button class="btn btn-primary btn-sm" id="fApply"><i class="fa-solid fa-filter me-1"></i>Aplicar</button></div>
  </div>
</div></div>

<div class="row g-3 mb-4" id="cards">
  <div class="col-12 col-sm-6 col-xl"><div class="card-eco stat"><div class="icon icon-water"><i class="fa-solid fa-droplet"></i></div><div><div class="label">Consumo de água</div><div class="value" id="cWater">—</div><div class="sub" id="cWaterSub"></div></div></div></div>
  <div class="col-12 col-sm-6 col-xl"><div class="card-eco stat"><div class="icon icon-energy"><i class="fa-solid fa-bolt"></i></div><div><div class="label">Consumo de energia</div><div class="value" id="cEnergy">—</div><div class="sub" id="cEnergySub"></div></div></div></div>
  <div class="col-12 col-sm-6 col-xl"><div class="card-eco stat"><div class="icon icon-waste"><i class="fa-solid fa-trash-can"></i></div><div><div class="label">Resíduos gerados</div><div class="value" id="cWaste">—</div><div class="sub">no período</div></div></div></div>
  <div class="col-12 col-sm-6 col-xl"><div class="card-eco stat"><div class="icon icon-recycle"><i class="fa-solid fa-recycle"></i></div><div><div class="label">Taxa de reciclagem</div><div class="value" id="cRec">—</div><div class="sub" id="cRecSub"></div></div></div></div>
  <div class="col-12 col-sm-6 col-xl"><div class="card-eco stat"><div class="icon icon-goal"><i class="fa-solid fa-bullseye"></i></div><div><div class="label">Metas atingidas</div><div class="value" id="cGoals">—</div><div class="sub" id="cGoalsSub"></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-6"><div class="card-eco"><div class="card-head"><h2>Evolução mensal da água (m³)</h2></div><div class="card-body-pad"><div class="chart-box"><canvas id="chWater"></canvas></div></div></div></div>
  <div class="col-12 col-lg-6"><div class="card-eco"><div class="card-head"><h2>Evolução mensal da energia (kWh)</h2></div><div class="card-body-pad"><div class="chart-box"><canvas id="chEnergy"></canvas></div></div></div></div>
  <div class="col-12 col-lg-6"><div class="card-eco"><div class="card-head"><h2>Geração de resíduos por mês (kg)</h2></div><div class="card-body-pad"><div class="chart-box"><canvas id="chWaste"></canvas></div></div></div></div>
  <div class="col-12 col-lg-6"><div class="card-eco"><div class="card-head"><h2>Resíduos por destinação</h2></div><div class="card-body-pad"><div class="chart-box"><canvas id="chDest"></canvas></div></div></div></div>
  <div class="col-12"><div class="card-eco"><div class="card-head"><h2>Evolução do progresso das metas (média, %)</h2></div><div class="card-body-pad"><div class="chart-box"><canvas id="chGoals"></canvas></div></div></div></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const $ = (id) => document.getElementById(id);
  const charts = {};
  const palette = { water: '#1d77b5', energy: '#d99a1e', waste: '#6a4fb0', goals: '#1b6b4d' };
  const destColors = ['#2fa872', '#1d77b5', '#8a6d3b', '#7a8a84', '#c0452f', '#b8c4be'];

  // período padrão: últimos 12 meses
  const now = new Date(), start = new Date(now.getFullYear(), now.getMonth() - 11, 1);
  const ym = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
  $('fFrom').value = ym(start); $('fTo').value = ym(now);

  const orgs = (await Api.get('/organizations', { per_page: 100 })).data;
  $('fOrg').innerHTML += orgs.map((o) => `<option value="${UI.esc(o.id)}">${UI.esc(o.name)}</option>`).join('');
  async function loadSectors() {
    const j = await Api.get('/sectors', { per_page: 100, organization_id: $('fOrg').value || undefined });
    $('fSector').innerHTML = '<option value="">Todos</option>' + j.data.map((s) => `<option value="${UI.esc(s.id)}">${UI.esc(s.name)}</option>`).join('');
  }
  $('fOrg').addEventListener('change', () => { loadSectors(); load(); });
  $('fSector').addEventListener('change', load);
  $('fApply').addEventListener('click', load);

  const params = () => ({ organization_id: $('fOrg').value || undefined, sector_id: $('fSector').value || undefined, from: $('fFrom').value || undefined, to: $('fTo').value || undefined });

  function trend(v, goodWhenDown = true) {
    if (v === null || v === undefined) return '<span class="text-muted">sem base de comparação</span>';
    const good = goodWhenDown ? v <= 0 : v >= 0;
    return `<span class="${good ? 'trend-good' : 'trend-bad'}"><i class="fa-solid fa-arrow-${v >= 0 ? 'up' : 'down'}"></i> ${UI.nf(Math.abs(v), 1)}%</span> vs. mês anterior`;
  }

  function draw(id, cfg, hasData, msg) {
    charts[id]?.destroy();
    const canvas = $(id), box = canvas.parentElement;
    box.querySelector('.empty-state')?.remove();
    canvas.style.display = hasData ? '' : 'none';
    if (!hasData) { box.insertAdjacentHTML('beforeend', `<div class="empty-state"><i class="fa-regular fa-chart-bar"></i>${msg || 'Sem dados no período.'}</div>`); return; }
    charts[id] = new Chart(canvas, cfg);
  }
  const line = (labels, data, label, color, bar) => ({ type: bar ? 'bar' : 'line', data: { labels, datasets: [{ label, data, borderColor: color, backgroundColor: bar ? color : color + '26', fill: !bar, tension: .3, borderRadius: 4, pointRadius: 3 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });

  async function load() {
    try {
      const [s, c] = await Promise.all([Api.get('/dashboard/summary', params()), Api.get('/dashboard/charts', params())]);
      const d = s.data, ch = c.data;
      $('cWater').textContent = d.water.has_data ? UI.nf(d.water.total_m3, 1) + ' m³' : '—';
      $('cWaterSub').innerHTML = d.water.has_data ? trend(d.water.variation_pct) : 'Sem registros';
      $('cEnergy').textContent = d.energy.has_data ? UI.nf(d.energy.total_kwh, 1) + ' kWh' : '—';
      $('cEnergySub').innerHTML = d.energy.has_data ? trend(d.energy.variation_pct) : 'Sem registros';
      $('cWaste').textContent = d.waste.has_data ? UI.nf(d.waste.total_kg, 1) + ' kg' : '—';
      $('cRec').textContent = UI.pct(d.recycling.pct);
      $('cRecSub').textContent = d.waste.has_data ? UI.nf(d.recycling.recycled_kg, 1) + ' kg reciclados' : 'Sem registros';
      $('cGoals').textContent = d.goals.total ? `${d.goals.achieved} de ${d.goals.total}` : '—';
      $('cGoalsSub').textContent = d.goals.total ? `${d.goals.in_progress} em andamento · ${d.goals.late} atrasada(s)` : 'Nenhuma meta cadastrada';

      draw('chWater', line(ch.water_monthly.map((x) => UI.month(x.month + '-01')), ch.water_monthly.map((x) => x.total), 'm³', palette.water), ch.water_monthly.length);
      draw('chEnergy', line(ch.energy_monthly.map((x) => UI.month(x.month + '-01')), ch.energy_monthly.map((x) => x.total), 'kWh', palette.energy), ch.energy_monthly.length);
      draw('chWaste', line(ch.waste_monthly.map((x) => UI.month(x.month + '-01')), ch.waste_monthly.map((x) => x.total), 'kg', palette.waste, true), ch.waste_monthly.length);
      const dest = ch.waste_by_destination.filter((x) => x.kg > 0);
      draw('chDest', { type: 'doughnut', data: { labels: dest.map((x) => x.name), datasets: [{ data: dest.map((x) => x.kg), backgroundColor: destColors }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: (i) => ` ${i.label}: ${UI.nf(i.parsed, 1)} kg` } } } } }, dest.length);
      draw('chGoals', { ...line(ch.goals_evolution.map((x) => UI.month(x.month + '-01')), ch.goals_evolution.map((x) => x.average_progress_pct), 'Progresso médio (%)', palette.goals),
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } } }, ch.goals_evolution.length, 'Cadastre metas para acompanhar a evolução.');
    } catch (e) { UI.showError(e); }
  }

  await loadSectors();
  load();
});
</script>
