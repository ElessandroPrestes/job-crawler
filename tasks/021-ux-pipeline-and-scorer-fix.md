# TASK-021: Implementação da SPEC-021 (Pipeline UX/UI e Scorer Fix)

## Checklist de Implementação
- [x] **1. Scorer**: Atualizar baseline para vagas sem descrição (`$description === '' && $requirements === ''`) em `src/Services/CompatibilityScorer.php` para `10`.
- [x] **2. Drivers**:
  - [x] Atualizar `CathoDriver.php` com suporte a `article.offer` e seletores atualizados de título e empresa.
  - [x] Atualizar `GupyDriver.php` com extração de `__NEXT_DATA__` em `https://portal.gupy.io/job-search/term=KEYWORD`.
  - [x] Atualizar `SolidesDriver.php` com o domínio correto `vagas.solides.com.br`.
- [x] **3. Testes**:
  - [x] Criar teste unitário específico para `CompatibilityScorer` garantindo que vagas de título único (PHP, Node) atinjam >= 80% sem descrição, e vagas proibidas (Python, React) continuem desqualificadas.
  - [x] Rodar suíte `make test` e garantir 100% de sucesso (230 testes passando).
- [x] **4. UI/UX Dashboard (`public/index.html`)**:
  - [x] Garantir que o botão "Atualizar Vagas" exiba permanentemente o texto e ícone sem classes restritivas (`hidden sm:inline`).
  - [x] Adicionar barra de métricas do pipeline de vagas (Total, 24h, 3d, Alta Compatibilidade).
  - [x] Implementar tabs/pills de filtro por intervalo e filtro dinâmico em tempo real por termo/empresa e stack.
  - [x] Refinar os cards de vagas com datas relativas amigáveis, tags visuais de stack, badges de match e CTA direto de candidatura.
  - [x] Sistema de toasts/notificações moderno para feedback de crawling.
- [x] **5. Build & Quality Gates**:
  - [x] Executar `make build-css` para compilar o Tailwind CSS atualizado.
  - [x] Executar `make test` e `make analyse` (100% de aprovação sem erros).
  - [x] Executar `make test-e2e` (Playwright E2E aprovado com 3/3 testes).
  - [x] Validar o fluxo ao vivo contra a API local e atualizar `PROJECT.md`.
