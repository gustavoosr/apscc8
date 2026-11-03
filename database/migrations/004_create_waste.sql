CREATE TABLE waste_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wcat_name (name)
) ENGINE=InnoDB;

CREATE TABLE waste_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  hazardous TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_wtype (category_id, name),
  CONSTRAINT fk_wtype_cat FOREIGN KEY (category_id) REFERENCES waste_categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE waste_destinations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(100) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_wdest_code (code)
) ENGINE=InnoDB;

CREATE TABLE waste_records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  sector_id INT UNSIGNED NOT NULL,
  waste_type_id INT UNSIGNED NOT NULL,
  destination_id INT UNSIGNED NOT NULL,
  record_date DATE NOT NULL,
  quantity_kg DECIMAL(14,3) NOT NULL,
  notes VARCHAR(500) NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  KEY idx_waste_org_date (organization_id, record_date),
  KEY idx_waste_sector (sector_id),
  KEY idx_waste_dest (destination_id),
  CONSTRAINT ck_waste_qty CHECK (quantity_kg >= 0),
  CONSTRAINT fk_waste_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_waste_sector FOREIGN KEY (sector_id) REFERENCES sectors(id) ON DELETE RESTRICT,
  CONSTRAINT fk_waste_type FOREIGN KEY (waste_type_id) REFERENCES waste_types(id) ON DELETE RESTRICT,
  CONSTRAINT fk_waste_dest FOREIGN KEY (destination_id) REFERENCES waste_destinations(id) ON DELETE RESTRICT,
  CONSTRAINT fk_waste_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO waste_destinations (code, name) VALUES
 ('RECICLAGEM','Reciclagem'),
 ('REUTILIZACAO','Reutilização'),
 ('COMPOSTAGEM','Compostagem'),
 ('ATERRO','Aterro sanitário'),
 ('TRATAMENTO','Tratamento especializado'),
 ('OUTRA','Outra destinação');

INSERT INTO waste_categories (name) VALUES
 ('Papel e papelão'),('Plástico'),('Metal'),('Vidro'),('Orgânico'),('Eletrônico'),('Perigoso'),('Rejeito comum');

INSERT INTO waste_types (category_id, name, hazardous)
SELECT id, 'Papel de escritório', 0 FROM waste_categories WHERE name='Papel e papelão' UNION ALL
SELECT id, 'Papelão', 0 FROM waste_categories WHERE name='Papel e papelão' UNION ALL
SELECT id, 'Plástico PET', 0 FROM waste_categories WHERE name='Plástico' UNION ALL
SELECT id, 'Plástico filme', 0 FROM waste_categories WHERE name='Plástico' UNION ALL
SELECT id, 'Alumínio', 0 FROM waste_categories WHERE name='Metal' UNION ALL
SELECT id, 'Sucata ferrosa', 0 FROM waste_categories WHERE name='Metal' UNION ALL
SELECT id, 'Vidro comum', 0 FROM waste_categories WHERE name='Vidro' UNION ALL
SELECT id, 'Resíduo de alimentos', 0 FROM waste_categories WHERE name='Orgânico' UNION ALL
SELECT id, 'Poda e jardinagem', 0 FROM waste_categories WHERE name='Orgânico' UNION ALL
SELECT id, 'Equipamentos eletrônicos', 1 FROM waste_categories WHERE name='Eletrônico' UNION ALL
SELECT id, 'Pilhas e baterias', 1 FROM waste_categories WHERE name='Perigoso' UNION ALL
SELECT id, 'Lâmpadas fluorescentes', 1 FROM waste_categories WHERE name='Perigoso' UNION ALL
SELECT id, 'Resíduo não reciclável', 0 FROM waste_categories WHERE name='Rejeito comum';
