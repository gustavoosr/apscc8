CREATE TABLE sectors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  organization_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at TIMESTAMP NULL,
  KEY idx_sectors_org (organization_id),
  UNIQUE KEY uq_sector_name (organization_id, name),
  CONSTRAINT fk_sectors_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT
) ENGINE=InnoDB;
