# EcoManager

Sistema web de monitoramento e gestão de indicadores ambientais (água, energia, resíduos e metas), desenvolvido como trabalho acadêmico de Ciência da Computação para demonstrar **Web Services / API REST** aliados a conceitos de gestão ambiental.

> A família **ISO 14000** (em especial a **ISO 14001**) é usada apenas como *referência conceitual* (política/objetivos, metas, monitoramento e melhoria). O EcoManager **não certifica** nem atesta conformidade com a norma.

## Tecnologias
PHP 8.x (sem frameworks, sem Composer) · MySQL 8 (PDO) · API REST/JSON · Bootstrap 5.3 · Chart.js · Font Awesome · JavaScript puro (Fetch API).

## Instalação

1. **Banco de dados** (como administrador do MySQL):
   ```sql
   CREATE DATABASE ecomanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'ecomanager'@'localhost' IDENTIFIED BY 'SUA_SENHA';
   GRANT ALL ON ecomanager.* TO 'ecomanager'@'localhost';
   ```
2. **Configuração**: `cp .env.example .env` e ajuste `DB_*`. O `.env` nunca é versionado.
3. **Migrações e dados de demonstração**:
   ```bash
   php database/migrate.php --seed          # aplica migrações pendentes + seeds
   php database/migrate.php --fresh --seed  # recria tudo do zero (apaga dados!)
   ```
4. **Servidor de desenvolvimento**:
   ```bash
   php -S 127.0.0.1:8080 -t public public/index.php
   ```
   Acesse http://127.0.0.1:8080.

### Apache / Nginx
O *document root* deve ser a pasta `public/`. Apache: `mod_rewrite` habilitado (já há `public/.htaccess`). Nginx: `try_files $uri /index.php?$query_string;`. As pastas `app/`, `config/`, `storage/` e `database/` ficam fora da raiz pública.

## Usuários de demonstração (seed, senha `Eco@12345`)
| Perfil | E-mail |
|---|---|
| Administrador | admin@ecomanager.local |
| Gestor | gestor@ecomanager.local |
| Operador | operador@ecomanager.local |

Todos os dados do seed têm `is_demo = 1` (organização "Indústria Verde Ltda (exemplo)"). **Troque as senhas em qualquer ambiente real.**

## Perfis
- **Administrador**: tudo, incluindo usuários e todas as organizações.
- **Gestor**: registros, setores, metas, tipos de resíduos e relatórios das suas organizações.
- **Operador**: cria e consulta registros de água/energia/resíduos; edita só os que criou; não exclui.

## Testes
Com o servidor rodando e o seed aplicado:
```bash
php tests/api_test.php
```
Cobre autenticação, CSRF, permissões, isolamento entre organizações, validações, conversões de unidade, cálculos de variação, reciclagem e progresso de metas (redução × aumento), paginação e bloqueio de tentativas de login. **Atenção:** o teste cria e remove registros de teste; use apenas em base de desenvolvimento.

## Documentação
- [docs/architecture.md](docs/architecture.md) · [docs/database.md](docs/database.md) · [docs/api.md](docs/api.md)
