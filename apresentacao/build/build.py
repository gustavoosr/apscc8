# -*- coding: utf-8 -*-
import re, sys, os, math
sys.path.insert(0, '.')
from docx import Document
from docx.shared import Pt, Cm, RGBColor, Emu
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK, WD_TAB_ALIGNMENT, WD_TAB_LEADER
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.enum.section import WD_ORIENT
from docx.oxml.ns import qn
from docx.oxml import OxmlElement
from PIL import Image, ImageFont
import content2
from content1 import C

ROOT = '/home/gustavorocha/projects/aps8cc'
OUT = ROOT + '/apresentacao/EcoManager - Trabalho APS 7o-8o CC.docx'
FONT = 'Arial'

# ------------------------------------------------------------------ dados do grupo
INSTITUICAO = '[NOME DA INSTITUIÇÃO DE ENSINO]'
ALUNOS = [('[Nome completo do aluno 1]', '[RA]'), ('[Nome completo do aluno 2]', '[RA]'), ('[Nome completo do aluno 3]', '[RA]')]
CIDADE_ANO = '[Cidade] – GO, 2026'

# ------------------------------------------------------------------ código-fonte
CODE_FILES = [
    'routes/api.php', 'app/Core/Router.php', 'app/Middleware/AuthMiddleware.php', 'app/Middleware/CsrfMiddleware.php',
    'app/Core/Response.php', 'app/Core/OrgResourceController.php',
    'app/Controllers/WaterConsumptionController.php', 'app/Helpers/Calc.php',
    'app/Services/AuthService.php',
]

BIB = [
 'ASSOCIAÇÃO BRASILEIRA DE NORMAS TÉCNICAS. **ABNT NBR ISO 14001:2015**: sistemas da gestão ambiental – requisitos com orientações para uso. Rio de Janeiro: ABNT, 2015.',
 'BARTH, A. **RFC 6265**: HTTP State Management Mechanism. Internet Engineering Task Force (IETF), 2011.',
 'BOOCH, G.; RUMBAUGH, J.; JACOBSON, I. **UML**: guia do usuário. 2. ed. Rio de Janeiro: Elsevier, 2006.',
 'BRASIL. **Lei nº 6.938, de 31 de agosto de 1981**. Dispõe sobre a Política Nacional do Meio Ambiente. Brasília, DF, 1981.',
 'BRASIL. **Lei nº 9.433, de 8 de janeiro de 1997**. Institui a Política Nacional de Recursos Hídricos. Brasília, DF, 1997.',
 'BRASIL. **Lei nº 12.305, de 2 de agosto de 2010**. Institui a Política Nacional de Resíduos Sólidos. Brasília, DF, 2010.',
 'BRAY, T. (ed.). **RFC 8259**: The JavaScript Object Notation (JSON) Data Interchange Format. Internet Engineering Task Force (IETF), 2017.',
 'COMISSÃO MUNDIAL SOBRE MEIO AMBIENTE E DESENVOLVIMENTO (CMMAD). **Nosso futuro comum**. 2. ed. Rio de Janeiro: Fundação Getulio Vargas, 1991.',
 'FIELDING, R. T. **Architectural styles and the design of network-based software architectures**. 2000. Tese (Doutorado em Ciência da Informação e da Computação) – University of California, Irvine, 2000.',
 'FIELDING, R.; NOTTINGHAM, M.; RESCHKE, J. (ed.). **RFC 9110**: HTTP Semantics. Internet Engineering Task Force (IETF), 2022.',
 'FOWLER, M. **Patterns of enterprise application architecture**. Boston: Addison-Wesley, 2002.',
 'FOWLER, M. **UML essencial**: um breve guia para a linguagem-padrão de modelagem de objetos. 3. ed. Porto Alegre: Bookman, 2005.',
 'GOIÁS. **Lei nº 13.123, de 1997**. Dispõe sobre a Política Estadual de Recursos Hídricos. Goiânia, GO, 1997.',
 'GOIÁS. **Lei nº 14.248, de 2002**. Dispõe sobre a Política Estadual de Resíduos Sólidos. Goiânia, GO, 2002.',
 'INTERNATIONAL ORGANIZATION FOR STANDARDIZATION. **ISO 14031:2021**: environmental management – environmental performance evaluation – guidelines. Geneva: ISO, 2021.',
 'ORACLE. **MySQL 8.4 Reference Manual**. Disponível em: https://dev.mysql.com/doc/. Acesso em: 2026.',
 'OWASP FOUNDATION. **OWASP Top 10:2021**: the ten most critical web application security risks. 2021.',
 'PHP DOCUMENTATION GROUP. **PHP Manual**. Disponível em: https://www.php.net/manual/. Acesso em: 2026.',
 'PHP-FIG. **PSR-4**: autoloader. Disponível em: https://www.php-fig.org/psr/psr-4/. Acesso em: 2026.',
 'RICHARDSON, L.; RUBY, S. **RESTful web services**. Sebastopol: O’Reilly Media, 2007.',
 'SILBERSCHATZ, A.; KORTH, H. F.; SUDARSHAN, S. **Sistema de banco de dados**. 6. ed. Rio de Janeiro: Elsevier, 2012.',
 'SOMMERVILLE, I. **Engenharia de software**. 9. ed. São Paulo: Pearson Prentice Hall, 2011.',
 'TANENBAUM, A. S.; VAN STEEN, M. **Sistemas distribuídos**: princípios e paradigmas. 2. ed. São Paulo: Pearson Prentice Hall, 2007.',
 'WORLD WIDE WEB CONSORTIUM (W3C). **SOAP Version 1.2 Part 1**: messaging framework. W3C Recommendation, 2003.',
 'WORLD WIDE WEB CONSORTIUM (W3C). **Web Services Architecture**. W3C Working Group Note, 2004.',
]

# ================================================================== helpers de formatação
def set_fonts(rpr_parent_el, name=FONT):
    rpr = rpr_parent_el.get_or_add_rPr()
    rf = rpr.get_or_add_rFonts()
    for a in list(rf.attrib):
        del rf.attrib[a]
    for a in ('ascii', 'hAnsi', 'cs', 'eastAsia'):
        rf.set(qn('w:' + a), name)

def style_setup(doc):
    st = doc.styles
    n = st['Normal']
    set_fonts(n.element, FONT)
    n.font.size = Pt(12); n.font.color.rgb = RGBColor(0, 0, 0)
    pf = n.paragraph_format
    pf.line_spacing = 1.5; pf.space_after = Pt(0); pf.space_before = Pt(0)
    pf.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    pf.first_line_indent = Cm(1.25)
    pf.widow_control = True
    for name, size, bold, ital, before, after, brk in (('Heading 1', 12, True, False, 0, 12, True), ('Heading 2', 12, True, False, 12, 6, False), ('Heading 3', 12, True, True, 6, 4, False)):
        s = st[name]
        set_fonts(s.element, FONT)
        s.font.size = Pt(size); s.font.bold = bold; s.font.italic = ital; s.font.color.rgb = RGBColor(0, 0, 0)
        f = s.paragraph_format
        f.line_spacing = 1.5; f.space_before = Pt(before); f.space_after = Pt(after)
        f.keep_with_next = True; f.first_line_indent = Cm(0); f.alignment = WD_ALIGN_PARAGRAPH.LEFT
        f.page_break_before = brk
    b = st['List Bullet']
    set_fonts(b.element, FONT); b.font.size = Pt(12)
    b.paragraph_format.line_spacing = 1.5; b.paragraph_format.first_line_indent = Cm(-0.63)
    b.paragraph_format.left_indent = Cm(1.9); b.paragraph_format.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY

TOKEN = re.compile(r'(\*\*.+?\*\*|`.+?`|\*[^*\s][^*]*?\*)')
def add_runs(par, text, size=None, bold=False, italic=False):
    for part in TOKEN.split(text):
        if not part:
            continue
        if part.startswith('**') and part.endswith('**'):
            r = par.add_run(part[2:-2]); r.bold = True; r.italic = italic
        elif part.startswith('`') and part.endswith('`'):
            r = par.add_run(part[1:-1]); r.font.name = 'Courier New'
            set_fonts(r._element, 'Courier New'); r.font.size = Pt((size or 12) - 1.5); r.bold = bold
        elif part.startswith('*') and part.endswith('*') and len(part) > 2:
            r = par.add_run(part[1:-1]); r.italic = True; r.bold = bold
        else:
            r = par.add_run(part); r.bold = bold; r.italic = italic
        if size and not (part.startswith('`')):
            r.font.size = Pt(size)

def fld(par, instr, text='1'):
    def el(tag, **attrs):
        e = OxmlElement(tag)
        for k, v in attrs.items():
            e.set(qn('w:' + k), v)
        return e
    r = par.add_run(); r._r.append(el('w:fldChar', fldCharType='begin'))
    r = par.add_run(); it = el('w:instrText'); it.set(qn('xml:space'), 'preserve'); it.text = instr; r._r.append(it)
    r = par.add_run(); r._r.append(el('w:fldChar', fldCharType='separate'))
    r = par.add_run(text)
    r = par.add_run(); r._r.append(el('w:fldChar', fldCharType='end'))

def shade(cell, hexcolor):
    tcPr = cell._tc.get_or_add_tcPr()
    sh = OxmlElement('w:shd'); sh.set(qn('w:val'), 'clear'); sh.set(qn('w:color'), 'auto'); sh.set(qn('w:fill'), hexcolor)
    tcPr.append(sh)

def cell_margins(table, top=40, bottom=40, left=90, right=90):
    tblPr = table._tbl.tblPr
    m = OxmlElement('w:tblCellMar')
    for k, v in (('top', top), ('left', left), ('bottom', bottom), ('right', right)):
        e = OxmlElement('w:' + k); e.set(qn('w:w'), str(v)); e.set(qn('w:type'), 'dxa'); m.append(e)
    look = tblPr.find(qn('w:tblLook'))
    if look is not None:
        look.addprevious(m)
    else:
        tblPr.append(m)

def para(doc, text='', style=None, align=None, size=None, bold=False, italic=False, indent=None, before=None, after=None, spacing=None, keep_next=False):
    p = doc.add_paragraph(style=style)
    if text:
        add_runs(p, text, size=size, bold=bold, italic=italic)
    pf = p.paragraph_format
    if align is not None: pf.alignment = align
    if indent is not None: pf.first_line_indent = indent
    if before is not None: pf.space_before = Pt(before)
    if after is not None: pf.space_after = Pt(after)
    if spacing is not None: pf.line_spacing = spacing
    if keep_next: pf.keep_with_next = True
    return p

def add_table(doc, cap, head, rows, widths, fs, bold_first):
    c = para(doc, cap, align=WD_ALIGN_PARAGRAPH.CENTER, size=10, bold=True, indent=Cm(0), spacing=1.0, before=8, after=4, keep_next=True)
    t = doc.add_table(rows=1, cols=len(head))
    t.style = 'Table Grid'; t.alignment = WD_TABLE_ALIGNMENT.CENTER; t.autofit = False
    cell_margins(t)
    def fill(cell, text, bold=False, w=None):
        cell.width = Cm(w)
        p = cell.paragraphs[0]
        pf = p.paragraph_format
        pf.first_line_indent = Cm(0); pf.line_spacing = 1.0; pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
        add_runs(p, text, size=fs, bold=bold)
    hdr = t.rows[0]
    for i, h in enumerate(head):
        fill(hdr.cells[i], h, True, widths[i]); shade(hdr.cells[i], 'D5E8DC')
    trPr = hdr._tr.get_or_add_trPr(); e = OxmlElement('w:tblHeader'); e.set(qn('w:val'), 'true'); trPr.append(e)
    for row in rows:
        cells = t.add_row().cells
        for i, v in enumerate(row):
            fill(cells[i], v, bold=(bold_first and i == 0), w=widths[i])
    for r in t.rows:
        trPr = r._tr.get_or_add_trPr(); e = OxmlElement('w:cantSplit'); e.set(qn('w:val'), 'true'); trPr.append(e)
    # largura total e grade
    tblW = t._tbl.tblPr.find(qn('w:tblW'))
    if tblW is None:
        tblW = OxmlElement('w:tblW'); t._tbl.tblPr.append(tblW)
    tblW.set(qn('w:type'), 'dxa'); tblW.set(qn('w:w'), str(int(sum(widths) * 567)))
    for i, gc in enumerate(t._tbl.tblGrid.findall(qn('w:gridCol'))):
        gc.set(qn('w:w'), str(int(widths[i] * 567)))
    para(doc, '', spacing=1.0, after=6, indent=Cm(0))

# ================================================================== estimador de páginas
FONT_REG = None
def _fonts():
    global FONT_REG
    if FONT_REG is None:
        import matplotlib
        d = os.path.join(os.path.dirname(matplotlib.__file__), 'mpl-data/fonts/ttf/')
        FONT_REG = {'r': d + 'DejaVuSans.ttf', 'b': d + 'DejaVuSans-Bold.ttf', 'm': d + 'DejaVuSansMono.ttf'}
    return FONT_REG
_fcache = {}
def _font(kind, size):
    k = (kind, size)
    if k not in _fcache:
        _fcache[k] = ImageFont.truetype(_fonts()[kind], int(size * 10))
    return _fcache[k]
ARIAL = 0.90   # razão aproximada de largura Arial / DejaVu Sans
def text_lines(text, size, width_pt, first_indent=0.0, kind='r', ratio=ARIAL):
    text = re.sub(r'\*\*|`|(?<!\w)\*|\*(?!\w)', '', text)
    f = _font(kind, size)
    words = text.split(' ')
    lines, cur, curw, avail = 1, 0.0, 0.0, width_pt - first_indent
    space = f.getlength(' ') / 10 * ratio
    for w in words:
        wl = f.getlength(w) / 10 * ratio
        add = wl if cur == 0 else wl + space
        if cur + add > avail and cur > 0:
            lines += 1; cur = wl; avail = width_pt
        else:
            cur += add
    return lines

PAGE_H = 700.0          # altura útil (pt)
TEXT_W = 453.5          # 16 cm
def estimate(items):
    """Retorna dict seção -> páginas estimadas (fracionário)."""
    res, sec, h = {}, None, 0.0
    LH = 20.7
    def close():
        if sec is not None:
            res[sec] = h
    for it in items:
        k = it[0]
        if k == 'h1':
            close(); sec = it[1]; h = LH + 12
        elif k == 'h2':
            h += 12 + LH + 6
        elif k == 'h3':
            h += 6 + LH + 4
        elif k == 'p':
            h += text_lines(it[1], 12, TEXT_W, 35.4) * LH
        elif k == 'bul':
            for b in it[1]:
                h += text_lines(b, 12, TEXT_W - 54, 0) * LH
        elif k == 'fig':
            im = Image.open('fig/' + it[1]); ratio = im.height / im.width
            h += it[3] / 2.54 * 72 * ratio + 6 + 12 * 1.15 + 6 + 14
        elif k == 'tbl':
            _, cap, head, rows, widths, fs, bf = it
            h += 8 + 11.5 + 4
            for r in [head] + rows:
                mx = 1
                for i, v in enumerate(r):
                    wpt = widths[i] / 2.54 * 72 - 9
                    mx = max(mx, text_lines(v, fs, wpt, 0, 'b' if (bf and i == 0) or r is head else 'r'))
                h += mx * fs * 1.15 + 4
            h += 18
        elif k == 'raw':
            h += it[1]
    close()
    return res

def code_lines(path):
    src = open(os.path.join(ROOT, path), encoding='utf-8').read().replace('\t', '    ').rstrip('\n').split('\n')
    return src

# ================================================================== montagem do documento
def build():
    doc = Document()
    style_setup(doc)
    sec = doc.sections[0]
    sec.page_width = Cm(21.0); sec.page_height = Cm(29.7)
    sec.left_margin = Cm(2.5); sec.right_margin = Cm(2.5); sec.top_margin = Cm(2.5); sec.bottom_margin = Cm(2.5)
    sec.different_first_page_header_footer = True
    fp = sec.footer.paragraphs[0]; fp.alignment = WD_ALIGN_PARAGRAPH.CENTER; fp.paragraph_format.first_line_indent = Cm(0)
    fld(fp, 'PAGE', '2')
    for r in fp.runs:
        r.font.size = Pt(10); r.font.name = FONT
    doc.core_properties.title = 'EcoManager – Web Services em Gestão Ambiental no Estado de Goiás'
    doc.core_properties.subject = 'Atividades Práticas Supervisionadas – 7º/8º Ciência da Computação'
    doc.core_properties.author = ''

    ctr = WD_ALIGN_PARAGRAPH.CENTER
    # ---------------------------------------------------------------- CAPA
    para(doc, INSTITUICAO, align=ctr, bold=True, indent=Cm(0), before=0)
    para(doc, 'CURSO DE CIÊNCIA DA COMPUTAÇÃO – 7º/8º PERÍODOS', align=ctr, bold=True, indent=Cm(0))
    para(doc, 'ATIVIDADES PRÁTICAS SUPERVISIONADAS (APS)', align=ctr, indent=Cm(0), before=6)
    para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0))
    para(doc, 'DESENVOLVIMENTO DE UMA APLICAÇÃO UTILIZANDO WEB SERVICES EM GESTÃO AMBIENTAL NO ESTADO DE GOIÁS', align=ctr, bold=True, indent=Cm(0), before=18)
    para(doc, 'EcoManager – Sistema Web de Monitoramento e Gestão de Indicadores Ambientais', align=ctr, italic=True, indent=Cm(0), before=12)
    para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0))
    para(doc, 'ALUNOS DO GRUPO', align=ctr, bold=True, indent=Cm(0), before=24)
    for n, ra in ALUNOS:
        para(doc, f'{n} – RA: {ra}', align=ctr, indent=Cm(0))
    para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0)); para(doc, '', indent=Cm(0))
    para(doc, 'Professor supervisor: [Nome do professor]', align=ctr, indent=Cm(0), before=24)
    p = para(doc, CIDADE_ANO, align=ctr, indent=Cm(0), before=60)

    # ---------------------------------------------------------------- ÍNDICE (campo TOC pré-preenchido)
    # estimativa de páginas por seção para pré-preencher
    items = list(C)
    sums = estimate(items)
    # relatório de código
    allcode = []
    for f in CODE_FILES:
        allcode.append((f, code_lines(f)))
    code_h = 0.0
    for f, ls in allcode:
        code_h += 14 + 6
        for l in ls:
            code_h += max(1, math.ceil(max(1, len(l)) / 92.0)) * 9.2
    bib_h = sum(text_lines(re.sub(r'\*\*', '', b), 12, TEXT_W, 0) * 20.7 * 0.5 + 6 for b in BIB) * 1.0
    pages = {}
    cur = 3  # capa=1, índice=2, seção 1 começa na 3
    order = list(sums.keys())
    for k in order:
        n = max(1, math.ceil(sums[k] * 1.04 / PAGE_H))
        pages[k] = (cur, n); cur += n
    nc = max(1, math.ceil(code_h / PAGE_H)); pages['7 RELATÓRIO COM AS LINHAS DE CÓDIGO DO PROGRAMA'] = (cur, nc); cur += nc
    pages['8 BIBLIOGRAFIA'] = (cur, 2); cur += 2
    pages['9 FICHA DE ATIVIDADES PRÁTICAS SUPERVISIONADAS'] = (cur, 1)

    doc.add_page_break()
    para(doc, 'ÍNDICE', align=ctr, bold=True, indent=Cm(0), after=12, keep_next=True)
    entries = []
    cp = None
    for it in items:
        if it[0] in ('h1', 'h2', 'h3'):
            if it[0] == 'h1':
                cp = pages[it[1]][0]
            entries.append((int(it[0][1]), it[1], cp))
    entries.insert(len(entries), (1, '7 RELATÓRIO COM AS LINHAS DE CÓDIGO DO PROGRAMA', pages['7 RELATÓRIO COM AS LINHAS DE CÓDIGO DO PROGRAMA'][0]))
    entries.append((1, '8 BIBLIOGRAFIA', pages['8 BIBLIOGRAFIA'][0]))
    entries.append((1, '9 FICHA DE ATIVIDADES PRÁTICAS SUPERVISIONADAS', pages['9 FICHA DE ATIVIDADES PRÁTICAS SUPERVISIONADAS'][0]))
    # só níveis 1 e 2 no índice impresso
    ents = [e for e in entries if e[0] == 1]
    first = True
    for i, (lvl, text, pg) in enumerate(ents):
        pp = doc.add_paragraph()
        pf = pp.paragraph_format
        pf.first_line_indent = Cm(0); pf.left_indent = Cm(0 if lvl == 1 else 0.8); pf.line_spacing = 1.15; pf.space_after = Pt(2)
        pf.alignment = WD_ALIGN_PARAGRAPH.LEFT
        pf.tab_stops.add_tab_stop(Cm(16.0), WD_TAB_ALIGNMENT.RIGHT, WD_TAB_LEADER.DOTS)
        if first:
            for typ in ('begin',):
                r = pp.add_run(); fc = OxmlElement('w:fldChar'); fc.set(qn('w:fldCharType'), 'begin'); r._r.append(fc)
            r = pp.add_run(); it_ = OxmlElement('w:instrText'); it_.set(qn('xml:space'), 'preserve'); it_.text = ' TOC \\o "1-2" \\h \\z \\u '; r._r.append(it_)
            r = pp.add_run(); fc = OxmlElement('w:fldChar'); fc.set(qn('w:fldCharType'), 'separate'); r._r.append(fc)
            first = False
        r = pp.add_run(text); r.bold = (lvl == 1); r.font.size = Pt(11 if lvl == 2 else 11.5)
        r = pp.add_run('\t' + str(pg)); r.font.size = Pt(11)
        if i == len(ents) - 1:
            r = pp.add_run(); fc = OxmlElement('w:fldChar'); fc.set(qn('w:fldCharType'), 'end'); r._r.append(fc)

    # ---------------------------------------------------------------- CORPO
    for it in items:
        k = it[0]
        if k == 'h1': doc.add_heading(it[1], 1)
        elif k == 'h2': doc.add_heading(it[1], 2)
        elif k == 'h3': doc.add_heading(it[1], 3)
        elif k == 'p': para(doc, it[1])
        elif k == 'bul':
            for b in it[1]:
                pp = doc.add_paragraph(style='List Bullet'); add_runs(pp, b)
        elif k == 'fig':
            _, f, cap, w = it
            pp = doc.add_paragraph(); pf = pp.paragraph_format
            pf.alignment = ctr; pf.first_line_indent = Cm(0); pf.line_spacing = 1.0; pf.space_before = Pt(6); pf.space_after = Pt(3); pf.keep_with_next = True
            pp.add_run().add_picture('fig/' + f, width=Cm(w))
            para(doc, cap, align=ctr, size=10, indent=Cm(0), spacing=1.0, after=8, bold=True)
        elif k == 'tbl':
            add_table(doc, *it[1:])

    # ---------------------------------------------------------------- 7 CÓDIGO
    doc.add_heading('7 RELATÓRIO COM AS LINHAS DE CÓDIGO DO PROGRAMA', 1)
    para(doc, 'Este relatório reproduz os trechos centrais do Web Service: as rotas da API, o roteador, os *middleware* de autenticação e CSRF, o padrão de resposta JSON, o controlador genérico de recursos por organização, um controlador concreto (consumo de água), as funções de cálculo e o serviço de autenticação. O código completo está no repositório https://github.com/gustavoosr/apscc8. Fonte: Courier New, tamanho 8, espaçamento simples.', size=11, spacing=1.15, after=6)
    for f, ls in allcode:
        pt = para(doc, 'Arquivo: ' + f, size=9, bold=True, indent=Cm(0), spacing=1.0, before=6, after=2, align=WD_ALIGN_PARAGRAPH.LEFT, keep_next=True)
        pc = doc.add_paragraph(); pf = pc.paragraph_format
        pf.first_line_indent = Cm(0); pf.line_spacing = 1.0; pf.alignment = WD_ALIGN_PARAGRAPH.LEFT; pf.space_after = Pt(4)
        for i, l in enumerate(ls):
            r = pc.add_run(l if l else ' '); r.font.name = 'Courier New'; set_fonts(r._element, 'Courier New'); r.font.size = Pt(8)
            if i < len(ls) - 1: r.add_break()

    # ---------------------------------------------------------------- 8 BIBLIOGRAFIA
    doc.add_heading('8 BIBLIOGRAFIA', 1)
    for b in BIB:
        pp = para(doc, b, align=WD_ALIGN_PARAGRAPH.LEFT, indent=Cm(0), spacing=1.0, after=10)

    # ---------------------------------------------------------------- 9 FICHA
    doc.add_heading('9 FICHA DE ATIVIDADES PRÁTICAS SUPERVISIONADAS', 1)
    for lab in ('Curso: Ciência da Computação – 7º/8º períodos', 'Tema: Desenvolvimento de uma aplicação utilizando Web Services em gestão ambiental no estado de Goiás (EcoManager)', 'Professor supervisor: [Nome do professor]'):
        para(doc, lab, align=WD_ALIGN_PARAGRAPH.LEFT, indent=Cm(0), spacing=1.15, after=2, size=11)
    for n, ra in ALUNOS:
        para(doc, f'Aluno: {n}    RA: {ra}', align=WD_ALIGN_PARAGRAPH.LEFT, indent=Cm(0), spacing=1.15, after=2, size=11)
    para(doc, '', indent=Cm(0), spacing=1.0, after=4)
    rows = [
        ['1', '', 'Planejamento: análise de requisitos, arquitetura, modelo de dados e endpoints', '', ''],
        ['2', '', 'Estrutura inicial: autoload, roteamento, configuração e tratamento de erros', '', ''],
        ['3', '', 'Banco de dados: migrações, restrições, índices e seeds', '', ''],
        ['4', '', 'API REST: autenticação, usuários, organizações, setores, consumo, resíduos, metas, indicadores e dashboard', '', ''],
        ['5', '', 'Interface web: login, layout, dashboard, formulários e integração com a API', '', ''],
        ['6', '', 'Relatórios e exportação (CSV e impressão)', '', ''],
        ['7', '', 'Testes de integração, correções e documentação', '', ''],
        ['8', '', 'Elaboração do trabalho escrito e preparação da apresentação', '', ''],
        ['9', '', '', '', ''], ['10', '', '', '', ''],
    ]
    add_table(doc, 'Registro cronológico das atividades', ['Nº', 'Data', 'Atividade desenvolvida', 'Carga horária', 'Visto do professor'], rows, [1.0, 2.2, 7.6, 2.3, 2.9], 10, False)
    para(doc, 'Data: ____/____/______          Assinatura do professor supervisor: ______________________________', align=WD_ALIGN_PARAGRAPH.LEFT, indent=Cm(0), spacing=1.15, before=10, size=11)

    # atualizar campos ao abrir (índice)
    settings = doc.settings.element
    uf = OxmlElement('w:updateFields'); uf.set(qn('w:val'), 'true')
    anchor = None
    for tag in ('hdrShapeDefaults', 'footnotePr', 'endnotePr', 'compat', 'docVars', 'rsids', 'mathPr', 'attachedSchema', 'themeFontLang', 'clrSchemeMapping', 'doNotIncludeSubdocsInStats', 'shapeDefaults', 'decimalSymbol', 'listSeparator'):
        anchor = settings.find(qn('w:' + tag))
        if anchor is not None:
            break
    if anchor is not None: anchor.addprevious(uf)
    else: settings.append(uf)
    z = settings.find(qn('w:zoom'))
    if z is not None and z.get(qn('w:percent')) is None: z.set(qn('w:percent'), '100')
    doc.save(OUT)

    # relatório de estimativa
    print('Estimativa de páginas por seção (Arial 12, 1,5):')
    lim = {'1': (1, 2), '2': (2, 4), '3': (4, 8), '4': (5, 15), '5': (3, 8)}
    for k in order:
        print('  %-70s %.1f pág.' % (k[:70], sums[k] * 1.04 / PAGE_H))
    print('  %-70s %.1f pág.' % ('7 código', code_h / PAGE_H))
    print('  linhas de código:', sum(len(l) for _, l in allcode), ' | páginas totais estimadas:', cur)

if __name__ == '__main__':
    build()
