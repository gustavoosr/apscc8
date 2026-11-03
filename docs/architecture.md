# Arquitetura

```
Navegador (HTML + Bootstrap + JS/Fetch)
      │  HTTP + JSON
      ▼
public/index.php  →  Router  →  Middleware (Auth, CSRF, Role)
                                   │
                              Controllers  →  Validators
                                   │
                         Services (IndicatorService, AuthService)
                                   │
                         PDO (prepared statements)  →  MySQL 8
```

## Camadas
| Camada | Local | Responsabilidade |
|---|---|---|
| Interface | `resources/views`, `public/assets` | Renderiza páginas e consome a API via `fetch`. Não acessa o banco. |
| API REST | `routes/api.php`, `app/Controllers` | HTTP: entrada, saída JSON, códigos de status. |
| Regras de negócio | `app/Services`, `app/Helpers/Calc.php`, `UnitConverter` | Cálculo de indicadores, variação, progresso de metas, conversões. |
| Acesso a dados | PDO nos controllers/serviços (`UserRepository` para usuários) | Consultas parametrizadas. |
| Banco | `database/migrations` | Esquema versionado. |

## Decisões técnicas
- **Autoload PSR-4 próprio** (`App\` → `app/`), sem Composer.
- **Sessão + CSRF**: a API usa o cookie de sessão (`HttpOnly`, `SameSite=Lax`) e exige `X-CSRF-Token` em POST/PUT/PATCH/DELETE. O token é entregue no login e em `/auth/me`.
- **CRUD genérico** (`Core/OrgResourceController`): recursos com `organization_id` herdam escopo por organização, validação, paginação, exclusão lógica e auditoria. Cada módulo define só regras, colunas e `prepare()`.
- **Escopo de organização no backend**: `AuthMiddleware` carrega as organizações do usuário; consultas filtram por elas; recursos de outras organizações respondem 404 (listagem filtrada por organização alheia, 403).
- **Cálculos só no backend** (`IndicatorService`): dashboard, indicadores, relatórios e metas usam a mesma classe.
- **Unidades padronizadas**: água em m³ (entrada original preservada), resíduos em kg.
- **Período**: água/energia por *mês de referência* (dia 1); vários lançamentos por setor/mês são somados. Resíduos por data.
- **Metas**: valor atual = último mês com dados dentro do período da meta (`min(hoje, prazo)`). Progresso respeita a direção:
  - Redução: `(base − atual) / (base − alvo)`
  - Aumento: `(atual − base) / (alvo − base)`
  - Exibição limitada a 0–100%; o valor bruto é exposto em `progress_raw_pct`. Situação: ATINGIDA (≥100%), ATRASADA (prazo vencido), EM_ANDAMENTO.
- **Sem dados ≠ zero**: variações e taxas retornam `null` quando não há base (divisão por zero tratada).
- **Destinação de resíduos** não é classificada como adequada/inadequada automaticamente.
- **Exportação**: CSV nativo (`;`, UTF-8 com BOM, proteção contra injeção de fórmulas); PDF via impressão do navegador (estrutura pronta para biblioteca futura).
- **Recuperação de senha**: tabela `password_resets` e endpoint preparados; retorna 501 até haver envio de e-mail.

## Segurança
Prepared statements; `password_hash/verify`; escape de saída (`UI.esc` / `htmlspecialchars`); CSRF; sessão regenerada no login; bloqueio após 5 tentativas em 10 min (429); ordenação por *whitelist*; logs sem corpo de requisição/segredos; `.env` fora da raiz pública; cabeçalhos `X-Frame-Options`, `nosniff`, `Referrer-Policy`.
