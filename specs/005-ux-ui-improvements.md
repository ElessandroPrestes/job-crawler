# SPEC-005: Melhorias de UX/UI no Dashboard

## Objetivo
Aplicar boas práticas de Design de Interface (UI) e Experiência do Usuário (UX) no dashboard de vagas, garantindo uma apresentação mais profissional, responsiva e agradável, sem adicionar frameworks JavaScript complexos.

## Requisitos
1. **Estado de Carregamento (Loading)**: Substituir o texto estático por _Skeleton Loaders_ para reduzir a ansiedade do usuário.
2. **Estado Vazio (Empty State)**: Melhorar a comunicação visual quando não houver vagas com ícones e chamadas para ação (CTA) claras.
3. **Tipografia e Espaçamento (Cards)**: Aprimorar a leitura e o peso visual das informações dentro dos cards de vagas. Uso de _badges_ mais visuais.
4. **Feedback de Erro**: Mostrar mensagens de erro amigáveis caso a API falhe, utilizando Toasts/Alertas.
5. **Cabeçalho (Header)**: Estilo moderno (ex: sticky, sombras suaves).

## Implementação
- Alterar o arquivo `public/index.html`.
- Usar utilitários do Tailwind CSS existentes para implementar o skeleton, as novas cores, tipografia, e os estados de hover/transição.
- Adicionar ícones SVG (Heroicons inline) para melhorar o apelo visual.
