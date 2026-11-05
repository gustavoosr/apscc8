# 3 FUNDAMENTOS E CONCEITOS GERAIS

Este capítulo apresenta a base teórica necessária para compreender o EcoManager. Parte-se do problema que o sistema atende, a gestão ambiental (ISO 14000), e chega-se aos elementos técnicos que o sustentam: algoritmos, banco de dados, sistemas distribuídos (web services) e engenharia de software. O conteúdo foi dimensionado para 8 páginas (Arial 12, espaçamento 1,5): algoritmos (1), banco de dados (1), sistemas distribuídos (3), engenharia de software (1) e gestão ambiental/ISO 14000 (2).

## 3.1 Algoritmos

Algoritmo é uma sequência finita e ordenada de passos, não ambígua, que leva à solução de um problema (ASCENCIO; CAMPOS, 2008). Forbellone e Eberspächer (2005) destacam que o algoritmo é a base da lógica de programação. Ele descreve *o que* fazer e em *que ordem*, independentemente da linguagem em que será escrito. Um algoritmo recebe **entradas**, as processa e produz **saídas**. Pode ser representado em linguagem natural, em fluxograma ou em pseudocódigo. Depois de validado, é codificado em uma linguagem de programação, como o PHP, usado neste trabalho.

Os autores citados descrevem os elementos básicos de qualquer algoritmo:

- **Variáveis e tipos de dados:** posições de memória nomeadas que guardam valores inteiros, reais, textos (caracteres) ou lógicos (verdadeiro/falso).
- **Operadores:** aritméticos (+, −, ×, ÷), relacionais (>, <, =) e lógicos (E, OU, NÃO).
- **Estruturas sequenciais:** os comandos são executados um após o outro.
- **Estruturas condicionais:** o fluxo se divide conforme uma condição (*se... então... senão*). Isso permite tratar casos distintos, como "existe valor-base?".
- **Estruturas de repetição:** um bloco se repete enquanto uma condição for verdadeira (*enquanto*, *para*). É o caso de somar vários lançamentos de consumo de um mesmo mês.
- **Estruturas de dados:** vetores e matrizes, que agrupam valores do mesmo tipo.
- **Modularização:** o problema é dividido em sub-rotinas (funções e procedimentos) reutilizáveis, o que reduz a repetição e facilita o teste.

Dois critérios medem a qualidade de um algoritmo: a **correção**, isto é, se produz o resultado esperado para qualquer entrada válida, e a **eficiência**, o tempo e a memória consumidos. Na prática, isso exige tratar casos-limite. Por exemplo, um cálculo de variação percentual é uma divisão e deve prever o divisor igual a zero, para não gerar erro.

**Relação com o trabalho:** o EcoManager implementa algoritmos para totalizar consumos por período, calcular a variação percentual entre meses e determinar o progresso de metas. A seção 5.7 mostra as fórmulas e a seção 7, o código.

## 3.2 Banco de Dados

Um banco de dados é uma coleção organizada de dados inter-relacionados, que representa aspectos do mundo real e atende a um conjunto de aplicações (DATE, 2004; HEUSER, 2009). O **Sistema Gerenciador de Banco de Dados (SGBD)** é o software que armazena e recupera esses dados. Ele evita que cada aplicação mantenha seus próprios arquivos, o que causaria redundância e inconsistência. Entre suas funções estão o controle de concorrência, a recuperação de falhas, a segurança de acesso e a independência entre o dado e o programa que o usa.

O **modelo relacional**, proposto por Codd e detalhado por Date (2004), organiza os dados em **relações** (tabelas) formadas por linhas (tuplas) e colunas (atributos). Seus conceitos centrais são:

- **Chave primária:** identifica cada linha de forma única.
- **Chave estrangeira:** referencia a chave primária de outra tabela e materializa o relacionamento entre elas.
- **Integridade:** são as regras que mantêm os dados válidos, entre elas a integridade de entidade (chave primária não nula), a referencial (a chave estrangeira aponta para registro existente) e as restrições de domínio.
- **Normalização:** técnica que decompõe as tabelas para eliminar redundâncias e anomalias de inserção, alteração e exclusão (1ª, 2ª e 3ª formas normais).
- **SQL:** linguagem padrão do modelo, com comandos de definição (DDL: `CREATE`, `ALTER`), manipulação (DML: `SELECT`, `INSERT`, `UPDATE`, `DELETE`) e controle.
- **Transação:** conjunto de operações tratado como unidade indivisível, que respeita as propriedades **ACID** (atomicidade, consistência, isolamento e durabilidade).

Segundo Heuser (2009), o projeto de um banco de dados tem três níveis. Primeiro vem o **modelo conceitual**, independente de tecnologia, normalmente representado pelo diagrama entidade-relacionamento (ER). Depois, o **modelo lógico**, que o traduz para tabelas. Por fim, o **modelo físico**, que define tipos, índices e o SGBD específico.

**Relação com o trabalho:** o EcoManager usa o MySQL com o mecanismo InnoDB, que oferece transações ACID e chaves estrangeiras. O acesso é feito por consultas preparadas, o que protege contra injeção de SQL. O modelo ER do sistema está na seção 5.

## 3.3 Sistemas Distribuídos

### 3.3.1 Conceito e características

Tanenbaum e Van Steen (2007) definem um sistema distribuído como um conjunto de computadores independentes que se apresenta aos usuários como um sistema único e coerente. Marques e Guedes (1998) acrescentam que os componentes, ligados por uma rede, se comunicam apenas por troca de mensagens e coordenam suas ações para atingir um objetivo comum. Essa definição traz duas consequências. Não existe memória compartilhada, e a rede pode falhar ou atrasar. O sistema, portanto, precisa ser projetado para tolerar essas condições.

Os objetivos e desafios descritos por Tanenbaum e Van Steen (2007) são:

- **Compartilhamento de recursos:** dados e serviços ficam acessíveis a muitos usuários e aplicações.
- **Transparência:** o usuário não percebe onde o recurso está (acesso, localização, migração, replicação, falha).
- **Abertura:** interfaces e protocolos são padronizados e publicados, o que permite a interoperabilidade entre tecnologias diferentes.
- **Escalabilidade:** o sistema continua eficiente quando cresce o número de usuários, de recursos ou a distância geográfica.
- **Tolerância a falhas e concorrência:** o sistema deve operar apesar de falhas parciais e de acessos simultâneos aos mesmos dados.
- **Heterogeneidade e segurança:** é preciso integrar máquinas, linguagens e redes distintas e proteger as comunicações.

### 3.3.2 Modelo cliente-servidor e arquiteturas em camadas

A arquitetura mais difundida é a **cliente-servidor**. O cliente solicita um serviço e o servidor o processa e responde. Em aplicações corporativas, divide-se a lógica em **camadas**: apresentação (interface), aplicação ou negócio (regras) e dados (armazenamento). Cada camada pode ser alterada ou escalada separadamente (TANENBAUM; VAN STEEN, 2007). O EcoManager segue esse modelo: o navegador é o cliente, o PHP executa as regras de negócio no servidor e o MySQL guarda os dados.

### 3.3.3 Comunicação: do RPC à arquitetura orientada a serviços

A comunicação entre processos remotos se apoia em protocolos de rede, como TCP/IP, e em modelos de mais alto nível. O mais clássico é a **chamada de procedimento remoto (RPC)**, em que o cliente invoca uma função como se fosse local e o sistema cuida do envio da mensagem e do retorno do resultado (MARQUES; GUEDES, 1998). Dele derivaram os objetos distribuídos (CORBA, Java RMI) e, depois, a **Arquitetura Orientada a Serviços (SOA)**. Na SOA, as funcionalidades são oferecidas como **serviços** independentes de plataforma, com contrato bem definido e acoplamento fraco, o que facilita a integração entre sistemas (COULOURIS et al., 2013).

### 3.3.4 Web Services

Um **web service** é um serviço de software disponibilizado pela rede, normalmente pela Web, e acessado por meio de protocolos padronizados. Seu objetivo é a **interoperabilidade** entre aplicações escritas em linguagens e plataformas diferentes. Há duas abordagens principais:

- **SOAP/WSDL:** as mensagens são envelopes XML, e o serviço é descrito por um contrato formal (WSDL). É robusto e padronizado, mas verboso e mais complexo.
- **REST (*Representational State Transfer*):** estilo arquitetural proposto por Fielding (2000). Os recursos são identificados por URIs, manipulados por operações padronizadas do HTTP e representados em formatos como o JSON. É leve, simples e adequado à Web.

O REST é definido por restrições arquiteturais: **cliente-servidor**, **ausência de estado** entre requisições (cada requisição contém tudo o que o servidor precisa), **cache**, **interface uniforme** e **sistema em camadas**. Nesse estilo, o projeto da API parte dos **recursos** (substantivos, como `/consumo-agua`), e não de ações.

O **HTTP** é o protocolo de aplicação em que o REST se apoia. As operações mais usadas são `GET` (consultar), `POST` (criar), `PUT` ou `PATCH` (atualizar) e `DELETE` (remover), o que corresponde às quatro operações básicas de dados (CRUD). O resultado é indicado por **códigos de status**: 2xx para sucesso (200 OK, 201 Created), 4xx para erro do cliente (400, 401 não autenticado, 403 proibido, 404 não encontrado, 422 dados inválidos, 429 excesso de requisições) e 5xx para erro do servidor. O **JSON** (*JavaScript Object Notation*) é o formato de troca de dados: texto leve, legível e baseado em pares chave-valor, que as principais linguagens sabem ler e gerar. Para que a API seja previsível, o EcoManager padroniza suas respostas no formato `{success, message, data}`.

### 3.3.5 Segurança em serviços distribuídos

Como as mensagens trafegam por redes abertas, a segurança é requisito de projeto. Os conceitos principais são:

- **Autenticação e autorização:** verificar quem é o usuário e o que ele pode fazer, por exemplo por perfis (RBAC).
- **Confidencialidade e integridade:** são garantidas pelo HTTPS/TLS e pelo armazenamento de senhas com *hash* e *salt*.
- **Gerenciamento de sessão:** neste trabalho, uma sessão com *cookie* protegido.
- **Ameaças comuns:** a lista **OWASP** destaca a injeção de SQL, o XSS e o CSRF (requisição forjada entre sites). As defesas correspondentes são as consultas parametrizadas, o escape de saída e o *token* anti-CSRF.
- **Validação de entrada:** todo dado recebido deve ser verificado no servidor, pois a validação feita no cliente pode ser contornada.

### 3.3.6 Relação com o trabalho

O EcoManager expõe uma **API REST** na versão `/api/v1`. O navegador a consome via JavaScript, mas qualquer cliente HTTP (aplicativo móvel, outro sistema, Postman) poderia usá-la. Essa separação entre serviço e interface é o centro do projeto.

## 3.4 Engenharia de Software

Sommerville (2011) define a engenharia de software como a disciplina que trata de todos os aspectos da produção de software, da especificação inicial à manutenção depois da entrega. Pressman (2011) a descreve como a aplicação de uma abordagem sistemática, disciplinada e quantificável ao desenvolvimento, à operação e à manutenção do software. A necessidade surgiu da "crise do software" dos anos 1960, quando os projetos estouravam prazos e orçamentos e entregavam produtos de baixa qualidade.

As atividades fundamentais do processo, segundo os dois autores, são:

- **Especificação (comunicação e planejamento):** levantamento dos requisitos. Os **funcionais** descrevem o que o sistema faz. Os **não funcionais** descrevem restrições como segurança, desempenho e usabilidade. As **regras de negócio** vêm do domínio do problema.
- **Projeto (modelagem):** definição da arquitetura, dos dados e das interfaces.
- **Implementação (construção):** codificação e testes.
- **Validação (testes):** verificação de que o software atende aos requisitos, com testes de unidade, de integração e de sistema.
- **Evolução (manutenção):** correção e adaptação após a entrega.

Esses elementos se organizam em **modelos de processo**. O **cascata** executa as fases em sequência. O **incremental** entrega o sistema em partes sucessivas. Os **métodos ágeis** (Scrum, XP) trabalham em ciclos curtos, com feedback contínuo do cliente.

A **UML** (*Unified Modeling Language*) é a linguagem gráfica padrão para modelar sistemas orientados a objetos (SOMMERVILLE, 2011). Os diagramas usados neste trabalho são o de **casos de uso**, que mostra os atores e as funcionalidades; o de **classes**, que mostra a estrutura estática (classes, atributos, métodos e relações); e o de **sequência**, que mostra a troca de mensagens ao longo do tempo. Também se aplicam **padrões de projeto** e de arquitetura, soluções reconhecidas para problemas recorrentes, como o MVC e o *Front Controller*. Por fim, vale o princípio da **separação de responsabilidades** e a prática do **controle de versão** (Git), que registra o histórico de alterações e permite o trabalho em equipe.

**Relação com o trabalho:** o desenvolvimento foi incremental. Os requisitos foram levantados e modelados em UML, a arquitetura foi dividida em camadas, o código foi versionado com o Git e a API foi validada por testes automatizados.

## 3.5 Gestão Ambiental e a Série ISO 14000

### 3.5.1 Gestão ambiental

A **gestão ambiental** é o conjunto de políticas, programas e práticas com que uma organização planeja, controla e reduz os impactos de suas atividades sobre o meio ambiente (BARBIERI, 2011; DIAS, 2011). Antes tratada só como obrigação legal, ela passou a ser uma questão estratégica. Reduz custos, ao diminuir o desperdício de água e energia, evita multas, melhora a imagem da organização e atende às exigências de clientes e da sociedade. No Brasil, a base legal é a Política Nacional do Meio Ambiente (Lei nº 6.938/1981), complementada por normas estaduais e municipais.

Gerir o desempenho ambiental exige **medi-lo**. Por isso se usam **indicadores**, medidas quantitativas que resumem o comportamento de um aspecto ambiental ao longo do tempo. Exemplos são o consumo de água (m³), o consumo de energia (kWh) e a geração de resíduos (kg). Os indicadores permitem fixar **metas** e comparar resultados com um período-base, e é nisso que o EcoManager se apoia.

### 3.5.2 A série ISO 14000

A **ISO** (*International Organization for Standardization*) é uma organização internacional de normalização. Sua série **ISO 14000**, criada a partir da Conferência Rio-92 e da necessidade de padronizar a gestão ambiental, reúne normas voluntárias aplicáveis a organizações de qualquer porte e setor. No Brasil, a **ABNT** publica as versões equivalentes, as normas **ABNT NBR ISO 14000**. As principais são:

| Norma | Finalidade |
|---|---|
| **ISO 14001** | Requisitos do **Sistema de Gestão Ambiental (SGA)**; é a única da série passível de certificação |
| **ISO 14004** | Diretrizes para implementar e manter o SGA |
| **ISO 14031** | Diretrizes para a **avaliação do desempenho ambiental** (indicadores) |
| **ISO 14040/14044** | **Avaliação do ciclo de vida** de produtos |
| **ISO 14064** | Quantificação e verificação de gases de efeito estufa |

### 3.5.3 O SGA e o ciclo PDCA

A **ISO 14001** (ABNT, 2015) estrutura o SGA no ciclo **PDCA** (*Plan-Do-Check-Act*), de melhoria contínua:

1. **Planejar (Plan):** identificar os **aspectos ambientais** (elementos da atividade que interagem com o ambiente, como o consumo de água) e os **impactos** resultantes, definir a política ambiental, os requisitos legais, os objetivos e as metas.
2. **Fazer (Do):** implementar o planejado, com recursos, competência, comunicação e controle operacional.
3. **Verificar (Check):** monitorar e medir o desempenho, avaliar o cumprimento legal e realizar auditorias internas.
4. **Agir (Act):** analisar criticamente os resultados pela direção e tomar ações corretivas para melhorar.

A norma também exige o **compromisso da liderança**, a análise do **contexto** da organização e das **partes interessadas** (clientes, órgãos reguladores, comunidade, funcionários), a atenção ao **ciclo de vida** e a **informação documentada**, isto é, os registros que comprovam a conformidade.

### 3.5.4 Avaliação de desempenho ambiental (ISO 14031)

A ISO 14031 orienta a seleção de indicadores em duas categorias. Os **indicadores de desempenho ambiental (IDA)** abrangem os **indicadores de desempenho operacional** (consumo de insumos, geração de resíduos, emissões) e os **indicadores de condição ambiental** (qualidade do ar e da água no entorno). Os **indicadores de desempenho gerencial** medem os esforços da gestão, como treinamentos realizados ou o cumprimento de metas. Os indicadores devem ser mensuráveis, comparáveis ao longo do tempo e relevantes para os objetivos definidos.

### 3.5.5 Relação com o trabalho

O EcoManager apoia as etapas **Planejar** e **Verificar** do PDCA. Os **objetivos** e as **metas** correspondem ao planejamento. Os lançamentos de água, energia e resíduos formam os indicadores de desempenho operacional da ISO 14031. O painel, o comparativo entre períodos e os relatórios dão suporte ao monitoramento, e o registro de atividades (auditoria) fornece a informação documentada. O sistema não substitui a certificação ISO 14001, mas fornece os dados organizados que ela exige.

---

## REFERÊNCIAS DESTE CAPÍTULO

ASCENCIO, Ana Fernanda G.; CAMPOS, Edilene A. V. de. **Fundamentos da programação de computadores**: algoritmos, Pascal e C/C++. 2. ed. São Paulo: Prentice Hall, 2008. Disponível em: https://plataforma.bvirtual.com.br/Leitor/Publicacao/417/pdf/0. Acesso em: 09 nov. 2022.

ASSOCIAÇÃO BRASILEIRA DE NORMAS TÉCNICAS. **ABNT NBR ISO 14001**: sistemas de gestão ambiental: requisitos com orientações para uso. Rio de Janeiro: ABNT, 2015.

BARBIERI, José Carlos. **Gestão ambiental empresarial**: conceitos, modelos e instrumentos. 3. ed. São Paulo: Saraiva, 2011.

BRASIL. **Lei nº 6.938, de 31 de agosto de 1981**. Dispõe sobre a Política Nacional do Meio Ambiente. Brasília, DF: Presidência da República, 1981.

COULOURIS, George et al. **Sistemas distribuídos**: conceitos e projeto. 5. ed. Porto Alegre: Bookman, 2013.

DATE, C. J. **Introdução a sistemas de bancos de dados**. 8. ed. Rio de Janeiro: Elsevier, 2004.

DIAS, Reinaldo. **Gestão ambiental**: responsabilidade social e sustentabilidade. 2. ed. São Paulo: Atlas, 2011.

FIELDING, Roy Thomas. **Architectural styles and the design of network-based software architectures**. 2000. Tese (Doutorado em Ciência da Informação e da Computação) – University of California, Irvine, 2000.

FORBELLONE, André Luiz Villar; EBERSPÄCHER, Henri Frederico. **Lógica de programação**: a construção de algoritmos e estrutura de dados. São Paulo: Pearson, 2005. Disponível em: https://plataforma.bvirtual.com.br/Leitor/Publicacao/323/pdf/0. Acesso em: 09 nov. 2022.

HEUSER, Carlos Alberto. **Projeto de banco de dados**. 6. ed. Porto Alegre: Bookman, 2009. (Série Livros Didáticos Informática UFRGS, v. 4).

INTERNATIONAL ORGANIZATION FOR STANDARDIZATION. **ISO 14031**: environmental management: environmental performance evaluation: guidelines. Geneva: ISO, 2013.

MARQUES, José Alves; GUEDES, Paulo. **Tecnologia de sistemas distribuídos**. Lisboa: FCA, 1998.

PRESSMAN, Roger S. **Engenharia de software**: uma abordagem profissional. 7. ed. Porto Alegre: AMGH, 2011.

SOMMERVILLE, Ian. **Engenharia de software**. 9. ed. São Paulo: Pearson Prentice Hall, 2011.

TANENBAUM, Andrew S.; VAN STEEN, Maarten. **Sistemas distribuídos**: princípios e paradigmas. 2. ed. São Paulo: Pearson Prentice Hall, 2007.
