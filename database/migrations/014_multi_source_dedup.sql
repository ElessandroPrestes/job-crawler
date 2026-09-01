-- SPEC-014: deduplicação cross-source + novos sources
ALTER TABLE jobs
  ADD COLUMN company_normalized VARCHAR(255) NULL DEFAULT NULL AFTER company,
  ADD COLUMN title_normalized VARCHAR(500) NULL DEFAULT NULL AFTER title;
ALTER TABLE jobs MODIFY COLUMN source VARCHAR(50) NOT NULL;
ALTER TABLE jobs ADD UNIQUE INDEX idx_dedup_cross_source (company_normalized(100), title_normalized(200));
ALTER TABLE jobs ADD INDEX idx_company_normalized (company_normalized);
