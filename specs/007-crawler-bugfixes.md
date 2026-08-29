# SPEC-007: Correções no Crawler e UI (Datas e PDO)

## Objetivo
Resolver o bug de exibição de datas antigas no frontend e a falha silenciosa (Erro 500) que impedia o Crawler de finalizar a execução corretamente no backend.

## Bugs Encontrados
1. **Frontend (Datas Incorretas)**: A interface estava renderizando a data de publicação (`published_at`) ao invés da data da coleta (`scraped_at`) para o label "Coletado em", fazendo com que todas as vagas de demonstração do banco de dados ficassem estáticas em 26/03.
2. **Backend (PDO Invalid parameter number)**: Quando a API disparava e-mails e marcava as vagas como notificadas, o PHP usava `array_unique` no array de IDs, criando buracos nos índices. Ao passar este array pro `PDOStatement->execute()`, o driver PDO não conseguia mapear os índices com os placeholders `?`, retornando o erro SQLSTATE[HY093].

## Implementação
- **Frontend**: Modificar `public/index.html` trocando `job.created_at || job.published_at` por `job.scraped_at`.
- **Backend**: No arquivo `JobRepository.php`, na função `markNotified()`, passar `array_values($ids)` para o PDO para garantir a integridade dos índices base 0.
