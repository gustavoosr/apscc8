# API REST — `/api/v1`

Autenticação por sessão (cookie `ECOSESSID`). Requisições que alteram dados exigem o header `X-CSRF-Token` (obtido em `POST /auth/login` ou `GET /auth/me`). Corpo e respostas em JSON UTF-8.

**Sucesso**
```json
{ "success": true, "message": "Registro cadastrado com sucesso.", "data": { "id": 1 }, "meta": { "page": 1, "per_page": 15, "total": 40, "total_pages": 3 } }
```
**Erro**
```json
{ "success": false, "message": "Dados inválidos.", "errors": [ { "field": "quantity", "message": "Campo obrigatório." } ] }
```
Status: 200, 201, 204, 400, 401, 403, 404, 405, 409 (conflito), 422 (validação), 429 (muitas tentativas), 500, 501.

**Listagens**: `page`, `per_page` (máx. 100), `sort` (ex.: `-reference_month`; só colunas permitidas), filtros por recurso, `organization_id`.

## Permissões
ADM = Administrador · GES = Gestor · OPE = Operador

| Recurso | Leitura | Escrita |
|---|---|---|
| /auth | login público; me/logout autenticado | — |
| /users | ADM | ADM |
| /organizations | todos (as suas) | criar/excluir: ADM · editar: ADM, GES |
| /sectors, /environmental-objectives, /environmental-goals | todos | ADM, GES |
| /water-consumption, /energy-consumption, /waste | todos | todos criam; OPE edita só o que criou; excluir: ADM, GES |
| /waste-types (+/categories) | todos | ADM, GES |
| /waste-destinations | todos | ADM |
| /indicators, /dashboard | todos | — |
| /reports | ADM, GES | — |

## Endpoints
| Método | Rota | Descrição |
|---|---|---|
| POST | /auth/login | `{email, password}` → usuário + `csrf_token` |
| POST | /auth/logout | encerra sessão |
| GET | /auth/me | usuário atual + `csrf_token` |
| POST | /auth/password/forgot | 501 (preparado) |
| GET/POST/PUT/PATCH/DELETE | /users[/{id}] | `name,email,password,role,active,organization_ids[]` |
| GET/POST/PUT/PATCH/DELETE | /organizations[/{id}] | `name,legal_id,email,phone,active` |
| GET/POST/PUT/PATCH/DELETE | /sectors[/{id}] | `organization_id,name,description,active`; filtros `active,search` |
| CRUD | /water-consumption | `organization_id,sector_id,reference_month(AAAA-MM),quantity,unit(m3\|L),notes`; filtros `sector_id,from,to` |
| GET | /water-consumption/compare | `a_from,a_to,b_from,b_to` → totais e `variation_pct` |
| CRUD | /energy-consumption | `…,quantity_kwh`; `/compare` idem |
| CRUD | /waste | `organization_id,sector_id,waste_type_id,destination_id,record_date,quantity,unit(kg\|t),notes` |
| GET | /waste/summary | totais por destinação e `recycling_pct` |
| CRUD | /waste-types; GET/POST /waste-types/categories | tipos e categorias |
| GET/POST/PUT | /waste-destinations | destinações |
| CRUD | /environmental-objectives | `organization_id,title,description,status` |
| CRUD | /environmental-goals | `organization_id,objective_id,sector_id?,title,metric,direction,baseline_value,target_value,start_date,due_date`; retorna `current_value,progress_pct,status` |
| GET | /environmental-goals/{id}/progress | meta avaliada + histórico mensal |
| GET | /indicators/water \| energy \| waste \| goals | filtros `organization_id,sector_id,from,to` |
| GET | /dashboard/summary \| charts | cards e séries dos gráficos |
| GET | /reports/consumption (`type=water\|energy\|both`) \| waste \| goals | `format=csv` para exportar |

`metric`: `WATER_M3, ENERGY_KWH, WASTE_KG, RECYCLING_RATE` · `direction`: `DECREASE, INCREASE`.

## Exemplos
```bash
# login
curl -c jar -H 'Content-Type: application/json' \
  -d '{"email":"gestor@ecomanager.local","password":"Eco@12345"}' http://localhost:8080/api/v1/auth/login

# cadastrar consumo de água em litros (convertido para m³)
curl -b jar -H 'Content-Type: application/json' -H 'X-CSRF-Token: <token>' \
  -d '{"organization_id":1,"sector_id":1,"reference_month":"2026-10","quantity":5000,"unit":"L"}' \
  http://localhost:8080/api/v1/water-consumption
# 201 {"success":true,"message":"Registro de água cadastrado com sucesso.","data":{"id":57}}

# indicador de resíduos
curl -b jar 'http://localhost:8080/api/v1/indicators/waste?from=2026-01&to=2026-10'
# {"success":true,...,"data":{"total_kg":2456.1,"recycled_kg":812.4,"recycling_pct":33.08,"by_destination":[...],"history":[...]}}
```
