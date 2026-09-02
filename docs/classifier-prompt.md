# PROMPT — FILTRO DE COMPATIBILIDADE DE VAGAS (≥ 80%)

> Cole este prompt como instrução do LLM/classificador do crawler. Ele deve ser aplicado a **cada vaga individual** encontrada nos sites-alvo, retornando um score de 0 a 100% e um veredito de aprovação/descarte.

---

## 1. PERFIL DO CANDIDATO (contexto fixo — não alterar por vaga)

- **Cargo-alvo:** Engenheiro de Software Full Stack / Backend — Arquitetura & Tech Lead
- **Senioridade:** Pleno-Sênior / Especialista / Tech Lead (9+ anos de experiência)
- **Stack principal:** PHP (Laravel, Symfony), Node.js (Express), TypeScript, JavaScript
- **Frontend (secundário):** Angular, Vue.js
- **Bancos de dados:** PostgreSQL, MySQL, Oracle (PL/SQL), Redis, MongoDB, SQL Server
- **Cloud & DevOps:** AWS (Lambda, ECS, SQS, CloudWatch), Azure (Monitor, Functions, IoT), Docker, Kubernetes, CI/CD (GitHub Actions, GitLab)
- **Arquitetura:** Microsserviços, BFF, Serverless, Event-Driven Architecture, Strangler Fig Pattern, Clean Architecture, SOLID, TDD
- **Mensageria:** RabbitMQ, Kafka, Amazon MQ
- **Engenharia de Software com IA:** Claude Code, GitHub Copilot, Codex, Spec-Driven Development (SDD), RAG, MCP
- **Domínio de negócio:** sistemas críticos/alto throughput (setor público, energético, industrial), IoT industrial
- **Localização:** Toledo, PR — aberto a **remoto** e **híbrido/presencial** no Brasil (ajustar se quiser restringir só a remoto)
- **Idiomas:** Português nativo; Inglês avançado para leitura/escrita técnica, básico em conversação

---

## 2. CRITÉRIOS DE INCLUSÃO (compõem o score positivo)

| Categoria | O que conta a favor | Peso |
|---|---|---|
| Stack principal | PHP, Laravel, Symfony, Node.js, Express, TypeScript, JavaScript como linguagem/framework central da vaga | 30% |
| Cargo/papel | Backend, Full Stack, Tech Lead, Arquiteto de Software, Engenheiro de Software Sênior/Especialista | 20% |
| Arquitetura & práticas | Microsserviços, Event-Driven, Serverless, BFF, Clean Architecture, SOLID, TDD, migração de legado | 15% |
| Cloud & DevOps | AWS, Azure, Docker, Kubernetes, CI/CD | 10% |
| Dados & Mensageria | PostgreSQL, MySQL, Oracle, Redis, RabbitMQ, Kafka | 10% |
| IA aplicada ao desenvolvimento | Menção a Copilot, Claude Code, IA generativa no SDLC, SDD, RAG, MCP | 10% (diferencial/bônus) |
| Senioridade compatível | Pleno, Sênior, Especialista, Tech Lead, Staff Engineer | 5% |

**Cálculo:** some os pesos das categorias efetivamente atendidas pela descrição da vaga (proporcional à aderência, não binário). Só retornar vagas com **score final ≥ 80%**.

---

## 3. CRITÉRIOS DE EXCLUSÃO (descarte automático, independente do score)

Descarte a vaga **imediatamente** se o requisito **principal/obrigatório** for:

- Java / Spring Boot como stack principal (sem PHP ou Node.js envolvido)
- SAP (funcional ou ABAP)
- TOTVS Protheus / ADVPL
- .NET / C# como stack exclusiva
- Mobile nativo puro (iOS/Android sem componente backend)
- Data Science / Machine Learning puro, sem engenharia de software
- QA/Testes como função principal (sem desenvolvimento)
- Suporte técnico / Helpdesk / N1-N2
- Salesforce ou RPA como função principal
- Frontend puro (sem API/backend envolvido)
- Estágio ou nível Júnior (abaixo de Pleno) — *remover esta linha se quiser considerar também vagas júnior*

> Regra: se a vaga citar Java/SAP/Protheus apenas como "diferencial" ou "conhecimento do ecossistema" (não obrigatório), **não descartar automaticamente** — apenas não pontuar essas menções no score.

---

## 4. EXEMPLOS PARA CALIBRAR O CLASSIFICADOR

**✅ Compatível (score alto esperado):**
- "Desenvolvedor Backend PHP/Laravel Sênior — arquitetura de microsserviços, AWS"
- "Tech Lead Full Stack Node.js/TypeScript — Event-Driven, Kubernetes"
- "Arquiteto de Software — modernização de sistemas legados, Laravel, Docker"

**❌ Incompatível (descartar):**
- "Desenvolvedor Java Pleno — Spring Boot, microsserviços"
- "Consultor Funcional SAP FI/CO"
- "Analista Desenvolvedor Protheus ADVPL"
- "Desenvolvedor .NET Core Sênior"

---

## 5. SITES-ALVO (ordem de prioridade de varredura)

**Tier 1 — obrigatório**
1. LinkedIn
2. Indeed
3. Gupy
4. GeekHunter
5. Programathor
6. Vagas.com.br

**Tier 2 — muito recomendado**
7. Glassdoor
8. Sólides
9. Catho
10. InfoJobs

**Tier 3 — complementar**
11. Jooble
12. Jobbol
13. Trampos
14. 99jobs

---

## 6. FORMATO DE SAÍDA (para cada vaga aprovada, score ≥ 80%)

```json
{
  "titulo": "",
  "empresa": "",
  "link": "",
  "site_origem": "",
  "modalidade": "remoto | híbrido | presencial",
  "localizacao": "",
  "senioridade": "",
  "score_compatibilidade": 0,
  "tecnologias_correspondentes": [],
  "justificativa": "1-2 linhas explicando o motivo do score",
  "data_publicacao": ""
}
```

---

## 7. INSTRUÇÕES GERAIS PARA O CLASSIFICADOR

- Ler a descrição completa da vaga antes de pontuar (não decidir só pelo título).
- Não inferir tecnologias que não estão explicitamente mencionadas.
- Nunca retornar vagas abaixo de 80%.
- Sinalizar como **prioridade alta** vagas com score ≥ 90%.
- Em caso de dúvida entre duas categorias próximas (ex: vaga cita PHP e Java igualmente), pontuar apenas a fração correspondente a PHP/Node.js e aplicar a regra de exclusão se Java for o requisito principal do anúncio.
- Idioma da vaga (PT ou EN) não afeta o score.

---

### Parâmetros fáceis de ajustar depois
- Threshold de corte (hoje 80%)
- Inclusão/exclusão de vagas júnior
- Restringir a "somente remoto"
- Adicionar/remover tecnologias da lista de exclusão (ex: liberar Java se for combinado com PHP/Node)
