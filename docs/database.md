# Modelo de dados (MySQL 8, InnoDB, utf8mb4)

```
organizations 1─N sectors 1─N water_consumption / energy_consumption / waste_records
organizations N─N users           (organization_users)
organizations 1─N environmental_objectives 1─N environmental_goals 1─N environmental_goal_progress
waste_categories 1─N waste_types 1─N waste_records N─1 waste_destinations
users 1─N activity_logs · users 1─N password_resets
```

| Tabela | Observações |
|---|---|
| organizations | `legal_id` único; exclusão lógica (`deleted_at`) |
| users | `role` ENUM(ADMINISTRADOR, GESTOR, OPERADOR); e-mail único; exclusão lógica |
| organization_users | PK composta; define as organizações acessíveis |
| sectors | único por (organização, nome); `active` |
| water_consumption | `quantity_m3` (padrão) + `original_quantity/unit`; CHECK ≥ 0 |
| energy_consumption | `quantity_kwh`; CHECK ≥ 0 |
| waste_categories / waste_types / waste_destinations | catálogos; destinações iniciais por migração |
| waste_records | `quantity_kg`; um registro = uma destinação |
| environmental_objectives / environmental_goals | metas com `metric`, `direction`, `baseline_value`, `target_value`; CHECK de datas e valores |
| environmental_goal_progress | snapshots mensais (gravados em `GET /environmental-goals/{id}/progress`) |
| activity_logs | ação/entidade/usuário/IP — sem dados sensíveis |
| password_resets, login_attempts, schema_migrations | apoio |

Todas com `created_at`/`updated_at`. Chaves estrangeiras `RESTRICT` (exceto vínculos e logs). Índices em `(organization_id, reference_month|record_date)` e `sector_id`. Registros de seed têm `is_demo = 1`.
