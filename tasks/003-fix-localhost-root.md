# TASK-003: Implementar redirecionamento da raiz para a documentação

## Descrição
Alterar as configurações do Nginx para resolver o bug de tela vazia/404 na raiz de `localhost:8080`.

## Passos
- [ ] Criar SPEC-003.
- [ ] Atualizar `nginx/nginx.conf` adicionando `absolute_redirect off;` ao bloco `server`.
- [ ] Atualizar `nginx/nginx.conf` adicionando o `location = / { return 301 /docs/; }`.
- [ ] Reiniciar o container do nginx localmente para testar.
- [ ] Atualizar `PROJECT.md` se necessário.
