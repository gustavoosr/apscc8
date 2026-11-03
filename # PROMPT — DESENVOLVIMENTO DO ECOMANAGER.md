# PROMPT — DESENVOLVIMENTO DO ECOMANAGER

Atue como um engenheiro de software sênior, arquiteto de sistemas e desenvolvedor full-stack especializado em PHP, MySQL, APIs REST e aplicações web.

Sua missão é projetar e desenvolver uma aplicação web chamada **EcoManager — Sistema Web de Monitoramento e Gestão de Indicadores Ambientais**.

O projeto será desenvolvido como trabalho acadêmico de graduação em Ciência da Computação e deverá demonstrar, principalmente, a aplicação prática de conceitos de Web Services, APIs REST, banco de dados e gestão ambiental, utilizando como referência os princípios da família ISO 14000, especialmente a ISO 14001.

O sistema deve ser funcional, organizado, seguro, documentado e visualmente profissional.

---

# 1. TECNOLOGIAS OBRIGATÓRIAS

Utilize as seguintes tecnologias:

**Backend**

- PHP 8.5.
- MySQL 8.0 ou superior.
- PDO para comunicação com o banco de dados.
- API REST própria.
- Comunicação HTTP.
- Troca de dados no formato JSON.
- Sessões PHP para autenticação da interface administrativa.

**Frontend**

- HTML5.
- CSS3.
- JavaScript puro (Vanilla JS).
- Bootstrap 5.3.
- Chart.js para gráficos.
- Font Awesome para ícones.

**Ambiente**

- Apache ou Nginx.
- Compatibilidade com Linux e Windows.
- Git para versionamento.

Não utilize Laravel, Symfony ou outros frameworks PHP.

Não utilize Composer ou NPM como dependências obrigatórias. Prefira soluções nativas e bibliotecas disponibilizadas por CDN quando necessário.

---

# 2. OBJETIVO DO SISTEMA

O EcoManager será uma aplicação web destinada a pequenas e médias empresas que desejam registrar, monitorar e analisar informações relacionadas ao seu desempenho ambiental.

A aplicação deverá permitir:

- Gerenciar organizações.
- Gerenciar setores.
- Registrar consumo de água.
- Registrar consumo de energia elétrica.
- Registrar geração de resíduos.
- Registrar destinação de resíduos.
- Criar objetivos e metas ambientais.
- Calcular indicadores ambientais.
- Acompanhar o cumprimento de metas.
- Visualizar informações em dashboards.
- Consultar históricos e gerar relatórios.

O sistema deverá transformar dados registrados pelos usuários em informações úteis para o acompanhamento ambiental da organização.

IMPORTANTE: o sistema não deverá afirmar que certifica uma empresa ou garante sua conformidade com a ISO 14001. A norma será utilizada como referência conceitual para as funcionalidades.

---

# 3. ARQUITETURA DO SISTEMA

Desenvolva uma arquitetura modular, separando claramente:

1. Interface web.
2. API REST.
3. Regras de negócio.
4. Camada de acesso a dados.
5. Banco de dados.

A interface deverá consumir a API REST por meio de requisições HTTP utilizando JavaScript Fetch API.

A API será responsável por validar, processar, armazenar e disponibilizar os dados.

Organize o backend de maneira que os módulos possam evoluir independentemente.

Estrutura sugerida:

EcoManager/
│
├── app/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── Middleware/
│   ├── Validators/
│   └── Helpers/
│
├── config/
│   ├── database.php
│   └── app.php
│
├── database/
│   ├── migrations/
│   └── seeds/
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── storage/
│   └── logs/
│
├── docs/
│   ├── architecture.md
│   ├── database.md
│   └── api.md
│
├── .env.example
├── .gitignore
└── README.md

Você pode ajustar essa estrutura caso identifique uma organização melhor, desde que mantenha a separação de responsabilidades.

Implemente:

- Autoload próprio seguindo PSR-4.
- Roteamento centralizado.
- Tratamento padronizado de exceções.
- Respostas JSON padronizadas.
- Configuração centralizada.
- Variáveis de ambiente para credenciais.
- Migrações numeradas para criação e atualização do banco.

Não permita que a interface acesse diretamente o banco de dados.

---

# 4. IDENTIDADE VISUAL

Crie uma interface moderna, profissional e intuitiva.

O sistema deve transmitir conceitos de sustentabilidade, tecnologia e gestão ambiental.

Diretrizes visuais:

- Nome: EcoManager.
- Identidade visual relacionada à sustentabilidade.
- Tons predominantes de verde, branco e cinza.
- Layout administrativo moderno.
- Sidebar lateral.
- Cabeçalho com identificação do usuário.
- Cards com indicadores.
- Gráficos interativos.
- Tabelas organizadas.
- Formulários intuitivos.
- Ícones consistentes.
- Feedback visual para operações.
- Layout responsivo para desktop, tablet e celular.

Evite uma aparência genérica de template administrativo antigo.

Utilize componentes visuais consistentes em todas as páginas.

Crie uma tela de login profissional e um dashboard como página inicial após a autenticação.

---

# 5. MÓDULOS FUNCIONAIS

## 5.1. Autenticação e usuários

Implemente:

- Login.
- Logout.
- Cadastro de usuários por administrador.
- Recuperação de senha preparada para futura integração.
- Controle de sessão.
- Hash de senhas com password_hash().
- Verificação com password_verify().
- Controle de permissões.

Perfis iniciais:

ADMINISTRADOR:
Acesso completo ao sistema e gerenciamento de usuários.

GESTOR:
Acesso aos registros ambientais, metas, indicadores e relatórios.

OPERADOR:
Permissão para cadastrar e consultar registros ambientais, conforme suas permissões.

O sistema deverá utilizar controle de acesso no backend, não apenas ocultar funcionalidades na interface.

## 5.2. Organizações e setores

Permitir:

- Cadastro de organizações.
- Nome e identificação da organização.
- Dados básicos de contato.
- Cadastro de setores.
- Associação dos setores à organização.
- Edição e desativação de setores.

Considere que uma organização poderá possuir vários setores.

## 5.3. Monitoramento de água

Permitir:

- Registrar consumo de água.
- Informar quantidade em litros ou metros cúbicos.
- Informar período de referência.
- Associar o registro a um setor.
- Informar observações.
- Consultar histórico.
- Editar registros.
- Excluir registros com confirmação.

O sistema deverá armazenar os valores em uma unidade padronizada, preferencialmente m³, realizando conversões quando necessário.

## 5.4. Monitoramento de energia

Permitir:

- Registrar consumo de energia elétrica em kWh.
- Associar o registro a um setor.
- Informar período de referência.
- Consultar histórico.
- Comparar períodos.
- Calcular variação percentual.

## 5.5. Gestão de resíduos

Permitir:

- Cadastro de categorias de resíduos.
- Cadastro de tipos de resíduos.
- Registro da quantidade gerada.
- Associação a um setor.
- Registro da data ou período.
- Registro da destinação.

Destinações iniciais:

- Reciclagem.
- Reutilização.
- Compostagem.
- Aterro sanitário.
- Tratamento especializado.
- Outra destinação.

Permitir consultar:

- Quantidade total gerada.
- Quantidade reciclada.
- Quantidade reutilizada.
- Quantidade compostada.
- Quantidade destinada a outras formas.

Calcular o percentual de resíduos reciclados em relação ao total gerado.

Não classifique automaticamente toda destinação diferente de reciclagem como inadequada. A adequação depende do tipo de resíduo e de critérios específicos.

## 5.6. Objetivos e metas ambientais

Permitir:

- Criar objetivos ambientais.
- Definir indicadores relacionados.
- Definir valores de referência.
- Definir valores desejados.
- Definir prazo.
- Acompanhar progresso.
- Identificar metas atingidas, em andamento ou atrasadas.

Exemplos:

OBJETIVO: Reduzir o consumo de água.

META: Reduzir o consumo em 10%.

INDICADOR: Consumo mensal de água.

OBJETIVO: Aumentar a reciclagem.

META: Atingir 40% de resíduos destinados à reciclagem.

O cálculo do progresso deverá considerar corretamente a direção da meta.

Para metas de redução, quanto menor o consumo, melhor.

Para metas de aumento, quanto maior o indicador, melhor.

Não utilize uma única fórmula indiscriminadamente para todos os tipos de metas.

## 5.7. Indicadores ambientais

Crie um módulo responsável por calcular indicadores a partir dos registros armazenados.

Indicadores iniciais:

ÁGUA:

- Consumo total.
- Consumo mensal.
- Variação percentual.
- Histórico de consumo.

ENERGIA:

- Consumo total.
- Consumo mensal.
- Variação percentual.
- Histórico de consumo.

RESÍDUOS:

- Quantidade total gerada.
- Quantidade destinada à reciclagem.
- Percentual reciclado.
- Distribuição por destinação.

METAS:

- Total de metas.
- Metas atingidas.
- Metas em andamento.
- Metas atrasadas.
- Percentual de progresso.

Os cálculos deverão ser realizados preferencialmente no backend, garantindo consistência entre diferentes telas e consultas.

Trate corretamente divisões por zero, ausência de registros e períodos sem dados.

## 5.8. Dashboard

Desenvolva um dashboard com:

Cards:

- Consumo de água.
- Consumo de energia.
- Total de resíduos.
- Taxa de reciclagem.
- Metas atingidas.

Gráficos:

- Evolução mensal do consumo de água.
- Evolução mensal do consumo de energia.
- Geração de resíduos por período.
- Distribuição dos resíduos por destinação.
- Evolução das metas ambientais.

Filtros:

- Período.
- Setor.
- Organização.

Os dados dos gráficos deverão vir da API REST.

Não utilize valores fictícios no dashboard após a implementação do banco de dados. Utilize dados de demonstração apenas por meio de seeds identificados como exemplos.

## 5.9. Relatórios

Permitir:

- Consultar indicadores por período.
- Filtrar informações por setor.
- Consultar históricos.
- Gerar relatórios de consumo.
- Gerar relatórios de resíduos.
- Gerar relatórios de metas.

Prepare a estrutura para exportação em PDF e CSV.

---

# 6. WEB SERVICES — API REST

A API REST é um dos principais requisitos acadêmicos do projeto.

Implemente endpoints seguindo convenções REST.

Grupos iniciais:

/api/v1/auth
/api/v1/users
/api/v1/organizations
/api/v1/sectors
/api/v1/water-consumption
/api/v1/energy-consumption
/api/v1/waste
/api/v1/waste-types
/api/v1/waste-destinations
/api/v1/environmental-objectives
/api/v1/environmental-goals
/api/v1/indicators
/api/v1/dashboard
/api/v1/reports

Utilize os métodos HTTP corretamente:

GET: consultar.
POST: cadastrar.
PUT/PATCH: atualizar.
DELETE: excluir.

Padronize as respostas.

Exemplo de sucesso:

{
  "success": true,
  "message": "Registro cadastrado com sucesso.",
  "data": {
    "id": 1
  }
}

Exemplo de erro:

{
  "success": false,
  "message": "Não foi possível processar a solicitação.",
  "errors": []
}

Utilize códigos HTTP apropriados:

- 200: sucesso.
- 201: criado.
- 204: operação concluída sem conteúdo.
- 400: requisição inválida.
- 401: não autenticado.
- 403: sem permissão.
- 404: recurso não encontrado.
- 422: erro de validação.
- 500: erro interno.

Implemente:

- Validação dos dados recebidos.
- Paginação.
- Filtros.
- Ordenação.
- Controle de autenticação.
- Controle de permissões.
- Tratamento de exceções.
- Proteção contra SQL Injection.
- Proteção contra requisições indevidas.

Documente os endpoints com exemplos de requisições e respostas.

---

# 7. BANCO DE DADOS

Utilize MySQL 8 com InnoDB.

Configure:

- Chaves primárias.
- Chaves estrangeiras.
- Índices.
- Restrições de integridade.
- Datas de criação e atualização.
- Relacionamentos consistentes.
- Exclusão lógica quando apropriado.

Modele inicialmente as seguintes entidades:

- organizations
- users
- organization_users
- sectors
- water_consumption
- energy_consumption
- waste_types
- waste_destinations
- waste_records
- environmental_objectives
- environmental_goals
- environmental_goal_progress
- activity_logs

Você poderá propor tabelas adicionais quando houver justificativa técnica.

Evite duplicação desnecessária de informações.

A estrutura deverá permitir que uma organização tenha vários usuários e setores.

Garanta que um usuário não consiga consultar ou modificar dados de organizações às quais não possui acesso.

Crie:

- Script de criação do banco.
- Migrações versionadas.
- Seeds de demonstração.
- Documentação do modelo relacional.

---

# 8. SEGURANÇA

A segurança deverá ser considerada desde o início.

Implemente:

- PDO com prepared statements.
- Senhas protegidas com password_hash().
- Proteção contra SQL Injection.
- Proteção contra XSS.
- Proteção CSRF nas operações realizadas por sessão.
- Validação de entrada no backend.
- Escape de saída HTML.
- Controle de permissões.
- Sessões com configurações seguras.
- Proteção contra acesso direto a arquivos internos.
- Variáveis de ambiente para credenciais.
- Logs sem exposição de senhas, tokens ou dados sensíveis.

Não armazene credenciais diretamente no código-fonte.

Não confie em dados enviados pelo frontend.

Não permita acesso direto ao diretório de configuração ou aos arquivos de armazenamento.

---

# 9. REGRAS IMPORTANTES DE DESENVOLVIMENTO

1. Antes de implementar, analise os requisitos e proponha a arquitetura.
2. Não comece criando todas as telas sem definir a estrutura do banco e da API.
3. Desenvolva por módulos.
4. Mantenha o código organizado e reutilizável.
5. Utilize nomes de classes, métodos e variáveis em inglês.
6. Utilize textos da interface em português brasileiro.
7. Documente decisões técnicas relevantes.
8. Não crie funcionalidades fictícias apenas para preencher telas.
9. Não deixe botões sem funcionamento.
10. Não utilize dados estáticos quando a funcionalidade exigir consulta real ao banco.
11. Não duplique regras de negócio entre frontend e backend.
12. Não ignore erros de execução.
13. Não considere uma funcionalidade concluída sem testá-la.
14. Priorize simplicidade, legibilidade e manutenção.
15. Evite abstrações desnecessárias e complexidade excessiva.

---

# 10. ETAPAS DE EXECUÇÃO

Trabalhe seguindo as seguintes etapas:

ETAPA 1 — PLANEJAMENTO

- Analisar os requisitos.
- Definir arquitetura.
- Definir estrutura de diretórios.
- Definir entidades e relacionamentos.
- Definir endpoints.
- Identificar regras de negócio.
- Apresentar o planejamento antes de implementar.

ETAPA 2 — ESTRUTURA INICIAL

- Criar estrutura do projeto.
- Configurar PHP.
- Configurar conexão MySQL.
- Criar autoload.
- Implementar roteamento.
- Configurar variáveis de ambiente.
- Criar tratamento de erros.

ETAPA 3 — BANCO DE DADOS

- Criar migrações.
- Criar tabelas.
- Definir relacionamentos.
- Criar índices.
- Criar seeds.

ETAPA 4 — API REST

- Implementar autenticação.
- Implementar usuários.
- Implementar organizações.
- Implementar setores.
- Implementar consumo.
- Implementar resíduos.
- Implementar metas.
- Implementar indicadores.
- Implementar dashboard.

ETAPA 5 — INTERFACE WEB

- Criar login.
- Criar layout administrativo.
- Criar dashboard.
- Criar formulários.
- Criar tabelas.
- Implementar integração com a API.
- Implementar gráficos.

ETAPA 6 — RELATÓRIOS

- Criar filtros.
- Criar relatórios.
- Implementar exportação.

ETAPA 7 — TESTES E DOCUMENTAÇÃO

- Testar endpoints.
- Testar regras de negócio.
- Testar permissões.
- Testar segurança.
- Corrigir erros.
- Documentar instalação.
- Documentar API.
- Documentar arquitetura.

---

# 11. PRIMEIRA AÇÃO

Neste primeiro momento, NÃO implemente o sistema inteiro.

Quero que você:

1. Analise cuidadosamente todos os requisitos.
2. Identifique possíveis inconsistências.
3. Proponha uma arquitetura adequada ao porte acadêmico do projeto.
4. Apresente a estrutura de diretórios.
5. Proponha o modelo inicial do banco de dados.
6. Apresente os principais relacionamentos.
7. Liste os endpoints da API REST.
8. Identifique as principais regras de negócio.
9. Proponha uma ordem de implementação.

Apresente o planejamento de maneira organizada e aguarde minha aprovação antes de iniciar a implementação.

Durante todo o desenvolvimento, mantenha o foco em entregar um sistema funcional, com qualidade de código, interface profissional e documentação suficiente para demonstrar academicamente a utilização de Web Services e os conceitos de gestão ambiental.
