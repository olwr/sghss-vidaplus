#!/usr/bin/env bash
# Confere cabeçalhos e exposições de um ambiente em execução (CT042/CT036 em produção).
# Uso: scripts/verificar-seguranca.sh https://api.dominio https://site.dominio
set -u
API="${1:?informe a URL base da API (sem /api/v1)}"
SITE="${2:?informe a URL do site}"
falhas=0

# Pré-voo: sem resposta HTTP não há o que verificar (HTTP 000 = DNS/conexão/TLS falhou).
# Sem isto, um domínio de exemplo ou fora do ar gerava dezenas de "FALHA" enganosas.
for alvo in "$API" "$SITE"; do
  if [[ "$(curl -sk -m 10 -o /dev/null -w '%{http_code}' "$alvo")" == "000" ]]; then
    echo "ERRO: não consegui conectar em $alvo (DNS, porta, TLS ou o ambiente está fora do ar)." >&2
    echo "      Use as URLs REAIS do ambiente (os domínios 'exemplo' não existem)." >&2
    exit 2
  fi
done

confere() { # descrição, condição(0=ok)
  if [[ "$2" -eq 0 ]]; then echo "  ok    $1"; else echo "  FALHA $1"; falhas=$((falhas + 1)); fi
}
cab() { curl -sk -m 10 -D - -o /dev/null "$1" | tr -d '\r'; }
codigo() { curl -sk -m 10 -o /dev/null -w '%{http_code}' "$1"; }

echo "API: $API"
h=$(cab "$API/api/v1/auth/unidades")
grep -qi '^x-content-type-options: nosniff' <<<"$h"; confere "X-Content-Type-Options: nosniff" $?
grep -qi '^content-security-policy:' <<<"$h"; confere "Content-Security-Policy presente" $?
[[ "$API" == https://* ]] && { grep -qi '^strict-transport-security:' <<<"$h"; confere "HSTS (HTTPS)" $?; }
! grep -qi '^x-powered-by:' <<<"$h"; confere "sem X-Powered-By" $?
! grep -qiE '^server: .*[0-9]+\.[0-9]+' <<<"$h"; confere "Server sem número de versão" $?
[[ "$(codigo "$API/api/v1/auth/me")" == "401" ]]; confere "rota protegida responde 401 sem token" $?
for p in /.env /storage/logs/laravel.log /telescope /horizon /docs/api /vendor/composer/installed.json; do
  c=$(codigo "$API$p"); [[ "$c" =~ ^(401|403|404)$ ]]; confere "$p não exposto (HTTP $c)" $?
done
# APP_DEBUG: erro 500/404 não pode trazer stack trace
corpo=$(curl -sk -m 10 -H 'Accept: application/json' "$API/api/v1/rota-que-nao-existe")
! grep -qE '"(trace|file|line)"|vendor/laravel' <<<"$corpo"; confere "erro sem stack trace (APP_DEBUG=false)" $?

echo "Site: $SITE"
h=$(cab "$SITE/login")
grep -qi '^content-security-policy:' <<<"$h"; confere "CSP presente" $?
! grep -qiE "^content-security-policy:.*unsafe-(inline|eval)" <<<"$h"; confere "CSP sem unsafe-inline/unsafe-eval" $?
grep -qi '^x-frame-options: deny' <<<"$h"; confere "X-Frame-Options: DENY" $?
[[ "$SITE" == https://* ]] && { grep -qi '^strict-transport-security:' <<<"$h"; confere "HSTS (HTTPS)" $?; }

if ((falhas > 0)); then echo "$falhas verificação(ões) falharam."; exit 1; fi
echo "Todas as verificações passaram."
