CREATE TABLE environmental_objectives (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  description VARCHAR(500) NULL,
  status ENUM('ATIVO','ENCERRADO') NOT NULL DEFAULT 'ATIVO',
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  KEY idx_obj_org (organization_id),
  CONSTRAINT fk_obj_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_obj_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE environmental_goals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  objective_id INT UNSIGNED NOT NULL,
  organization_id INT UNSIGNED NOT NULL,
  sector_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL,
  metric ENUM('WATER_M3','ENERGY_KWH','WASTE_KG','RECYCLING_RATE') NOT NULL,
  direction ENUM('DECREASE','INCREASE') NOT NULL,
  baseline_value DECIMAL(14,3) NOT NULL COMMENT 'Valor de referência (mensal para consumo/resíduos; % para reciclagem)',
  target_value DECIMAL(14,3) NOT NULL,
  start_date DATE NOT NULL,
  due_date DATE NOT NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  KEY idx_goal_org (organization_id),
  KEY idx_goal_obj (objective_id),
  CONSTRAINT ck_goal_dates CHECK (due_date >= start_date),
  CONSTRAINT ck_goal_values CHECK (baseline_value <> target_value),
  CONSTRAINT fk_goal_obj FOREIGN KEY (objective_id) REFERENCES environmental_objectives(id) ON DELETE RESTRICT,
  CONSTRAINT fk_goal_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_goal_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE environmental_goal_progress (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id INT UNSIGNED NOT NULL,
  reference_month DATE NOT NULL,
  current_value DECIMAL(14,3) NOT NULL,
  progress_pct DECIMAL(8,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_progress (goal_id, reference_month),
  CONSTRAINT fk_progress_goal FOREIGN KEY (goal_id) REFERENCES environmental_goals(id) ON DELETE CASCADE
) ENGINE=InnoDB;
