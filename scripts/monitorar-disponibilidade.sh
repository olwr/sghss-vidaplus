#!/usr/bin/env bash
# Sonda de disponibilidade (RNF 99,5 %).
# Uso: scripts/monitorar-disponibilidade.sh URL [AMOSTRAS] [INTERVALO_S]
# Saída: uma linha por amostra e o resumo. Código 1 se a disponibilidade < META (padrão 99.5).
set -u
URL="${1:?informe a URL de /api/v1/saude}"
AMOSTRAS="${2:-10}"
INTERVALO="${3:-1}"
META="${META:-99.5}"

ok=0
for ((i = 1; i <= AMOSTRAS; i++)); do
  codigo=$(curl -s -o /dev/null -m 5 -w '%{http_code}' "$URL" || true)
  [[ "$codigo" == "200" ]] && ok=$((ok + 1))
  printf '%s amostra=%d http=%s\n' "$(date -u +%FT%TZ)" "$i" "${codigo:-000}"
  ((i < AMOSTRAS)) && sleep "$INTERVALO"
done

disp=$(awk -v o="$ok" -v t="$AMOSTRAS" 'BEGIN { printf "%.2f", (o / t) * 100 }')
echo "disponibilidade=${disp}% (${ok}/${AMOSTRAS}) meta=${META}%"
awk -v d="$disp" -v m="$META" 'BEGIN { exit (d + 0 >= m + 0) ? 0 : 1 }'
