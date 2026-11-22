#!/usr/bin/env bash
# Procura segredos versionados no repositório (somente arquivos rastreados pelo git).
# Uso: scripts/verificar-segredos.sh   → código 1 se achar algo.
set -u
cd "$(git rev-parse --show-toplevel)" || exit 2
falhou=0

# 1) arquivos de ambiente reais não podem estar no git (só os *.example e .env.ci)
proibidos=$(git ls-files | grep -E '(^|/)\.env(\.[A-Za-z0-9_-]+)?$' | grep -vE '\.(example)$|(^|/)\.env\.ci$' || true)
if [[ -n "$proibidos" ]]; then echo "ARQUIVO .env RASTREADO:"; echo "$proibidos"; falhou=1; fi

# 2) padrões clássicos de credenciais no conteúdo (exclui lockfiles e este script)
padroes='(AKIA[0-9A-Z]{16}|-----BEGIN (RSA |EC |OPENSSH |PGP )?PRIVATE KEY-----|ghp_[A-Za-z0-9]{36}|xox[baprs]-[A-Za-z0-9-]{10,}|sk-[A-Za-z0-9]{32,})'
achados=$(git grep -nIE "$padroes" -- . ':!*lock*' ':!scripts/verificar-segredos.sh' || true)
if [[ -n "$achados" ]]; then echo "POSSÍVEL SEGREDO NO CÓDIGO:"; echo "$achados"; falhou=1; fi

# 3) APP_KEY preenchida em arquivo versionado
appkey=$(git grep -nE '^APP_KEY=base64:[A-Za-z0-9+/=]{20,}' || true)
if [[ -n "$appkey" ]]; then echo "APP_KEY REAL VERSIONADA:"; echo "$appkey"; falhou=1; fi

((falhou == 0)) && echo "Nenhum segredo encontrado nos arquivos versionados."
exit $falhou
