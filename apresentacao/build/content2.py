# -*- coding: utf-8 -*-
"""Conteúdo do trabalho — parte 2: plano de desenvolvimento, projeto e formação."""
from content1 import C, h1, h2, h3, p, bul, fig, tbl

# =============================================================== 4
h1('4 PLANO DE DESENVOLVIMENTO DA APLICAÇÃO (ELEMENTOS E FERRAMENTAS)')
p('O plano de desenvolvimento define o que será utilizado e como o trabalho será conduzido: a metodologia, os requisitos a atender, a arquitetura pretendida, as ferramentas e tecnologias escolhidas, os padrões adotados para a API, a estratégia de segurança e de testes, o cronograma e os riscos. O detalhamento da estrutura final do sistema (diagramas e modelo de dados) é apresentado na seção 5.')
h2('4.1 Metodologia de desenvolvimento')
p('Adotou-se um processo **iterativo e incremental**, no qual o sistema foi construído em módulos que evoluíram em ciclos curtos de análise, implementação e teste, em vez de uma única entrega ao final. Essa abordagem é adequada a projetos acadêmicos de duração limitada, pois permite validar cedo as decisões de maior risco (como o modelo de dados e o formato da API) e ajustar o escopo conforme o andamento (SOMMERVILLE, 2011). Antes de qualquer codificação, foram definidos a arquitetura, o modelo de banco de dados, a lista de endpoints e as regras de negócio. Em seguida, o desenvolvimento seguiu a ordem: estrutura inicial, banco de dados, API, interface, relatórios, testes e documentação.')
p('O controle de versão foi feito com o **Git**, com o repositório hospedado no **GitHub** (https://github.com/gustavoosr/apscc8), de modo a registrar a evolução do código, permitir a recuperação de versões anteriores e facilitar o trabalho em grupo. Informações sensíveis, como as credenciais do banco de dados, ficam em um arquivo de ambiente (`.env`) excluído do versionamento, e um arquivo modelo (`.env.example`) documenta as variáveis necessárias.')
h2('4.2 Levantamento de requisitos')
p('Os requisitos funcionais descrevem o que o sistema deve fazer. Os principais estão no Quadro 1.')
tbl('Quadro 1 – Requisitos funcionais', ['ID', 'Descrição'], [
 ['RF01', 'Autenticar usuários por e-mail e senha, mantendo sessão e permitindo o encerramento (logout).'],
 ['RF02', 'Gerenciar usuários e seus perfis (Administrador, Gestor e Operador); função restrita ao Administrador.'],
 ['RF03', 'Cadastrar e manter organizações e vincular usuários às organizações a que têm acesso.'],
 ['RF04', 'Cadastrar, editar e desativar setores, associados a uma organização.'],
 ['RF05', 'Registrar o consumo de água por setor e mês, em m³ ou litros, com conversão para unidade padrão.'],
 ['RF06', 'Registrar o consumo de energia elétrica (kWh) por setor e mês e comparar períodos com variação percentual.'],
 ['RF07', 'Registrar resíduos por tipo, setor, data, quantidade (kg ou t) e destinação.'],
 ['RF08', 'Manter o catálogo de categorias, tipos e destinações de resíduos.'],
 ['RF09', 'Cadastrar objetivos e metas ambientais, com direção (redução ou aumento), valor de referência, valor desejado e prazo.'],
 ['RF10', 'Calcular indicadores: totais, médias, variação percentual, taxa de reciclagem e distribuição por destinação.'],
 ['RF11', 'Avaliar metas: valor atual, progresso e situação (atingida, em andamento ou atrasada).'],
 ['RF12', 'Fornecer dados para o dashboard (cards e gráficos), filtráveis por organização, setor e período.'],
 ['RF13', 'Gerar relatórios de consumo, resíduos e metas em JSON e CSV.'],
 ['RF14', 'Oferecer paginação, filtros e ordenação nas listagens.'],
 ['RF15', 'Registrar trilha de auditoria das operações de escrita.'],
 ['RF16', 'Reservar o endpoint de recuperação de senha para futura integração com e-mail.'],
], [1.6, 14.4], bold_first=True)
p('Os requisitos não funcionais definem as qualidades esperadas do sistema (Quadro 2).')
tbl('Quadro 2 – Requisitos não funcionais', ['ID', 'Descrição'], [
 ['RNF01', 'Comunicação entre cliente e servidor por API REST sobre HTTP, com troca de dados em JSON.'],
 ['RNF02', 'Respostas padronizadas e uso correto dos códigos de status HTTP (200, 201, 204, 400, 401, 403, 404, 405, 409, 422, 429, 500, 501).'],
 ['RNF03', 'Segurança: comandos preparados, hash de senhas, proteção contra CSRF e XSS, validação de entrada e sessões seguras.'],
 ['RNF04', 'Controle de acesso por perfil e por organização aplicado no servidor.'],
 ['RNF05', 'Portabilidade entre Linux e Windows, com servidor Apache ou Nginx.'],
 ['RNF06', 'Manutenibilidade: arquitetura em camadas, autoload PSR-4 e módulos independentes.'],
 ['RNF07', 'Desempenho: índices nas consultas frequentes e paginação limitada a 100 itens por página.'],
 ['RNF08', 'Interface responsiva para computador, tablet e celular, em português brasileiro.'],
 ['RNF09', 'Testabilidade: roteiro automatizado de testes de integração dos serviços.'],
 ['RNF10', 'Documentação da arquitetura, do banco de dados, da API e da instalação.'],
], [1.8, 14.2], bold_first=True)
h2('4.3 Regras de negócio')
p('As regras de negócio, executadas no servidor, são as seguintes: **RN01**, os consumos são armazenados em unidade padrão (água em m³ e resíduos em kg), preservando o valor informado originalmente; **RN02**, a variação percentual é calculada em relação ao período anterior e retorna nulo, e não zero, quando não há base de comparação; **RN03**, a taxa de reciclagem é a razão entre a massa reciclada e a massa total gerada, nula quando não há resíduos registrados; **RN04**, o progresso de uma meta depende de sua direção; **RN05**, a situação da meta é atingida quando o progresso alcança 100%, atrasada quando o prazo vence sem atingimento e em andamento nos demais casos; **RN06**, um usuário só acessa dados das organizações às quais está vinculado; **RN07**, um setor com registros não pode ser excluído, apenas desativado; **RN08**, o Operador edita somente os registros que criou e não exclui registros; **RN09**, o login é bloqueado temporariamente após cinco tentativas malsucedidas em dez minutos; e **RN10**, nenhuma destinação de resíduos é classificada automaticamente como inadequada.')
h2('4.4 Arquitetura planejada')
p('A arquitetura foi planejada em quatro camadas (Figura 1): o cliente, que consome a API; o Web Service REST, composto por roteador, *middleware*, controladores e respostas padronizadas; a camada de regras de negócio, que concentra validação, cálculos de indicadores e conversões; e a persistência, com acesso ao MySQL por PDO. A separação garante que a interface nunca acesse o banco diretamente e que as regras de negócio não sejam duplicadas no cliente.')
fig('arch.png', 'Figura 1 – Arquitetura em camadas planejada para o EcoManager', 15.5)
h2('4.5 Ferramentas e tecnologias')
p('O Quadro 3 resume as ferramentas escolhidas e a justificativa de cada uma. Não foram utilizados frameworks PHP nem gerenciadores de dependências, de modo que os mecanismos centrais (roteamento, autoload, validação, tratamento de exceções) fossem implementados no projeto, o que contribui para a compreensão de como um Web Service funciona internamente.')
tbl('Quadro 3 – Ferramentas e tecnologias utilizadas', ['Item', 'Uso e justificativa'], [
 ['PHP 8.5', 'Linguagem do backend e do Web Service. Ampla adoção na web, execução em Apache/Nginx e recursos modernos (tipagem, exceções, `match`, `readonly`).'],
 ['MySQL 8.4 (InnoDB)', 'Banco de dados relacional com transações ACID, chaves estrangeiras, restrições CHECK e índices.'],
 ['PDO', 'Acesso ao banco com comandos preparados, prevenindo injeção de SQL.'],
 ['API REST / JSON', 'Estilo arquitetural dos Web Services, com recursos identificados por URI e versão `/api/v1`.'],
 ['Apache / Nginx', 'Servidores web de produção, com a pasta `public/` como raiz. Em desenvolvimento, o servidor embutido do PHP (`php -S`).'],
 ['HTML5, CSS3 e JavaScript', 'Aplicação cliente. A comunicação com a API usa a Fetch API, sem frameworks de interface.'],
 ['Bootstrap 5.3', 'Layout responsivo e componentes visuais (tabelas, formulários, modais).'],
 ['Chart.js 4.4', 'Gráficos interativos do dashboard, alimentados pelos dados da API.'],
 ['Font Awesome 6.5', 'Ícones da interface.'],
 ['Git e GitHub', 'Controle de versão e hospedagem do repositório do projeto.'],
 ['cURL e script de testes em PHP', 'Chamadas manuais aos serviços e roteiro automatizado com 63 verificações de integração.'],
 ['Diagramas UML e ER', 'Documentação visual do projeto: casos de uso, classes, sequência e modelo entidade-relacionamento.'],
], [4.0, 12.0], bold_first=True)
h2('4.6 Padrões e convenções da API')
p('Para garantir consistência, foram definidas as convenções a seguir. Todos os endpoints ficam sob o prefixo `/api/v1`, o que permite evoluir a API sem quebrar clientes existentes. Os nomes dos recursos são substantivos no plural ou no singular conforme o domínio (por exemplo, `/sectors` e `/waste`), e os métodos HTTP expressam a operação. As respostas de sucesso têm a forma `{"success": true, "message": "...", "data": {...}}`, acrescida de `meta` com os dados de paginação nas listagens; as de erro têm a forma `{"success": false, "message": "...", "errors": [...]}`, com a lista de campos inválidos em `errors`. As listagens aceitam `page`, `per_page`, `sort` (por exemplo, `-reference_month` para ordem decrescente) e filtros por recurso. As datas seguem o formato ISO 8601 (`AAAA-MM-DD`) e os meses o formato `AAAA-MM`.')
h2('4.7 Estratégia de segurança')
p('A segurança foi planejada em camadas. Na **autenticação**, as senhas são armazenadas com `password_hash()` e verificadas com `password_verify()`; o identificador de sessão é regenerado após o login e o cookie recebe os atributos `HttpOnly` e `SameSite=Lax`; após cinco tentativas falhas em dez minutos, o login é bloqueado por um período. Na **autorização**, um *middleware* verifica a sessão e carrega, a cada requisição, o usuário e suas organizações, e outro verifica o perfil exigido pela rota; além disso, todas as consultas de dados filtram pelas organizações do usuário. Na **integridade das requisições**, as operações de escrita exigem o token CSRF no cabeçalho `X-CSRF-Token`. Na **entrada e saída de dados**, todos os campos são validados no servidor, as consultas usam comandos preparados, a ordenação aceita apenas colunas de uma lista permitida e a saída HTML é escapada. Por fim, os logs não registram senhas, tokens nem o corpo das requisições, e os arquivos de configuração ficam fora da raiz pública do servidor web.')
h2('4.8 Estratégia de testes')
p('Os serviços foram testados em três níveis. Primeiro, os cálculos puros (variação, percentual e progresso de metas) foram isolados em uma classe sem acesso a banco, o que facilita sua verificação. Em segundo lugar, foi criado um roteiro automatizado de **testes de integração**, que executa requisições HTTP reais contra o servidor e verifica status, corpo e efeitos, abrangendo autenticação, CSRF, permissões por perfil, isolamento entre organizações, validação de dados, conversão de unidades, paginação, ordenação, tentativas de injeção, cálculo de indicadores e metas e bloqueio por excesso de tentativas de login. Por fim, foram realizados testes manuais com cURL e com a interface web, para conferir o comportamento observado pelo usuário.')
h2('4.9 Cronograma')
p('O Quadro 4 apresenta as etapas de execução e suas entregas, distribuídas em semanas.')
tbl('Quadro 4 – Cronograma de desenvolvimento', ['Etapa', 'Atividades', 'Entregas', 'Semana'], [
 ['1', 'Planejamento: análise de requisitos, arquitetura, modelo de dados e lista de endpoints', 'Plano e modelo aprovados', '1'],
 ['2', 'Estrutura inicial: autoload, roteamento, configuração, tratamento de erros', 'Esqueleto do Web Service', '2'],
 ['3', 'Banco de dados: migrações, restrições, índices e seeds de demonstração', 'Banco versionado', '3'],
 ['4', 'API REST: autenticação, usuários, organizações, setores, consumo, resíduos, metas, indicadores e dashboard', 'Endpoints funcionais', '4 a 5'],
 ['5', 'Interface web: login, layout, dashboard, formulários e tabelas integrados à API', 'Aplicação cliente', '6'],
 ['6', 'Relatórios e exportação CSV/impressão', 'Relatórios', '7'],
 ['7', 'Testes, correções e documentação (arquitetura, banco, API e este trabalho)', 'Sistema testado e documentado', '8'],
], [1.4, 8.2, 4.6, 1.8])
h2('4.10 Riscos e mitigação')
tbl('Quadro 5 – Principais riscos do projeto', ['Risco', 'Mitigação'], [
 ['Acesso indevido a dados de outra organização', 'Escopo por organização aplicado no servidor e coberto por testes automatizados.'],
 ['Cálculos inconsistentes entre telas', 'Cálculos centralizados em uma única classe de serviço, sem duplicação no cliente.'],
 ['Dados inválidos ou maliciosos', 'Validação rigorosa no servidor, comandos preparados e escape de saída.'],
 ['Divisão por zero ou ausência de dados', 'Funções de cálculo retornam nulo e a interface exibe "sem dados" em vez de zero.'],
 ['Perda de código ou de configuração', 'Versionamento em Git/GitHub e migrações para recriar o banco.'],
], [6.0, 10.0], bold_first=True)

h2('4.11 Ambiente de implantação e organização do projeto')
p('O código foi organizado de modo que apenas a pasta `public/` seja exposta pelo servidor web: nela ficam o ponto de entrada (`index.php`) e os arquivos estáticos (CSS, JavaScript e imagens). As demais pastas ficam fora da raiz pública: `app/` (núcleo, controladores, serviços, validadores e utilitários), `config/` (configurações lidas do ambiente), `database/` (migrações numeradas e seeds), `routes/` (definição das rotas da API e das páginas), `resources/views/` (páginas HTML), `storage/` (logs), `tests/` (roteiro de testes) e `docs/` (documentação técnica). A instalação consiste em criar o banco e um usuário dedicado, copiar `.env.example` para `.env` com as credenciais, executar `php database/migrate.php --seed` para criar as tabelas e os dados de demonstração e iniciar o servidor. Os dados de demonstração são identificados como exemplos (`is_demo`), para que nunca sejam confundidos com dados reais.')

# =============================================================== 5
h1('5 PROJETO (ESTRUTURA E MÓDULOS DO PROGRAMA)')
p('Esta seção apresenta o projeto do sistema: os casos de uso, a estrutura de classes do Web Service, a interação entre seus componentes nos principais cenários, o modelo de dados e o catálogo de serviços. O foco está no Web Service; a aplicação web é tratada como um cliente da API.')
h2('5.1 Diagrama e tabela de casos de uso')
p('O Web Service atende a três atores, que correspondem aos perfis do sistema. Como a permissão de cada perfil inclui a do anterior, o Gestor herda do Operador e o Administrador herda do Gestor. A autenticação (UC01) é pré-requisito dos demais casos de uso.')
fig('usecase.png', 'Figura 2 – Diagrama de casos de uso do EcoManager', 10.0)
tbl('Quadro 6 – Descrição dos casos de uso', ['Caso de uso', 'Atores', 'Fluxo principal', 'Exceções (HTTP)', 'Pós-condição'], [
 ['UC01 Autenticar usuário', 'Todos', 'Informa e-mail e senha; o serviço valida, abre a sessão e devolve o token CSRF.', 'Credenciais inválidas (401); excesso de tentativas (429); dados ausentes (422)', 'Sessão ativa e usuário identificado'],
 ['UC02 Gerenciar usuários', 'Administrador', 'Cria, consulta, altera, desativa e exclui usuários; define perfil e organizações.', 'E-mail duplicado (422); sem permissão (403); excluir a si mesmo (422)', 'Usuário persistido e acesso configurado'],
 ['UC03 Gerenciar organizações e setores', 'Gestor, Administrador', 'Cadastra e altera organizações (criação e exclusão só do Administrador) e setores.', 'Setor com registros não é excluído (409); organização alheia (404)', 'Organização ou setor atualizado'],
 ['UC04 Registrar consumo de água', 'Operador e acima', 'Informa setor, mês, quantidade e unidade; o serviço converte para m³ e grava.', 'Validação (422); setor de outra organização (422); organização sem acesso (403)', 'Registro de água criado'],
 ['UC05 Registrar consumo de energia', 'Operador e acima', 'Informa setor, mês e kWh; pode comparar períodos e obter a variação percentual.', 'Validação (422); sem base de comparação retorna variação nula', 'Registro de energia criado'],
 ['UC06 Registrar resíduos', 'Operador e acima', 'Informa setor, tipo, destinação, data e quantidade; o serviço converte para kg e grava.', 'Tipo ou destinação inválidos (422); alteração de registro alheio pelo Operador (403)', 'Registro de resíduo criado'],
 ['UC07 Gerenciar objetivos e metas', 'Gestor, Administrador', 'Cria objetivos e metas com direção, referência, alvo e prazo; consulta progresso e histórico.', 'Alvo incoerente com a direção (422); Operador (403)', 'Meta persistida e avaliável'],
 ['UC08 Consultar indicadores e dashboard', 'Operador e acima', 'Solicita indicadores e séries com filtros de organização, setor e período.', 'Período inválido (422); organização sem acesso (403)', 'Indicadores calculados e retornados'],
 ['UC09 Gerar relatórios', 'Gestor, Administrador', 'Solicita relatório de consumo, resíduos ou metas em JSON ou CSV.', 'Operador (403); sem dados retorna lista vazia', 'Relatório entregue'],
 ['UC10 Gerenciar catálogos de resíduos', 'Gestor, Administrador', 'Mantém categorias e tipos de resíduos; destinações são mantidas pelo Administrador.', 'Tipo em uso não é excluído (409); duplicidade (422)', 'Catálogo atualizado'],
], [2.9, 2.1, 4.8, 4.0, 2.2], fs=8.5, bold_first=True)
h2('5.2 Diagramas de classes do Web Service')
p('O Web Service foi organizado em classes de responsabilidade única. A Figura 3 mostra o núcleo: `Request` encapsula a requisição HTTP; o `Router` associa rota e método a um tratador e executa a cadeia de *middleware* (`AuthMiddleware`, `CsrfMiddleware` e `RoleMiddleware`); `Response` produz as respostas JSON padronizadas; `Session` e `Database` isolam a sessão e a conexão PDO; `Validator` valida a entrada; e `ExceptionHandler` converte as exceções da hierarquia `HttpException` em respostas com o status adequado.')
fig('class_core.png', 'Figura 3 – Diagrama de classes: núcleo e cadeia de processamento da requisição', 12.5)
p('A Figura 4 apresenta a hierarquia dos controladores. A classe abstrata `OrgResourceController` implementa o CRUD genérico dos recursos de uma organização (listagem paginada, consulta, criação, atualização e exclusão lógica), já com escopo por organização, perfis e auditoria; cada controlador concreto informa apenas suas regras de validação e particularidades, o que evita duplicação e padroniza a API. Os demais controladores estendem diretamente `BaseController`.')
fig('class_ctrl.png', 'Figura 4 – Diagrama de classes: hierarquia dos controladores', 12.5)
p('A Figura 5 mostra a camada de regras de negócio e os utilitários. `IndicatorService` concentra o cálculo dos indicadores e a avaliação das metas e utiliza `Calc`, que contém as fórmulas puras; `OrgScope` aplica o isolamento entre organizações; `PeriodFilter` interpreta os filtros de período; `UnitConverter` padroniza as unidades; `AuthService` executa o login; `ActivityLog` registra a auditoria; e `UserRepository` encapsula as consultas de usuários.')
fig('class_services.png', 'Figura 5 – Diagrama de classes: serviços, cálculos e utilitários', 12.5)
h2('5.3 Diagramas de sequência')
p('O primeiro cenário é o de autenticação (Figura 6). O cliente envia as credenciais ao endpoint de login; o serviço consulta as tentativas recentes e o usuário, verifica a senha com `password_verify()`, regenera o identificador de sessão e devolve os dados do usuário e o token CSRF, que o cliente deverá enviar nas próximas operações de escrita.')
fig('seq_login.png', 'Figura 6 – Diagrama de sequência: autenticação (POST /api/v1/auth/login)', 12.5)
p('O segundo cenário é o registro de consumo de água (Figura 7), que ilustra a cadeia completa do Web Service. A requisição passa pelo roteador e pelos *middleware*, que validam a sessão, carregam as organizações do usuário e conferem o token CSRF; em caso de falha, a resposta 401 ou 403 é devolvida imediatamente. Em seguida, o controlador valida o corpo, converte a unidade para m³, grava o registro com comandos preparados, registra a auditoria e responde 201.')
fig('seq_water.png', 'Figura 7 – Diagrama de sequência: registro de consumo de água (POST /api/v1/water-consumption)', 12.5)
p('O terceiro cenário é a consulta ao dashboard (Figura 8). O controlador interpreta os filtros, delega os cálculos ao `IndicatorService`, que executa consultas agregadas por mês e aplica as fórmulas de `Calc`, e devolve ao cliente os valores já prontos para exibição, sem que a interface precise recalculá-los.')
fig('seq_dash.png', 'Figura 8 – Diagrama de sequência: consulta ao dashboard (GET /api/v1/dashboard/summary)', 12.5)
h2('5.4 Modelo de dados')
p('O banco de dados possui 14 tabelas de negócio, mais tabelas de apoio (`schema_migrations`, `login_attempts` e `password_resets`). A Figura 9 apresenta o modelo entidade-relacionamento. Uma organização possui vários setores, objetivos e usuários (relação N:N por `organization_users`); os registros de consumo e de resíduos pertencem a um setor e a uma organização; e cada meta pertence a um objetivo, podendo restringir-se a um setor, e possui um histórico de progresso mensal. Chaves estrangeiras, índices por organização e período e restrições `CHECK` (quantidades não negativas e coerência entre datas e valores das metas) garantem a integridade.')
fig('er.png', 'Figura 9 – Modelo entidade-relacionamento (simplificado)', 10.5)
h2('5.5 Catálogo de serviços (endpoints)')
p('O Quadro 7 apresenta os principais recursos expostos pela API, todos sob o prefixo `/api/v1`. Os recursos de CRUD respondem a GET (lista e consulta), POST, PUT/PATCH e DELETE.')
tbl('Quadro 7 – Principais endpoints da API REST', ['Recurso', 'Operações', 'Perfis (escrita)'], [
 ['/auth/login, /auth/logout, /auth/me', 'POST, POST, GET; /auth/password/forgot responde 501', 'Todos'],
 ['/users', 'CRUD completo', 'Administrador'],
 ['/organizations', 'CRUD completo', 'Criar e excluir: Administrador; editar: Gestor'],
 ['/sectors', 'CRUD, desativação', 'Gestor e Administrador'],
 ['/water-consumption', 'CRUD; GET /compare (variação entre períodos)', 'Todos criam; Operador edita só o seu'],
 ['/energy-consumption', 'CRUD; GET /compare', 'Todos criam; Operador edita só o seu'],
 ['/waste', 'CRUD; GET /summary (totais e taxa de reciclagem)', 'Todos criam; Operador edita só o seu'],
 ['/waste-types e /waste-destinations', 'Consulta; escrita restrita', 'Gestor (tipos); Administrador (destinações)'],
 ['/environmental-objectives e /environmental-goals', 'CRUD; GET /{id}/progress (histórico da meta)', 'Gestor e Administrador'],
 ['/indicators/water, /energy, /waste, /goals', 'GET com filtros de organização, setor e período', 'Somente leitura'],
 ['/dashboard/summary e /dashboard/charts', 'GET (cards e séries dos gráficos)', 'Somente leitura'],
 ['/reports/consumption, /waste, /goals', 'GET em JSON ou CSV (`format=csv`)', 'Gestor e Administrador'],
], [6.0, 6.0, 4.0], fs=9, bold_first=True)
h2('5.6 Cálculo de indicadores e metas')
p('Os cálculos são implementados em `Calc` e `IndicatorService`. A **variação percentual** entre o valor atual (A) e o anterior (P) é (A − P) ÷ P × 100, retornando nulo quando P é zero ou inexistente. A **taxa de reciclagem** é a massa reciclada ÷ massa total × 100. O **progresso da meta** depende da direção: em metas de redução, é (referência − atual) ÷ (referência − alvo) × 100; em metas de aumento, é (atual − referência) ÷ (alvo − referência) × 100. O valor atual de uma meta é o do último mês com dados dentro de seu período; o progresso exibido é limitado ao intervalo de 0 a 100%, e o valor bruto é mantido em `progress_raw_pct`. Por exemplo, em uma meta de reduzir o consumo mensal de 100 para 50 kWh, um consumo atual de 75 kWh corresponde a 50% de progresso, enquanto um consumo de 120 kWh resulta em progresso bruto de −40%, exibido como 0%.')
h2('5.7 Aplicação cliente')
p('A aplicação cliente é composta por páginas HTML com Bootstrap que carregam os dados da API com a Fetch API. Um módulo genérico monta as telas de cadastro (tabela paginada, filtros, formulário modal e confirmação de exclusão) a partir de uma descrição de campos, e o dashboard apresenta cinco indicadores e cinco gráficos do Chart.js, alimentados por `/dashboard/summary` e `/dashboard/charts`. A tela de relatórios permite gerar, exportar em CSV e imprimir. Como as regras ficam no servidor, qualquer outro cliente, como um aplicativo móvel, poderia reutilizar a mesma API.')

# =============================================================== 6
h1('6 CONTRIBUIÇÃO PARA A FORMAÇÃO E INTERDISCIPLINARIDADE')
p('O desenvolvimento do EcoManager exigiu a aplicação integrada de conhecimentos adquiridos ao longo do curso e mostrou, na prática, como disciplinas aparentemente independentes se complementam na construção de um sistema real. Esta seção discute o efeito do trabalho na formação do grupo e a interdisciplinaridade envolvida.')
h2('6.1 Efeito na formação')
p('O trabalho consolidou a compreensão de como funcionam, por dentro, os Web Services. Ao implementar o roteador, a cadeia de *middleware*, a validação e o tratamento de erros sem utilizar frameworks, o grupo pôde entender o que normalmente fica oculto por essas ferramentas, como o ciclo da requisição HTTP, a semântica dos métodos e códigos de status e a importância de respostas padronizadas. Também foi possível exercitar a modelagem de dados relacional, a escrita de consultas agregadas, o uso de transações e restrições de integridade e a elaboração de diagramas UML como instrumento de projeto e documentação, e não apenas como exigência formal.')
p('A preocupação com segurança desde o início do projeto evidenciou que o controle de acesso, a validação de entradas e a proteção contra ataques como injeção de SQL, XSS e CSRF não são acréscimos opcionais, mas requisitos de qualidade. A criação de um roteiro automatizado de testes de integração mostrou o valor de verificar, de forma repetível, o comportamento do sistema, em especial as regras de permissão e de isolamento de dados. O uso de controle de versão e a documentação do trabalho, por sua vez, aproximaram a atividade das práticas profissionais da área.')
h2('6.2 Interdisciplinaridade')
p('A interdisciplinaridade manifesta-se em duas dimensões. A primeira é a integração entre áreas da computação: **programação e desenvolvimento web** (PHP, JavaScript, HTML e CSS), **banco de dados** (modelagem, normalização, SQL e índices), **engenharia de software** (requisitos, UML, arquitetura em camadas e testes), **redes de computadores e sistemas distribuídos** (modelo cliente-servidor, HTTP, serviços e interoperabilidade), **segurança da informação** (autenticação, autorização, criptografia de senhas e prevenção de ataques) e **algoritmos e matemática** (cálculo de variações, percentuais, médias e progresso de metas, com tratamento de casos limites como a divisão por zero).')
p('A segunda dimensão é a articulação da computação com outros campos do conhecimento. A **gestão ambiental** forneceu o domínio do problema: os conceitos de aspecto e impacto ambiental, objetivos e metas, indicadores de desempenho e melhoria contínua, além da hierarquia de gestão de resíduos. A **legislação** (políticas nacional e estadual de meio ambiente, recursos hídricos e resíduos sólidos) delimitou o que a organização precisa registrar e demonstrar. A **administração** contribuiu com as noções de partes interessadas, ciclo PDCA e planejamento, e a **ética e a responsabilidade socioambiental** orientaram decisões como a de não afirmar que o sistema certifica uma organização e a de não classificar automaticamente uma destinação de resíduos como inadequada. O trabalho evidencia que a tecnologia da informação é um instrumento para a sustentabilidade e que o profissional de computação precisa dialogar com outras áreas para construir soluções de valor.')
