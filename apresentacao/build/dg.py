"""Gera os diagramas (UML/ER) do trabalho escrito com matplotlib."""
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from matplotlib.patches import Rectangle, Ellipse, Polygon, FancyBboxPatch

OUT = 'fig/'
INK = '#1f2a26'
FILL = '#eaf5ef'
HEAD = '#cfe8da'
GREY = '#f4f6f5'
FS = 6.6

def canvas(w_in, h_in):
    fig = plt.figure(figsize=(w_in, h_in), dpi=220)
    ax = fig.add_axes([0, 0, 1, 1])
    ax.set_xlim(0, w_in * 10); ax.set_ylim(0, h_in * 10)
    ax.axis('off')
    return fig, ax

def save(fig, name):
    fig.savefig(OUT + name, dpi=220, facecolor='white', bbox_inches='tight', pad_inches=0.06)
    plt.close(fig)

def txt(ax, x, y, s, fs=FS, ha='left', va='center', style='normal', weight='normal', color=INK, **kw):
    return ax.text(x, y, s, fontsize=fs, ha=ha, va=va, fontstyle=style, fontweight=weight, color=color, **kw)

# ------------------------------------------------------------------ classes
LH = 1.38
def cbox(ax, x, top, w, name, attrs=(), meths=(), abstract=False, stereo=None):
    th = 1.9 + (1.2 if stereo else 0)
    na = max(1, len(attrs)); nm = max(1, len(meths))
    h = th + LH * na + LH * nm + 0.8
    ax.add_patch(Rectangle((x, top - h), w, h, fc=FILL, ec=INK, lw=0.8))
    ax.add_patch(Rectangle((x, top - th), w, th, fc=HEAD, ec=INK, lw=0.8))
    if stereo:
        txt(ax, x + w / 2, top - 0.8, '«%s»' % stereo, fs=FS - 1, ha='center')
        txt(ax, x + w / 2, top - th + 0.95, name, ha='center', weight='bold', style='italic' if abstract else 'normal')
    else:
        txt(ax, x + w / 2, top - th / 2, name, ha='center', weight='bold', style='italic' if abstract else 'normal')
    y = top - th
    ax.plot([x, x + w], [y - LH * na - 0.4, y - LH * na - 0.4], color=INK, lw=0.8)
    for i, a in enumerate(attrs):
        txt(ax, x + 0.5, y - 0.4 - LH * (i + 0.5), a)
    y2 = y - LH * na - 0.4
    for i, m in enumerate(meths):
        txt(ax, x + 0.5, y2 - 0.4 - LH * (i + 0.5), m)
    return dict(x=x, top=top, w=w, h=h, bottom=top - h, cx=x + w / 2, cy=top - h / 2, right=x + w)

def tbox(ax, x, top, w, name, h=2.4, abstract=False):
    ax.add_patch(Rectangle((x, top - h), w, h, fc=HEAD, ec=INK, lw=0.8))
    txt(ax, x + w / 2, top - h / 2, name, ha='center', weight='bold', style='italic' if abstract else 'normal')
    return dict(x=x, top=top, w=w, h=h, bottom=top - h, cx=x + w / 2, cy=top - h / 2, right=x + w)

def tri(ax, x, y, direction='up', s=0.9):
    if direction == 'up':
        pts = [(x, y), (x - s, y - 1.6), (x + s, y - 1.6)]
    else:
        pts = [(x, y), (x - s, y + 1.6), (x + s, y + 1.6)]
    ax.add_patch(Polygon(pts, closed=True, fc='white', ec=INK, lw=0.8))

def inherit(ax, child, parent, busy=None):
    """Generalização: de child (abaixo) para parent (acima)."""
    x1, y1 = child['cx'], child['top']
    x2, y2 = parent['cx'], parent['bottom']
    by = busy if busy is not None else (y1 + y2) / 2
    ax.plot([x1, x1, x2, x2], [y1, by, by, y2 - 1.6], color=INK, lw=0.8)
    tri(ax, x2, y2, 'up')

def arrow(ax, p1, p2, label=None, dashed=True, lpos=0.5, dy=0.9, dx=0, ha='center'):
    ax.annotate('', xy=p2, xytext=p1, arrowprops=dict(arrowstyle='->', lw=0.8, color=INK, ls='--' if dashed else '-', shrinkA=0, shrinkB=0))
    if label:
        txt(ax, p1[0] + (p2[0] - p1[0]) * lpos + dx, p1[1] + (p2[1] - p1[1]) * lpos + dy, label, fs=FS - 0.8, ha=ha, style='italic')

# ============================================================ CASOS DE USO
def usecase():
    fig, ax = canvas(6.3, 5.9)
    ax.add_patch(Rectangle((26, 0.2), 36, 57, fc='white', ec=INK, lw=1))
    txt(ax, 44, 55, 'EcoManager — API REST', ha='center', weight='bold', fs=7.5)

    def actor(x, y, name):
        ax.add_patch(plt.Circle((x, y + 4.2), 1.1, fc='white', ec=INK, lw=0.9))
        ax.plot([x, x], [y + 3.1, y + 0.8], color=INK, lw=0.9)
        ax.plot([x - 1.7, x + 1.7], [y + 2.3, y + 2.3], color=INK, lw=0.9)
        ax.plot([x, x - 1.5], [y + 0.8, y - 1.0], color=INK, lw=0.9)
        ax.plot([x, x + 1.5], [y + 0.8, y - 1.0], color=INK, lw=0.9)
        txt(ax, x, y - 2.5, name, ha='center', weight='bold')
        return (x, y + 2)

    ucs = [
        ('UC01', 'Autenticar usuário', 'op'),
        ('UC04', 'Registrar consumo de água', 'op'),
        ('UC05', 'Registrar consumo de energia', 'op'),
        ('UC06', 'Registrar resíduos', 'op'),
        ('UC08', 'Consultar indicadores e dashboard', 'op'),
        ('UC03', 'Gerenciar organizações e setores', 'ge'),
        ('UC07', 'Gerenciar objetivos e metas', 'ge'),
        ('UC09', 'Gerar relatórios', 'ge'),
        ('UC10', 'Gerenciar catálogos de resíduos', 'ge'),
        ('UC02', 'Gerenciar usuários', 'ad'),
    ]
    pos = {}
    y = 49.5
    for code, name, who in ucs:
        ax.add_patch(Ellipse((47, y), 28, 4.6, fc=FILL, ec=INK, lw=0.8))
        txt(ax, 47, y + 0.5, name, ha='center')
        txt(ax, 47, y - 1.2, code, ha='center', fs=FS - 1.2, color='#555')
        pos[code] = (33, y)
        y -= 5.15
    a_op = actor(8, 40, 'Operador')
    a_ge = actor(8, 24, 'Gestor')
    a_ad = actor(8, 8, 'Administrador')
    for code, _, who in ucs:
        src = {'op': a_op, 'ge': a_ge, 'ad': a_ad}[who]
        ax.plot([src[0] + 2, pos[code][0]], [src[1], pos[code][1]], color=INK, lw=0.7)
    # generalização entre atores
    ax.plot([8, 8], [30.2 - 0.0, 30.2], color=INK)
    ax.plot([8, 8], [20.8 + 5.0 - 0.0, 28.0], color=INK, lw=0.8)  # gestor -> operador (sobe)
    ax.plot([8, 8], [13.0, 22.5 - 0.0], color=INK, lw=0.8)       # admin -> gestor
    tri(ax, 8, 36.3, 'up'); tri(ax, 8, 19.5 + 0.0, 'up')
    ax.plot([8, 8], [28.2, 34.7], color=INK, lw=0.8)
    ax.plot([8, 8], [12.6, 18.0], color=INK, lw=0.8)
    txt(ax, 63.2, 28, 'Todo caso de uso, exceto\nUC01, inclui «include» UC01.\nGestor herda do Operador;\nAdministrador herda do Gestor.', fs=FS - 0.4, va='center')
    save(fig, 'usecase.png')

# ============================================================ CLASSES 1
def classes_core():
    fig, ax = canvas(6.3, 6.0)
    req = cbox(ax, 0.5, 59, 20, 'Request', ['+method: string', '+path: string', '+query: array', '+params: array', '+user: ?array'],
               ['+capture(): Request', '+body(): array', '+input(key)', '+q(key)', '+header(name)', '+ip(): string'])
    rou = cbox(ax, 22.5, 59, 20, 'Router', ['-routes: array', '-prefix: string', '-groupMiddleware'],
               ['+group(prefix, mw, cb)', '+add(method, path, h)', '+get() / post() / put()', '+patch() / delete()', '+resource(path, ctrl)', '+dispatch(Request)'])
    res = cbox(ax, 44.5, 59, 18, 'Response', [], ['+json(payload, status)', '+success(data, msg)', '+created(data)', '+noContent()', '+error(msg, status)', '+redirect(url)'])
    ay = 38.8
    a = cbox(ax, 0.5, ay, 20, 'AuthMiddleware', [], ['+handle(Request, next)'])
    c = cbox(ax, 22.5, ay, 20, 'CsrfMiddleware', [], ['+handle(Request, next)'])
    r = cbox(ax, 44.5, ay, 18, 'RoleMiddleware', [], ['+handle(Request, next, roles)'])
    arrow(ax, (rou['cx'], rou['bottom']), (c['cx'], c['top']), '«executa pipeline»', dy=0, dx=1, ha='left')
    arrow(ax, (rou['x'] + 3, rou['bottom']), (a['right'] - 4, a['top']), None)
    arrow(ax, (rou['right'] - 3, rou['bottom']), (r['x'] + 4, r['top']), None)
    arrow(ax, (req['right'], 52), (rou['x'], 52), '«usa»', dy=0.8)
    arrow(ax, (rou['right'], 52), (res['x'], 52), None)
    by = 29.5
    db = cbox(ax, 0.5, by, 20, 'Database', ['-pdo: PDO'], ['+pdo(): PDO'])
    ses = cbox(ax, 22.5, by, 20, 'Session', [], ['+start()', '+destroy()', '+csrfToken(): string'])
    val = cbox(ax, 44.5, by, 18, 'Validator', [], ['+validate(data, rules,', '   partial): array'])
    arrow(ax, (a['cx'], a['bottom']), (db['cx'], db['top']), None)
    arrow(ax, (a['right'] - 2, a['bottom']), (ses['x'] + 4, ses['top']), '«usa»', dy=0.3, dx=-2.5, ha='right')
    arrow(ax, (c['cx'], c['bottom']), (ses['cx'], ses['top']), None)
    ey = 17.5
    eh = cbox(ax, 0.5, ey, 20, 'ExceptionHandler', [], ['+register()', '+handle(Throwable)'])
    he = cbox(ax, 22.5, ey, 20, 'HttpException', ['+status: int', '+errors: array'], [])
    arrow(ax, (eh['right'], eh['cy']), (he['x'], eh['cy']), '«captura»', dy=0.9)
    bus = 7.2
    subs = [('ValidationException', 0.3), ('NotFoundException', 16.0), ('ForbiddenException', 31.7), ('UnauthorizedException', 47.4)]
    for n, x in subs:
        b = tbox(ax, x, 5.4, 15.1, n, h=2.5)
        ax.plot([b['cx'], b['cx']], [b['top'], bus], color=INK, lw=0.8)
        ax.plot([b['cx'], he['cx']], [bus, bus], color=INK, lw=0.8)
    ax.plot([he['cx'], he['cx']], [bus, he['bottom'] - 1.6], color=INK, lw=0.8)
    tri(ax, he['cx'], he['bottom'], 'up')
    save(fig, 'class_core.png')

# ============================================================ CLASSES 2
def classes_ctrl():
    fig, ax = canvas(6.3, 5.4)
    base = cbox(ax, 19, 53, 25, 'BaseController', [], ['#id(Request): int'], abstract=True)
    org = cbox(ax, 0.5, 44, 33, 'OrgResourceController',
               ['#table, #columns, #joins', '#sortable, #defaultSort', '#softDelete, #hasCreatedBy'],
               ['+index() / show() / store()', '+update() / destroy()', '#rules(): array {abstract}', '#prepare(data, existing): array', '#filters() / #transform()', '#assertSector(id, org)'], abstract=True)
    inherit(ax, org, base, busy=45.8)
    stack = ['AuthController', 'UserController', 'OrganizationController', 'WasteTypeController', 'WasteDestinationController', 'IndicatorController', 'DashboardController', 'ReportController']
    y = 44.0
    last = None
    first = None
    for n in stack:
        b = tbox(ax, 37, y, 25.5, n, h=2.1)
        if first is None: first = b
        y -= 2.6
    # barramento de herança para a coluna
    ax.plot([base['cx'], base['cx']], [base['bottom'], 45.8], color=INK, lw=0.8)
    ax.plot([base['cx'], 36.0], [45.8, 45.8], color=INK, lw=0.8)
    ax.plot([36.0, 36.0], [45.8, 44.0 - 2.6 * 7 - 1.05], color=INK, lw=0.8)
    for i in range(len(stack)):
        yy = 44.0 - 2.6 * i - 1.05
        ax.plot([36.0, 37], [yy, yy], color=INK, lw=0.8)
    # filhos de OrgResourceController
    ytop = 22.5
    kids = [('SectorController', 0.5), ('ObjectiveController', 11.8), ('GoalController', 23.1), ('WasteController', 33.4)]
    bus = 25.3
    for n, x in kids:
        b = tbox(ax, x, ytop, 10.5, n, h=2.5)
        ax.plot([b['cx'], b['cx']], [b['top'], bus], color=INK, lw=0.8)
    cons = cbox(ax, 45, ytop, 17.5, 'ConsumptionController', ['#valueColumn: string'], ['+compare()'], abstract=True)
    ax.plot([cons['cx'], cons['cx']], [cons['top'], bus], color=INK, lw=0.8)
    ax.plot([5.75, cons['cx']], [bus, bus], color=INK, lw=0.8)
    ax.plot([org['cx'], org['cx']], [bus, org['bottom'] - 1.6], color=INK, lw=0.8)
    tri(ax, org['cx'], org['bottom'], 'up')
    w = tbox(ax, 36.2, 9.4, 13.2, 'WaterConsumptionController', h=2.6)
    e = tbox(ax, 50.4, 9.4, 12.6, 'EnergyConsumptionController', h=2.6)
    bus2 = 12.4
    for b in (w, e):
        ax.plot([b['cx'], b['cx']], [b['top'], bus2], color=INK, lw=0.8)
    ax.plot([w['cx'], e['cx']], [bus2, bus2], color=INK, lw=0.8)
    ax.plot([cons['cx'], cons['cx']], [bus2, cons['bottom'] - 1.6], color=INK, lw=0.8)
    tri(ax, cons['cx'], cons['bottom'], 'up')
    txt(ax, 0.8, 8.5, 'Controllers que herdam de OrgResourceController reutilizam:\nescopo por organização, paginação, validação, exclusão lógica,\nperfis de acesso e auditoria. Cada um implementa apenas\nrules(), prepare() e filtros específicos.', fs=FS - 0.3, va='top')
    save(fig, 'class_ctrl.png')

# ============================================================ CLASSES 3
def classes_services():
    fig, ax = canvas(6.3, 4.6)
    ind = cbox(ax, 0.5, 44, 25.5, 'IndicatorService', [],
               ['+monthlySeries(metric, filters)', '+consumption(metric, filters)', '+waste(filters)', '+goals(filters)', '+evaluate(goal): array', '+goalHistory(goal): array', '+goalsEvolution(filters)'])
    calc = cbox(ax, 37, 44, 25.5, 'Calc', [], ['+variation(cur, prev): ?float', '+percent(part, total): ?float', '+goalProgress(dir, base,', '   target, current): ?float', '+goalStatus(progress, due)'])
    uc = cbox(ax, 37, 29.5, 25.5, 'UnitConverter', [], ['+waterToM3(q, unit): float', '+wasteToKg(q, unit): float'])
    scope = cbox(ax, 0.5, 29, 25.5, 'OrgScope', [], ['+assert(Request, orgId)', '+ids(Request): array', '+in(col, ids, &params)'])
    per = cbox(ax, 0.5, 18.5, 25.5, 'PeriodFilter', [], ['+from(Request): array'])
    pag = cbox(ax, 28.3, 18.5, 8.3, 'Pagination', [], ['+from()', '+meta()'])
    auth = cbox(ax, 37, 19.5, 25.5, 'AuthService', [], ['+login(Request, email, pw)', '+logout(Request)'])
    log = cbox(ax, 0.5, 9.0, 25.5, 'ActivityLog', [], ['+record(Request, action,', '   entity, id)'])
    repo = cbox(ax, 37, 9.0, 25.5, 'UserRepository', [], ['+paginate() / find()', '+create() / update()', '+syncOrganizations()'])
    arrow(ax, (ind['right'], 36), (calc['x'], 36), '«usa»', dy=0.9)
    arrow(ax, (per['cx'] - 3, per['top']), (scope['cx'] - 3, scope['bottom']), '«usa»', dy=0, dx=-1, ha='right')
    arrow(ax, (auth['x'], 14.3), (log['right'], 14.3), '«registra»', dy=0.9)
    arrow(ax, (auth['cx'], auth['bottom'] - 0.3), (repo['cx'] , repo['top']), None, dashed=True)
    arrow(ax, (ind['cx'] + 8, ind['bottom']), (per['cx'] + 8, per['top'] + 0.01), None)
    txt(ax, 28.6, 38.5, 'Cálculos centralizados no backend:\ndashboard, indicadores, relatórios\ne metas usam as mesmas classes.', fs=FS - 0.6, va='center')
    txt(ax, 28.6, 28.3, '«Todos os acessos a dados usam\nPDO com prepared statements»', fs=FS - 0.9, va='center', style='italic')
    save(fig, 'class_services.png')

# ============================================================ SEQUÊNCIA
def seq(name, h_in, parts, msgs, frames=()):
    fig, ax = canvas(6.3, h_in)
    H = h_in * 10
    n = len(parts)
    xs = [4.5 + i * (54.0 / (n - 1)) for i in range(n)]
    top = H - 1
    for (t, sub), x in zip(parts, xs):
        ax.add_patch(Rectangle((x - 4.0, top - 3.4), 8.0, 3.4, fc=HEAD, ec=INK, lw=0.8))
        txt(ax, x, top - 1.2, t, ha='center', weight='bold', fs=FS - 0.6)
        txt(ax, x, top - 2.5, sub, ha='center', fs=FS - 1.5, color='#444')
        ax.plot([x, x], [top - 3.4, 0.8], color='#777', lw=0.7, ls=(0, (4, 3)))
    y = top - 6
    ys = {}
    for i, m in enumerate(msgs):
        kind = m[0]
        if kind == 'm':
            _, a, b, label, dashed = m
            x1, x2 = xs[a], xs[b]
            if a == b:
                ax.plot([x1, x1 + 3.5, x1 + 3.5], [y, y, y - 1.6], color=INK, lw=0.8)
                ax.annotate('', xy=(x1 + 0.1, y - 1.6), xytext=(x1 + 3.5, y - 1.6), arrowprops=dict(arrowstyle='->', lw=0.8, color=INK))
                txt(ax, x1 + 4.2, y - 0.8, label, fs=FS - 0.9)
                y -= 3.8
            else:
                ax.annotate('', xy=(x2, y), xytext=(x1, y), arrowprops=dict(arrowstyle='->' if not dashed else '->', lw=0.8, color=INK, ls='--' if dashed else '-', shrinkA=0, shrinkB=0))
                txt(ax, (x1 + x2) / 2, y + 0.9, label, fs=FS - 0.9, ha='center')
                y -= 3.6
            ys[i] = y
        elif kind == 'f_start':
            ys[('s', m[1])] = y + 1.2
            txt(ax, 1.0, y, '', fs=1)
            y -= 1.0
        elif kind == 'f_end':
            x0, label = m[2], m[1]
            ax.add_patch(Rectangle((x0, y + 0.6), 62.5 - x0, ys[('s', label)] - y - 0.6, fc='none', ec='#555', lw=0.7))
            ax.add_patch(Rectangle((x0, ys[('s', label)] - 1.9), 9.5, 1.9, fc=GREY, ec='#555', lw=0.7))
            txt(ax, x0 + 0.4, ys[('s', label)] - 0.95, m[3], fs=FS - 1, weight='bold')
            y -= 0.6
    save(fig, name)

def sequences():
    seq('seq_water.png', 3.9, [('Cliente', 'web / curl'), ('Router', ''), ('Middleware', 'Auth + CSRF'), ('Controller', 'Water…Ctrl'), ('Validator', ''), ('UnitConverter', ''), ('MySQL', '')],
        [('m', 0, 1, 'POST /api/v1/water-consumption (JSON, cookie, X-CSRF-Token)', False),
         ('m', 1, 2, 'handle(request)', False),
         ('m', 2, 6, 'SELECT usuário ativo + organizações', False),
         ('m', 2, 2, 'hash_equals(token CSRF)', False),
         ('f_start', 'alt'),
         ('m', 2, 0, '[token inválido ou sem sessão] 403 / 401', True),
         ('f_end', 'alt', 18.5, 'alt'),
         ('m', 2, 3, '[válido] store(request)', False),
         ('m', 3, 4, 'validate(body, rules)', False),
         ('m', 3, 5, 'waterToM3(quantity, unit)', False),
         ('m', 3, 6, 'INSERT water_consumption (prepared)', False),
         ('m', 3, 6, 'INSERT activity_logs', False),
         ('m', 3, 0, '201 {success:true, data:{id}}', True)])
    seq('seq_dash.png', 3.5, [('Cliente', 'web / curl'), ('Router', ''), ('Middleware', 'Auth'), ('DashboardCtrl', ''), ('IndicatorService', ''), ('Calc', ''), ('MySQL', '')],
        [('m', 0, 1, 'GET /api/v1/dashboard/summary?organization_id&from&to', False),
         ('m', 1, 2, 'handle(request)', False),
         ('m', 2, 3, 'summary(request)', False),
         ('m', 3, 3, 'PeriodFilter::from() + OrgScope', False),
         ('m', 3, 4, 'consumption() / waste() / goals()', False),
         ('m', 4, 6, 'SELECT SUM(...) GROUP BY mês', False),
         ('m', 4, 5, 'variation() / percent() / goalProgress()', False),
         ('m', 3, 0, '200 {cards: água, energia, resíduos, reciclagem, metas}', True)])
    seq('seq_login.png', 2.6, [('Cliente', 'web / curl'), ('Router', ''), ('AuthController', ''), ('AuthService', ''), ('MySQL', '')],
        [('m', 0, 1, 'POST /api/v1/auth/login {email, password}', False),
         ('m', 1, 2, 'login(request)', False),
         ('m', 2, 3, 'login(email, password)', False),
         ('m', 3, 4, 'contagem de tentativas + SELECT usuário', False),
         ('m', 3, 3, 'password_verify() · session_regenerate_id()', False),
         ('m', 3, 0, '200 {user, csrf_token} + cookie ECOSESSID', True)])

# ============================================================ ER
def er():
    fig, ax = canvas(6.3, 6.4)
    def tb(x, top, w, name, cols):
        h = 2.1 + LH * len(cols) + 0.5
        ax.add_patch(Rectangle((x, top - h), w, h, fc=FILL, ec=INK, lw=0.8))
        ax.add_patch(Rectangle((x, top - 2.1), w, 2.1, fc=HEAD, ec=INK, lw=0.8))
        txt(ax, x + w / 2, top - 1.05, name, ha='center', weight='bold', fs=(FS - 1.1) if len(name) > 22 else (FS - 0.2))
        for i, c in enumerate(cols):
            txt(ax, x + 0.5, top - 2.1 - 0.3 - LH * (i + 0.5), c, fs=FS - 0.9)
        return dict(x=x, top=top, w=w, bottom=top - h, cx=x + w / 2, cy=top - h / 2, right=x + w, h=h)
    W = 13.4
    c = [0.2, 16.2, 32.2, 48.2]
    R = [62.5, 50.0, 38.0, 22.0, 7.0]
    users = tb(c[0], R[0], W, 'users', ['PK id', 'name, email (UQ)', 'password_hash', 'role, active'])
    ou = tb(c[1], R[0], W, 'organization_users', ['PK/FK organization_id', 'PK/FK user_id'])
    org = tb(c[2], R[0], W, 'organizations', ['PK id', 'name, legal_id (UQ)', 'email, phone, active'])
    sec = tb(c[3], R[0], W, 'sectors', ['PK id', 'FK organization_id', 'name, active'])
    logs = tb(c[0], R[1], W, 'activity_logs', ['PK id', 'FK user_id', 'action, entity, entity_id', 'ip, created_at'])
    goals = tb(c[1], R[1], W, 'environmental_goals', ['PK id', 'FK objective_id', 'FK organization_id', 'FK sector_id (opc.)', 'metric, direction', 'baseline, target', 'start_date, due_date'])
    obj = tb(c[2], R[1], W, 'environmental_objectives', ['PK id', 'FK organization_id', 'title, status'])
    wat = tb(c[3], R[1], W, 'water_consumption', ['PK id', 'FK organization_id', 'FK sector_id', 'reference_month', 'quantity_m3', 'original_quantity/unit'])
    prog = tb(c[1], 33.0, W, 'environmental_goal_progress', ['PK id', 'FK goal_id', 'reference_month', 'current_value', 'progress_pct'])
    ene = tb(c[3], 36.5, W, 'energy_consumption', ['PK id', 'FK organization_id', 'FK sector_id', 'reference_month', 'quantity_kwh'])
    cat = tb(c[1], R[3], W, 'waste_categories', ['PK id', 'name (UQ)'])
    typ = tb(c[2], R[3] - 0.0, W, 'waste_types', ['PK id', 'FK category_id', 'name, hazardous, active'])
    rec = tb(c[3], R[3] + 0.0, W, 'waste_records', ['PK id', 'FK organization_id', 'FK sector_id', 'FK waste_type_id', 'FK destination_id', 'record_date, quantity_kg'])
    dst = tb(c[3], R[4], W, 'waste_destinations', ['PK id', 'code (UQ), name', 'active'])

    def line(p, q, l1=None, l2=None):
        ax.plot([p[0], q[0]], [p[1], q[1]], color=INK, lw=0.8)
        if l1: txt(ax, p[0] + (0.2 if q[0] >= p[0] else -0.2), p[1] + 0.9, l1, fs=FS - 0.9, ha='left' if q[0] >= p[0] else 'right', weight='bold')
        if l2: txt(ax, q[0] + (-0.2 if q[0] >= p[0] else 0.2), q[1] - 0.9, l2, fs=FS - 0.9, ha='right' if q[0] >= p[0] else 'left', weight='bold')
    y1 = R[0] - 4.0
    line((users['right'], y1), (ou['x'], y1), '1', 'N')
    line((org['x'], y1), (ou['right'], y1), '1', 'N')
    line((org['right'], y1), (sec['x'], y1), '1', 'N')
    ax.plot([users['cx'], users['cx']], [users['bottom'], logs['top']], color=INK, lw=0.8)
    txt(ax, users['cx'] + 0.5, users['bottom'] - 1.0, '1', fs=FS - 0.7, weight='bold')
    txt(ax, users['cx'] + 0.5, logs['top'] + 1.0, 'N', fs=FS - 0.7, weight='bold')
    ax.plot([org['cx'], org['cx']], [org['bottom'], obj['top']], color=INK, lw=0.8)
    txt(ax, org['cx'] + 0.5, org['bottom'] - 1.0, '1', fs=FS - 0.7, weight='bold')
    txt(ax, org['cx'] + 0.5, obj['top'] + 1.0, 'N', fs=FS - 0.7, weight='bold')
    y2 = R[1] - 5.5
    line((obj['x'], y2), (goals['right'], y2), '1', 'N')
    ax.plot([goals['cx'], goals['cx']], [goals['bottom'], prog['top']], color=INK, lw=0.8)
    txt(ax, goals['cx'] + 0.5, goals['bottom'] - 1.0, '1', fs=FS - 0.7, weight='bold')
    txt(ax, goals['cx'] + 0.5, prog['top'] + 1.0, 'N', fs=FS - 0.7, weight='bold')
    # barramento do setor (lado direito)
    bx = 62.8
    ax.plot([sec['right'], bx], [sec['cy'], sec['cy']], color=INK, lw=0.8)
    ax.plot([bx, bx], [sec['cy'], rec['top'] - 4.0], color=INK, lw=0.8)
    for t in (wat, ene, rec):
        yy = t['top'] - 4.0
        ax.plot([t['right'], bx], [yy, yy], color=INK, lw=0.8)
        txt(ax, t['right'] + 0.2, yy + 0.9, 'N', fs=FS - 0.7, weight='bold')
    txt(ax, sec['right'] + 0.2, sec['cy'] + 0.9, '1', fs=FS - 0.7, weight='bold')
    line((cat['right'], cat['top'] - 3.0), (typ['x'], cat['top'] - 3.0), '1', 'N')
    line((typ['right'], typ['top'] - 3.0), (rec['x'], typ['top'] - 3.0), '1', 'N')
    ax.plot([dst['cx'], dst['cx']], [dst['top'], rec['bottom']], color=INK, lw=0.8)
    txt(ax, dst['cx'] + 0.5, dst['top'] + 1.0, '1', fs=FS - 0.7, weight='bold')
    txt(ax, dst['cx'] + 0.5, rec['bottom'] - 1.0, 'N', fs=FS - 0.7, weight='bold')
    txt(ax, 0.2, 38, 'Legenda:\nPK chave primária\nFK chave estrangeira\nUQ valor único\n\nTodas possuem\ncreated_at/updated_at.\nAs de dados ambientais\ntêm deleted_at (exclusão\nlógica) e is_demo.\nConsumo e resíduos\ntambém referenciam\norganizations.', fs=FS - 0.8, va='top')
    save(fig, 'er.png')

# ============================================================ ARQUITETURA
def arch():
    fig, ax = canvas(6.3, 4.0)
    def layer(y, h, title, items, fc):
        ax.add_patch(FancyBboxPatch((1, y), 61, h, boxstyle='round,pad=0.2,rounding_size=1', fc=fc, ec=INK, lw=0.9))
        txt(ax, 2.2, y + h - 1.6, title, weight='bold', fs=FS + 0.3)
        n = len(items); bw = (58.5 - (n - 1) * 1.2) / n
        for i, it in enumerate(items):
            bx = 2.2 + i * (bw + 1.2)
            ax.add_patch(Rectangle((bx, y + 0.9), bw, h - 3.6, fc='white', ec=INK, lw=0.7))
            txt(ax, bx + bw / 2, y + 0.9 + (h - 3.6) / 2, it, ha='center', fs=FS - 0.6)
    layer(31, 8, 'Cliente (aplicação consumidora)', ['Interface web\n(HTML, Bootstrap, JS)', 'Chart.js\n(gráficos)', 'curl / Postman\n(testes)', 'Outros sistemas\n(integração)'], '#f4f6f5')
    layer(20.5, 8, 'Web Service — API REST /api/v1 (HTTP + JSON)', ['Router\n(rotas e versões)', 'Middleware\n(Auth, CSRF, Role)', 'Controllers\n(recursos REST)', 'Respostas JSON\npadronizadas'], '#eaf5ef')
    layer(10, 8, 'Regras de negócio', ['IndicatorService\n(indicadores)', 'Calc\n(variação, metas)', 'UnitConverter\n(m³, kg)', 'Validator\n(entrada)'], '#eaf5ef')
    layer(0.6, 6.6, 'Persistência', ['PDO + prepared statements', 'MySQL 8 (InnoDB)', 'Migrações e seeds'], '#f4f6f5')
    for yb, yt in ((28.7, 31.0), (18.2, 20.5), (7.7, 10.0)):
        ax.annotate('', xy=(31.5, yb), xytext=(31.5, yt), arrowprops=dict(arrowstyle='<|-|>', lw=0.9, color=INK, mutation_scale=7))
    save(fig, 'arch.png')


# ======================= REVISÕES (sobrescrevem as versões anteriores) =======================
def usecase():
    fig, ax = canvas(6.3, 5.9)
    ax.add_patch(Rectangle((26, 0.2), 36, 57, fc='white', ec=INK, lw=1))
    txt(ax, 44, 55, 'EcoManager — API REST', ha='center', weight='bold', fs=7.5)

    def actor(x, y, name):
        ax.add_patch(plt.Circle((x, y + 4.2), 1.1, fc='white', ec=INK, lw=0.9))
        ax.plot([x, x], [y + 3.1, y + 0.8], color=INK, lw=0.9)
        ax.plot([x - 1.7, x + 1.7], [y + 2.3, y + 2.3], color=INK, lw=0.9)
        ax.plot([x, x - 1.5], [y + 0.8, y - 1.0], color=INK, lw=0.9)
        ax.plot([x, x + 1.5], [y + 0.8, y - 1.0], color=INK, lw=0.9)
        txt(ax, x, y - 2.5, name, ha='center', weight='bold')
        return (x, y + 2)

    ucs = [('UC01', 'Autenticar usuário', 'op'), ('UC04', 'Registrar consumo de água', 'op'),
           ('UC05', 'Registrar consumo de energia', 'op'), ('UC06', 'Registrar resíduos', 'op'),
           ('UC08', 'Consultar indicadores e dashboard', 'op'), ('UC03', 'Gerenciar organizações e setores', 'ge'),
           ('UC07', 'Gerenciar objetivos e metas', 'ge'), ('UC09', 'Gerar relatórios', 'ge'),
           ('UC10', 'Gerenciar catálogos de resíduos', 'ge'), ('UC02', 'Gerenciar usuários', 'ad')]
    pos = {}
    y = 49.5
    for code, name, who in ucs:
        ax.add_patch(Ellipse((47, y), 28, 4.6, fc=FILL, ec=INK, lw=0.8))
        txt(ax, 47, y + 0.5, name, ha='center')
        txt(ax, 47, y - 1.2, code, ha='center', fs=FS - 1.2, color='#555')
        pos[code] = (33, y)
        y -= 5.15
    a_op = actor(8, 40, 'Operador'); a_ge = actor(8, 24, 'Gestor'); a_ad = actor(8, 8, 'Administrador')
    for code, _, who in ucs:
        src = {'op': a_op, 'ge': a_ge, 'ad': a_ad}[who]
        ax.plot([src[0] + 2, pos[code][0]], [src[1], pos[code][1]], color=INK, lw=0.7)
    # generalizações: Gestor -> Operador, Administrador -> Gestor
    ax.plot([8, 8], [29.5, 34.2], color=INK, lw=0.8); tri(ax, 8, 35.8, 'up')
    ax.plot([8, 8], [13.5, 18.2], color=INK, lw=0.8); tri(ax, 8, 19.8, 'up')
    txt(ax, 0.5, 56.5, 'Gestor herda do Operador;\nAdministrador herda do Gestor.\nTodo caso de uso, exceto UC01,\n«include» UC01 (autenticação).', fs=FS - 0.6, va='top')
    save(fig, 'usecase.png')

def classes_ctrl():
    fig, ax = canvas(6.3, 5.4)
    base = cbox(ax, 19.5, 53, 24, 'BaseController', [], ['#id(Request): int'], abstract=True)
    org = cbox(ax, 0.5, 44.5, 33, 'OrgResourceController',
               ['#table, #columns, #joins', '#sortable, #defaultSort', '#softDelete, #hasCreatedBy'],
               ['+index() / show() / store()', '+update() / destroy()', '#rules(): array {abstract}', '#prepare(data, existing): array', '#filters() / #transform()', '#assertSector(id, org)'], abstract=True)
    bus = 46.0
    ax.plot([org['cx'], org['cx']], [org['top'], bus], color=INK, lw=0.8)
    ax.plot([org['cx'], 36.0], [bus, bus], color=INK, lw=0.8)
    ax.plot([base['cx'], base['cx']], [base['bottom'] - 1.6, bus], color=INK, lw=0.8)
    tri(ax, base['cx'], base['bottom'], 'up')
    names = ['AuthController', 'UserController', 'OrganizationController', 'WasteTypeController', 'WasteDestinationController', 'IndicatorController', 'DashboardController', 'ReportController']
    y = 44.0
    ys = []
    for n in names:
        tbox(ax, 37.5, y, 25, n, h=1.9)
        ys.append(y - 0.95); y -= 2.3
    ax.plot([36.0, 36.0], [bus, ys[-1]], color=INK, lw=0.8)
    for yy in ys:
        ax.plot([36.0, 37.5], [yy, yy], color=INK, lw=0.8)
    ytop = 21.5; bus2 = 24.6
    kids = [('SectorController', 0.3), ('ObjectiveController', 12.0), ('GoalController', 23.7), ('WasteController', 35.4)]
    for n, x in kids:
        b = tbox(ax, x, ytop, 11.4, n, h=2.5)
        ax.plot([b['cx'], b['cx']], [b['top'], bus2], color=INK, lw=0.8)
    cons = cbox(ax, 48, ytop, 14.5, 'ConsumptionController', ['#valueColumn'], ['+compare()'], abstract=True)
    ax.plot([cons['cx'], cons['cx']], [cons['top'], bus2], color=INK, lw=0.8)
    ax.plot([6.0, cons['cx']], [bus2, bus2], color=INK, lw=0.8)
    ax.plot([org['cx'], org['cx']], [bus2, org['bottom'] - 1.6], color=INK, lw=0.8)
    tri(ax, org['cx'], org['bottom'], 'up')
    w = tbox(ax, 28.0, 11.0, 17.0, 'WaterConsumptionController', h=2.6)
    e = tbox(ax, 45.4, 11.0, 17.2, 'EnergyConsumptionController', h=2.6)
    bus3 = 14.3
    for b in (w, e):
        ax.plot([b['cx'], b['cx']], [b['top'], bus3], color=INK, lw=0.8)
    ax.plot([w['cx'], e['cx']], [bus3, bus3], color=INK, lw=0.8)
    ax.plot([cons['cx'], cons['cx']], [bus3, cons['bottom'] - 1.6], color=INK, lw=0.8)
    tri(ax, cons['cx'], cons['bottom'], 'up')
    txt(ax, 0.8, 11.5, 'Os controllers que herdam de OrgResourceController\nreutilizam escopo por organização, paginação, validação,\nexclusão lógica, perfis de acesso e auditoria. Cada um\nimplementa apenas rules(), prepare() e filtros próprios.', fs=FS - 0.5, va='top')
    save(fig, 'class_ctrl.png')

def classes_services():
    fig, ax = canvas(6.3, 4.6)
    ind = cbox(ax, 0.5, 45, 25, 'IndicatorService', [],
               ['+monthlySeries(metric, f)', '+consumption(metric, f)', '+waste(f)', '+goals(f)', '+evaluate(goal): array', '+goalHistory(goal): array', '+goalsEvolution(f)'])
    calc = cbox(ax, 37.5, 45, 25, 'Calc', [], ['+variation(cur, prev): ?float', '+percent(part, total): ?float', '+goalProgress(dir, base,', '   target, current): ?float', '+goalStatus(progress, due)'])
    arrow(ax, (ind['right'], 39), (calc['x'], 39), '«usa»', dy=0.9)
    per = cbox(ax, 0.5, 27, 18, 'PeriodFilter', [], ['+from(Request): array'])
    sco = cbox(ax, 22.5, 27, 18, 'OrgScope', [], ['+assert(Request, id)', '+ids(Request): array', '+in(col, ids, &p)'])
    uc = cbox(ax, 44.5, 27, 18, 'UnitConverter', [], ['+waterToM3(q, unit)', '+wasteToKg(q, unit)'])
    arrow(ax, (per['right'], 22.0), (sco['x'], 22.0), None)
    arrow(ax, (ind['cx'] + 6, ind['bottom']), (sco['cx'], sco['top']), None)
    log = cbox(ax, 0.5, 14.5, 18, 'ActivityLog', [], ['+record(Request,', '   action, entity, id)'])
    aut = cbox(ax, 24.0, 14.5, 17, 'AuthService', [], ['+login(Request,', '   email, password)', '+logout(Request)'])
    rep = cbox(ax, 47.0, 14.5, 15.5, 'UserRepository', [], ['+paginate() / find()', '+create() / update()', '+syncOrganizations()'])
    arrow(ax, (aut['x'], 9.5), (log['right'], 9.5), '«registra»', dy=0.9)
    txt(ax, 0.5, 4.0, 'Os cálculos ficam centralizados no backend: dashboard, indicadores, relatórios e metas\nusam as mesmas classes. Todo acesso a dados usa PDO com prepared statements.', fs=FS - 0.6, va='center')
    save(fig, 'class_services.png')

def seq(name, parts, msgs):
    n = len(parts)
    h_in = (8 + sum(7.6 if m[-1] == 'F' else (4.8 if m[0] == m[1] else 3.4) for m in msgs)) / 10.0
    fig, ax = canvas(6.3, h_in)
    H = h_in * 10
    xs = [4.5 + i * (54.0 / (n - 1)) for i in range(n)]
    top = H - 0.8
    for (t, sub), x in zip(parts, xs):
        ax.add_patch(Rectangle((x - 4.2, top - 3.4), 8.4, 3.4, fc=HEAD, ec=INK, lw=0.8))
        txt(ax, x, top - 1.2, t, ha='center', weight='bold', fs=FS - 0.7)
        txt(ax, x, top - 2.5, sub, ha='center', fs=FS - 1.6, color='#444')
        ax.plot([x, x], [top - 3.4, 0.5], color='#777', lw=0.7, ls=(0, (4, 3)))
    y = top - 6.2
    for m in msgs:
        frame = m[-1] == 'F'
        a, b, label, dashed = m[0], m[1], m[2], m[3]
        if frame:
            y -= 2.2
            ax.add_patch(Rectangle((xs[2] - 5.5, y - 2.6), 62.5 - xs[2] + 5.5, 5.0, fc='none', ec='#555', lw=0.7))
            ax.add_patch(Rectangle((xs[2] - 5.5, y + 0.6), 5.0, 1.8, fc=GREY, ec='#555', lw=0.7))
            txt(ax, xs[2] - 5.1, y + 1.5, 'alt', fs=FS - 1, weight='bold')
            y -= 0.9
        x1, x2 = xs[a], xs[b]
        if a == b:
            ax.plot([x1, x1 + 3.5, x1 + 3.5], [y, y, y - 1.6], color=INK, lw=0.8)
            ax.annotate('', xy=(x1 + 0.1, y - 1.6), xytext=(x1 + 3.5, y - 1.6), arrowprops=dict(arrowstyle='->', lw=0.8, color=INK))
            txt(ax, x1 + 4.2, y - 0.8, label, fs=FS - 0.9)
        else:
            ax.annotate('', xy=(x2, y), xytext=(x1, y), arrowprops=dict(arrowstyle='->', lw=0.8, color=INK, ls='--' if dashed else '-', shrinkA=0, shrinkB=0))
            if x2 > x1:
                txt(ax, x1 + 0.7, y + 0.9, label, fs=FS - 0.9, ha='left')
            else:
                txt(ax, x1 - 0.7, y + 0.9, label, fs=FS - 0.9, ha='right')
        y -= 5.4 if frame else (4.8 if a == b else 3.4)
    save(fig, name)

def sequences():
    seq('seq_water.png', [('Cliente', 'web / curl'), ('Router', ''), ('Middleware', 'Auth + CSRF'), ('Controller', 'Water…Ctrl'), ('Validator', ''), ('UnitConverter', ''), ('MySQL', '')],
        [(0, 1, 'POST /api/v1/water-consumption (JSON + cookie + X-CSRF-Token)', False, ''),
         (1, 2, 'handle(request)', False, ''),
         (2, 6, 'SELECT usuário ativo e organizações', False, ''),
         (2, 2, 'hash_equals(token CSRF)', False, ''),
         (2, 0, '[token inválido ou sem sessão] 403 / 401', True, 'F'),
         (2, 3, '[válido] store(request)', False, ''),
         (3, 4, 'validate(body, rules)', False, ''),
         (3, 5, 'waterToM3(quantity, unit)', False, ''),
         (3, 6, 'INSERT water_consumption (prepared) + activity_logs', False, ''),
         (3, 0, '201 {success: true, data: {id}}', True, '')])
    seq('seq_dash.png', [('Cliente', 'web / curl'), ('Router', ''), ('Middleware', 'Auth'), ('DashboardCtrl', ''), ('IndicatorService', ''), ('Calc', ''), ('MySQL', '')],
        [(0, 1, 'GET /api/v1/dashboard/summary?organization_id&from&to', False, ''),
         (1, 2, 'handle(request)', False, ''),
         (2, 3, 'summary(request)', False, ''),
         (3, 3, 'PeriodFilter::from() + OrgScope', False, ''),
         (3, 4, 'consumption() / waste() / goals()', False, ''),
         (4, 6, 'SELECT SUM(...) GROUP BY mês', False, ''),
         (4, 5, 'variation() / percent() / goalProgress()', False, ''),
         (3, 0, '200 {água, energia, resíduos, reciclagem, metas}', True, '')])
    seq('seq_login.png', [('Cliente', 'web / curl'), ('Router', ''), ('AuthController', ''), ('AuthService', ''), ('MySQL', '')],
        [(0, 1, 'POST /api/v1/auth/login {email, password}', False, ''),
         (1, 2, 'login(request)', False, ''),
         (2, 3, 'login(email, password)', False, ''),
         (3, 4, 'tentativas recentes + SELECT usuário', False, ''),
         (3, 3, 'password_verify() · session_regenerate_id()', False, ''),
         (3, 0, '200 {user, csrf_token} + cookie ECOSESSID', True, '')])

if __name__ == '__main__':
    usecase(); classes_core(); classes_ctrl(); classes_services(); sequences(); er(); arch()
    print('ok')
