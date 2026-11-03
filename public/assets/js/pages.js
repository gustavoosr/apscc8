/* Configuração das telas CRUD. Cada função monta uma tela usando o motor Crud. */
const Pages = (() => {
  const isManager = ['ADMINISTRADOR', 'GESTOR'].includes(APP.role);
  const isAdmin = APP.role === 'ADMINISTRADOR';
  const own = (r) => isManager || Number(r.created_by) === APP.userId;

  const orgField = { name: 'organization_id', label: 'Organização', type: 'select', required: true, createOnly: true, autoSingle: true, source: { url: '/organizations?per_page=100', label: 'name' } };
  const sectorField = (extra = {}) => ({ name: 'sector_id', label: 'Setor', type: 'select', required: true, dependsOn: 'organization_id', source: { url: '/sectors?active=1&per_page=100&organization_id={organization_id}', label: 'name' }, ...extra });
  const orgFilter = { name: 'organization_id', label: 'Organização', type: 'select', source: { url: '/organizations?per_page=100', label: 'name' } };
  const sectorFilter = { name: 'sector_id', label: 'Setor', type: 'select', source: { url: '/sectors?per_page=100', label: 'name' } };
  const monthFilters = [{ name: 'from', label: 'De (mês)', type: 'month' }, { name: 'to', label: 'Até (mês)', type: 'month' }];
  const today = () => new Date().toISOString().slice(0, 10);
  const thisMonth = () => new Date().toISOString().slice(0, 7);

  function organizations() {
    Crud.init({
      title: 'Organizações', singular: 'organização', icon: 'fa-solid fa-building', endpoint: '/organizations', sort: 'name',
      canWrite: isManager, newLabel: 'Nova organização', canDelete: isAdmin,
      confirmDelete: 'Excluir esta organização? Só é possível se ela não tiver setores.',
      filters: [{ name: 'search', label: 'Buscar', placeholder: 'Nome ou identificação' }],
      columns: [
        { label: 'Nome', key: 'name', sort: 'name', render: (r) => `<strong>${UI.esc(r.name)}</strong>` },
        { label: 'Identificação', key: 'legal_id' }, { label: 'E-mail', key: 'email' }, { label: 'Telefone', key: 'phone' },
        { label: 'Situação', render: (r) => UI.activeBadge(r.active) },
      ],
      fields: [
        { name: 'name', label: 'Nome', required: true, col: 8, max: 150 },
        { name: 'legal_id', label: 'CNPJ / identificação', col: 4, max: 30 },
        { name: 'email', label: 'E-mail de contato', type: 'email', col: 6, max: 150 },
        { name: 'phone', label: 'Telefone', col: 6, max: 30 },
        { name: 'active', label: '', type: 'checkbox', col: 12 },
      ],
    });
  }

  function sectors() {
    Crud.init({
      title: 'Setores', singular: 'setor', icon: 'fa-solid fa-sitemap', endpoint: '/sectors', sort: 'name',
      canWrite: isManager, canDelete: isManager, newLabel: 'Novo setor',
      confirmDelete: 'Excluir este setor? Setores com registros só podem ser desativados.',
      filters: [orgFilter, { name: 'search', label: 'Buscar', placeholder: 'Nome do setor' }, { name: 'active', label: 'Situação', type: 'select', options: [{ value: 1, label: 'Ativos' }, { value: 0, label: 'Inativos' }] }],
      columns: [
        { label: 'Setor', key: 'name', sort: 'name', render: (r) => `<strong>${UI.esc(r.name)}</strong>` },
        { label: 'Organização', key: 'organization_name' }, { label: 'Descrição', key: 'description' },
        { label: 'Situação', render: (r) => UI.activeBadge(r.active) },
      ],
      fields: [
        orgField,
        { name: 'name', label: 'Nome do setor', required: true, max: 120 },
        { name: 'description', label: 'Descrição', col: 12, max: 255 },
        { name: 'active', label: '', type: 'checkbox', col: 12 },
      ],
    });
  }

  function water() {
    Crud.init({
      title: 'Consumo de água', singular: 'registro de água', icon: 'fa-solid fa-droplet', endpoint: '/water-consumption', sort: '-reference_month',
      canWrite: true, canDelete: isManager, canEditRow: own, newLabel: 'Novo registro',
      filters: [orgFilter, sectorFilter, ...monthFilters],
      columns: [
        { label: 'Mês', sort: 'reference_month', render: (r) => UI.month(r.reference_month) },
        { label: 'Setor', key: 'sector_name', sort: 'sector' },
        { label: 'Consumo (m³)', sort: 'quantity_m3', render: (r) => `<strong>${UI.nf(r.quantity_m3, 3)}</strong>` },
        { label: 'Informado', render: (r) => `${UI.nf(r.original_quantity, 3)} ${r.original_unit === 'L' ? 'L' : 'm³'}` },
        { label: 'Observações', key: 'notes' },
      ],
      fields: [
        orgField, sectorField(),
        { name: 'reference_month', label: 'Mês de referência', type: 'month', required: true, default: thisMonth(), get: (r) => r.reference_month.slice(0, 7) },
        { name: 'quantity', label: 'Quantidade', type: 'number', step: '0.001', min: 0, required: true, get: (r) => r.original_quantity },
        { name: 'unit', label: 'Unidade', type: 'select', required: true, options: [{ value: 'm3', label: 'Metros cúbicos (m³)' }, { value: 'L', label: 'Litros (L)' }], default: 'm3', get: (r) => r.original_unit },
        { name: 'notes', label: 'Observações', type: 'textarea', col: 12 },
      ].map((f) => f.name === 'unit' ? { ...f, default: 'm3' } : f),
    });
  }

  function energy() {
    Crud.init({
      title: 'Consumo de energia', singular: 'registro de energia', icon: 'fa-solid fa-bolt', endpoint: '/energy-consumption', sort: '-reference_month',
      canWrite: true, canDelete: isManager, canEditRow: own, newLabel: 'Novo registro',
      filters: [orgFilter, sectorFilter, ...monthFilters],
      columns: [
        { label: 'Mês', sort: 'reference_month', render: (r) => UI.month(r.reference_month) },
        { label: 'Setor', key: 'sector_name', sort: 'sector' },
        { label: 'Consumo (kWh)', sort: 'quantity_kwh', render: (r) => `<strong>${UI.nf(r.quantity_kwh, 3)}</strong>` },
        { label: 'Observações', key: 'notes' },
      ],
      fields: [
        orgField, sectorField(),
        { name: 'reference_month', label: 'Mês de referência', type: 'month', required: true, default: thisMonth(), get: (r) => r.reference_month.slice(0, 7) },
        { name: 'quantity_kwh', label: 'Consumo (kWh)', type: 'number', step: '0.001', min: 0, required: true },
        { name: 'notes', label: 'Observações', type: 'textarea', col: 12 },
      ],
    });
  }

  function waste() {
    Crud.init({
      title: 'Registros de resíduos', singular: 'registro de resíduo', icon: 'fa-solid fa-recycle', endpoint: '/waste', sort: '-record_date',
      canWrite: true, canDelete: isManager, canEditRow: own, newLabel: 'Novo registro',
      filters: [orgFilter, sectorFilter,
        { name: 'destination_id', label: 'Destinação', type: 'select', source: { url: '/waste-destinations', label: 'name' } },
        { name: 'from', label: 'De', type: 'date' }, { name: 'to', label: 'Até', type: 'date' }],
      columns: [
        { label: 'Data', sort: 'record_date', render: (r) => UI.date(r.record_date) },
        { label: 'Setor', key: 'sector_name', sort: 'sector' },
        { label: 'Tipo', sort: 'type', render: (r) => `${UI.esc(r.waste_type)}<br><small class="text-muted">${UI.esc(r.category)}</small>` },
        { label: 'Quantidade (kg)', sort: 'quantity_kg', render: (r) => `<strong>${UI.nf(r.quantity_kg, 3)}</strong>` },
        { label: 'Destinação', sort: 'destination', render: (r) => `<span class="badge-soft ${r.destination_code === 'RECICLAGEM' ? 'badge-ok' : 'badge-neutral'}">${UI.esc(r.destination)}</span>` },
      ],
      fields: [
        orgField, sectorField(),
        { name: 'waste_type_id', label: 'Tipo de resíduo', type: 'select', required: true, source: { url: '/waste-types?active=1', label: (o) => `${o.name} (${o.category})` } },
        { name: 'destination_id', label: 'Destinação', type: 'select', required: true, source: { url: '/waste-destinations', label: 'name' } },
        { name: 'record_date', label: 'Data', type: 'date', required: true, default: today() },
        { name: 'quantity', label: 'Quantidade', type: 'number', step: '0.001', min: 0, required: true, col: 3, get: (r) => r.quantity_kg },
        { name: 'unit', label: 'Unidade', type: 'select', required: true, col: 3, options: [{ value: 'kg', label: 'kg' }, { value: 't', label: 't' }], default: 'kg', get: () => 'kg' },
        { name: 'notes', label: 'Observações', type: 'textarea', col: 12 },
      ],
    });
  }

  function wasteTypes() {
    Crud.init({
      title: 'Tipos de resíduos', singular: 'tipo de resíduo', icon: 'fa-solid fa-dumpster', endpoint: '/waste-types', newLabel: 'Novo tipo',
      confirmDelete: 'Excluir este tipo? Tipos em uso só podem ser desativados.',
      columns: [
        { label: 'Tipo', render: (r) => `<strong>${UI.esc(r.name)}</strong>` }, { label: 'Categoria', key: 'category' },
        { label: 'Perigoso', render: (r) => Number(r.hazardous) ? '<span class="badge-soft badge-bad">Sim</span>' : 'Não' },
        { label: 'Situação', render: (r) => UI.activeBadge(r.active) },
      ],
      fields: [
        { name: 'category_id', label: 'Categoria', type: 'select', required: true, source: { url: '/waste-types/categories', label: 'name' } },
        { name: 'name', label: 'Nome', required: true, max: 120 },
        { name: 'hazardous', label: '', type: 'checkbox', checkLabel: 'Resíduo perigoso', default: false, col: 6 },
        { name: 'active', label: '', type: 'checkbox', col: 6 },
      ],
    });
  }

  function users() {
    Crud.init({
      title: 'Usuários', singular: 'usuário', icon: 'fa-solid fa-users-gear', endpoint: '/users', sort: 'name', newLabel: 'Novo usuário',
      confirmDelete: 'Excluir este usuário? Ele perderá o acesso ao sistema.',
      filters: [{ name: 'search', label: 'Buscar', placeholder: 'Nome ou e-mail' }, { name: 'role', label: 'Perfil', type: 'select', options: [{ value: 'ADMINISTRADOR', label: 'Administrador' }, { value: 'GESTOR', label: 'Gestor' }, { value: 'OPERADOR', label: 'Operador' }] }],
      columns: [
        { label: 'Nome', key: 'name', sort: 'name', render: (r) => `<strong>${UI.esc(r.name)}</strong>` }, { label: 'E-mail', key: 'email', sort: 'email' },
        { label: 'Perfil', sort: 'role', render: (r) => `<span class="badge-soft badge-neutral">${UI.esc(r.role)}</span>` },
        { label: 'Situação', render: (r) => UI.activeBadge(r.active) },
      ],
      fields: [
        { name: 'name', label: 'Nome', required: true, max: 120 },
        { name: 'email', label: 'E-mail', type: 'email', required: true, max: 150, autocomplete: 'off' },
        { name: 'password', label: 'Senha', type: 'password', required: true, optionalOnEdit: true, autocomplete: 'new-password', help: 'Mínimo de 8 caracteres. Na edição, deixe em branco para manter.', get: () => '' },
        { name: 'role', label: 'Perfil', type: 'select', required: true, options: [{ value: 'ADMINISTRADOR', label: 'Administrador' }, { value: 'GESTOR', label: 'Gestor' }, { value: 'OPERADOR', label: 'Operador' }] },
        { name: 'organization_ids', label: 'Organizações com acesso', type: 'multiselect', col: 12, source: { url: '/organizations?per_page=100', label: 'name' }, help: 'Ctrl/Cmd + clique para selecionar várias. Administradores acessam todas.' },
        { name: 'active', label: '', type: 'checkbox', col: 12 },
      ],
    });
  }

  const metricLabel = { WATER_M3: 'Água (m³/mês)', ENERGY_KWH: 'Energia (kWh/mês)', WASTE_KG: 'Resíduos (kg/mês)', RECYCLING_RATE: 'Reciclagem (%)' };

  function goals() {
    Crud.init({
      root: '#goalsRoot', title: 'Metas ambientais', singular: 'meta', icon: 'fa-solid fa-bullseye', endpoint: '/environmental-goals', sort: 'due_date',
      canWrite: isManager, canDelete: isManager, newLabel: 'Nova meta',
      filters: [orgFilter, { name: 'metric', label: 'Indicador', type: 'select', options: Object.entries(metricLabel).map(([value, label]) => ({ value, label })) }],
      rowActions: [{ name: 'history', title: 'Evolução', icon: 'fa-solid fa-chart-line', handler: showHistory }],
      columns: [
        { label: 'Meta', sort: 'title', render: (r) => `<strong>${UI.esc(r.title)}</strong><br><small class="text-muted">${UI.esc(r.objective_title)}</small>` },
        { label: 'Indicador', render: (r) => `${UI.esc(metricLabel[r.metric])}<br><small class="text-muted">${r.direction === 'DECREASE' ? '↓ Redução' : '↑ Aumento'}${r.sector_name ? ' · ' + UI.esc(r.sector_name) : ''}</small>` },
        { label: 'Ref. → Meta', render: (r) => `${UI.nf(r.baseline_value)} → ${UI.nf(r.target_value)}` },
        { label: 'Atual', render: (r) => r.has_data ? UI.nf(r.current_value) : '<span class="text-muted">sem dados</span>' },
        { label: 'Progresso', render: (r) => `<div style="min-width:120px"><div class="progress"><div class="progress-bar ${r.status === 'ATRASADA' ? 'late' : ''}" style="width:${r.progress_pct}%"></div></div><small class="text-muted">${UI.nf(r.progress_pct, 1)}%</small></div>` },
        { label: 'Prazo', sort: 'due_date', render: (r) => UI.date(r.due_date) },
        { label: 'Situação', render: (r) => UI.statusBadge(r.status) },
      ],
      fields: [
        orgField,
        { name: 'objective_id', label: 'Objetivo', type: 'select', required: true, dependsOn: 'organization_id', source: { url: '/environmental-objectives?per_page=100&organization_id={organization_id}', label: 'title' } },
        { name: 'title', label: 'Título da meta', required: true, col: 12, max: 150 },
        { name: 'metric', label: 'Indicador', type: 'select', required: true, options: Object.entries(metricLabel).map(([value, label]) => ({ value, label })) },
        { name: 'direction', label: 'Direção', type: 'select', required: true, options: [{ value: 'DECREASE', label: 'Redução (menor é melhor)' }, { value: 'INCREASE', label: 'Aumento (maior é melhor)' }] },
        { name: 'baseline_value', label: 'Valor de referência (mensal)', type: 'number', step: '0.001', min: 0, required: true, help: 'Ex.: consumo mensal médio atual; para reciclagem, o % atual.' },
        { name: 'target_value', label: 'Valor desejado', type: 'number', step: '0.001', min: 0, required: true },
        { name: 'start_date', label: 'Início', type: 'date', required: true, default: today() },
        { name: 'due_date', label: 'Prazo', type: 'date', required: true },
        sectorField({ required: false, label: 'Setor (opcional)', help: 'Em branco = toda a organização.' }),
      ],
    });
    Crud.init({
      root: '#objectivesRoot', title: 'Objetivos ambientais', singular: 'objetivo', icon: 'fa-solid fa-flag', endpoint: '/environmental-objectives', sort: 'title',
      canWrite: isManager, canDelete: isManager, newLabel: 'Novo objetivo',
      confirmDelete: 'Excluir este objetivo? Só é possível se não houver metas associadas.',
      columns: [
        { label: 'Objetivo', key: 'title', sort: 'title', render: (r) => `<strong>${UI.esc(r.title)}</strong>` }, { label: 'Descrição', key: 'description' },
        { label: 'Situação', render: (r) => UI.statusBadge(r.status) },
      ],
      fields: [
        orgField,
        { name: 'title', label: 'Título', required: true, max: 150 },
        { name: 'description', label: 'Descrição', type: 'textarea', col: 12 },
        { name: 'status', label: 'Situação', type: 'select', options: [{ value: 'ATIVO', label: 'Ativo' }, { value: 'ENCERRADO', label: 'Encerrado' }], default: 'ATIVO' },
      ],
    });
  }

  async function showHistory(goal) {
    const wrap = document.createElement('div');
    wrap.className = 'modal fade';
    wrap.innerHTML = `<div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">${UI.esc(goal.title)}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="chart-box"><canvas></canvas></div><p class="small text-muted mt-2 mb-0" id="hNote"></p></div></div></div>`;
    document.body.appendChild(wrap);
    const m = new bootstrap.Modal(wrap); m.show();
    wrap.addEventListener('hidden.bs.modal', () => wrap.remove());
    try {
      const j = await Api.get(`/environmental-goals/${goal.id}/progress`);
      const h = j.data.history;
      if (!h.length) { wrap.querySelector('#hNote').textContent = 'Ainda não há registros no período da meta.'; return; }
      new Chart(wrap.querySelector('canvas'), { type: 'line', data: { labels: h.map((x) => UI.month(x.month + '-01')), datasets: [
        { label: 'Progresso (%)', data: h.map((x) => x.progress_pct), borderColor: '#1b6b4d', backgroundColor: 'rgba(47,168,114,.15)', fill: true, tension: .3 },
      ] }, options: { maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } } } });
      wrap.querySelector('#hNote').textContent = 'Valor atual considerado: último mês com dados dentro do período da meta.';
    } catch (e) { UI.showError(e); }
  }

  return { organizations, sectors, water, energy, waste, wasteTypes, users, goals };
})();
