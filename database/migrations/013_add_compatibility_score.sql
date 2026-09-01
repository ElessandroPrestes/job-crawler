-- SPEC-013: adiciona campos de compatibilidade com currículo
ALTER TABLE jobs
  ADD COLUMN compatibility_score TINYINT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Score 0-100 de compatibilidade com currículo do candidato'
    AFTER is_notified,
  ADD COLUMN matched_skills JSON NULL DEFAULT NULL
    COMMENT 'Array JSON com as skills do candidato encontradas na vaga'
    AFTER compatibility_score,
  ADD INDEX idx_compatibility_score (compatibility_score);
