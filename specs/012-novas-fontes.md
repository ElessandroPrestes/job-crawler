# SPEC-012: Integração de Múltiplas Fontes de Vagas

## Objetivo
Expandir o motor de coleta (`CrawlerService`) para suportar as principais plataformas de emprego do mercado nacional e tech, ampliando as fontes de dados do Job Crawler.

## Plataformas a serem integradas
- [x] Indeed (Agregador, já implementado)
- [ ] Gupy (Grandes empresas brasileiras)
- [ ] Vagas.com.br (Grandes empresas e corporativo)
- [ ] Catho (Mercado brasileiro em geral)
- [ ] InfoJobs (Grande volume de vagas)
- [ ] Glassdoor (Vagas, salários e avaliações)
- [ ] Sólides (ATS Sólides)
- [ ] Programathor (Tecnologia/Programação)
- [ ] GeekHunter (Desenvolvedores/Tecnologia)
- [ ] Trampos.co (Tecnologia, produto, design)
- [ ] Jooble (Agregador)
- [ ] Empregos.com.br

## Implementação
Para cada plataforma, deverá ser criado um novo `Driver` em `src/Services/Drivers/` que implemente a interface do Crawler, extraindo título, empresa, localização, url e descrição via seletor CSS ou API proprietária da plataforma.
O driver deverá seguir o padrão do Filtro 24h (SPEC-011) quando suportado nativamente pelo portal.

*Tarefa agendada para início no dia 31/08/2026.*
