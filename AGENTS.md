# Protocolo Comum para Pessoas e Agentes de IA

Este documento define como agentes humanos e agentes de Inteligência Artificial colaboram neste projeto utilizando o Universal SDD.

## Regras para IA
- A IA não toma decisões de produto sem aprovação humana.
- A IA deve sempre consultar a SPEC atual antes de escrever código.
- A IA deve garantir que todas as implementações passem nos quality gates definidos.
- Se houver divergência entre SPEC, PROJECT.md e código, a IA deve alertar o humano e tratar como defeito ou dívida técnica, nunca silenciando o problema.
- A documentação (PROJECT.md) deve ser atualizada pela IA ao concluir uma tarefa (TASK).

## Papéis
Veja `docs/agents.md` para responsabilidades detalhadas de cada agente.
