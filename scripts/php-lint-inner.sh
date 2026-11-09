#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

count=0
fail=0
while IFS= read -r -d '' file; do
  line="$(php -l "$file" 2>&1)"
  if ! printf '%s\n' "$line" | grep -Eq '^No syntax errors detected in .+$'; then
    printf '%s\n' "$line" >&2
    fail=1
  fi
  count=$((count + 1))
done < <(find src -type f -name '*.php' -print0)

if [[ "$fail" -ne 0 ]]; then
  exit 1
fi

printf 'php -l: No syntax errors detected in %s files\n' "$count"
