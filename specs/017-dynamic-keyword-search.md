# SPEC-017: Busca Dinâmica por Palavra-chave no Dashboard

## Objetivo
Permitir que o usuário defina dinamicamente a palavra-chave (keyword) de busca na interface visual (dashboard) antes de iniciar a extração (crawler). 

## Problema Atual
A string de busca estava fixa/hardcoded no arquivo `public/index.html` como `keyword: 'php laravel node.js typescript backend'`. Quando enviada para as plataformas (como o LinkedIn), essa string excessivamente específica era tratada como uma condição "AND" inflexível, resultando em zero vagas retornadas, mesmo quando existiam oportunidades ótimas correspondentes ao perfil do usuário (ex: vagas para `desenvolvedor node.js`).

## Requisitos

### R1 — Input de Palavra-chave no Frontend
Adicionar um campo de entrada de texto (`<input type="text">`) na barra de ferramentas superior do `index.html`.
- O valor padrão (placeholder ou value) deve sugerir o cargo, ex: `desenvolvedor node.js` ou usar o currículo base.
- A função `crawlJobs()` deve ler o valor desse input e enviá-lo como `keyword` no payload de `/api/crawl/all`.

### R2 — Flexibilidade de Coleta
O usuário poderá testar múltiplos termos (`desenvolvedor node.js`, `desenvolvedor php`, etc.) isoladamente, o que aumenta consideravelmente a taxa de retorno das plataformas.

## Quality Gates
- Ao clicar em "Atualizar Vagas", o payload `POST /api/crawl/all` refletirá exatamente o termo digitado no campo de texto.
- O campo estará integrado responsivamente ao layout usando Tailwind.
