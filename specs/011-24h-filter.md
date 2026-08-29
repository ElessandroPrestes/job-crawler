# SPEC-011: Filtro Restrito de Vagas nas Últimas 24 Horas

## Objetivo
Atender ao requisito de negócio onde o Crawler deve focar apenas nas vagas publicadas e coletadas no prazo de 24 horas (ex: de 27/08 até 28/08). Vagas mais antigas não devem ser rastreadas ou exibidas no dashboard para garantir que os alertas e a visualização sejam sempre frescos.

## Requisitos
1. **Drivers de Coleta**:
   - LinkedIn: Modificar o parâmetro de tempo (`f_TPR`) de 3 dias (`r259200`) para 1 dia (`r86400`).
   - Indeed: Adicionar o parâmetro `fromage=1` para limitar as buscas ao último dia.
2. **API & Repositório**:
   - `JobRepository::findAll()` deve receber um filtro para retornar apenas vagas coletadas/publicadas nas últimas 24 horas.
3. **Frontend**:
   - A listagem no `index.html` consumirá a API com esse filtro, exibindo nativamente apenas as vagas fresquinhas.

## Implementação
- Alterar `LinkedInDriver.php` e `IndeedDriver.php`.
- Alterar `JobRepository.php` para incluir `scraped_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)` quando solicitado ou por padrão.
- Alterar `JobController.php` para assumir `24h` como filtro nativo na API.
