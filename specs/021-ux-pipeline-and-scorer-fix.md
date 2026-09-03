# SPEC-021: Pipeline de Vagas UX/UI e Correção do Scorer de Vagas sem Descrição

## Objetivo
1. Corrigir a visibilidade e clareza do botão de ação principal "Atualizar Vagas" no dashboard, garantindo que o texto descritivo nunca seja omitido e que boas práticas de UX/UI sejam aplicadas em todo o pipeline de vagas.
2. Corrigir a linha de base de cálculo do score para vagas capturadas sem descrição/requisitos (listagens de scraping), assegurando que vagas legítimas alinhadas às stacks principais do currículo (PHP, Laravel, Node.js) não sejam descartadas indevidamente pelo filtro de corte (>= 80%).
3. Aprimorar os drivers de coleta de dados (Catho, Gupy e Sólides) para restabelecer a extração multi-source.

## Problema Atual
1. **Ocultação de Texto do Botão**: No dashboard (`public/index.html`), o botão de atualização utilizava a classe `hidden sm:inline` e o texto apenas "Atualizar" (em vez de "Atualizar Vagas"), ocultando o texto em resoluções menores ou causando inconsistência visual onde apenas o ícone ficava aparente.
2. **Descarte Massivo de Vagas Válidas no Scorer**: Ao raspar plataformas como LinkedIn, as vagas chegam apenas com o título (sem o corpo da descrição carregado a priori). O `CompatibilityScorer` aplicava uma baseline fixa de 25 pontos para vagas sem descrição. Com isso, vagas com títulos como "Desenvolvedor PHP" (peso 10) ou "Desenvolvedor Node.js" (peso 18) pontuavam apenas 40% ou 72%, falhando no corte mínimo de 80% (`ResumeProfile::MIN_SCORE = 80`). Como consequência, quase a totalidade das vagas recentes foi descartada, restando apenas uma vaga ("Desenvolvedor Web Senior (PHP - Laravel)") que acumulava múltiplos termos no título.
3. **UX/UI do Pipeline de Vagas**: Falta de visão executiva de pipeline (métricas de vagas encontradas, novas, por período), controles de filtro visualmente integrados e intuitivos (filtros por período em abas/pills e busca textual em tempo real), além de feedbacks de carregamento e toasts não invasivos.
4. **Drivers Secundários com Seletores e URLs Desatualizados**: Catho (`article.offer`), Gupy (SSR via `__NEXT_DATA__` no portal) e Sólides (`vagas.solides.com.br`) apresentavam incompatibilidades que impediam a captura de vagas válidas.

## Requisitos

### R1 — Botão "Atualizar Vagas" com Feedback Visual e Acessibilidade
- O botão deve exibir de forma explícita e permanente o texto **"Atualizar Vagas"** acompanhado de ícone de sincronização/refresh.
- Deve possuir estados visuais claros: normal, hover, active (pressionado), disabled (durante crawling) e loading com spinner animado e mensagem descritiva de progresso.
- Atributos de acessibilidade (`aria-label`, foco visível) adequados.

### R2 — Métricas e Controles do Pipeline de Vagas
- Adicionar uma barra de métricas do pipeline de vagas: Total de Vagas, Vagas Recentes (24h), Vagas em 3 dias, Match Superior (>= 90%).
- Controles de filtro intuitivos em formato de tabs/pills: "Últimas 24h", "Últimos 3 dias", "Todas".
- Campo de filtro rápido em tempo real (busca instantânea no frontend por cargo ou empresa).
- Filtro rápido por tecnologia (Todas, PHP / Laravel, Node.js).

### R3 — Cards de Vagas Aprimorados
- Hierarquia tipográfica nítida: Título em destaque, Empresa, Localização com indicador visual de trabalho Remoto / Presencial.
- Badge de score com gradiente/cores acessíveis (verde para >= 90%, azul para 80-89%) e lista de competências correspondentes identificadas.
- Data relativa ("Hoje às 15:30", "Ontem", "Há 2 dias") com tooltip de data exata.
- Botão CTA explícito e seguro para candidatura externa ("Ver Vaga" / link com ícone externo seguro `target="_blank" rel="noopener noreferrer"`).

### R4 — Correção da Linha de Base de Compatibilidade no Scorer
- Em `CompatibilityScorer.php`, quando a vaga não possuir descrição e requisitos (`$description === '' && $requirements === ''`), a pontuação deve refletir a aderência do título à stack do candidato. O baseline para avaliação exclusiva por título deve ser ajustado para `10` (peso de uma stack primária no currículo, como PHP ou Laravel), permitindo que vagas de Desenvolvedor PHP (10/10 = 100%), Node.js (18/10 = 100%), Node (9/10 = 90%) atinjam a nota de corte (>= 80%). Vagas com tecnologias desqualificantes (Python, React, Java, etc.) continuam rigorosamente rejeitadas (score 0).

### R5 — Correção dos Drivers de Coleta (Catho, Gupy, Sólides)
- `CathoDriver`: Ajustar o seletor para capturar `<article class="offer ...">` e os nós correspondentes de título e empresa.
- `GupyDriver`: Ajustar a URL para a página de busca pública `https://portal.gupy.io/job-search/term=KEYWORD` e extrair as vagas do payload `__NEXT_DATA__`.
- `SolidesDriver`: Ajustar o domínio de busca para `vagas.solides.com.br`.

## Quality Gates
1. `make test`: Todos os testes unitários e de integração continuam passando sem regressão.
2. `make analyse`: Análise estática com PHPStan nível 8 aprovada sem nenhum erro.
3. `make build-css`: Tailwind CLI compila o CSS minificado para o dashboard sem erros.
4. Ao acionar "Atualizar Vagas", as vagas do LinkedIn, Catho e Gupy são devidamente pontuadas e múltiplas oportunidades relevantes aparecem no pipeline.
