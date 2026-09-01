# SPEC-013: Filtro de Compatibilidade com Currículo (Resume Match ≥80%)

## Objetivo
Filtrar e pontuar as vagas coletadas de acordo com a compatibilidade com o perfil técnico do
candidato (PHP/Laravel/Node.js). Apenas vagas com score ≥80% são armazenadas e exibidas.
Vagas com tecnologias fora do perfil (Python, Java, React, .NET etc.) são descartadas na
origem, mesmo quando o título é genérico ("Engenheiro de Backend").

## Requisitos
1. **ResumeProfile**: Classe imutável com 50+ skills ponderadas (peso 1–10) e duas listas de
   desqualificadoras: por título (TITLE_DISQUALIFYING) e por texto completo (FULLTEXT_DISQUALIFYING).
2. **CompatibilityScorer**: Duas etapas de desqualificação antes do score 0–100.
   - Etapa 1: skill desqualificadora no título → score 0.
   - Etapa 2: framework exclusivo de ecossistema indesejado em qualquer texto (fastapi, sqlalchemy,
     spring boot, django etc.) → score 0. Resolve caso título genérico + Python na descrição.
   - Etapa 3: soma ponderada das skills encontradas, normalizada para 0–100.
3. **Filtro no pipeline**: CrawlerService aplica scoreAndFilter() após filterRelevant().
4. **Persistência**: Campos compatibility_score (TINYINT) e matched_skills (JSON) na tabela jobs.
5. **API**: GET /api/jobs aceita min_score para filtrar por score mínimo.
6. **Frontend**: Cards exibem badge "✓ Match X%". Keyword de busca ampliada.

## Quality Gates
- Score 0 para Python/FastAPI/Java/Spring no título.
- Score 0 para título genérico mas FastAPI/SQLAlchemy/Django/Spring Boot na descrição (caso Jobbol/Locus).
- Score ≥80% para vagas PHP + Laravel + Redis + Docker.
- Migration executa sem erros.
