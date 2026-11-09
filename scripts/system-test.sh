#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

USE_SUDO_DOCKER="${USE_SUDO_DOCKER:-0}"
LINT_IMAGE="portable-ascii-php-lint:remote-test-2026-11-09"
TEST_IMAGE="portable-ascii-system-test:remote-test-2026-11-09"

docker_cmd() {
  if [[ "${USE_SUDO_DOCKER}" == "1" ]]; then
    sudo docker "$@"
  else
    docker "$@"
  fi
}

docker_cmd build -f "${ROOT}/docker/php-lint/Dockerfile" -t "${LINT_IMAGE}" "${ROOT}/docker/php-lint"

docker_cmd run --rm \
  -v "${ROOT}:/app" \
  -w /app \
  "${LINT_IMAGE}" \
  bash /app/scripts/php-lint-inner.sh

docker_cmd build -f "${ROOT}/docker/system-test/Dockerfile" -t "${TEST_IMAGE}" "${ROOT}/docker/system-test"

docker_cmd run --rm \
  -v "${ROOT}:/app" \
  -w /app \
  "${TEST_IMAGE}" \
  bash /app/scripts/system-test-inner.sh

echo 'System test passed.'
