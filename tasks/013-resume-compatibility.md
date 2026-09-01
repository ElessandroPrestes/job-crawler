# TASK-013: Implementação do Filtro de Compatibilidade com Currículo

Ref: SPEC-013

## Checklist
- [x] Criar src/Services/ResumeProfile.php
- [x] Criar src/Services/CompatibilityScorer.php
- [x] Modificar CrawlerService.php — método scoreAndFilter()
- [x] Modificar JobRepository.php — campos compatibility_score e matched_skills
- [x] Migration 013_add_compatibility_score.sql
- [x] Modificar index.html — badge de score + keyword ampliada
- [x] Testar scorer com vagas positivas, negativas e título genérico (caso Jobbol)
- [x] Commitar
