# TASK-014: Implementação do Crawling Multi-Source com Deduplicação

Ref: SPEC-014

## Checklist
- [x] Migration 014: company_normalized, title_normalized, índice UNIQUE cross-source
- [x] Migration 014: expandir enum source na tabela jobs
- [x] Reescrever GupyDriver (API JSON real)
- [x] Reescrever GeekHunterDriver (API JSON real)
- [x] Reescrever ProgramathorDriver (scraping real)
- [x] Reescrever VagasDriver (scraping real)
- [x] Reescrever IndeedDriver (scraping real, br.indeed.com)
- [x] Reescrever GlassdoorDriver (scraping real)
- [x] Reescrever SolidesDriver (scraping real)
- [x] Reescrever CathoDriver (scraping real)
- [x] Reescrever InfoJobsDriver (scraping real)
- [x] Reescrever JoobleDriver (API JSON real)
- [x] Reescrever TramposDriver (scraping real)
- [x] Criar JobbolDriver (scraping real)
- [x] JobRepository: normalização + upsert cross-source com UNIQUE
- [x] MultiSourceCrawlerService: executeAll()
- [x] CrawlerController: rota /api/crawl/all
- [x] Router: registrar nova rota
- [x] index.html: botão usa /api/crawl/all
- [x] Testar deduplicação
- [x] Commitar
